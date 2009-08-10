<?php

/**
 * An event-based authentication framework
 */

abstract class Xerxes_Framework_Authenticate
{
	protected $user; // user objec
	public $id; // the id of this auth scheme, set by the factory method invoking it
	protected $role = "named"; // users role as named or guest
	protected $registry; // config object
	protected $request; // request object
	protected $return; // the return url to get the user back to where they are in Xerxes
	
	public function __construct($objRequest, $objRegistry)
	{
		$this->request = $objRequest;
		$this->registry = $objRegistry;
		$this->user = new Xerxes_User();
		$this->return = $this->request->getProperty( "return" );
		
		if ( $this->return == "" )
		{
			$this->return = $this->registry->getConfig( "BASE_WEB_PATH", false, "/" );
		}
	}
	
	/**
	 * This gets called _before_ Xerxes shows the user the login form.
	 * 
	 * SSO schemes, like CAS or OpenSSL, will use this function to redirect the 
	 * user to the external SSO system for login.  'Local' authentication options -- 
	 * that is, those that use the Xerxes login form -- would not (re-)define this.
	 * 
	 */
	
	public function onLogin()
	{
	}
	
	/**
	 * This gets called after the user has _returned_ to Xerxes from the local/remote 
	 * login form.
	 * 
	 * SSO schemes would use this function to validate the login request with the SSO service.  
	 * The local authentication schemes would use this function to actually send the user's 
	 * credential to the authentication system (via LDAP, HTTP, etc.) and decide whether 
	 * the user has supplied the correct credentials.
	 */
	
	public function onCallBack()
	{
		return false;
	}
	
	/**
	 * This gets called after the user has chosen to log out
	 * 
	 * Xerxes will itself destroy the session, so only use this if you need to do any 
	 * clean-up with the external authentication system
	 */
	
	public function onLogout()
	{
	}
	
	/**
	 * Get the user object set by authentication/validation function
	 */
	
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * Registers the user in session and with the user tables in the database
	 * and then forwards them to the return url
	 */

	protected function register()
	{
		// data map
		
		$objData = new Xerxes_DataMap();
		
		// if the user was previously active under a local username 
		// then reassign any saved records to the new username
		
		$oldUsername = $this->request->getSession("username");
		$oldRole = $this->request->getSession("role");
		
		if ( $oldRole == "local" )
		{
			$objData->reassignRecords( $oldUsername, $this->user->username );
		}
		
		// add or update user in the database, get any values in the db not
		// specified here.
		 
		$this->user = $objData->touchUser( $this->user );
		
		// set main properties in session
		
		$this->request->setSession("username", $this->user->username);
		$this->request->setSession("role", $this->role);
		
		$configApplication = $this->registry->getConfig( "BASE_WEB_PATH", false, "" );
		$this->request->setSession("application", $configApplication);
		
		// store user's additional properties in session, so they can be used by
		// controller, and included in xml for views. 
		
		$this->request->setSession("user_properties", $this->user->properties());
		
		// groups too empty array not null please. 
	
		$this->request->setSession("user_groups", $this->user->usergroups);
		
		// set this object's id in session
		
		$this->request->setSession("auth", $this->id);
		
		// now forward them to the return url
		
		$this->request->setRedirect($this->return);
	}
}

class Xerxes_User extends Xerxes_Framework_DataValue
{
	public $username;
	public $last_login;
	public $suspended;
	public $first_name;
	public $last_name;
	public $email_addr;
	public $usergroups = array ( );
	
	function __construct($username = null)
	{
		$this->username = $username;
	}
}


?>