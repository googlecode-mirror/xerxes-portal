<?php

/**
 * custom authentication
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_CustomAuth_Custom extends Xerxes_Model_Authentication_Abstract
{
	public function onLogin()
	{
		// Use this to redirect the user to an exteral system where they will login; 
		// in which case, send the user back to the authenticate/validate action together with 
		// other querystring parameters as needed; that will in turn call onCallBack() below
		
		/* Example:
		
		$url = "https://some.example.edu/login?return=" . $this->validate_url;
		$this->request->setRedirect($url);
		return true;
		*/
	}
	
	public function onCallBack()
	{
		// do some logic here to authenticate the user
		
		$bolSuccess = false;
		
		// LOCAL:
		//
		// if you are using the Xerxes login form for authentication, then do your authentication
		// logic here and return true or false
		
		// the following paramaters are the default ones offered  by the form; if you need others, 
		// simply customize the login form
		
		$strUsername = $this->request->getProperty("username");
		$strPassword = $this->request->getProperty("password");
		
		// REMOTE:
		//
		// if onLogin() pushed the user to an external system for login, then this function
		// will be called when the user comes back; the task here then is simply to validate 
		// that the login was successful (if necessary), and register the user; If the request
		// is bad, throw an Exception with details, do _not_ simple return false
		
		
		// REGISTRATION:
		//
		// If the user passed the authentication challenge, then assign values to the properties
		// below; username is REQUIRED, all others optional
		
		$this->user->username;
		$this->user->first_name;
		$this->user->last_name;
		$this->user->email_addr;

		return $bolSuccess;
	}
}
