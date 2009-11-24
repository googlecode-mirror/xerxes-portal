<?php

	/**
	 * Authenticate users against an LDAP-enabled directory server
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id: LDAP.php 974 2009-10-28 20:54:47Z dwalker@calstate.edu $
	 * @package Xerxes
	 */

	class Xerxes_LDAP extends Xerxes_Framework_Authenticate 
	{
		/**
		* Authenticates the user against the directory server
		*/
		
		public function onCallBack()
		{
			$strUsername = $this->request->getProperty( "username" );
			$strPassword = $this->request->getProperty( "password" );
			
			$strController = $this->registry->getConfig( "DIRECTORY_SERVER", true );
			$strDomain = $this->registry->getConfig( "DOMAIN", true );
			
			$bolAuth = false;
			
			// connect to ldap server
			
			$objConn = ldap_connect($strController);
			
			if ($objConn)
			{
				if ( $strPassword != null )
				{
					// bind with username and pass
					
					$bolAuth = ldap_bind($objConn, $strUsername . "@" . $strDomain, $strPassword);
				} 
				
				ldap_close($objConn);
			}
					
			if ( $bolAuth == true )
			{
				// register the user and stop the flow
				
				$this->user->username = $strUsername;
				$this->register();
				return true;
			}
			else
			{
				return false;
			}
		}
	}

?>