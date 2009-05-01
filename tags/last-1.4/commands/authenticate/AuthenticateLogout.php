<?php

/**
 * Destorys the user's session and logs them out of the system
 */

class Xerxes_Command_AuthenticateLogout extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		// values from the request
		
		$strPostBack = $this->request->getProperty("postback");
		
		// if this is not a 'postback', then the user has not 
		// submitted the form, thus confirming logout
		
		if ( $strPostBack == null ) return 1;
		
		// configuration settings
		
		$configBaseURL = $this->registry->getConfig("BASE_URL", true);
		$configLogoutUrl = $this->registry->getConfig("LOGOUT_URL", false, $configBaseURL);
		
		// release the data associated with the session
		
		session_destroy();			
		session_unset();			
			
		// delete cookies
		
		setcookie("PHPSESSID", "", 0, "/");
		setcookie("saves", "", 0, "/");
			
		// redirect to specified logout location

		$this->request->setRedirect($configLogoutUrl);
		
		return 1;
	}
}


?>