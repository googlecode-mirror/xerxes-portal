<?php

/**
 * Authenticate a guest user
 *
 */

class Xerxes_Command_AuthenticateGuest extends Xerxes_Command_Authenticate
{
	/**
	 * Acquires the return url from parameters and registers the user under
	 * a guest role
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int	status
	 */
	
	public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
		$strReturn = $objRequest->getProperty("return");
		
		$this->register("guest@" . session_id(), "guest");
		$objRequest->setRedirect( "http://" . $objRequest->getServer('SERVER_NAME') . $strReturn );
		
		return 1;
	}
}


?>