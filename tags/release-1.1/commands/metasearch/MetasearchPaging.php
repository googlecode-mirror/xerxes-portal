<?php	
	
	/**
	 * Assists the view with basic paging and sorting options for brief metasearch results page
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchPaging extends Xerxes_Command_Metasearch
	{
		/**
		 * Construct paging, sorting, and hit summary elements for the current page
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */ 
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$objPage = new Xerxes_Framework_Page();
			
			$iTotalHits = (int) $objRequest->getData("results/hits");
			$strDatabaseTitle = (string) $objRequest->getData("results/database");
			$strSortKeys = (string) $objRequest->getData("results/sort");
			
			$iMax = $objRegistry->getConfig("RECORDS_PER_PAGE", false, 10);
			
			
			// create page hit summary element
		
			$objSummaryXml = $objPage->summary(
				$iTotalHits,
				(int) $objRequest->getProperty("startRecord"),
				$iMax
				);
				
			$objRequest->addDocument($objSummaryXml);
			
				
			// create sorting element only for merged results
				
			if ( $strDatabaseTitle == "Top Results" )
			{
				$strQueryString = "./?base=metasearch&action=sort&group=" . $objRequest->getProperty("group");
				$arrSortOptions = array("rank" => "relevance", "year" => "date", "title" => "title",  "author" => "author");
						
				if ( $strSortKeys == null )
				{ 
					$strSortKeys = $objRegistry->getConfig("SORT_ORDER_PRIMARY", false, "rank");
				}
					
				$objSortXml = $objPage->sortDisplay( $strQueryString, $strSortKeys, $arrSortOptions);
				
				$objRequest->addDocument($objSortXml);
			}
				
			
			// create paging element
			
			$strFacetNodes = "";
			
			if ( $objRequest->getProperty("node") != null ) $strFacetNodes .= "&node=" . $objRequest->getProperty("node");
			if ( $objRequest->getProperty("facet") != null ) $strFacetNodes .= "&facet=" . $objRequest->getProperty("facet");
	   
			$objPagerXml = $objPage->pager( 
				"./", 
				"startRecord",  (int) $objRequest->getProperty("startRecord"), 
				null,  (int) $iTotalHits, 
				$iMax,
				"&base=metasearch&action=" . $objRequest->getProperty("action") .
				"&group=" . $objRequest->getProperty("group") . 
				"&resultSet=" . $objRequest->getProperty("resultSet") .
				$strFacetNodes
				);
	
			
			$objRequest->addDocument($objPagerXml);
				
			return 1;
		}
	}

?>