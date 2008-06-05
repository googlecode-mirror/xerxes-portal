<?php	
	
	/**
	 *  Initiaties the search with Metalib
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchSearch extends Xerxes_Command_Metasearch
	{
		/**
		 * Initiate the search with metalib, spell check the query, grab any full-text links
		 * from chosen IRD records for potential linking in the results, and save all of that 
		 * in the cache; Request object should include params for: 'query' the user's search terms;
		 * 'field' the fied to search on; 'database' multiple database chosen to search; optionally
		 * 'spell' a flag indicating the request is coming back from a spell suggestion; 'context'
		 * the subject from which the user initiated the search; 'context_url' the url of that subject
		 * page.  Redirects the user to the search status (hits) page.
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// metalib search object
			
			$objSearch = $this->getSearchObject($objRequest, $objRegistry);
			
			// params from the request
			
		 	$strQuery = $objRequest->getProperty("query");
		 	$strField = $objRequest->getProperty("field");
		 	$arrDatabases = $objRequest->getProperty("database", true);
      $strSubject = $objRequest->getProperty("subject");     
		 	$strSpell = $objRequest->getProperty("spell");
		 	$strContext = $objRequest->getProperty("context");
		 	$strContextUrl = $objRequest->getProperty("context_url");

		 	// configuration options
		 	
		 	$configNormalize = $objRegistry->getConfig("NORMALIZE_QUERY", false, false);
		 	$configBaseUrl = $objRegistry->getConfig("BASE_URL", true);
		 	$configYahooID = $objRegistry->getConfig("YAHOO_ID", false, "calstate" );
		 	$configSearchLimit = $objRegistry->getConfig("SEARCH_LIMIT", true);
		 	
      // If subject is given but not databases, automatically find
      // databases for subject, from first sub-category. 
      if ( $strSubject && ! $arrDatabases ) {                  
        $search_limit = $objRegistry->getConfig("SEARCH_LIMIT", true);
        
        $arrDatabases = array();
        $objData = new Xerxes_DataMap();
        $objSubject = $objData->getSubject($strSubject);
        
        // Did we find a subject that has subcategories?
        if ( $objSubject != null && $objSubject->subcategories != null && count($objSubject->subcategories) > 0) {
          $subs = $objSubject->subcategories;
          $objSubcategory = $subs[0];
          $index = 0;
          // Get databases up to search limit from first subcat,
          // add to $arrDatabases. 
          foreach ( $objSubcategory->databases as $objDatabaseData ) {
            if ($objDatabaseData->searchable == 1 ) {
              array_push( $arrDatabases, $objDatabaseData->metalib_id );           
              $index++;
            }
            if ($index >= $search_limit) { 
              break; 
            }
          }
        }        
      }
		 	
		 	// ensure a query and field
		 	
		 	if ( $strQuery == "" ) throw new Exception("Please enter search terms");
		 	if ( $strField == "" ) $strField = "WRD";
		 	
      
      // Load datamap, we need it now, and later. 
      $objDataMap = new Xerxes_DataMap();			
			$arrDB = $objDataMap->getDatabases($arrDatabases);
      
      // Start out database information XML object. 
      $objXml = new DOMDocument();
			$objXml->loadXML("<search />");
      
      // access control for databases
      
      $excludedDbs = array();
      $excludedIDs = array();
      foreach ( $arrDB as $db) {
        if (! Xerxes_Framework_Restrict::dbSearchableForUser($db, $objRequest, $objRegistry)) {
           $excludedDbs[] = $db;
           $excludedIDs[] = (string) $db->metalib_id;
        }        
      }
      if ( count($excludedDbs) > 0) {
        // remove excluded dbs from our db lists. What a pain in php, sorry.        
        foreach ( $arrDB as $key => $db) {
          if ( in_array((string) $db->metalib_id, $excludedIDs)) {
            unset($arrDB[$key]);
          }
        }
        foreach ( $arrDatabases as $key => $id) {
          if ( in_array($id, $excludedIDs)) {
            unset($arrDatabases[$key]); 
          }
        }
                
        // and make a note of the excluded dbs please. 
        $excluded_xml = $objXml->createElement("excluded_dbs");
        $objXml->documentElement->appendChild($excluded_xml);
        foreach ( $excludedDbs as $db ) {
          $element = Xerxes_Helper::databaseToNodeset($db, $objRequest, $objRegistry);
          $element = $objXml->importNode( $element, true );
          $excluded_xml->appendChild( $element );
        }
      }        
		 	// ensure correct number of databases selected
		 	
		 	if ( count($arrDatabases) < 1 && count($excludedDbs) > 0 ) 
		 	{
        $e = new Xerxes_DatabasesDeniedException("You are not authorized to search the databases you selected. Please choose other databases and try again.");
        $e->setDeniedDatabases( $excludedDbs );
        throw $e;
      }
      elseif ( count($arrDatabases) < 1)
      {
		 		throw new Exception("Please choose one or more databases to search");
		 	}
		 	if ( count($arrDatabases) > $configSearchLimit )
		 	{
		 		throw new Exception("You can only search up to $configSearchLimit databases at a time");
		 	}

		 	
		 	$strSpellCorrect = "";			// spelling correction
			$strSpellUrl = "";				// return url for spelling change
			$strGroup = "";					// group id number
			$strNormalizedQuery = "";		// normalized query
			
			// normalize query
			
			$objQueryParser = new Xerxes_QueryParser();
			
			if ( $configNormalize == true )
			{
				$strNormalizedQuery = $objQueryParser->normalize($strField, $strQuery);
			}
			else
			{
				$strNormalizedQuery = "$strField=($strQuery)";
			}
			
			// initiate search with Metalib

			$strGroup = $objSearch->search($strNormalizedQuery, $arrDatabases);
			
			// something went wrong, yo!
			
			if ( $strGroup == "" ) throw new Exception("Could not initiate search with Metalib server");
			
			// check spelling unless this is a return submission from a previous spell correction
			
			if ( $strSpell == null )
			{
				// check spelling
				$strSpellCorrect = $objQueryParser->checkSpelling($strQuery, $configYahooID);	
				
				if ( $strSpellCorrect != "" )
				{
					// construct spell check return url with spelling suggestion
					
					$strSpellUrl = "./?base=metasearch&action=search&spell=1&query=" . urlencode($strSpellCorrect) . "&field=" . $strField;
					$strSpellUrl .= "&context=" . urlencode( $strContext );
					$strSpellUrl .= "&context_url=" . urlencode( $strContextUrl );
					
					foreach(  $arrDatabases as $strDatabase )
					{
						if ( $strDatabase != null )
						{
							$strSpellUrl .= "&database=" . $strDatabase;
						}
					}
				}
			}
	
			// create search information xml			
			
			$arrSearch = array();
			$arrSearch["date"] = date("Y-m-d");
			$arrSearch["spelling"] = $strSpellCorrect;
			$arrSearch["spelling_url"] = $strSpellUrl;
			$arrSearch["context"] = $strContext;
			$arrSearch["context_url"] = $strContextUrl;
			
			foreach ( $arrSearch as $key => $value )
			{
				$objElement = $objXml->createElement($key, Xerxes_Parser::escapeXml($value));
				$objXml->documentElement->appendChild($objElement);
			}
			
			// for now we're only using one search box, still need to consider if we want to offer
			// two, and thus include code in this command to catch that, or use one box and hijack
			// the second one for the normalize query option above, which is still experimental (2008-01-09)
			
			$objPair = $objXml->createElement("pair");
			$objPair->setAttribute("position", 1);
			$objXml->documentElement->appendChild($objPair);
			
			$arrQuery = array();
			$arrQuery["query"] = $strQuery;
			$arrQuery["normalized"] = $strNormalizedQuery;
			$arrQuery["field"] = $strField;

			foreach ( $arrQuery as $key => $value )
			{
				$objElement = $objXml->createElement($key, Xerxes_Parser::escapeXml($value));
				$objPair->appendChild($objElement);
			}
			
			// get links from ird records for those databases that have been included in the search and 
			// store it here so we can get at this information easily on any subsequent page without having
			// to go back to the database
			

			
			$objDatabaseLinks = $objXml->createElement("database_links");
			$objXml->documentElement->appendChild($objDatabaseLinks);
			
			foreach ( $arrDB as $objDatabase )
			{
				// create a database node and append to database_links
				
				$objNodeDatabase = $objXml->createElement("database");
				$objNodeDatabase->setAttribute("metalib_id", $objDatabase->metalib_id);
				$objDatabaseLinks->appendChild($objNodeDatabase);
				
				// attach all the links and the database name
				
				foreach ( $objDatabase->properties() as $key => $value )
				{
					if ( $value != "" && ( strstr($key, "link_") || $key == "title_display" ))
					{
						$objLink = $objXml->createElement($key, Xerxes_Parser::escapeXml($value));
						$objNodeDatabase->appendChild($objLink);
					}
				}
			}
			
			// add any warnings from metalib
			
			$objWarnings = $objSearch->getWarnings();
			
			if ( $objWarnings != null )
			{
				$objImport = $objXml->importNode($objWarnings->documentElement, true);
				$objXml->documentElement->appendChild($objImport);
			}
						

			// save this information in the cache
							
			$this->setCache($strGroup, "search", $objXml);
			
			// redirect to hits page
			
			$objRequest->setRedirect($configBaseUrl . "/?base=metasearch&action=hits&group=$strGroup");
			
			return 1;	
		}
	}

?>
