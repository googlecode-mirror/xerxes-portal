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
	 * @param string $strUsername	username
	 * @param string $strRole		role
	 */
	
	protected function register($strUsername, $strRole)
	{
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
				$objData->reassignRecords($_SESSION["username"], $strUsername);
			}
		}
		
		$_SESSION["username"] = $strUsername;
		$_SESSION["role"] = $strRole;
		$_SESSION["application"] = $configApplication;
		
		// add or update user in the database
		
		$objData->touchUser($strUsername);
	}
}


?>