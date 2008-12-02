<?php

/**
 * Validate a CAS authentication return URL
 *
 */

class Xerxes_Command_AuthenticateCasValidate extends Xerxes_Command_Authenticate
{
	/**
	 * Acquires the ticket and return urls from paramaters, and checks back with the CAS
	 * server to validate the user
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int	status
	 */
	
	public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
		// values from the request
		
		$strTicket = $objRequest->getProperty("ticket");
		$strReturn = $objRequest->getProperty("return");
		
		// configuration settings

		$configUrlBaseDirectory = $objRegistry->getConfig("BASE_URL", true);
		$configCasValidate = $objRegistry->getConfig("CAS_VALIDATE", true);
		$configCasVersion = $objRegistry->getConfig("CAS_VERSION", true);

		// get validation response
			
		$strUrl = $configCasValidate . "?ticket=" . $strTicket . 
			"&service=" . urlencode($configUrlBaseDirectory . "/?base=authenticate&action=cas-validate" .
			"&return=" . urlencode($strReturn) );
		
		$strResults = file_get_contents( $strUrl );

		// validate the request
		
		$objValidate = new Xerxes_CAS();
			
		if ( $objValidate->isValid($strResults, $configCasVersion) )
		{
			$this->register($objValidate->getUsername(), "named");
			$objRequest->setRedirect("http://" . $objRequest->getServer('SERVER_NAME') . $strReturn );
		}
		else
		{
			throw new Exception("Could not validate user against CAS server");
		}
		
		return 1;
	}
}


?>