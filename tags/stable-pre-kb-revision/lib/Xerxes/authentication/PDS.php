<?php

	/**
	 * Authenticate users against Metalib PDS
	 * 
	 * @author David Walker
	 * @copyright 2010 California State University
	 * 
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */

	class Xerxes_PDS extends Xerxes_Framework_Authenticate 
	{
		/**
		* Authenticates the user against the directory server
		*/
		
		public function onCallBack()
		{
			$username = $this->request->getProperty("username");
			$password = $this->request->getProperty("password");
			$configInstitute = $this->registry->getConfig("METALIB_INSTITUTE", true);
			
			$configMetalibAddress = $this->registry->getConfig("METALIB_ADDRESS", true);
			$configMetalibUsername = $this->registry->getConfig("METALIB_USERNAME", true);
			$configMetalibPassword = $this->registry->getConfig("METALIB_PASSWORD", true);
			
			$configSecure = $this->registry->getConfig("PDS_USE_HTTPS", false, true);
			
			$objMetalib = new Xerxes_MetaSearch($configMetalibAddress, $configMetalibUsername, $configMetalibPassword);

			// see if user passed authentication
			
			$pass = $objMetalib->authenticateUser($configInstitute,$username, $password, $configSecure);
			
			if ( $pass == true )
			{
				$this->user->username = $username;
				$this->register();
			}
			
			return $pass;
		}
	}

?>
