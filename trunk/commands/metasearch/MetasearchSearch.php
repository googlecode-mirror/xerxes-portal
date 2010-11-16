<?php

/**
 *  Initiaties the search with Metalib
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_MetasearchSearch extends Xerxes_Command_Metasearch
{
	public function doExecute()
	{
		// metalib search object
		
		$objSearch = $this->getSearchObject();
		
		// params from the request

		$strQuery = $this->request->getProperty( "query" );
		$strQuery2 = $this->request->getProperty( "query2" );
		$strField = $this->request->getProperty( "field" );
		$strField2 = $this->request->getProperty( "field2" );
		$strFindOperator = $this->request->getProperty( "find_operator1" );
    
		$arrDatabases = $this->request->getProperty( "database", true );
		$strSubject = $this->request->getProperty( "subject" );
		$strSpell = $this->request->getProperty( "spell" );
		$strContext = $this->request->getProperty( "context" );
		$strContextUrl = $this->request->getProperty( "context_url" );
		
		// configuration options
		
		$configNormalize = $this->registry->getConfig( "NORMALIZE_QUERY", false, false );
		$configBaseUrl = $this->registry->getConfig( "BASE_URL", true );
		$configYahooID = $this->registry->getConfig( "YAHOO_ID", false, "calstate" );
		$configSearchLimit = $this->registry->getConfig( "SEARCH_LIMIT", true );
		$configContextUrl = $this->registry->getConfig( "LIMIT_CONTEXT_URL", false );
		
		
		//  if so configured, ensure that context_url is limited to certain domain(s)
		
		if ( $configContextUrl != null )
		{
			$bolPassed = Xerxes_Framework_Parser::withinDomain($strContextUrl,$configContextUrl);
			
			if ( $bolPassed == false )
			{
				throw new Exception("context_url only allowed for specified domains");	
			}
		}
		
		// database communications object
		
		$objData = new Xerxes_DataMap();
		
		// if subject is given but not databases, automatically find
		// databases for subject, from first sub-category.
		
		if ( $strSubject != null && count($arrDatabases) == 0 )
		{
			$search_limit = $this->registry->getConfig( "SEARCH_LIMIT", true );
			
			$arrDatabases = array ( );
        
			$objSubject = $objData->getSubject( $strSubject, null, "metalib", null, $this->request->getProperty("lang"));
			
			// did we find a subject that has subcategories?
			
			if ( $objSubject != null && $objSubject->subcategories != null && count( $objSubject->subcategories ) > 0 )
			{
				$subs = $objSubject->subcategories;
				$objSubcategory = $subs[0];
				$index = 0;
				
				// get databases up to search limit from first subcat,
				// add to $arrdatabases. 
				
				foreach ( $objSubcategory->databases as $objDatabaseData )
				{
					if ( $objDatabaseData->searchable == 1 )
					{
						array_push( $arrDatabases, $objDatabaseData->metalib_id );
						$index ++;
					}
					if ( $index >= $search_limit )
					{
						break;
					}
				}
			}
		}
			
		// if we have a subject, but no context/contexturl, look
		// them up for the subject. Allows convenient defaults
		// for direct-linking into search results. 
		
		if ( $strContext == "" && $strSubject != "" )
		{
			// look up the subject if we haven't already, to get the name.
			
			if ( ! isset( $objSubject ) )
			{
				$objSubject = $objData->getSubject( $strSubject );
			}
			
			$strContext = $objSubject->name;
		}
		
		if ( $strContextUrl == "" && $strSubject != "" )
		{
			$strContextUrl = $this->request->url_for( array ("base" => "databases", "action" => "subject", "subject" => $strSubject ) );
		}
		
		// ensure a query and field

		if ( $strQuery == "" )
		{
			throw new Exception( "Please enter search terms" );
		}
		if ( $strField == "" )
		{
			$strField = "WRD";
		}
		if ( $strField2 == "" )
		{
			$strField2 = "WRD";
		}
		if ( $strFindOperator == "" )
		{
			$strFindOperator = "AND";
		}
			
		// get databases
		
		$arrDB = $objData->getDatabases( $arrDatabases );
		
		// start out database information xml object. 
		
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<search />" );
		
		// access control for databases
		
		$excludedDbs = array ( );
		$excludedIDs = array ( );
		
		foreach ( $arrDB as $db )
		{
			if ( ! Xerxes_Helper::dbSearchableForUser( $db, $this->request, $this->registry ) )
			{
				$excludedDbs[] = $db;
				$excludedIDs[] = ( string ) $db->metalib_id;
			}
		}
		if ( count( $excludedDbs ) > 0 )
		{
			// remove excluded dbs from our db lists. what a pain in php, sorry. 
			      
			foreach ( $arrDB as $key => $db )
			{
				if ( in_array( ( string ) $db->metalib_id, $excludedIDs ) )
				{
					unset( $arrDB[$key] );
				}
			}
			foreach ( $arrDatabases as $key => $id )
			{
				if ( in_array( $id, $excludedIDs ) )
				{
					unset( $arrDatabases[$key] );
				}
			}
			
			// and make a note of the excluded dbs please.
			
			$excluded_xml = $objXml->createElement( "excluded_dbs" );
			$objXml->documentElement->appendChild( $excluded_xml );
			
			foreach ( $excludedDbs as $db )
			{
				$element = Xerxes_Helper::databaseToNodeset( $db, $this->request, $this->registry );
				$element = $objXml->importNode( $element, true );
				$excluded_xml->appendChild( $element );
			}
		}
		
		// ensure correct number of databases selected
		
		if ( count( $arrDatabases ) < 1 && count( $excludedDbs ) > 0 )
		{
			$e = new Xerxes_Exception_DatabasesDenied( "You are not authorized to search the databases you selected. Please choose other databases and try again." );
			$e->setDeniedDatabases( $excludedDbs );
			throw $e;
		} 
		elseif ( count( $arrDatabases ) < 1 )
		{
			throw new Exception( "Please choose one or more databases to search" );
		}
		
		if ( count( $arrDatabases ) > $configSearchLimit )
		{
			throw new Exception( "You can only search up to $configSearchLimit databases at a time" );
		}
		
		$strSpellCorrect = ""; // spelling correction
		$strSpellUrl = ""; // return url for spelling change
		$strGroup = ""; // group id number
		$strNormalizedQuery = ""; // normalized query

		// query parser provides normalization and spell check

		$objQueryParser = new Xerxes_QueryParser( );
		
		// normalize query option is still experimental (2009-04-16)
			
		$strFullQuery =	$objQueryParser->normalizeMetalibQuery($strField, $strQuery, $strFindOperator, $strField2, $strQuery2, $configNormalize);
		
		// initiate search with Metalib
		
		$strGroup = $objSearch->search( $strFullQuery, $arrDatabases );
		
		// something went wrong, yo!
		
		if ( $strGroup == "" )
		{
			throw new Exception( "Could not initiate search with Metalib server" );
		}
			
		// check spelling unless this is a return submission from a previous spell correction
		
		$strSpellSuggestions = null;
		
		if ( $strSpell == null )
		{
			// check spelling
			
			$strAltYahoo = $this->registry->getConfig("ALTERNATE_YAHOO_LOCATION", false);

			$strSpellCorrect = $objQueryParser->checkSpelling( $strQuery, $configYahooID, $strAltYahoo );
			$strSpellCorrect2 = null;

			if ( $strQuery2 )
			{
				$strSpellCorrect2 = $objQueryParser->checkSpelling( $strQuery2, $configYahooID );
			}
			
			if ( $strSpellCorrect != "" || $strSpellCorrect2 != "" )
			{
				// construct spell check return url with spelling suggestion
				// If both search fields were used (advanced search), spell corrections
				// may be in first, second, or both. 
				
				$strNewQuery = $strQuery;
				$arrSuggestions = array ( );
				
				if ( $strSpellCorrect )
				{
					$strNewQuery = $strSpellCorrect;
					array_push( $arrSuggestions, $strSpellCorrect );
				}
				
				$strNewQuery2 = $strQuery2;
				
				if ( $strSpellCorrect2 )
				{
					$strNewQuery2 = $strSpellCorrect2;
					array_push( $arrSuggestions, $strSpellCorrect2 );
				}
				
				$strSpellSuggestions = join( " ", $arrSuggestions );
				
				$strSpellUrl = "./?base=metasearch&action=search&spell=1&query=" . urlencode( $strNewQuery ) . "&field=" . $strField;
				
				if ( $strNewQuery2 )
				{
					$strSpellUrl .= "&query2=" . urlencode( $strNewQuery2 ) . "&field2=" . $strField2;
				}
        
				$strSpellUrl .= "&context=" . urlencode( $strContext );
				$strSpellUrl .= "&context_url=" . urlencode( $strContextUrl );
				
				foreach ( $arrDatabases as $strDatabase )
				{
					if ( $strDatabase != null )
					{
						$strSpellUrl .= "&database=" . $strDatabase;
					}
				}
			}
		}
		
		// create search information xml			
		
		$arrSearch = array ( );
		$arrSearch["date"] = date( "Y-m-d" );
		
		$arrSearch["spelling"] = $strSpellSuggestions;
		$arrSearch["spelling_url"] = $strSpellUrl;
    
		$arrSearch["context"] = $strContext;
		$arrSearch["context_url"] = $strContextUrl;
		
		foreach ( $arrSearch as $key => $value )
		{
			$objElement = $objXml->createElement( $key, Xerxes_Framework_Parser::escapeXml( $value ) );
			$objXml->documentElement->appendChild( $objElement );
		}

		$objPair = $objXml->createElement( "pair" );
		$objPair->setAttribute( "position", 1 );
		$objXml->documentElement->appendChild( $objPair );
		
		$arrQuery = array ( );
		$arrQuery["query"] = $strQuery;
		$arrQuery["field"] = $strField;
		$arrQuery["normalized"] = $strFullQuery;
		
		foreach ( $arrQuery as $key => $value )
		{
			$objElement = $objXml->createElement( $key, Xerxes_Framework_Parser::escapeXml( $value ) );
			$objPair->appendChild( $objElement );
		}
			
		// add second pair if present.
		 
		if ( $strQuery2 )
		{
			$objOperator = $objXml->createElement( "operator", $strFindOperator );
			$objOperator->setAttribute( "position", 1 );
			$objXml->documentElement->appendChild( $objOperator );
			
			$objPair = $objXml->createElement( "pair" );
			$objPair->setAttribute( "position", 2 );
			$objXml->documentElement->appendChild( $objPair );
			
			$arrQuery = array ( );
			$arrQuery["query"] = $strQuery2;
			$arrQuery["field"] = $strField2;
			
			foreach ( $arrQuery as $key => $value )
			{
				$objElement = $objXml->createElement( $key, Xerxes_Framework_Parser::escapeXml( $value ) );
				$objPair->appendChild( $objElement );
			}
		}
		
		// get links from ird records for those databases that have been included in the search and 
		// store it here so we can get at this information easily on any subsequent page without having
		// to go back to the database
		
		$objDatabaseLinks = $objXml->createElement( "database_links" );
		$objXml->documentElement->appendChild( $objDatabaseLinks );
		
		foreach ( $arrDB as $objDatabase )
		{
			// create a database node and append to database_links

			$objNodeDatabase = Xerxes_Helper::databaseToLinksNodeset( $objDatabase, $this->request, $this->registry );
			$objNodeDatabase = $objXml->importNode( $objNodeDatabase, true );
			$objDatabaseLinks->appendChild( $objNodeDatabase );      
		}
		
		// add any warnings from metalib
		
		$objWarnings = $objSearch->getWarnings();
		
		if ( $objWarnings != null )
		{
			$objImport = $objXml->importNode( $objWarnings->documentElement, true );
			$objXml->documentElement->appendChild( $objImport );
		}
		
		$strGroup = $this->getSearchDate() . "-" . $strGroup;
		
		// save this information in the cache

		$this->setCache( $strGroup, "search", $objXml );
		
		// redirect to hits page
		
		$arrParams = array(
			"base" => "metasearch",
			"action" => "hits",
			"group" => $strGroup
		);
		
		$this->cache->save();

		$this->request->setRedirect($this->request->url_for($arrParams));
		return 1;
	}
}

?>
