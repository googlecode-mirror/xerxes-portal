<?php

/**
 * Authenticate a guest user
 *
 */

class Xerxes_Command_AuthenticateGuest extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		$strReturn = $this->request->getProperty( "return" );
		
		if ( $strReturn == "" )
		{
			$strReturn = $this->registry->getConfig( "base_web_path" );
		}
		
		$this->register( "guest@" . session_id(), "guest" );
		$this->request->setRedirect( "http://" . $this->request->getServer( 'SERVER_NAME' ) . $strReturn );
		
		return 1;
	}
}

?>