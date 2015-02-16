<?php

namespace CDesign\LdapBundle\Ldap;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Role\Role;

/**
 *
 * CDesign/LdapBundle/Ldap/LdapUserInterface
 *
 */
interface LdapUserInterface extends UserInterface
{
    /**
     * Sets the user roles property.
     *
     * @param Role[] $roles
     * @return LdapUser
     */
    public function setRoles($roles);
}