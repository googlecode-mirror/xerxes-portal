<?php	
	
	/**
	 * Assists the view with basic paging and sorting options for the my saved records page
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderPaging extends Xerxes_Command_Folder
	{
		public function doExecute()
		{
			$objPage = new Xerxes_Framework_Page();
			
			// get parameters and configuration information
			
			$strUsername = $this->request->getSession("username");
			$iStart = (int) $this->request->getProperty("startRecord");
			$strLabel = $this->request->getProperty("label");
			$strType = $this->request->getProperty("type");
			
			$iMax = $this->registry->getConfig("SAVED_RECORDS_PER_PAGE", false, self::DEFAULT_RECORDS_PER_PAGE);
			
			// get total number of saved records
			
			$iTotal = $this->getTotal($strUsername, $strLabel, $strType);
			
			### create page hit summary element
		
			$objSummaryXml = $objPage->summary($iTotal,$iStart,$iMax);
			$this->request->addDocument($objSummaryXml);
			
			
			### create sorting element
			
			$strSort = $this->request->getProperty("sortKeys");
			if ( $strSort == "" ) $strSort = "id";
			
			$arrParams = array(
				"base" => "folder",
				"action" => "home",
				"username" => $strUsername,
				"startRecord" => 1,
				"label" => $strLabel,
				"type" => $strType
			);
		
			$strQueryString = $this->request->url_for($arrParams);
			
			$arrSortOptions = array("title" => "title", "author" => "author", "year" => "date", "id" => "most recently added");
			$objSortXml = $objPage->sortDisplay( $strQueryString, $strSort, $arrSortOptions);
			
			$this->request->addDocument($objSortXml);
			
			
			### create paging element

			$params = array (
				"base" => "folder",
				"action" => "home",
				"username" => $this->request->getSession("username"),
				"sortKeys" => $this->request->getProperty("sortKeys"),
				"label" => $strLabel,
				"type" => $strType
			);
			
			
			$objPagerXml = $objPage->pager_dom(
				$params,
				"startRecord", (int) $this->request->getProperty("startRecord"),
				null,  $iTotal, 
				$iMax, $this->request
			);
			
			$this->request->addDocument($objPagerXml);
			
			return 1;
		}
	}
?>