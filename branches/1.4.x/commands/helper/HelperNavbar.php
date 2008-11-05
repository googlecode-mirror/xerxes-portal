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
		
		 $savedRecordsLink = $this->addNavbarElement( $objXml, $objRequest, "saved_records", $arrLink );
		
		// add numSavedRecords  and sessionSavedRecords for proper icon display
		
		$objData = new Xerxes_DataMap( );
		$num = $objData->totalRecords( $objRequest->getSession( "username" ) );
		$savedRecordsLink->setAttribute( "numSavedRecords", ( string ) $num );
		$savedRecordsLink->setAttribute( "numSessionSavedRecords", Xerxes_Helper::numMarkedSaved() );
		
		//login
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
		return $element;
	}

}
?>
