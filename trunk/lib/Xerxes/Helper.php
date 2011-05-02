<?php

/**
 * Static methods used by multiple commands, factored out here to keep
 * things clean-ish
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Helper
{
	/**
	 *  Take a Xerxes data object representing a database, output
	 *  a DOMDocument nodeset representing that database, for including
	 *  in an XML response. Used by some Databases controllers. 
	 *
	 *  To actually include the returned value, you will need to import it into
	 *  your DOMDocument of choice first. Example:
	 *        $objDatabase = self::databaseToNodeset($objDatabaseData, $objRequest);
	 *        $objDatabase = $objXml->importNode( $objDatabase, true );
	 *        $objXml->documentElement->appendChild($objDatabase);
	 *
	 *
	 * @param Xerxes_Data_Database $objDatabaseData
	 * @param Xerxes_Framework_Request $objRequest  need the Xerxes request object to create urls for us. 
	 * @param Xerxes_Framework_Registry $objRegistry need a registry object too, sorry. 
	 * @param &$index = null  sometimes we want to append a count index to the xml. Pass in a counter variable, and it will be included AND incremented (passed by reference).
	 * @return DOMNode
	 */
	
	public static function databaseToNodeset(Xerxes_Data_Database $objDatabaseData, Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry, &$index = null)
	{
		$xml = $objDatabaseData->xml;
		
		//PHP 5.1.6 simplexml bug, 'for' iteration over ->searchable will create
		//it already if it doesn't exist, which we don't want, so
		//we have to wrap 'for' in 'if'. 
    if (count($xml->searchable)) {		
      foreach ( $xml->searchable as $searchable )
      {
        // sometimes we're asked to track and record index.
  
        if ( (string) $searchable == "1" )
        {
          $searchable["count"] = $index;
          $index++;
        }
      }
    }
		// display name for group restrictions
		// bug in PGP 5.1.6 SimpleXML, if we do the foreach WITHOUT wrapping
		// it in this if testing count first, 
		// it'll add on an empty <group_restriction/> to the xml
		// graph even if none were there previously. That's bad. 		
		if (count($xml->group_restriction) > 0) {
      foreach ( $xml->group_restriction as $group_restriction )
      {
        $group_restriction->addAttribute( "display_name", $objRegistry->getGroupDisplayName( (string) $group_restriction) );
      }
    }
		
		$multilingual = $objRegistry->getConfig ( "db_description_multilingual", false, "" ); // XML object
		
		$lang = $objRequest->getProperty("lang");
		
		if ($lang == "")
			$lang = "eng";
		
		// build a list of configured description languages
		
		if ( $multilingual != "" )
		{
			$order = 0;
			foreach ($multilingual->language as $language)
			{
				$order++;
				$code  = NULL;
				foreach ($language->attributes() as $name => $val)
				{
					if ($name == "code")
						$code = (string) $val;
				}
				$db_languages_order[$code] = $order;
				$db_languages_code[$order] = $code;
			}
		}
			
		$notes = array ("description", "search_hints");
		
		foreach ( $notes as $note_field_name )
		{
			$node_queue = array();	// nodes to add when done looping to prevent looping over nodes added inside the loop
			
			foreach ($xml->$note_field_name as $note_field_xml )
			{
				$note_field = (string) $note_field_xml;
				
				// strip out "##" chars, not just singular "#" to allow # in markup
				// or other places. 
				
				$pos = strpos( $note_field, '######' );
				if ($multilingual == false or $pos === false)
				{
					$note_field = str_replace ( '######', '\n\n\n', $note_field );
				}
				else
				{
					$descriptions = explode( '######', $note_field );
					$i = 1;
					foreach ($descriptions as $description) {
						$description = self::embedNoteField($description);
						$node_queue[] = array (
							'note_field_name' => $note_field_name,
							'description' => $description,
							'code' => $db_languages_code[$i++]
						);
					}
				}
				
				$note_field = self::embedNoteField($note_field);
				
				$xml->$note_field_name = $note_field;
				$xml->$note_field_name->addAttribute('lang', 'ALL');
			}

			foreach ($node_queue as $node)
			{
				$descNode = $xml->addChild($node['note_field_name'], $node['description']);
				$descNode->addAttribute('lang', $node['code']);
			}
		}
		
		$objDom = new DOMDocument();
		$objDom->loadXML($xml->asXML());
		$objDatabase = $objDom->documentElement;
		
		$objDatabase->setAttribute ( "metalib_id", $objDatabaseData->metalib_id );
    
		// is the particular user allowed to search this?

		$objElement = $objDom->createElement( "searchable_by_user", self::dbSearchableForUser( $objDatabaseData, $objRequest, $objRegistry ) );
		$objDatabase->appendChild( $objElement );
		
		//add an element for url to xerxes detail page for this db

		$objElement = $objDom->createElement( "url", $objRequest->url_for( array ("base" => "databases", "action" => "database", "id" => htmlentities( $objDatabaseData->metalib_id ) ) ) );
		$objDatabase->appendChild( $objElement );
			
		// The 'add to personal collection' url for logged in user--if no
		// logged in user, generate link anyway, but it's going to have
		// an empty user. User should be required to log in before continuing
		// with action. 
		
		$url = $objRequest->url_for( array ("base" => "collections", "action" => "save_choose_collection", "id" => $objDatabaseData->metalib_id, "username" => $objRequest->getSession( "username" ), "return" => $objRequest->getServer( 'REQUEST_URI' ) ) );
		
		$objElement = $objDom->createElement( "add_to_collection_url", $url );
		$objDatabase->appendChild( $objElement );
		
		//add an element for url to xerxes-mediated direct link to db. 

		$objElement = $objDom->createElement( "xerxes_native_link_url", $objRequest->url_for( array("base" => "databases", "action" => "proxy", "database" => htmlentities( $objDatabaseData->metalib_id ) ) ) );
		$objDatabase->appendChild( $objElement );
		
		return $objDatabase;
	}
		
	public static function databaseToLinksNodeset($objDatabase, $objRequest, $objRegistry)
	{
		return self::databaseToNodeset($objDatabase, $objRequest, $objRegistry);
	}
		
	/* Ensures that specified user is logged in, or throws exception */
	
	public static function ensureSpecifiedUser($username, $objRequest, $objRegistry, $strMessage = "Access only allowed by specific user.")
	{
		if (! ($objRequest->getSession ( "username" ) == $username))
		{
			throw new Xerxes_Exception_AccessDenied ( $strMessage );
		}
	}
  
	/**
	 * Checks to see if any of the databases currently being searched are restricted
	 * to the user, throws Xerxes_Exception_DatabasesDenied if one is not
	 *
	 * @param array $dbList							list of Xerxes_Data_Database objects
	 * @param Xerxes_Framework_Request $objRequest	Xerxes request object
	 * @param Xerxes_Framework_Registry $objRegistry Xerxes registry object
	 * @return bool 
	 */
	
	public static function checkDbListSearchableByUser($objSearchXml, $objRequest, $objRegistry)
	{
		$search_xml = simplexml_import_dom( $objSearchXml->documentElement );
		
		$deniedList = array ( );
		
		foreach ( $search_xml->xpath( "//database_links/database" ) as $database )
		{
			// extract the database info held in the cache and convert it here
			// to a data value object so we can feed it to dbSearchableForUser()
			// which is expecting that
			

			$db = new Xerxes_Data_Database( );
			$db->title_display = ( string ) $database->title_display;
			$db->searchable = ( string ) $database->searchable;
			$db->guest_access = ( string ) $database->guest_access;
			
			if ( count( $database->group_restrictions ) > 0 )
			{
				foreach ( $database->group_restrictions->group_restriction as $restriction )
				{
					array_push( $db->group_restrictions, ( string ) $restriction );
				}
			}
			
			// now check to see if it is allowed to be searched
			
			if ( ! self::dbSearchableForUser( $db, $objRequest, $objRegistry ) )
			{
				$deniedList[] = $db;
			}
		}
		
		// if any one of them doesn't match, kill this thing!

		if ( count( $deniedList ) > 0 )
		{
			$e = new Xerxes_Exception_DatabasesDenied( );
			$e->setDeniedDatabases( $deniedList );
			throw $e;
		} 
		else
		{
			return true;
		}
	}
	
	/**
	 * Determines if the database is searchable by user
	 *
	 * @param Xerxes_Data_Database $db
	 * @param Xerxes_Framework_Request $objRequest	Xerxes request object
	 * @param Xerxes_Framework_Registry $objRegistry Xerxes registry object
	 * @return unknown
	 */
	
	public static function dbSearchableForUser(Xerxes_Data_Database $db, $objRequest, $objRegistry)
	{
		$allowed = "";
		
		if ( $db->searchable != 1)
		{
			//nobody can search it!
			$allowed = false;
		} 
		elseif ( $db->guest_access != "")
		{
			//anyone can search it!
			$allowed = true;
		} 
		elseif ( count( $db->group_restrictions ) > 0 )
		{
			// they have to be authenticated, and in a group that is included
			// in the restrictions, or in an ip address associated with a
			// restricted group.

			$allowed = (Xerxes_Framework_Restrict::isAuthenticatedUser( $objRequest ) && array_intersect( $_SESSION["user_groups"], $db->group_restrictions ));
			
			if ( ! $allowed )
			{
				// not by virtue of a login, but now check for ip address

				$ranges = array ( );
				
				foreach ( $db->get("group_restrictions") as $group )
				{
					$ranges[] = $objRegistry->getGroupLocalIpRanges( $group );
				}
				
				$allowed = Xerxes_Framework_Restrict::isIpAddrInRanges( $objRequest->getServer( 'REMOTE_ADDR' ), implode( ",", $ranges ) );
			}
		} 
		else
		{
			// ordinary generally restricted resource.  they need to be 
			// an authenticated user, or in the local ip range. 

			if ( Xerxes_Framework_Restrict::isAuthenticatedUser( $objRequest ) || Xerxes_Framework_Restrict::isIpAddrInRanges( $objRequest->getServer( 'REMOTE_ADDR' ), $objRegistry->getConfig( "LOCAL_IP_RANGE" ) ) )
			{
				$allowed = true;
			}
		}
		
		return $allowed;
	}
	
	// Functions for saving saved record state from a result set in session
	// This is used for knowing whether to add or delete on a 'toggle' command
	// (MetasearchSaveDelete), and also used for knowing whether to display
	// a result line as saved or not. 
	
	public static function markSaved($objRecord)
	{
		$key = self::savedRecordKey( $objRecord->getResultSet(), $objRecord->getRecordNumber() );
		$_SESSION['resultsSaved'][$key]['xerxes_record_id'] = $objRecord->id;
	}
	
	public static function unmarkSaved($strResultSet, $strRecordNumber)
	{
		if ( $strResultSet == "" )
		{
			$key = $strRecordNumber;
		}
		else
		{
			$key = self::savedRecordKey( $strResultSet, $strRecordNumber );
		}
		
		if ( array_key_exists( "resultsSaved", $_SESSION ) && array_key_exists( $key, $_SESSION["resultsSaved"] ) )
		{
			unset( $_SESSION['resultsSaved'][$key] );
		}
	}
	
	public static function isMarkedSaved($strResultSet, $strRecordNumber)
	{
		$key = self::savedRecordKey( $strResultSet, $strRecordNumber );
		return (array_key_exists( "resultsSaved", $_SESSION ) && array_key_exists( $key, $_SESSION["resultsSaved"] ));
	}
	
	public static function numMarkedSaved()
	{
		$num = 0;
		if ( array_key_exists( "resultsSaved", $_SESSION ) )
		{
			$num = count( $_SESSION["resultsSaved"] );
		}
		return $num;
	}
	
	public static function savedRecordKey($strResultSet, $strRecordNumber)
	{
		// key based on result set and record number in search results. Save id
		// of saved xerxes_record. 
		
		$key = $strResultSet . ":" . $strRecordNumber;
		
		return $key;
	}
	
	public static function embedNoteField($note_field)
	{
		// description we handle special for escaping setting. Note that we
		// handle html escpaing here in controller for description, view
		// should use disable-output-escaping="yes" on value-of of description.

		$objRegistry = Xerxes_Framework_Registry::getInstance();
		$escape_behavior = $objRegistry->getConfig ( "db_description_html", false, "escape" ); // 'escape' ; 'allow' ; or 'strip'
		
		
		$note_field = str_replace ( '##', ' ', $note_field );
		
		if ($escape_behavior == "strip")
		{
			$allow_tag_list = $objRegistry->getConfig ( "db_description_allow_tags", false, '' );
			$arr_allow_tags = explode( ',', $allow_tag_list );
			$param_allow_tags = '';
			
			foreach ( $arr_allow_tags as $tag )
			{
				$param_allow_tags .= "<$tag>";
			}
			
			$note_field = strip_tags ( $note_field, $param_allow_tags );
		}
		
		if ($escape_behavior == "escape")
		{
			$note_field = htmlspecialchars ( $note_field );
		}
		
		return $note_field;
	}
}
?>