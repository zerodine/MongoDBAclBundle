<?php

namespace PWalkow\MongoDBAclBundle\Security\Problematic\Model;

use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * This code has been taken from the fork of Problematic
 */
interface PermissionContextInterface
{
    /**
     * @return int
     */
    public function getMask();

    /**
     * @return SecurityIdentityInterface
     */
    public function getSecurityIdentity();

    /**
     * @return mixed
     */
    public function getPermissionType();

    /**
     * @return string
     */
    public function getField();

    /**
     * @return bool
     */
    public function isGranting();

    /**
     * @param AuditableEntryInterface $ace
     */
    public function equals(AuditableEntryInterface $ace);

    /**
     * @param AuditableEntryInterface $ace
     */
    public function hasDifferentPermission(AuditableEntryInterface $ace);
}
