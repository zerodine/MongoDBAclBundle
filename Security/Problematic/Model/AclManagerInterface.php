<?php

namespace PWalkow\MongoDBAclBundle\Security\Problematic\Model;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This code has been taken from the fork of Problematic
 */
interface AclManagerInterface
{
    /**
     * Sets permission mask for a given domain object. All previous permissions for this
     * user and this object will be over written. If none existed, a new one will be created.
     *
     * @param  mixed                                      $domainObject
     * @param  int                                        $mask
     * @param  UserInterface|TokenInterface|RoleInterface $securityIdentity if none given, the current session user will be used
     * @return self
     */
    public function addObjectPermission($domainObject, $mask, $securityIdentity = null);

    /**
     * Sets permission mask for a given class. All previous permissions for this
     * user and this class will be over written. If none existed, a new one will be created.
     *
     * @param  mixed                                      $domainObject
     * @param  int                                        $mask
     * @param  UserInterface|TokenInterface|RoleInterface $securityIdentity if none given, the current session user will be used
     * @return self
     */
    public function addClassPermission($domainObject, $mask, $securityIdentity = null);

    /** Set permission mask for a given field of a domain object. All previous permissions
     * for this user and this object will be over written. If none existed, a new one will be created.
     *
     * @param  mixed                                      $domainObject
     * @param  string                                     $field
     * @param  int                                        $mask
     * @param  UserInterface|TokenInterface|RoleInterface $securityIdentity if none fiven, the current session user will be used
     * @return self
     */
    public function addObjectFieldPermission($domainObject, $field, $mask, $securityIdentity = null);

    /** Set permission mask for a given field of a class. All previous permissions for this
     * user and this object will be over written. If none existed, a new one will be created.
     *
     * @param  mixed                                      $domainObject
     * @param  string                                     $field
     * @param  int                                        $mask
     * @param  UserInterface|TokenInterface|RoleInterface $securityIdentity if none fiven, the current session user will be used
     * @return self
     */
    public function addClassFieldPermission($domainObject, $field, $mask, $securityIdentity = null);

    /**
     * Sets permission mask for a given domain object. All previous permissions for this
     * user and this object will be over written. If none existed, a new one will be created.
     *
     * @param mixed                                      $domainObject
     * @param int                                        $mask
     * @param UserInterface|TokenInterface|RoleInterface $securityIdentity if none given, the current session user will be used
     */
    public function setObjectPermission($domainObject, $mask, $securityIdentity = null);

    /**
     * Sets permission mask for a given class. All previous permissions for this
     * user and this class will be over written. If none existed, a new one will be created.
     *
     * @param mixed                                      $domainObject
     * @param int                                        $mask
     * @param UserInterface|TokenInterface|RoleInterface $securityIdentity if none given, the current session user will be used
     */
    public function setClassPermission($domainObject, $mask, $securityIdentity = null);

    /** Set permission mask for a given field of a domain object. All previous permissions
     * for this user and this object will be over written. If none existed, a new one will be created.
     *
     * @param mixed                                      $domainObject
     * @param string                                     $field
     * @param int                                        $mask
     * @param UserInterface|TokenInterface|RoleInterface $securityIdentity if none fiven, the current session user will be used
     */
    public function setObjectFieldPermission($domainObject, $field, $mask, $securityIdentity = null);

    /** Set permission mask for a given field of a class. All previous permissions for this
     * user and this object will be over written. If none existed, a new one will be created.
     *
     * @param mixed                                      $domainObject
     * @param string                                     $field
     * @param int                                        $mask
     * @param UserInterface|TokenInterface|RoleInterface $securityIdentity if none fiven, the current session user will be used
     */
    public function setClassFieldPermission($domainObject, $field, $mask, $securityIdentity = null);

    /**
     * @param mixed  $domainObject
     * @param int    $mask
     * @param null   $securityIdentity
     * @param string $type
     *
     * @return self
     */
    public function revokePermission($domainObject, $mask, $securityIdentity = null, $type = 'object');

    /**
     * @param mixed  $domainObject
     * @param string $field
     * @param int    $mask
     * @param null   $securityIdentity
     * @param string $type
     *
     * @return self
     */
    public function revokeFieldPermission($domainObject, $field, $mask, $securityIdentity = null, $type = 'object');

    /**
     * @param mixed                                          $domainObject
     * @param UserInterface | TokenInterface | RoleInterface $securityIdentity if none given, the current session user will be used
     */
    public function revokeAllObjectPermissions($domainObject, $securityIdentity = null);

    /**
     * @param mixed                                          $domainObject
     * @param UserInterface | TokenInterface | RoleInterface $securityIdentity if none given, the current session user will be used
     */
    public function revokeAllClassPermissions($domainObject, $securityIdentity = null);

    /**
     * @param mixed                                          $domainObject
     * @param string                                         $field
     * @param UserInterface | TokenInterface | RoleInterface $securityIdentity if none given, the current session user will be used
     */
    public function revokeAllObjectFieldPermissions($domainObject, $field, $securityIdentity = null);

    /**
     * @param mixed                                          $domainObject
     * @param string                                         $field
     * @param UserInterface | TokenInterface | RoleInterface $securityIdentity if none given, the current session user will be used
     */
    public function revokeAllClassFieldPermissions($domainObject, $field, $securityIdentity = null);

    /**
     * Pre Load Acls for all managed entries, that avoid doctrine to create N extra request.
     *
     * @param array $objects
     * @param array $identities
     *
     * @return \SplObjectStorage
     */
    public function preloadAcls($objects, $identities = array());

    /**
     * Delete entry related of item managed via ACL system
     *
     * @param string|DomainObject|DomainObjectInterface $managedItem
     *
     * @return self
     */
    public function deleteAclFor($managedItem, $type = 'class');

    /**
     * @param string|string[] $attributes
     * @param null|object     $object
     *
     * @return bool
     */
    public function isGranted($attributes, $object = null);

    /**
     * @param string|string[] $attributes
     * @param object          $object
     * @param string          $field
     *
     * @return bool
     */
    public function isFieldGranted($attributes, $object, $field);

    /**
     * Retrieves the current session user
     *
     * @return UserInterface
     */
    public function getUser();
}
