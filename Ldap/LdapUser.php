<?php

namespace CDesign\LdapBundle\Ldap;

/**
 *
 * CDesign/LdapBundle/Ldap/LdapUser
 *
 */
class LdapUser implements LdapUserInterface
{
    private $username;
    private $password;
    private $salt;
    private $roles;

    public function __construct($username, $password = '', $salt = '', array $roles = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set roles
     *
     * @param array $roles
     * @return LdapUser
     */
    public function setRoles($roles)
    {
        $this->roles = array_filter(array_unique($roles)); // array_filter() removes blank values

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }
}
