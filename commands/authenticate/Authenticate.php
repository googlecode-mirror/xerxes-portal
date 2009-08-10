<?php

	/**
	 * Base class for authentication commands
	 *
	 */
	
	abstract class Xerxes_Command_Authenticate extends Xerxes_Framework_Command
	{
		protected $authentication = null;
		
		public function execute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
		{
			// if the authentication_source is set in the request, then it takes precedence
			
			$override = $objRequest->getProperty("authentication_source");
			
			if ( $override == null )
			{
				// otherwise, see if one has been set in session from a previous login
				
				$session_auth = $objRequest->getSession("auth");
				
				if ( $session_auth != "" )
				{
					$override = $session_auth;
				}
			}
			
			// now make the object
			
			$configAuth = $objRegistry->getAuthenticationSource($override);
			
			switch ( $configAuth )
			{
				case "ldap":
					$this->authentication = new Xerxes_LDAP($objRequest, $objRegistry);
					break;
					
				case "innovative":
					require_once("config/authentication/innovative.php");
					$this->authentication =  new Xerxes_InnovativePatron_Local($objRequest, $objRegistry);
					break;
					
				case "cas":
					$this->authentication =  new Xerxes_CAS($objRequest, $objRegistry);
					break;
					
				case "guest":
					$this->authentication =  new Xerxes_GuestAuthentication($objRequest, $objRegistry);
					break;					

				case "demo":
					$this->authentication =  new Xerxes_DemoAuthentication($objRequest, $objRegistry);
					break;		

				case "shibboleth":
					require_once("config/authentication/shibboleth.php");
					$this->authentication =  new Xerxes_Shibboleth_Local($objRequest, $objRegistry);
					break;			
				
				case "custom":
					require_once("config/authentication/custom.php");
					$this->authentication =  new Xerxes_CustomAuth($objRequest, $objRegistry);
					break;
					
				default:
					throw new Exception("unsupported authentication type");
			}
			
			// we set this so we can keep track of the authentication type
			// through various requests
			
			$this->authentication->id = $configAuth;
			
			parent::execute($objRequest, $objRegistry);
		}
	}

?>