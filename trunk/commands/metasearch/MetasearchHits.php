<?php	
	
	/**
	 * Command class for polling metalib on the status of the search and displaying that information
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchHits extends Xerxes_Command_Metasearch
	{
		/**
		 * Check the status of the search; Request should include params for:
		 * 'group' the group number of the search; optionally 'mergeRedirect' to override
		 * a redirect on merge, should that option be set in config.  Adds search status
		 * information to the response.
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return unknown
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$objSearch = $this->getSearchObject($objRequest, $objRegistry); // metalib search object

			$bolRedirect = false;				// final determination to redirect on merge	
			$objStatus = new DOMDocument();		// search status
			$objMerge = new DOMDocument();		// merge information
			$iProgress = null;					// number of times metalib status polled
			
			// params from the request
			
		 	$strGroup = $objRequest->getProperty("group");
			$strMergeRedirect = $objRequest->getProperty("mergeRedirect");

		 	// configuration options
		 	
		 	$configShowMergedResults = $objRegistry->getConfig("IMMEDIATELY_SHOW_MERGED_RESULTS", false, true);
		 	$configApplication = $objRegistry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
		 	$configParentDirectory = $objRegistry->getConfig("PATH_PARENT_DIRECTORY", true);
		 	$configFacets = $objRegistry->getConfig("FACETS", false, false);
		 	$configRecordsPerPage = $objRegistry->getConfig("RECORDS_PER_PAGE", false, 10);
		 	
		 	$iRefreshSeconds = $objRegistry->getConfig("SEARCH_PROGRESS_CAP", false, 35);
		 	$configHitsCap = (string) $iRefreshSeconds / 5;
		 	$configSortPrimary = $objRegistry->getConfig("SORT_ORDER_PRIMARY", false, "rank");
		 	$configSortSecondary = $objRegistry->getConfig("SORT_ORDER_SECONDARY", false, "year");
		 	
			// determine if redirect after merge
			
			if ( $configShowMergedResults == true )
			{ 
				$bolRedirect = true; 
			}
			if ( $strMergeRedirect == "false" ) 
			{ 
				$bolRedirect = false; 
			} 
			else if ( $strMergeRedirect == "true" ) 
			{ 
				$bolRedirect = true;
			} 
		
			// get status of the search and cache result
			
			$objStatus = $objSearch->searchStatus($strGroup);
			$this->setCache($strGroup, "group", $objStatus);
			
			// cap the number of refreshes
			
			if ( $objRequest->getSession("refresh-$strGroup") != null )
			{
				// get refresh count
				$iProgress = (int) $objRequest->getSession("refresh-$strGroup");
				
				// if we hit limit, set metalib finish flag to true
				if ( $iProgress >= (int) $configHitsCap )
				{ 
					$objSearch->setFinished(true);
				}
				else 
				{ 
					$iProgress++; 
					$objRequest->setSession("refresh-$strGroup", $iProgress); 
				}
			}
			else
			{
				$objRequest->setSession("refresh-$strGroup", 1); 
			}
			
			// if done see if there are results and merge and redirect, 
			// otherwise this will fall thru and continue with auto refreshing
			
			if ( $objSearch->getFinished() == true )
			{
				$objRequest->setSession("refresh-$strGroup", 10); 
				
				// check to see if there are any documents to merge, and then merge
				// and create facets
					
				$iGroupTotal = 0;				// total number of hits in the group
				$arrDatabaseHits = array();		// total number of databases with hits
				
				
				$objSimpleXml = simplexml_import_dom($objStatus->documentElement);
					
				foreach ( $objSimpleXml->xpath("//base_info") as $objBase )
				{
					if ( (string) $objBase->no_of_documents != "888888888" )
					{
						if ( (int) $objBase->no_of_documents > 0 )
						{
							// we'll only count the databases with hits
							
							$iGroupTotal += (int) $objBase->no_of_documents;
							array_push($arrDatabaseHits, (string) $objBase->set_number);
							
						}						
					}
				}
				
				// we'll only issue the merge command if there is more than one database
				// with hits, otherwise there's no point
				
				if ( $iGroupTotal > 0 )
				{					
					if ( count($arrDatabaseHits) == 1 )
					{
						$strSetNumber = $arrDatabaseHits[0];
						
						// redirect to results page
						
						$objRequest->setRedirect("./?base=metasearch&action=results&group=$strGroup&resultSet=$strSetNumber");
							
						return 1;
					}
					else
					{
						$strMergeSet = "";			// merge set identifier
						$iMergeCount = 0;			// count of documents in the merge set

						// merge the top results
						
						$objMerge = $objSearch->merge($strGroup, $configSortPrimary, $configSortSecondary);
						
						// get the newly updated status that resulted from that merge
						// operation and cache
						
						$objStatus = $objSearch->searchStatus($strGroup);	
						$this->setCache($strGroup, "group", $objStatus);
	
						$objXPath = new DOMXPath($objMerge);
				
						// extract new merge set number and total hits for merge set
						
						if ( $objXPath->query("//new_set_number")->item(0) != null)
						{
							$strMergeSet = $objXPath->query("//new_set_number")->item(0)->nodeValue;
						}
						if ( $objXPath->query("//no_of_documents")->item(0) != null)
						{
							$iMergeCount = (int) $objXPath->query("//no_of_documents")->item(0)->nodeValue;
						}
						
						if ( $strMergeSet == "" )
						{
							throw new Exception("Result from Metalib returned no set number");
						}
						
						// cache the facets response, but only if there are more results than a single page
						// and facets have been turned on
						
						if ( $iMergeCount > $configRecordsPerPage && $configFacets == true )
						{
							$objFacetXml = $objSearch->facets($strMergeSet, "all", $configApplication);
							$this->setCache($strGroup, "facets", $objFacetXml);
							
							// cache a slimmed down version of the facets as well
							// to ease the load on the interface
							
							$strFacetSlim = Xerxes_Parser::transform($objFacetXml, "xsl/facets-slim.xsl");
							$this->setCache($strGroup, "facets-slim", $strFacetSlim);
						}
					}
					
					// redirect to results page with merge set
			
					if ( $bolRedirect == true )
					{
						// catch no hits
						
						if ( $iMergeCount == 0 )
						{
							// check to see if any of the individual dbs had a search-and-link with 
							// a 'results found' indicator
							
							$bolIndividual = false;
							$strIndividualSet = "";
							
							foreach ( $objStatus->getElementsByTagName("base_info") as $objDb )
							{
								foreach ( $objDb->getElementsByTagName("no_of_documents") as $objDocs)
								{
									if ( $objDocs->nodeValue == "888888888" )
									{
										$bolIndividual = true;
										
										if ( $objDb->getElementsByTagName("set_number")->item(0) != null )
										{
											$strIndividualSet = $objDb->getElementsByTagName("set_number")->item(0)->nodeValue;
										}
									}
								}
							}
							
							if ( $bolIndividual == true )
							{
								// redirect to individual results page

								$objRequest->setRedirect("./?base=metasearch&action=results&group=$strGroup&resultSet=$strIndividualSet");
								
								return 1;
							}
						}
						else
						{							
							// redirect to merged results page
							
							$objRequest->setRedirect("./?base=metasearch&action=results&group=$strGroup&resultSet=$strMergeSet");
							
							return 1;
						}
					}
				}
			}
					
			// build the response from previous cached data	
						
			$objXml = new DOMDocument();
			
			$objXml = $this->documentElement();
			$objXml = $this->addSearchInfo($objXml, $strGroup);
			$objXml = $this->addStatus($objXml, $strGroup);
			$objXml = $this->addProgress($objXml, $objRequest->getSession("refresh-$strGroup"));
			
			$objRequest->addDocument($objXml);
		 	
			return 1;	
		}
	}

?>