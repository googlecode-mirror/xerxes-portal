<?php

/**
 * Authenticate the user against a directory server, either LDAP, Innovative Patron API, 
 * or a demo user account
 */

class Xerxes_Command_AuthenticateLogin extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		$bolAuthenticated = false; // user is an authenticated  user
		$bolDemo = false; // user is a demo user

		// values from the request

		$strPostBack = $this->request->getProperty( "postback" );
		$strUsername = $this->request->getProperty( "username" );
		$strPassword = $this->request->getProperty( "password" );
		$strReturn = $this->request->getProperty( "return" );
		
		if ( $strReturn == null )
		{
			// default to home page
			$strReturn = $this->registry->getConfig( "BASE_WEB_PATH", false, "/" );
		}
		
		// configuration settings
		
		$configAuthenticationSource = $this->registry->getAuthenticationSource( $this->request->getProperty("authentication_source"));
    
		$configDemoUsers = $this->registry->getConfig( "DEMO_USERS", false );
		$configHttps = $this->registry->getConfig( "SECURE_LOGIN", false, false );
		

    
		### REDIRECT TO SECURE if neccesary ###

		// if secure login is required, then force the user back thru https

		if ( $configHttps == true && $this->request->getServer( "HTTPS" ) == null )
		{
			$this->request->setRedirect( "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );
			return 1;
		}
		
		### REMOTE AUTHENTICATION: CAS and Shibboleth ###

		// for cas different from 'local' authentication sources in that 
		// we redirect the user out to an external login page		
		
		if ( $configAuthenticationSource == "cas" )
		{
			$configUrlBaseDirectory = $this->registry->getConfig( "BASE_URL", true );
			$configCasLogin = $this->registry->getConfig( "CAS_LOGIN", true );
			
			$strUrl = $configCasLogin . "?service=" . urlencode( $configUrlBaseDirectory . "/?base=authenticate&action=cas-validate" . "&return=" . urlencode( $strReturn ) );
			
			$this->request->setRedirect( $strUrl );
			return 1;
		}
		
		// for shibboleth, if they got this far, we should have authentication
		// params in header already from the Shib SP and apache config, we don't
		// need to redirect, just read what's been provided. 
		
		if ( $configAuthenticationSource == "shibboleth" )
		{
			// get username header from configged name
			
			$username_header = $this->registry->getConfig( "shib_username_header", false, "REMOTE_USER" );
			$strUsername = null;
			
			if ( array_key_exists( $username_header, $_SERVER ) )
			{
				$strUsername = $_SERVER[$username_header];
			}
			
			if ( $strUsername )
			{
				
				$user = new Xerxes_User( $strUsername );
				
				// set usergroups to null meaning unless the delegate sets
				// usergroups, we'll just keep what's in the db, if anything. 
				
				$user->usergroups = null;
				$shib_map_file = "config/shibboleth/shib_map.php";
				
				if ( file_exists( $shib_map_file ) )
				{
					require_once ($shib_map_file);
					$user = local_shib_user_setup( $_SERVER, $user );
				}
				
				$this->register( $user, "named" );
				
				$this->request->setRedirect( "http://" . $this->request->getServer( 'SERVER_NAME' ) . $strReturn );
        
        
				return 1;
			}
		}
		
		### LOCAL AUTHENTICATION ###

		// if this is not a 'postback', then the user has not 
		// submitted the form, they are arriving for first time
		// so stop the flow and just show the login page with form

		if ( $strPostBack == null ) return 1;
			
		// try to authenticate user against the configured authentication source

		if ( $configAuthenticationSource == "demo" )
		{
			// skip authentication against a directory
		} 
		elseif ( $configAuthenticationSource == "custom" )
		{
			// custom authentication
			
			$local_auth_file = "config/authentication/custom.php";
				
			if ( file_exists( $local_auth_file ) )
			{
				require_once ($local_auth_file);
				
				$objCustomAuth = new Xerxes_CustomAuth();
				$bolAuthenticated = $objCustomAuth->authenticate($this->request, $this->registry);
				$strUsername = $objCustomAuth->username;
			}
			else
			{
				throw new Exception("authentication source set to custom, but no custom auth file found");
			}
		}
		elseif ( $configAuthenticationSource == "ldap" )
		{
			// see if user can bind to directory server with supplied credentials
			
			$configDirectoryServer = $this->registry->getConfig( "DIRECTORY_SERVER", true );
			$configDomain = $this->registry->getConfig( "DOMAIN", true );
			
			$objAuth = new Xerxes_LDAP( $configDirectoryServer );
			
			if ( $objAuth->authenticate( $configDomain, $strUsername, $strPassword ) == true )
			{
				$bolAuthenticated = true;
			}
		} 
		elseif ( $configAuthenticationSource == "innovative" )
		{
			$configPatronApi = $this->registry->getConfig( "INNOVATIVE_PATRON_API", true );
			
			$objAuth = new Xerxes_InnovativePatron( $configPatronApi );
			
			// see if the user passes the pin test

			if ( $objAuth->authenticate( $strUsername, $strPassword ) == true )
			{
				$bolAuthenticated = true;
			}
		} 
		else
		{
			throw new Exception( "unsupported authentication source" );
		}
		
		### DEMO USER ###

		// see if user is in demo user list; we do this seperately so demo accounts
		// can log-in even if another authentication source is configured

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
					$bolDemo = true;
				}
			}
		}
		
		### REGISTER THE USER ####
		
		if ( $bolAuthenticated == true || $bolDemo == true )
		{
			// assign role appropriately
			
			$role = "named";
			
			$this->register( $strUsername, $role );
			
			$this->request->setRedirect( "http://" . $this->request->getServer( 'SERVER_NAME' ) . $strReturn );
		}
		else
		{
			// failed the login, so present a message to the user
			// whether this is authentication or authorization error

			$objXml = new DOMDocument( );
			$objXml->loadXML( "<error />" );
			$strFailed = "";
			
			if ( $bolAuthenticated == false )
			{
				$strFailed = "authentication";
			} 
			else
			{
				$strFailed = "authorization";
			}
			
			$objFail = $objXml->createElement( "message", $strFailed );
			$objXml->documentElement->appendChild( $objFail );
			
			$this->request->addDocument( $objXml );
		}
		
		return 1;
	
	}
}

?>