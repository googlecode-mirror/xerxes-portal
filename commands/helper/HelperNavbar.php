<?php

class Xerxes_Command_HelperNavbar extends Xerxes_Command_Helper
{
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<navbar />" );
		
		// saved records link
		$this->addNavbarElement( $objXml, $objRequest, "saved_records", array ("base" => "folder", "return" => $objRequest->getServer( "REQUEST_URI" ) ) );
		
    
    //loging
    $this->addNavbarElement( $objXml, $objRequest, "login", array ("base" => "authenticate", "action" => "login", "return" => $objRequest->getServer( "REQUEST_URI" ) ) );
		
    //logout
    $this->addNavbarElement( $objXml, $objRequest, "logout", array ("base" => "authenticate", "action" => "logout", "return" => $objRequest->getServer( "REQUEST_URI" ) ) );
		
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
