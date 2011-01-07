<?php

/**
 * Constructs links for navigation elements
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_HelperNavbar extends Xerxes_Command_Helper
{
	public function doExecute()
	{
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<navbar />" );
		
		
		
		### saved records link
		
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
		
		
		
		
		### my collections (i.e., databases)
		
		$arrCollectionUrl = array ("base" => "collections", "action" => "list" );
		
		if ( Xerxes_Framework_Restrict::isAuthenticatedUser( $this->request ) )
		{
			$arrCollectionUrl["username"] = $this->request->getSession( "username" );
		}
		
		$this->addNavbarElement( $objXml, "saved_collections", $arrCollectionUrl );
		
		
		
		
		### authentication
		
		// tell it to force an https url if so configured. 
		
		$force_secure_login = false;
		
		if ( $this->registry->getConfig( "secure_login", false ) == "true" )
		{
			$force_secure_login = true;
		}

		// login
		
		$this->addNavbarElement( $objXml, "login", array ("base" => "authenticate", "action" => "login", "return" => $this->request->getServer( "REQUEST_URI" ) ), $force_secure_login );
		
		// logout
		
		$this->addNavbarElement( $objXml, "logout", array ("base" => "authenticate", "action" => "logout", "return" => $this->request->getServer( "REQUEST_URI" ) ) );
		
		
		
		
		### db alphabetical list
		
		$this->addNavbarElement( $objXml, "database_list", array ("base" => "databases", "action" => "alphabetical" ) );
		
		
		
		### languages
		
		$languages = $this->registry->getConfig("LANGUAGES", false);
		
		// map locales to language codes
		foreach ($languages as $language)
		{
			$order = NULL;
			$code  = NULL;
			foreach ($language->attributes() as $name => $val)
			{
				if ($name == "code")
					$code = (string) $val;
				if ($name == "locale") {
					$locale = (string) $val;
					if ($locale == '')
						$locale = 'C';
				}
			}
			$locales[$code] = $locale;
		}
		
		if ( $languages != null )
		{
			$languages_xml = $objXml->createElement("languages");
			$objXml->documentElement->appendChild($languages_xml);
			
			$language_names = Xerxes_Framework_Languages::getInstance();
			
			foreach ( $languages->language as $language )
			{
				$code = (string) $language["code"];
				$readable_name = $language_names->getNameFromCode("iso_639_2B_code", $code, $locales[$code]);
				$native_name   = $language_names->getNameFromCode("iso_639_2B_code", $code);
				
				$language_node = $objXml->createElement("language");
				$languages_xml->appendChild($language_node);
				
				$language_node->setAttribute("code", $code);
				$language_node->setAttribute("name", $readable_name);
				$language_node->setAttribute("native_name", $native_name);
				$language_node->setAttribute("locale", $locales[$code]);
				
				// link back to home page
				
				$current_params = $this->request->getURIProperties(); // this page
				
				// this is necessary on the home page
				
				if ( ! array_key_exists("base", $current_params) )
				{
					$current_params["base"] = "";
				}
				
				// subject pages can't support a swap, so send user back to home page
				
				if ( ($current_params["base"] == "databases" || $current_params["base"] == "embed") && 
					array_key_exists("subject", $current_params) )
				{
					$current_params = array();
					$current_params["base"] = "";
				}
				
				// add the languages
				
				$current_params["lang"] = $code; // with language set
				
				$url = $this->request->url_for($current_params);
				
				$language_node->setAttribute("url", $url);
			}
		}

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
