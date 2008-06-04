<?php

/**
 * Authenticate the user against a directory server, either LDAP, Innovative Patron API, 
 * or a demo user account
 */

class Xerxes_Command_AuthenticateLogin extends Xerxes_Command_Authenticate
{
	/**
	 * Acquires username, password, and return url from params, and authenticates the user
	 * against a directory server
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int		status
	 */
	
	public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
		$bolAuthenticated = false;		// user is an authenticated  user
		$bolAuthorized = true;			// user is an authorized user, true unless authorization source included
		$bolDemo = false;				// user is a demo user
		
		
		// values from the request
		
		$strPostBack = $objRequest->getProperty("postback");
		$strUsername = $objRequest->getProperty("username");
		$strPassword = $objRequest->getProperty("password");
		$strReturn = $objRequest->getProperty("return");
				
		if ( $strReturn == null ) 
    {
      // default to home page
      $strReturn = $objRegistry->getConfig("BASE_WEB_PATH", true);
    }

		// configuration settings
		
		$configAuthenticationSource = $objRegistry->getConfig("AUTHENTICATION_SOURCE", false, "demo");		
		$configAuthorizeSource = $objRegistry->getConfig("AUTHORIZATION_SOURCE", false);
		$configDemoUsers = $objRegistry->getConfig("DEMO_USERS", false);
		$configHttps = $objRegistry->getConfig("SECURE_LOGIN", false, false);
		
		
    ### REDIRECT TO SECURE if neccesary ###
		
		// if secure login is required, then force the user back thru https
		
		if ( $configHttps == true && $objRequest->getServer("HTTPS") == null )
		{
			$objRequest->setRedirect("https://" .  $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );
			return 1;
		}
    
		### REMOTE AUTHENTICATION: CAS and Shibboleth ###

		// for cas different from 'local' authentication sources in that 
		// we redirect the user out to an external login page		
		
		if ( $configAuthenticationSource == "cas")
		{
			$configUrlBaseDirectory = $objRegistry->getConfig("BASE_URL", true);
			$configCasLogin = $objRegistry->getConfig("CAS_LOGIN", true);
			
			$strUrl = $configCasLogin . "?service=" . 
				urlencode($configUrlBaseDirectory . "/?base=authenticate&action=cas-validate" .
				"&return=" . urlencode($strReturn));
				
			$objRequest->setRedirect($strUrl);
			return 1;
		}
		
    // for shibboleth, if they got this far, we should have authentication
    // params in header already from the Shib SP and apache config, we don't
    // need to redirect, just read what's been provided.  
    if ( $configAuthenticationSource == "shibboleth" ) {
      
       //Get username header from configged name
       $username_header = $objRegistry->getConfig("shib_username_header", false, "REMOTE_USER");      
       $strUsername = null;
       if ( array_key_exists($username_header, $_SERVER)) {
         $strUsername = $_SERVER[$username_header];
       }
       
       if ( $strUsername ) 
       {
         
         $user = new Xerxes_User($strUsername);
         // set usergroups to null meaning unless the delegate sets
         // usergroups, we'll just keep what's in the db, if anything. 
         $user->usergroups = null;
         $shib_map_file = $objRegistry->getConfig("APP_DIRECTORY", true) . "/config/shibboleth/shib_map.php";
         if ( file_exists($shib_map_file)) {
           require_once($shib_map_file);
           $user = local_shib_user_setup($_SERVER, $user);
         }                  
         $this->register($user, "named");
         
         $objRequest->setRedirect("http://" . $objRequest->getServer('SERVER_NAME') . $strReturn );
         return 1;
       }
    }
		
		### LOCAL AUTHENTICATION ###
		
    
		// if this is not a 'postback', then the user has not 
		// submitted the form, they are arriving for first time
		// so stop the flow and just show the login page with form
		
		if ( $strPostBack == null ) return 1;
			
		// otherwise: we'll start off by assuming that the user id is the same as the username
		// unless one of the authentication sources tells us otherwise
		
		$strUID = $strUsername;
		
		// try to authenticate user against the configured authentication source

		if ( $configAuthenticationSource == "demo" )
		{
			// skip authentication against a directory
		}
		elseif ( $configAuthenticationSource == "ldap")
		{			
			$configDirectoryServer = $objRegistry->getConfig("DIRECTORY_SERVER", true);
			$configDomain = $objRegistry->getConfig("DOMAIN", true);
			$configLdapSearchBase = $objRegistry->getConfig("LDAP_SEARCH_BASE", false);			
			
			$objAuth = new Xerxes_LDAP( $configDirectoryServer );
			
			// this is a search look-up for the uid, get the uid
			
			if ( $configLdapSearchBase != null )
			{
				// search criteria
				
				$configLdapSearchFilter = $objRegistry->getConfig("LDAP_SEACH_FILTER", true);
				$configLdapSearchUID = $objRegistry->getConfig("LDAP_SEACH_UID", true);
				$configLdapSuperUser = $objRegistry->getConfig("LDAP_SUPER_USER", false);
				$configLdapSuperPass = $objRegistry->getConfig("LDAP_SUPER_PASS", false);
				$configLdapCannonical = $objRegistry->getConfig("LDAP_CANNONICALIZE_USER", false, false);
				
				// extract the cannonical user id
				
				$strUID = $objAuth->getUID( $configLdapSearchBase, $configLdapSearchFilter, $configLdapSearchUID, 
					$strUsername, $configLdapSuperUser, $configLdapSuperPass );
				
				// the username, which was an alias, should be converted to the uid so
				// users have one cannonical username internally within xerxes
					
				if ( $configLdapCannonical !== false )
				{
					$strUsername = $strUID;
				}
			}

			// see if user can bind to directory server 
			// with supplied credentials
			
			if ( $objAuth->authenticate( $configDomain, $strUID, $strPassword ) == true )
			{
				$bolAuthenticated = true;
			}
		}
		elseif ( $configAuthenticationSource == "innovative")
		{
			$configPatronApi = $objRegistry->getConfig("INNOVATIVE_PATRON_API", true);
			
			$objAuth = new Xerxes_InnovativePatron( $configPatronApi );
			
			// see if the user passes the pin test
			
			if ( $objAuth->authenticate( $strUID, $strPassword ) == true )
			{
				$bolAuthenticated = true;
			}
		}
		else
		{
			throw new Exception("unsupported authentication source");
		}
		
		### AUTHORIZATION ###
		
		// if authorization is necessary, add it here; only one currently
		// is innovative patron api
		
		if ( $bolAuthenticated == true && $configAuthorizeSource != null )
		{
			$bolAuthorized = false;		// now must pass authorization test as well
			
			if ( $configAuthorizeSource == "innovative")
			{
				$configPatronTypes = $objRegistry->getConfig("INNOVATIVE_PATRON_API", false);
				$configPatronApi = $objRegistry->getConfig("INNOVATIVE_PATRON_API", true);
			
				$objAuth = new Xerxes_InnovativePatron( $configPatronApi );
				
				// if config requires authorization based on the user's
				// patron type code
					
				if ( $configPatronTypes != "" )
				{
					// get the user's patron type
					
					$arrData = $objAuth->getData( $strUID );
					$iUserType = (int) $arrData["P TYPE"];
										
					// get the list of approved types
					
					$arrPatronTypes = explode(",", $configPatronTypes );
					
					// compare them
					
					foreach ( $arrPatronTypes as $strPtype)
					{
						$strPtype = trim($strPtype);
						$iPtype = (int) $strPtype;
						
						if ( $iUserType == $iPtype )
						{
							$bolAuthorized = true;
						}
					}
				}
				else
				{
					$bolAuthorized = true;
				}				
			}
		}
		

		### DEMO USER ###
		
		// see if user is in demo user list; we do this seperately so demo accounts
		// can log-in even if another authentication source is configured
		
		if ( $configDemoUsers != null )
		{
			// get demo user list from config
			
			$arrUsers = explode(",", $configDemoUsers );
			
			foreach ( $arrUsers as $user )
			{
				$user = trim($user);

				// split the username and password
				
				$arrCredentials = array();
				$arrCredentials = explode(":", $user);

				$strDemoUsername = $arrCredentials[0];
				$strDemoPassword = $arrCredentials[1];
				
				if ( $strUsername == $strDemoUsername && $strPassword == $strDemoPassword )
				{
					$bolDemo = true;
				}
			}
		}
		
		### REGISTER THE USER ####
		
		if ( ( $bolAuthenticated == true && $bolAuthorized == true ) || $bolDemo == true )
		{			
			// assign role appropriately
			
			$role = "named"; if ( $bolDemo == true ) $role = "demo";
			
			$this->register($strUsername, $role);
			
			$objRequest->setRedirect("http://" . $objRequest->getServer('SERVER_NAME') . $strReturn );
		}
		else
		{
			// failed the login, so present a message to the user
			// whether this is authentication or authorization error
			
			$objXml = new DOMDocument();
			$objXml->loadXML("<error />");
			$strFailed = "";
			
			if ( $bolAuthenticated == false )
			{
				$strFailed = "authentication";
			}
			else
			{
				$strFailed = "authorization";
			}
			
			$objFail = $objXml->createElement("message", $strFailed);
			$objXml->documentElement->appendChild($objFail);
			
			$objRequest->addDocument($objXml);
		}
		
		return 1;			

	}
}


?>