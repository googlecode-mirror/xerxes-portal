<?php

/**
 * Login action
 */

class Xerxes_Command_AuthenticateLogin extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		// values from the request and configuration

		$strPostBack = $this->request->getProperty( "postback" );
		$configHttps = $this->registry->getConfig( "SECURE_LOGIN", false, false );
    
		// if secure login is required, then force the user back thru https

		if ( $configHttps == true && $this->request->getServer( "HTTPS" ) == null )
		{
			$web = $this->registry->getConfig( "SERVER_URL" );
			$web = str_replace("http://", "https://", $web);
			
			$this->request->setRedirect( $web . $_SERVER['REQUEST_URI'] );
			return 1;
		}
		
		### remote authentication
		
		$bolStop = $this->authentication->onLogin();
		
		if ( $bolStop == true )
		{
			return 1;
		}
		
		### local authentication

		// if this is not a 'postback', then the user has not submitted the form, they are arriving 
		// for first time so stop the flow and just show the login page with form

		if ( $strPostBack == null ) return 1;
		
		$bolAuth = $this->authentication->onCallBack();
			
		if ( $bolAuth == false )
		{
			// failed the login, so present a message to the user

			$objXml = new DOMDocument( );
			$objXml->loadXML( "<error />" );
			
			$objFail = $objXml->createElement( "message", "authentication" );
			$objXml->documentElement->appendChild( $objFail );
			
			$this->request->addDocument( $objXml );
		}
		
		return 1;
	
	}
}

?>