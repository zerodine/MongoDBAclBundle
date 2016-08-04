<?php

namespace PWalkow\MongoDBAclBundle\Security\Problematic\Domain;

use PWalkow\MongoDBAclBundle\Security\Problematic\Model\PermissionContextInterface;
use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * This code has been taken from the fork of Problematic
 */
class PermissionContext implements PermissionContextInterface
{
    protected $permissionMask;
    protected $securityIdentity;
    protected $permissionType;
    protected $field;
    protected $granting;

    public function __construct()
    {
    }

    /**
     * @param integer $mask permission mask, or null for all
     */
    public function setMask($mask)
    {
        $this->permissionMask = $mask;
    }

    public function getMask()
    {
        return $this->permissionMask;
    }

    public function setSecurityIdentity(SecurityIdentityInterface $securityIdentity)
    {
        $this->securityIdentity = $securityIdentity;
    }

    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    public function setPermissionType($type)
    {
        $this->permissionType = $type;
    }

    public function getPermissionType()
    {
        return $this->permissionType;
    }

    public function setGranting($granting)
    {
        $this->granting = $granting;
    }

    public function isGranting()
    {
        return $this->granting;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }
    public function equals(AuditableEntryInterface $ace)
    {
        return $ace->getSecurityIdentity() == $this->getSecurityIdentity() &&
            $ace->isGranting() === $this->isGranting() &&
            $ace->getMask() === $this->getMask();
    }

    public function hasDifferentPermission(AuditableEntryInterface $ace)
    {
        return $ace->getSecurityIdentity() == $this->getSecurityIdentity() &&
            $ace->isGranting() === $this->isGranting() && $ace->getMask() !== $this->getMask();
    }
}
