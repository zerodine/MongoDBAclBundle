<?php

namespace PWalkow\MongoDBAclBundle\Security\Problematic\Domain;

use Doctrine\MongoDB\Connection;
use PWalkow\MongoDBAclBundle\Security\Problematic\Model\AclManagerInterface;
use PWalkow\MongoDBAclBundle\Security\Problematic\Model\PermissionContextInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This code has been taken from the fork of Problematic
 */
abstract class AbstractAclManager implements AclManagerInterface
{
    /**
     * @var MutableAclProviderInterface
     */
    protected $aclProvider;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var ObjectIdentityRetrievalStrategyInterface
     */
    protected $objectIdentityRetrievalStrategy;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param MutableAclProviderInterface                 $aclProvider
     * @param TokenStorage                    $tokenStorage
     * @param ObjectIdentityRetrievalStrategyInterface $objectIdentityRetrievalStrategy
     * @param Connection                                  $connection
     */
    public function __construct(
        MutableAclProviderInterface $aclProvider,
        TokenStorage $tokenStorage,
        ObjectIdentityRetrievalStrategyInterface $objectIdentityRetrievalStrategy,
        Connection $connection
    ) {
        $this->aclProvider = $aclProvider;
        $this->tokenStorage = $tokenStorage;
        $this->objectIdentityRetrievalStrategy = $objectIdentityRetrievalStrategy;
        $this->connection = $connection;
    }

    /**
     * @return MutableAclProviderInterface
     */
    protected function getAclProvider()
    {
        return $this->aclProvider;
    }

    /**
     * @return TokenStorage
     */
    protected function getSecurityContext()
    {
        return $this->tokenStorage;
    }

    /**
     * @return ObjectIdentityRetrievalStrategyInterface
     */
    protected function getObjectIdentityRetrievalStrategy()
    {
        return $this->objectIdentityRetrievalStrategy;
    }

    /**
     * Loads an ACL from the ACL provider, first by attempting to create, then finding if it already exists
     *
     * @param ObjectIdentityInterface $objectIdentity
     *
     * @return MutableAclInterface
     */
    protected function doLoadAcl(ObjectIdentityInterface $objectIdentity)
    {
        $acl = null;
        try {
            $acl = $this->getAclProvider()->createAcl($objectIdentity);
        } catch (AclAlreadyExistsException $ex) {
            $acl = $this->getAclProvider()->findAcl($objectIdentity);
        }

        return $acl;
    }

    /**
     * @param ObjectIdentityInterface|TokenInterface $token
     */
    protected function doRemoveAcl($token)
    {
        if (!$token instanceof ObjectIdentityInterface) {
            $token = ObjectIdentity::fromDomainObject($token);
        }

        $this->getAclProvider()->deleteAcl($token);
    }

    /**
     * Returns an instance of PermissionContext. If !$securityIdentity instanceof SecurityIdentityInterface, a new security identity will be created using it
     *
     * @param  string            $type
     * @param  string            $field
     * @param $securityIdentity
     * @param  integer           $mask
     * @param  boolean           $granting
     * @return PermissionContext
     */
    protected function doCreatePermissionContext($type, $field, $securityIdentity, $mask, $granting = true)
    {
        if (!$securityIdentity instanceof SecurityIdentityInterface) {
            $securityIdentity = $this->doCreateSecurityIdentity($securityIdentity);
        }

        $permissionContext = new PermissionContext();
        $permissionContext->setPermissionType($type);
        $permissionContext->setField($field);
        $permissionContext->setSecurityIdentity($securityIdentity);
        $permissionContext->setMask($mask);
        $permissionContext->setGranting($granting);

        return $permissionContext;
    }

    /**
     * Creates a new object instanceof SecurityIdentityInterface from input implementing one of UserInterface, TokenInterface or RoleInterface (or its string representation)
     *
     * @param  mixed                     $identity
     * @throws \InvalidArgumentException
     *
     * @return SecurityIdentityInterface
     */
    protected function doCreateSecurityIdentity($identity)
    {
        if (!$identity instanceof UserInterface && !$identity instanceof TokenInterface && !$identity instanceof RoleInterface && !is_string($identity)) {
            throw new \InvalidArgumentException(sprintf('$identity must implement one of: UserInterface, TokenInterface, RoleInterface (%s given)', get_class($identity)));
        }

        $securityIdentity = null;
        if ($identity instanceof UserInterface) {
            $securityIdentity = UserSecurityIdentity::fromAccount($identity);
        } elseif ($identity instanceof TokenInterface) {
            $securityIdentity = UserSecurityIdentity::fromToken($identity);
        } elseif ($identity instanceof RoleInterface || is_string($identity)) {
            $securityIdentity = new RoleSecurityIdentity($identity);
        }

        if (!$securityIdentity instanceof SecurityIdentityInterface) {
            throw new \InvalidArgumentException('Couldn\'t create a valid SecurityIdentity with the provided identity information');
        }

        return $securityIdentity;
    }

    /**
     * Loads an ACE collection from the ACL and updates the permissions (creating if no appropriate ACE exists)
     *
     * @param  MutableAclInterface        $acl
     * @param  PermissionContextInterface $context
     * @return void
     */
    protected function doApplyPermission(MutableAclInterface $acl, PermissionContextInterface $context, $replaceExisting = false)
    {
        $type = $context->getPermissionType();
        $field = $context->getField();

        if (is_null($field)) {
            $aceCollection = $this->getAceCollection($acl, $type);
        } else {
            $aceCollection = $this->getFieldAceCollection($acl, $type, $field);
        }

        $size = count($aceCollection) - 1;
        reset($aceCollection);

        //If transaction already
        if($this->connection->isTransactionActive()){
            $this->doUpdatePermission($size, $replaceExisting, $aceCollection, $context, $acl, $field, $type);
        }else{
            try{
                $this->connection->beginTransaction();
                $this->doUpdatePermission($size, $replaceExisting, $aceCollection, $context, $acl, $field, $type);
                $this->connection->commit();
            } catch(\Exception $e){
                $this->connection->rollBack();
                throw $e;
            }
        }
    }

    /**
     * @param int                           $size
     * @param bool                           $replaceExisting
     * @param array                           $aceCollection
     * @param PermissionContextInterface $context
     * @param string                           $acl
     * @param string                           $field
     * @param string                           $type
     */
    protected function doUpdatePermission($size, $replaceExisting, $aceCollection, PermissionContextInterface $context, $acl, $field, $type)
    {
        for ($i = $size; $i >= 0; $i--) {
            if (true === $replaceExisting) {
                // Replace all existing permissions with the new one
                if ($context->hasDifferentPermission($aceCollection[$i])) {
                    // The ACE was found but with a different permission. Update it.
                    if (is_null($field)) {
                        $acl->{"update{$type}Ace"}($i, $context->getMask());
                    } else {
                        $acl->{"update{$type}FieldAce"}($i, $field, $context->getMask());
                    }

                    //No need to proceed further because the acl is updated
                    return;
                } else {
                    if ($context->equals($aceCollection[$i])) {
                        // The exact same ACE was found. Nothing to do.
                        return;
                    }
                }
            } else {
                if ($context->equals($aceCollection[$i])) {
                    // The exact same ACE was found. Nothing to do.
                    return;
                }
            }
        }

        //If we come this far means we have to insert ace
        if (is_null($field)) {
            $acl->{"insert{$type}Ace"}(
                $context->getSecurityIdentity(),
                $context->getMask(),
                0,
                $context->isGranting()
            );
        } else {
            $acl->{"insert{$type}FieldAce"}(
                $field,
                $context->getSecurityIdentity(),
                $context->getMask(),
                0,
                $context->isGranting()
            );
        }
    }

    /**
     * @param MutableAclInterface        $acl
     * @param PermissionContextInterface $context
     */
    protected function doRevokePermission(MutableAclInterface $acl, PermissionContextInterface $context)
    {
        $type = $context->getPermissionType();
        $field = $context->getField();

        if (is_null($field)) {
            $aceCollection = $this->getAceCollection($acl, $type);
        } else {
            $aceCollection = $this->getFieldAceCollection($acl, $type, $field);
        }

        $found = false;
        $size = count($aceCollection) - 1;
        reset($aceCollection);

        for ($i = $size; $i >= 0; $i--) {
            //@todo: probably not working if multiple ACEs or different bit mask
            // but that include these permissions.
            if ($context->equals($aceCollection[$i])) {
                if (is_null($field)) {
                    $acl->{"delete{$type}Ace"}($i);
                } else {
                    $acl->{"delete{$type}FieldAce"}($i, $field);
                }
                $found = true;
            }
        }

        if (false === $found) {
            // create a non-granting ACE for this permission
            $newContext = $this->doCreatePermissionContext($context->getPermissionType(), $field, $context->getSecurityIdentity(), $context->getMask(), false);
            $this->doApplyPermission($acl, $newContext);
        }
    }

    /**
     * @param MutableAclInterface       $acl
     * @param SecurityIdentityInterface $securityIdentity
     * @param string                    $type
     * @param string|null               $field
     */
    protected function doRevokeAllPermissions(MutableAclInterface $acl, SecurityIdentityInterface $securityIdentity, $type = 'object', $field = null)
    {
        if (is_null($field)) {
            $aceCollection = $this->getAceCollection($acl, $type);
        } else {
            $aceCollection = $this->getFieldAceCollection($acl, $type, $field);
        }

        $size = count($aceCollection) - 1;
        reset($aceCollection);

        if (is_null($field)) {
            for ($i = $size; $i >= 0; $i--) {
                if ($aceCollection[$i]->getSecurityIdentity() == $securityIdentity) {
                    $acl->{"delete{$type}Ace"}($i);
                }
            }
        } else {
            for ($i = $size; $i >= 0; $i--) {
                if ($aceCollection[$i]->getSecurityIdentity() == $securityIdentity) {
                    $acl->{"delete{$type}FieldAce"}($i, $field);
                }
            }
        }
    }

    /**
     * @param MutableAclInterface $acl
     * @param string              $type
     *
     * @return mixed
     */
    protected function getAceCollection(MutableAclInterface $acl, $type = 'object')
    {
        $aceCollection = $acl->{"get{$type}Aces"}();

        return $aceCollection;
    }

    /**
     * @param MutableAclInterface $acl
     * @param string              $type
     * @param string              $field
     *
     * @return mixed
     */
    protected function getFieldAceCollection(MutableAclInterface $acl, $type = 'object', $field)
    {
        $aceCollection = $acl->{"get{$type}FieldAces"}($field);

        return $aceCollection;
    }
}
