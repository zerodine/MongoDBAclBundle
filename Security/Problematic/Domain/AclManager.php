<?php

namespace PWalkow\MongoDBAclBundle\Security\Problematic\Domain;

use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This code has been taken from the fork of Problematic
 */
class AclManager extends AbstractAclManager
{
    /**
     * {@inheritDoc}
     */
    public function addObjectPermission($domainObject, $mask, $securityIdentity = null)
    {
        $this->addPermission($domainObject, null,  $mask, $securityIdentity, 'object', false);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addClassPermission($domainObject, $mask, $securityIdentity = null)
    {
        $this->addPermission($domainObject, null, $mask, $securityIdentity, 'class', false);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addObjectFieldPermission($domainObject, $field, $mask, $securityIdentity = null)
    {
        $this->addPermission($domainObject, $field, $mask, $securityIdentity, 'object', false);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addClassFieldPermission($domainObject, $field, $mask, $securityIdentity = null)
    {
        $this->addPermission($domainObject, $field, $mask, $securityIdentity, 'class', false);

        return $this;
    }

    /**
     * @param  mixed                                      $domainObject
     * @param  string                                     $field
     * @param  int                                        $mask
     * @param  UserInterface|TokenInterface|RoleInterface $securityIdentity
     * @param  string                                     $type
     * @param  string                                     $field
     * @param  boolean                                    $replace_existing
     * @return AbstractAclManager
     */
    protected function addPermission($domainObject, $field, $mask, $securityIdentity = null, $type = 'object', $replace_existing = false)
    {
        if (is_null($securityIdentity)) {
            $securityIdentity = $this->getUser();
        }

        $context = $this->doCreatePermissionContext($type, $field, $securityIdentity, $mask);

        $objectIdentityRetriever = $this->getObjectIdentityRetrievalStrategy();
        $objectIdentityRetriever->setType($type);
        $oid = $objectIdentityRetriever->getObjectIdentity($domainObject);

        $acl = $this->doLoadAcl($oid);
        $this->doApplyPermission($acl, $context, $replace_existing);

        $this->getAclProvider()->updateAcl($acl);

        return $this;
    }

    /**
     * @param  mixed                                                   $domainObject
     * @param  int                                                     $mask
     * @param  UserInterface | TokenInterface | RoleInterface          $securityIdentity
     * @param  string                                                  $type
     * @param  string                                                  $field
     * @return AbstractAclManager
     */
    protected function setPermission($domainObject, $field, $mask, $securityIdentity = null, $type = 'object')
    {
        $this->addPermission($domainObject, $field, $mask, $securityIdentity, $type, true);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectPermission($domainObject, $mask, $securityIdentity = null)
    {
        $this->setPermission($domainObject, null, $mask, $securityIdentity, 'object');
    }

    /**
     * {@inheritDoc}
     */
    public function setClassPermission($domainObject, $mask, $securityIdentity = null)
    {
        $this->setPermission($domainObject, null, $mask, $securityIdentity, 'class');
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectFieldPermission($domainObject, $field, $mask, $securityIdentity = null)
    {
        $this->setPermission($domainObject, $field, $mask, $securityIdentity, 'object');
    }

    /**
     * {@inheritDoc}
     */
    public function setClassFieldPermission($domainObject, $field, $mask, $securityIdentity = null)
    {
        $this->setPermission($domainObject, $field, $mask, $securityIdentity, 'class');
    }

    /**
     * {@inheritDoc}
     */
    public function revokePermission($domainObject, $mask, $securityIdentity = null, $type = 'object')
    {
        if (is_null($securityIdentity)) {
            $securityIdentity = $this->getUser();
        }

        $context = $this->doCreatePermissionContext($type, null, $securityIdentity, $mask);

        $objectIdentityRetriever = $this->getObjectIdentityRetrievalStrategy();
        $objectIdentityRetriever->setType($type);
        $oid = $objectIdentityRetriever->getObjectIdentity($domainObject);

        $acl = $this->doLoadAcl($oid);
        $this->doRevokePermission($acl, $context);
        $this->getAclProvider()->updateAcl($acl);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function revokeFieldPermission($domainObject, $field, $mask, $securityIdentity = null, $type = 'object')
    {
        if (is_null($securityIdentity)) {
            $securityIdentity = $this->getUser();
        }

        $context = $this->doCreatePermissionContext($type, $field, $securityIdentity, $mask);

        $objectIdentityRetriever = $this->getObjectIdentityRetrievalStrategy();
        $objectIdentityRetriever->setType($type);
        $oid = $objectIdentityRetriever->getObjectIdentity($domainObject);

        $acl = $this->doLoadAcl($oid);
        $this->doRevokePermission($acl, $context);
        $this->getAclProvider()->updateAcl($acl);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllClassPermissions($domainObject, $securityIdentity = null)
    {
        $this->revokeAllPermissions($domainObject, null, $securityIdentity, 'class');
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllObjectPermissions($domainObject, $securityIdentity = null)
    {
        $this->revokeAllPermissions($domainObject, null, $securityIdentity, 'object');
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllClassFieldPermissions($domainObject, $field, $securityIdentity = null)
    {
        $this->revokeAllPermissions($domainObject, $field, $securityIdentity, 'class');
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllObjectFieldPermissions($domainObject, $field, $securityIdentity = null)
    {
        $this->revokeAllPermissions($domainObject, $field, $securityIdentity, 'object');
    }

    /**
     * @param mixed  $domainObject
     * @param string $field
     * @param null   $securityIdentity
     * @param string $type
     *
     * @return $this
     */
    protected function revokeAllPermissions($domainObject, $field, $securityIdentity = null, $type = 'object')
    {
        if (is_null($securityIdentity)) {
            $securityIdentity = $this->getUser();
        }

        $securityIdentity = $this->doCreateSecurityIdentity($securityIdentity);

        $objectIdentityRetriever = $this->getObjectIdentityRetrievalStrategy();
        $objectIdentityRetriever->setType($type);
        $oid = $objectIdentityRetriever->getObjectIdentity($domainObject);

        $acl = $this->doLoadAcl($oid);
        $this->doRevokeAllPermissions($acl, $securityIdentity, $type, $field);
        $this->getAclProvider()->updateAcl($acl);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function preloadAcls($objects, $identities = array())
    {
        $oids = array();
        $objectIdentityRetriever = $this->getObjectIdentityRetrievalStrategy();

        foreach ($objects as $object) {
            $oids[] = $objectIdentityRetriever->getObjectIdentity($object);
        }

        $sids = array();

        foreach ($identities as $identity) {
            $sid = $this->doCreateSecurityIdentity($identity);
            $sids[] = $sid;
        }

        $acls = $this->getAclProvider()->findAcls($oids, $sids); // todo: do we need to do anything with these?

        return $acls;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAclFor($managedItem, $type = 'class')
    {
        $objectIdentityRetriever = $this->getObjectIdentityRetrievalStrategy();
        $objectIdentityRetriever->setType($type);

        $oid = $objectIdentityRetriever->getObjectIdentity($managedItem);
        $this->getAclProvider()->deleteAcl($oid);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isGranted($attributes, $object = null)
    {
        return $this->getSecurityContext()->isGranted($attributes, $object);
    }

    /**
     * {@inheritDoc}
     */
    public function isFieldGranted($masks, $object, $field)
    {
        $oid = $this->getObjectIdentityRetrievalStrategy()->getObjectIdentity($object);
        $acl = $this->doLoadAcl($oid);

        try {
            return $acl->isFieldGranted($field, $masks, array(
                $this->doCreateSecurityIdentity($this->getUser()),
            ));
        } catch (NoAceFoundException $ex) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUser()
    {
        $token = $this->getSecurityContext()->getToken();

        if (null === $token) {
            return;
        }

        $user = $token->getUser();

        return (is_object($user)) ? $user : AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY;
    }
}
