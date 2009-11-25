<?php

/**
 * Validate an external authentication request
 */

class Xerxes_Command_AuthenticateValidate extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		// validate the request
		
		$this->authentication->onCallBack();
	}
}

?>