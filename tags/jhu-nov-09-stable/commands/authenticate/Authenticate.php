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
			
			// make sure it's in our list, or if blank still, we get the default
			
			$configAuth = $objRegistry->getAuthenticationSource($override);
			
			// now make it!
			
			switch ( $configAuth )
			{
				case "ldap":
					
					$this->authentication = new Xerxes_LDAP($objRequest, $objRegistry);
					break;
					
				case "innovative":
					
					$iii_file = "config/authentication/innovative.php";
					
					if ( file_exists($iii_file) )
					{
						require_once($iii_file);
						$this->authentication = new Xerxes_InnovativePatron_Local($objRequest, $objRegistry);
					}
					else
					{
						$this->authentication = new Xerxes_InnovativePatron($objRequest, $objRegistry);
					}
					break;
					
				case "cas":
					
					$this->authentication = new Xerxes_CAS($objRequest, $objRegistry);
					break;
					
				case "guest":
					
					$this->authentication = new Xerxes_GuestAuthentication($objRequest, $objRegistry);
					break;					

				case "demo":
					
					$this->authentication = new Xerxes_DemoAuthentication($objRequest, $objRegistry);
					break;		

				case "shibboleth":
					
					$shib_file = "config/authentication/shibboleth.php";

					if ( file_exists($shib_file) )
					{
						require_once($shib_file);
						$this->authentication = new Xerxes_Shibboleth_Local($objRequest, $objRegistry);
					}
					else
					{
						$this->authentication = new Xerxes_Shibboleth($objRequest, $objRegistry);
					}
					break;			
				
				case "custom":
					
					require_once("config/authentication/custom.php");
					$this->authentication =  new Xerxes_CustomAuth($objRequest, $objRegistry);
					break;
					
				default:
					
					// check to see if a file exists with an authentication class that extends the framework,
					// if so, then use it; this supports multiple custom schemes
					
					$local_file = "config/authentication/$configAuth.php";
					$class_name = "Xerxes_CustomAuth_" . strtoupper( substr( $configAuth, 0, 1 ) ) . substr( $configAuth, 1 );
					
					if ( file_exists($local_file) )
					{
						require_once($local_file);
						
						if ( ! class_exists($class_name) )
						{
							throw new Exception("the custom authentication scheme '$configAuth' should have a class called '$class_name'");
						}
						
						$this->authentication =  new $class_name($objRequest, $objRegistry);
						
						if ( ! $this->authentication instanceof Xerxes_Framework_Authenticate)
						{
							throw new Exception("class '$class_name' for the '$configAuth' authentication scheme must extend Xerxes_Framework_Authenticate");
						}
					}
					else
					{
						throw new Exception("unsupported authentication type");
					}
			}
			
			// we set this so we can keep track of the authentication type
			// through various requests
			
			$this->authentication->id = $configAuth;
			
			parent::execute($objRequest, $objRegistry);
		}
	}

?>