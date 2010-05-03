<?php

	/**
	 * Authenticate users against the 'demo_users' list in configuration file
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id: DemoAuthentication.php 1009 2009-11-30 21:34:21Z dwalker@calstate.edu $
	 * @package Xerxes
	 */

	class Xerxes_DemoAuthentication extends Xerxes_Framework_Authenticate 
	{
		/**
		* Authenticates the user against the directory server
		*/
		
		public function onCallBack()
		{
			$strUsername = $this->request->getProperty( "username" );
			$strPassword = $this->request->getProperty( "password" );
			
			$configDemoUsers = $this->registry->getConfig( "DEMO_USERS", false );

			// see if user is in demo user list
			
			$bolAuth = false;
			
			if ( $configDemoUsers != null )
			{
				// get demo user list from config
				
				$arrUsers = explode( ",", $configDemoUsers );
				
				foreach ( $arrUsers as $user )
				{
					$user = trim( $user );
					
					// split the username and password
	
					$arrCredentials = array ( );
					$arrCredentials = explode( ":", $user );
					
					$strDemoUsername = $arrCredentials[0];
					$strDemoPassword = $arrCredentials[1];
					
					if ( $strUsername == $strDemoUsername && $strPassword == $strDemoPassword )
					{
						$bolAuth = true;
					}
				}
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