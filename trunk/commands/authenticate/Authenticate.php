<?php

/**
 * Base class for authentication commands
 *
 */

abstract class Xerxes_Command_Authenticate extends Xerxes_Framework_Command
{
	/**
	 * Stores the username and role in session state, reassigns any previously
	 * saved records under a temporary username to the new named user
	 *
	 * @param string $user	string username (legacy) or Xerxes_User object (preferred). 
	 * @param string $strRole		role
	 */
	
	protected function register($user, $strRole)
	{
    
    if (is_string($user)) {
      $user = new Xerxes_User($user);
    }
    
		// configuration settings
		
		$objRegistry = Xerxes_Framework_Registry::getInstance(); $objRegistry->init();	
		$configApplication = $objRegistry->getConfig("APPLICATION_NAME", false, "xerxes");
		
		// data map
		
		$objData = new Xerxes_DataMap();

		// if the user was previously active under a local username 
		// then reassign any saved records to the new username
			
		if ( array_key_exists("username", $_SESSION) && array_key_exists("role", $_SESSION))
		{					
			if ( $_SESSION["role"] == "local")
			{
				$objData->reassignRecords($_SESSION["username"], $user->username);
			}
		}
		
		$_SESSION["username"] = $user->username;
		$_SESSION["role"] = $strRole;
		$_SESSION["application"] = $configApplication;
    // Store user's properties in session, so they can be used by
    // controller, and included in XML for views. 
    $_SESSION["user_properties"] = $user->properties();
    
		// add or update user in the database
		
    $objData->touchUser($user);        
	}
}

class Xerxes_AccessDeniedException extends Xerxes_Exception { 
  //New default heading. 
  public function __construct($message, $heading = "Access Denied")
  {
    parent::__construct($message, $heading);
    $this->heading = $heading;
  }

}

?>