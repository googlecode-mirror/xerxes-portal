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
		$configApplication = $objRegistry->getConfig("BASE_WEB_PATH", false, "xerxes");
		
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
    
    // add or update user in the database, get any values in the db not
    // specified here.       
    $user  = $objData->touchUser($user);
    
    // Set properties in session
    $_SESSION["username"] = $user->username;
    $_SESSION["role"] = $strRole;
    $_SESSION["application"] = $configApplication;
    
    // store user's properties in session, so they can be used by
    // controller, and included in xml for views. 
    
    $_SESSION["user_properties"] = $user->properties();
    //Groups too. Empty array not null please. 
    $_SESSION["user_groups"] = $user->usergroups;
	}
}

?>

