<?php

/**
 * Constructs links for 'my stuff' navigation elements
 *
 */

class Xerxes_Command_HelperNavbar extends Xerxes_Command_Helper
{
	public function doExecute()
	{
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<navbar />" );
		
		// saved records link
		
		$arrLink = array ("base" => "folder" );
		
		// make sure there is no return if coming from login to prevent a spider
		// from thinking this is a different page

		if ( $this->request->getProperty( "base" ) != "authenticate" )
		{
			$arrLink["return"] = $this->request->getServer( "REQUEST_URI" );
		}
		
		$savedRecordsLink = $this->addNavbarElement( $objXml, "saved_records", $arrLink );
		
		// add numSavedRecords  and sessionSavedRecords for proper icon display
		
		$objData = new Xerxes_DataMap( );
		$num = $objData->totalRecords( $this->request->getSession( "username" ) );
		$savedRecordsLink->setAttribute( "numSavedRecords", ( string ) $num );
		$savedRecordsLink->setAttribute( "numSessionSavedRecords", Xerxes_Helper::numMarkedSaved() );
		
		// my collections (i.e., databases)
		
		$arrCollectionUrl = array ("base" => "collections", "action" => "list" );
		
		if ( Xerxes_Framework_Restrict::isAuthenticatedUser( $this->request ) )
		{
			$arrCollectionUrl["username"] = $this->request->getSession( "username" );
		}
		
		$this->addNavbarElement( $objXml, "saved_collections", $arrCollectionUrl );
		
		// login, tell it to force an https url if so configured. 
		
		$force_secure_login = false;
		
		if ( $this->registry->getConfig( "secure_login", false ) == "true" )
		{
			$force_secure_login = true;
		}
		
		$this->addNavbarElement( $objXml, "login", array ("base" => "authenticate", "action" => "login", "return" => $this->request->getServer( "REQUEST_URI" ) ), $force_secure_login );
		
		// logout
		
		$this->addNavbarElement( $objXml, "logout", array ("base" => "authenticate", "action" => "logout", "return" => $this->request->getServer( "REQUEST_URI" ) ) );
		
		// db alphbetical list
		
		$this->addNavbarElement( $objXml, "database_list", array ("base" => "databases", "action" => "alphabetical" ) );
		
		$this->request->addDocument( $objXml );
		
		return 1;
	}
	
	protected function addNavbarElement($objXml, $element_id, $url_params, $force_secure = false)
	{
		$element = $objXml->createElement( "element" );
		$element->setAttribute( "id", $element_id );
		$url = $objXml->createElement( 'url', $this->request->url_for( $url_params, false, $force_secure ) );
		$element->appendChild( $url );
		$objXml->documentElement->appendChild( $element );
		return $element;
	}

}
?>
