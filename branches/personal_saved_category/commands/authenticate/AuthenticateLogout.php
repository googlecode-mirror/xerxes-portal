<?php

/**
 * Destorys the user's session and logs them out of the system
 */



class Xerxes_Command_AuthenticateLogout extends Xerxes_Command_Authenticate
{
	/**
	 * Logs the user out and sends them to the logout url, if specified,
	 * or base url fo the application if not.
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int		status
	 */
	
	public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
		// values from the request
		
		$strPostBack = $objRequest->getProperty("postback");
		
		// if this is not a 'postback', then the user has not 
		// submitted the form, thus confirming logout
		
		if ( $strPostBack == null ) return 1;
		
		// configuration settings
		
		$configBaseURL = $objRegistry->getConfig("BASE_URL", true);
		$configLogoutUrl = $objRegistry->getConfig("LOGOUT_URL", false, $configBaseURL);
		
		// release the data associated with the session
		
		session_destroy();			
		session_unset();			
			
		// delete cookies
		
		setcookie("PHPSESSID", "", 0, "/");
		setcookie("saves", "", 0, "/");
			
		// redirect to specified logout location

		$objRequest->setRedirect($configLogoutUrl);
		
		return 1;
	}
}


?>