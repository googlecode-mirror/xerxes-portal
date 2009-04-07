<?php

	/**
	 * Authenticate users against an LDAP-enabled directory server
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */

	class Xerxes_LDAP
	{
		private $strConroller = null;		// location of the host
		private $iPort = null;				// port number to connect on
		
		/**
		* Constructor
		* 
		* @param string $strHost		address of directory server or domain controller
		* @param int $iPort				[optional] port to bind on
		*/
		
		public function __construct( $strHost, $iPort = null )
		{
			$this->strController = $strHost;
			$this->iPort = $iPort;
		}
		
		/**
		* Authenticates the user against the directory server
		* 
		* @param string $strDomain		domain for user, e.g., "calstate.edu"
		* @param string $strUserName	username
		* @param string $strPassword	password
		* @param bool $bolAlias			[optional] whether the supplied username might be an alias in which
		* 								 case will do a look-up of the uid
		* @return bool					true on successful bind, false otherwise
		*/
		
		public function authenticate( $strDomain, $strUserName, $strPassword )
		{
			$bolAuth = false;
			
			// connect to ldap server
			$objConn = ldap_connect($this->strController, $this->iPort);
			
			if ($objConn)
			{
				if ( $strPassword != null )
				{
					// bind to ldap server
					$bolAuth = ldap_bind($objConn, $strUserName . "@" . $strDomain, $strPassword);
				} 
				
				ldap_close($objConn);
			}
			
			return $bolAuth;
		}
		
		public function getUID( $strLdapBase, $strLdapFilter, $strAttribute, $strUserName, $strSuperUser = null, $strSuperPass = null )
		{
			$strUID = "";
			
			// connect to ldap server
			
			$objConn = ldap_connect($this->strController, $this->iPort);
			
			if ( $objConn )
			{
				// bind to the directory; if superuser and superpass are null
				// then this is an annonymous bind
				
				if (ldap_bind($objConn, $strSuperUser, $strSuperPass))
				{
					// lookup the user by criteria
					
					$objResults = ldap_search($objConn, $strLdapBase, $strLdapFilter);
					
					if ( $objResults )
					{
						// retrieve match(es)
						
						$arrEntries = ldap_get_entries($objConn, $objResults);
						
						if ( count($arrEntries) == 1)
						{
							if ( array_key_exists($strAttribute, $arrEntries[0]))
							{
								$entry = $arrEntries[0][$strAttribute];
								
								if ( is_array($entry) )
								{
									$strUID = $entry[0];
								}
								else
								{
									$strUID = $entry;
								}
							}
						}
						if ( count($arrEntries) > 1 )
						{
							// throw some error here?
						}
						
						foreach ( $arrEntries as $arrEntry )
						{
							print_r($arrEntry);
						}
					}	
				}

				ldap_close($objConn);
			}
			
			return $strUID;
		}
	}

?>