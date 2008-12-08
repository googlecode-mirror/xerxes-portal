<?php

class Xerxes_CustomAuth
{
	public $username;
	public $first_name;
	public $last_name;
	public $email;
	
	function authenticate($objRequest, $objRegistry)
	{
		$strUsername = $objRequest->getProperty("username");
		$strPassword = $objRequest->getProperty("password");
		
		// do some logic here to authenticate the user
		// return true if successful, false otherwise
		
		// TODO: need to work out the logic for (optionally) passing
		// back first and last name, email address, like is possible
		// with Shibboleth
	}
}


?>