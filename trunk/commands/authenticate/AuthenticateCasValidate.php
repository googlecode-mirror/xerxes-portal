<?php

/**
 * Validate a CAS authentication return URL
 *
 */

class Xerxes_Command_AuthenticateCasValidate extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		// values from the request
		
		$strTicket = $this->request->getProperty("ticket");
		$strReturn = $this->request->getProperty("return");
		
		// configuration settings

		$configUrlBaseDirectory = $this->registry->getConfig("BASE_URL", true);
		$configCasValidate = $this->registry->getConfig("CAS_VALIDATE", true);
		$configCasVersion = $this->registry->getConfig("CAS_VERSION", true);

		// get validation response
			
		$strUrl = $configCasValidate . "?ticket=" . $strTicket . 
			"&service=" . urlencode($configUrlBaseDirectory . "/?base=authenticate&action=cas-validate" .
			"&return=" . urlencode($strReturn) );
		
		$strResults = Xerxes_Parser::request( $strUrl );

		// validate the request
		
		$objValidate = new Xerxes_CAS();
			
		if ( $objValidate->isValid($strResults, $configCasVersion) )
		{
			$this->register($objValidate->getUsername(), "named");
			$this->request->setRedirect("http://" . $this->request->getServer('SERVER_NAME') . $strReturn );
		}
		else
		{
			throw new Exception("Could not validate user against CAS server");
		}
		
		return 1;
	}
}


?>