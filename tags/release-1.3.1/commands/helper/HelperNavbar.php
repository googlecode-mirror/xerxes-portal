<?php

class Xerxes_Command_HelperNavbar extends Xerxes_Command_Helper
{
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<navbar />" );
		
		// saved records link
		
		$arrLink = array ("base" => "folder");
		
		// make sure there is no return if coming from login to prevent a spider
		// from thinking this is a different page
		
		if ( $objRequest->getProperty("base") != "authenticate")
		{
			$arrLink["return"] = $objRequest->getServer( "REQUEST_URI" );
		}
		
		$this->addNavbarElement( $objXml, $objRequest, "saved_records", $arrLink );
		
		// login or logout, just include appropriate one. 
		$auth_action_params = array ("base" => "authenticate", "return" => $objRequest->getServer( "REQUEST_URI" ) );
		
		$element_id = "";

		if ( $objRequest->hasLoggedInUser() || $objRequest->getSession( "role" ) == "guest" )
		{
			$element_id = "logout";
			$auth_action_params["action"] = "logout";
		} 
		else
		{
			$element_id = "login";
			$auth_action_params["action"] = "login";
		}
		
		// login or logout
		$this->addNavbarElement( $objXml, $objRequest, $element_id, $auth_action_params );
		
		// db alphbetical list
		$this->addNavbarElement( $objXml, $objRequest, "database_list", array ("base" => "databases", "action" => "alphabetical" ) );
		
		$objRequest->addDocument( $objXml );
		
		return 1;
	}
	
	protected function addNavbarElement($objXml, $objRequest, $element_id, $url_params)
	{
		$element = $objXml->createElement( "element" );
		$element->setAttribute( "id", $element_id );
		$url = $objXml->createElement( 'url', $objRequest->url_for( $url_params ) );
		$element->appendChild( $url );
		$objXml->documentElement->appendChild( $element );
	}

}
?>
