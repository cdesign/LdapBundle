<?php

namespace CDesign\LdapBundle\Ldap;

use CDesign\LdapBundle\Ldap\Exception\ConnectionException;
use CDesign\LdapBundle\Ldap\Exception\LdapException;

/*
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Francis Besset <francis.besset@gmail.com>
 * @author John Christensen <john@christensendesign.net>
 */
class Ldap
{
    private $host;
    private $port;
    private $baseDn;
    private $namingAttribute;
    private $adminBind;
    private $adminDn;
    private $adminPassword;
    private $version;
    private $useSsl;
    private $useStartTls;
    private $optReferrals;
    private $connection;
    private $boundEntry;

	/**
     * constructor
     *
     * @param string  $host
     * @param integer $port (def is 389)
     * @param string  $baseDn
     * @param string  $namingAttribute LDAP attribute to filter names by; pass 'upn' for 'userprincipalname', 'sam' for 'samaccountName', or 'both' (def is 'both')
     * @param boolean $adminBind
     * @param string  $adminDn
     * @param string  $adminPassword
     * @param integer $version  LDAP protocol version 2 vs 3 (def is 3)
     * @param boolean $useSsl
     * @param boolean $useStartTls
     * @param boolean $optReferrals
     *
     * @throws LdapException
     */
    public function __construct(
        $host            = null,
        $port            = 389,
        $baseDn          = null,
        $namingAttribute = 'both',
        $adminBind       = false,
        $adminDn         = null,
        $adminPassword   = null,
        $version         = 3,
        $useSsl          = false,
        $useStartTls     = false,
        $optReferrals    = false )
    {
        if (!extension_loaded('ldap')) {
            throw new LdapException('Ldap module is needed. ');
        }

        $this->host            = $host;
        $this->port            = $port;
        $this->baseDn          = $baseDn;
        $this->namingAttribute = $namingAttribute;
        $this->adminBind       = (boolean) $adminBind;
        $this->adminDn         = $adminDn;
        $this->adminPassword   = $adminPassword;
        $this->version         = $version;
        $this->useSsl          = (boolean) $useSsl;
        $this->useStartTls     = (boolean) $useStartTls;
        $this->optReferrals    = (boolean) $optReferrals;

        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    //------------------------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setHost($host)
    {
        $this->host = $host;
    
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function setPort($port)
    {
        $this->port = $port;
    
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setBaseDn($baseDn)
    {
        $this->baseDn = $baseDn;
    
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamingAttribute()
    {
        return $this->namingAttribute;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamingAttribute($namingAttribute)
    {
        $this->namingAttribute = $namingAttribute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminBind()
    {
        return $this->adminBind;
    }

    /**
     * {@inheritdoc}
     */
    public function setAdminBind($adminBind)
    {
        $this->adminBind = (boolean) $adminBind;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminDn()
    {
        return $this->adminDn;
    }

    /**
     * {@inheritdoc}
     */
    public function setAdminDn($adminDn)
    {
        $this->adminDn = $adminDn;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPassword()
    {
        return $this->adminPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function setAdminPassword($adminPassword)
    {
        $this->adminPassword = $adminPassword;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUseSsl()
    {
        return $this->useSsl;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setUseSsl($useSsl)
    {
        $this->useSsl = (boolean) $useSsl;
    
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUseStartTls()
    {
        return $this->useStartTls;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setUseStartTls($useStartTls)
    {
        $this->useStartTls = (boolean) $useStartTls;
    
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getOptReferrals()
    {
        return $this->optReferrals;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setOptReferrals($optReferrals)
    {
        $this->optReferrals = (boolean) $optReferrals;
    
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getBoundEntry()
    {
        return $this->boundEntry;
    }

    //------------------------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function bind($username = '', $password = '')
    {
        if (!$this->connection) {
            $this->connect();
        }

        if (strlen($username)==0){
            throw new ConnectionException('Username cannot be blank.');
        } elseif (strlen($password)==0){
            throw new ConnectionException('Password cannot be blank.');
        }

        $attr = strtolower($this->namingAttribute);

        if($attr == 'upn'){

            $field = 'UserPrincipalName';
            $filter = 'userprincipalname='. $username .'@' . $this->getLdapDomain();

        } else if($attr == 'sam'){

            $field = 'sAMAccountName';
            $filter = 'samaccountname='. $username;

        } else {

            $field = 'UserPrincipalName or sAMAccountName';
            $filter = '(|(userprincipalname='. $username .'@' . $this->getLdapDomain() . ')(samaccountname='. $username .'))';

        }

        // Retrieve all the ldap entries for this username...
        $search = ldap_search($this->connection, $this->baseDn, $filter);
        $entries = ldap_get_entries($this->connection, $search);

        if( !$entries || $entries['count'] == 0 ){
            throw new ConnectionException(sprintf("Username '" . $username . "' does not exist as a " . $field . " on Ldap server %s:%s", $this->host, $this->port));
        } else {
            unset($entries['count']); // remove the array count
        }

        // Remove all disabled accounts...
        $disabled = 0;
        foreach($entries as $key => $entry)
        {
            if ($this->bitIsSet($entry['useraccountcontrol'][0], 2)) // Bit 2 of 'useraccountcontrol' is 'ACCOUNTDISABLE'
            {
                unset($entries[$key]);
                $disabled++;
            }
        }

        // Report disabled errors...
        if(count($entries) == 0)
        {
            if($disabled == 1) {
                $message = "The Ldap account for '" . $username . "' is disabled";
            } else {
                $message = "All Ldap accounts for '" . $username . "' are disabled";
            }
            throw new ConnectionException(sprintf($message . " on Ldap server %s:%s", $this->host, $this->port));
        }

        // Remove all locked accounts...
        $locked = 0;
        foreach($entries as $key => $entry)
        {
            if ($this->bitIsSet($entry['useraccountcontrol'][0], 5)) // Bit 5 of 'useraccountcontrol' is 'LOCKOUT'
            {
                unset($entries[$key]);
                $locked++;
            }
        }

        // Report locked errors...
        if(count($entries) == 0)
        {
            if($locked == 1) {
                $message = "The only active Ldap account for '" . $username . "' is locked";
            } else {
                $message = "All Ldap accounts for '" . $username . "' are locked";
            }
            throw new ConnectionException(sprintf($message . " on Ldap server %s:%s", $this->host, $this->port));
        }

        // Attempt to bind with all remaining entries (until one is successful)...
        foreach($entries as $key => $entry)
        {
            if (false !== @ldap_bind($this->connection, $entry['dn'], $password)) {
                // we are now bound!!
                $this->boundEntry = $entry;
                return $this;
            }
        }

        // if we got here, we couldn't bind to any of the entries...
        throw new ConnectionException(sprintf('Username / password invalid to connect on Ldap server %s:%s', $this->host, $this->port));
    }

    /**
     * {@inheritdoc}
     */
    public function unbind()
    {
        if (is_resource($this->connection)) {
            ldap_unbind($this->connection);
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBoundRoles($node = 'ou')
    {
        $roles = array();

        if ( $this->boundEntry === null ) {
            $this->bind();
        }
        if (isset($this->boundEntry['memberof'])) {

            foreach ($this->boundEntry['memberof'] as $nodeListing)
            {
                $matches = array();
                preg_match_all('/(' . $node . '=[^,]+)/i', $nodeListing, $matches);
                foreach ( $matches[0] as $membership )
                {
                    $roles[] = 'ROLE_'.strtoupper(preg_replace('/.*=/', '', $membership));
                }
            }
        }

        return array_unique($roles);
    }

    //------------------------------------------------------------------------------------------

    /**
     * Establishes an ldap connection
     *
     * @return Ldap
     */
    private function connect()
    {
        if (!$this->connection) {

            $this->connection = ldap_connect( ( $this->getUseSsl() ? 'ldaps://' : '') . $this->getHost(), $this->getPort());

            ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->getVersion());
            ldap_set_option($this->connection, LDAP_OPT_REFERRALS, $this->getOptReferrals());

            if ($this->getUseStartTls()) {
                $tlsResult = ldap_start_tls($this->connection);
                if (!$tlsResult) throw new ConnectionException('TLS initialization failed!');
            }

            if ($this->adminBind) {
                if ( ($this->adminDn === null) || ($this->adminPassword === null) ) {
                    throw new ConnectionException('Admin bind required but credentials not provided. Please see ldapcredentials.yml.');
                }
                if (false === @ldap_bind($this->connection, $this->adminDn, $this->adminPassword)) {
                    throw new ConnectionException('Admin bind credentials incorrect. Please see ldapcredentials.yml or review your LDAP configurations.');
                }
            }
        }

        return $this;
    }

    /**
     * Terminates the current ldap connecton
     *
     * @@return Ldap
     */
    private function disconnect()
    {
        if ($this->connection && is_resource($this->connection)) {
            $this->unbind();
        }

        $this->boundEntry = null;
        $this->connection   = null;

        return $this;
    }

    /**
     * returns the ldap domain extracted from the specified baseDn
     *
     * @return string ldap domain
     */
    private function getLdapDomain(){

        $matches = array();
        preg_match_all('/dc=([^,]+)/i', $this->baseDn, $matches);

        return implode('.', $matches[1]);
    }

    /**
     * returns TRUE if the passed bit is set
     *
     * @param integer $decValue
     * @param integer $bitNo (1 = first bit from right of decValue)
     * @return boolean
     */
    private function bitIsSet($decValue, $bitNo){

        $bitValue = pow(2, $bitNo-1);

        return ( $decValue & $bitValue) == $bitValue;
    }
}
