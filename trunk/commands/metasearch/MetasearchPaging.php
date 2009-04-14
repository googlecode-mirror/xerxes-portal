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
		public function doExecute()
		{
			$objPage = new Xerxes_Framework_Page();
			
			$iTotalHits = (int) $this->request->getData("results/hits");
			$strDatabaseTitle = (string) $this->request->getData("results/database");
			$strSortKeys = (string) $this->request->getData("results/sort");
			
			$iMax = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
			
			
			// create page hit summary element
		
			$objSummaryXml = $objPage->summary(
				$iTotalHits,
				(int) $this->request->getProperty("startRecord"),
				$iMax
				);
				
			$this->request->addDocument($objSummaryXml);
			
				
			// create sorting element only for merged results
				
			if ( $strDatabaseTitle == "Top Results" )
			{
				$arrParams = array(
					"base" => "metasearch",
					"action" => "sort",
					"group" => $this->request->getProperty("group")
				);
				
				$strQueryString = $this->request->url_for($arrParams);
				
				$arrSortOptions = array("rank" => "relevance", "year" => "date", "title" => "title",  "author" => "author");
						
				if ( $strSortKeys == null )
				{ 
					$strSortKeys = $this->registry->getConfig("SORT_ORDER_PRIMARY", false, "rank");
				}
					
				$objSortXml = $objPage->sortDisplay( $strQueryString, $strSortKeys, $arrSortOptions);
				
				$this->request->addDocument($objSortXml);
			}
				
			
			// create paging element
			
			$arrParams = array(
				"base" => "metasearch",
				"action" => $this->request->getProperty("action"),
				"group" => $this->request->getProperty("group"),
				"resultSet" => $this->request->getProperty("resultSet") 
			);
			
			// only used in facets
			
			if ( $this->request->getProperty("node") != null )
			{
				$arrParams["node"] = $this->request->getProperty("node");
				$arrParams["facet"] = $this->request->getProperty("facet");
			}

			$objPagerXml = $objPage->pager_dom(
				$arrParams,
				"startRecord", (int) $this->request->getProperty("startRecord"),
				null,  (int) $iTotalHits, 
				$iMax, $this->request
			);			
			
			$this->request->addDocument($objPagerXml);
				
			return 1;
		}
	}

?>