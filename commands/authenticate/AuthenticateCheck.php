<?php

/**
 * Authentication hook called on every request
 *
 */

class Xerxes_Command_AuthenticateCheck extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		$this->authentication->onEveryRequest();
		return 1;
	}
}

?>