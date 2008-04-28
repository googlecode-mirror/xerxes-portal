<?php	
	
	/**
	 * Assists the view with basic paging and sorting options for the my saved records page
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderPaging extends Xerxes_Command_Folder
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
			
			// get parameters and configuration information
			
			$strUsername = $objRequest->getSession("username");		
			$iStart = (int) $objRequest->getProperty("startRecord");	
			
			$iMax = $objRegistry->getConfig("SAVED_RECORDS_PER_PAGE", false, 20);
			$configModRewrite = $objRegistry->getConfig("REWRITE", false, false);
			
			// get total number of saved records
			
			$objData = new Xerxes_DataMap;
			$iTotal = $objData->totalRecords($strUsername);
			
			
			
			### create page hit summary element
		
			$objSummaryXml = $objPage->summary($iTotal,$iStart,$iMax);
			$objRequest->addDocument($objSummaryXml);
			
			
			
			### create sorting element
			
			$strSort = $objRequest->getProperty("sortKeys");
			if ( $strSort == "" ) $strSort = "id";
		
			$strQueryString = $objRequest->url_for( array( "base" => "folder",
                                                     "action" => "home",
                                                     "username" => $strUsername,
                                                     "startRecord" => 1));;


			$arrSortOptions = array("title" => "title", "author" => "author", "year" => "date", "id" => "most recently added");
			$objSortXml = $objPage->sortDisplay( $strQueryString, $strSort, $arrSortOptions);
				
			$objRequest->addDocument($objSortXml);
			
			
			
			### create paging element			

      $params = array ( "base" => "folder",
                       "action" => "home",
                       "username" => $objRequest->getSession("username"),
                       "sortKeys" => $objRequest->getProperty("sortKeys"));
	   
      
			$objPagerXml = $objPage->pager_dom(  
        $params,
				"startRecord",  (int) $objRequest->getProperty("startRecord"), 
				null,  $iTotal, 
				$iMax, $objRequest
			);
			
			$objRequest->addDocument($objPagerXml);
				
			return 1;
		}
	}

?>