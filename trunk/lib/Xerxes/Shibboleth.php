<?php

	/**
	 * Sibboleth Authentication
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package Xerxes
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	class Xerxes_Shibboleth extends Xerxes_Framework_Authenticate 
	{
		/**
		 * For shibboleth, if user got this far, we should have authentication
		 * params in header already from the Shib SP and apache config, just read 
		 * what's been provided. 
		 */
		
		public function onLogin()
		{
			// get username header from configged name
			
			$username_header = $this->registry->getConfig( "shib_username_header", false, "REMOTE_USER" );
			
			$strUsername = $this->request->getServer($username_header);
			
			if ( $strUsername != null )
			{
				$this->user->username = $strUsername;
				
				// set usergroups to null meaning unless the delegate sets
				// usergroups, we'll just keep what's in the db, if anything. 
				
				$this->user->usergroups = null;
			
				// let the 'local' child class parse the headers
				
				$this->mapUserData();
				
				// register the user
				
				$this->register();
				
				return true;
			}
			else 
			{
				throw new Exception("Couldn't find Shibboleth supplied username in header");	
			}
		}
		
		/**
		 * Shibboleth_Local class defines this
		 */
		
		protected function mapHeaders()
		{
			
		}
	}

?>