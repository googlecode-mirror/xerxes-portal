<?php

/**
 * Static methods used by multiple commands, factored out here to keep
 * things clean.
 *
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
		$objDom = new DOMDocument( );
		$objDom->loadXML( "<database/>" );
		
		$objDatabase = $objDom->documentElement;
		
		// single value fields
		
		foreach ( $objDatabaseData->properties() as $key => $value )
		{
			if ( $value != null && $key != "description" )
			{
				$objElement = $objDom->createElement( $key, Xerxes_Parser::escapeXml( $value ) );
				$objDatabase->appendChild( $objElement );
				
				// sometimes we're asked to track and record index.
				

				if ( ! is_null( $index ) && $key == "searchable" && $value == "1" )
				{
					$objElement->setAttribute( "count", $index );
					$index ++;
				}
			}
		}
		
    // description we handle special for escaping setting. Note that we
    // handle html escpaing here in controller for description, view
    // should use disable-output-escaping="yes" on value-of of description.
    $escape_behavior = $objRegistry->getConfig("db_description_html", false, "escape"); // 'escape' ; 'allow' ; or 'strip'
    $note_field = $objDatabaseData->description;
    if ( $note_field != null ) {
      
      //strip out "##" chars, not just singular "#" to allow # in markup
      //or other places. 
      $note_field = str_replace('##', '', $note_field);
      
      if ( $escape_behavior == "strip" ) {          
        $allow_tag_list = $objRegistry->getConfig("db_description_allow_tags", false, '');
        $arr_allow_tags = split(',', $allow_tag_list);
        $param_allow_tags = '';
        foreach ( $arr_allow_tags as $tag ) {
          $param_allow_tags .= "<$tag>";
        }          
        $note_field = strip_tags($note_field, $param_allow_tags); 
      }
      
      if ( $escape_behavior == "escape" ) {
        $note_field = htmlspecialchars($note_field);   
      }
      $objElement = $objDom->createElement( "description", Xerxes_Parser::escapeXml($note_field) );
      $objDatabase->appendChild( $objElement );

    }

    
		// multi-value fields

		$arrMulti = array ("keywords", "languages", "notes", "alternate_titles", "alternate_publishers", "group_restrictions" );
		
		foreach ( $arrMulti as $multi )
		{
			foreach ( $objDatabaseData->$multi as $value )
			{
				// remove the trailing 's'
				

				$single = substr( $multi, 0, strlen( $multi ) - 1 );
				
				if ( $value != null )
				{
					$objElement = $objDom->createElement( $single, Xerxes_Parser::escapeXml( $value ) );
					
					//group restriction needs another attribute
					if ( $multi == "group_restrictions" )
					{
						$objElement->setAttribute( "display_name", $objRegistry->getGroupDisplayName( $value ) );
					}
					
					$objDatabase->appendChild( $objElement );
				}
			}
		}
		
		// is the particular user allowed to search this?
		

		$objElement = $objDom->createElement( "searchable_by_user", self::dbSearchableForUser( $objDatabaseData, $objRequest, $objRegistry ) );
		$objDatabase->appendChild( $objElement );
		
		//add an element for url to xerxes detail page for this db

		$objElement = $objDom->createElement( "url", $objRequest->url_for( array ("base" => "databases", "action" => "database", "id" => htmlentities( $objDatabaseData->metalib_id ) ) ) );
		$objDatabase->appendChild( $objElement );
    
    // The 'add to personal collection' url for logged in user, if there
    // is a logged in user. 
    if ( Xerxes_Framework_Restrict::isAuthenticatedUser($objRequest) ) {
      $objElement = $objDom->createElement( "add_to_collection_url", $objRequest->url_for( array ("base" => "collections", "action" => "save_choose_collection", "id" => htmlentities( $objDatabaseData->metalib_id), "username" => $objRequest->getSession("username") ) ) );
      $objDatabase->appendChild( $objElement );
    }
		
		//add an element for url to xerxes-mediated direct link to db. 
		

		$objElement = $objDom->createElement( "xerxes_native_link_url", $objRequest->url_for( array ("base" => "databases", "action" => "proxy", "database" => htmlentities( $objDatabaseData->metalib_id ) ) ) );
		$objDatabase->appendChild( $objElement );
		
		return $objDatabase;
	}
	
  /* Ensures that specified user is logged in, or throws exception */
  public static function ensureSpecifiedUser($username, $objRequest, $objRegistry, $strMessage = "Access only allowed by specific user.") {
    if (! $objRequest->getSession("username") == $username) {
      throw new Xerxes_AccessDeniedException($strMessage);
    }
  }
  
	/**
	 * Checks to see if any of the databases currently being searched are restricted
	 * to the user, throws Xerxes_DatabasesDeniedException if one is not
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
			$e = new Xerxes_DatabasesDeniedException( );
			$e->setDeniedDatabases( $deniedList );
			throw $e;
		} else
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
		
		if ( ! $db->searchable )
		{
			//nobody can search it!
			$allowed = false;
		} 
		elseif ( $db->guest_access )
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
				foreach ( $db->group_restrictions as $group )
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
		$key = self::savedRecordKey( $strResultSet, $strRecordNumber );
		
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
}
?>
