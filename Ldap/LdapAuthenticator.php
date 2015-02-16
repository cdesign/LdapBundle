<?php
/**
 * Created by PhpStorm.
 * User: john_christensen
 * Date: 8/14/14
 * Time: 4:58 PM
 *
 * This authenticator checks the user's credentials against an LDAP server and (optionally)
 * a user provider. If the user is not active in both, login is denied. Roles can be assigned
 * statically and/or by assigning roles from ldap or the user provider.
 *
 */

namespace CDesign\LdapBundle\Ldap;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\SimpleFormAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\DependencyInjection\Container;

use CDesign\LdapBundle\Ldap\Exception\ConnectionException;

class LdapAuthenticator implements SimpleFormAuthenticatorInterface
{
    private $container;
    private $encoderFactory;
    private $allowSpaces;
    private $roles;
    private $rolesNode;
    private $enableInDebug;
    private $addLdapRoles;
    private $addUserRoles;
    private $ldap;

    public function __construct( Container $container, EncoderFactoryInterface $encoderFactory )
    {
        $this->container      = $container;
        $this->encoderFactory = $encoderFactory;
        $this->allowSpaces    = $container->getParameter('cdesign.ldap.allow_spaces');
        $roles                = $container->getParameter('cdesign.ldap.roles');
        $this->rolesNode      = $container->getParameter('cdesign.ldap.roles_node');
        $this->enableInDebug  = $container->getParameter('cdesign.ldap.debug_enable');

        // Determine if roles will be added from ldap and/or the user...
        $this->addLdapRoles = strpos($roles, 'ldap_roles') !== false;
        $this->addUserRoles = strpos($roles, 'user_roles') !== false;

        // Initialize the roles array (with flags removed)...
        $this->roles = array_filter(array_unique(explode(',', str_ireplace([' ', 'ldap_roles', 'user_roles'], '', $roles))));

        $this->ldap = new Ldap(
            $container->getParameter('cdesign.ldap.host'),
            $container->getParameter('cdesign.ldap.port'),
            $container->getParameter('cdesign.ldap.base_dn'),
            $container->getParameter('cdesign.ldap.naming_attribute'),
            $container->getParameter('cdesign.ldap.admin_bind'),
            $container->getParameter('cdesign.ldap.admin_dn'),
            $container->getParameter('cdesign.ldap.admin_password')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        // Get the username for authentication...
        $username = $token->getUsername();

        // Optionally allow underscore or space in AD name...
        $username = ( $this->allowSpaces ? str_replace(' ', '_', $username) : $username );

        // Check to see if debug mode is on...
        $isDebug = $this->container->get('kernel')->isDebug();

        if( !$isDebug || $this->enableInDebug ){
            try {
                $this->ldap = $this->ldap->bind($username, $token->getCredentials());
            } catch (ConnectionException $e) {
                throw new AuthenticationException("Invalid username or password.");
            }

            if($this->addLdapRoles){
                $this->roles = array_merge( $this->roles, $this->ldap->getBoundRoles( strtolower($this->rolesNode) ) );
            }
        }

        try {
            $user = $userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            throw new UsernameNotFoundException( $e->getMessage() ?: "The username '{$username}' does not exist in this system. Please contact the system administrator to request access.");
        }

        $userClass = new \ReflectionClass(get_class($user));

        if ($userClass->implementsInterface('Symfony\Component\Security\Core\User\AdvancedUserInterface'))
        {
            if( !$user->isEnabled() ){
                throw new AuthenticationException("The account for '{$user->getUsername()}' is inactive.");
            }
        }

        if($this->addUserRoles){
            $this->roles = array_merge( $user->getRoles(), $this->roles );
        }

        if( !$this->roles ){
            throw new AuthenticationException("{$user->getUsername()} has no assigned roles in this system.");
        }

        // Update the user's roles (if allowed)...
        if ($userClass->implementsInterface('CDesign\LdapBundle\Ldap\LdapUserInterface'))
        {
            $user->setRoles($this->roles);
        }

        return new UsernamePasswordToken(
            $user,
            $user->getPassword(),
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken
            && $token->getProviderKey() === $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function createToken(Request $request, $username, $password, $providerKey)
    {
        return new UsernamePasswordToken($username, $password, $providerKey);
    }
}