<?php	
	
	/**
	 * Sort the merged set based on new sort criteria
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchSort extends Xerxes_Command_Metasearch
	{
		/**
		 * Sort the merged result set; Request object should include 'group' the search
		 * group id number; 'sortKeys' the index on which to sort.  Will save the sort order
		 * in the search status (group) cache, and redirect the user (to ensure they don't 
		 * step back on this command) to the results page to fetch the records in the new order
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$strResultSet = "";				// merged result set number to send back in return url
			
			// get paramters and configuration settings
			
			$strSortKeys = $objRequest->getProperty("sortKeys");
			$strGroup = $objRequest->getProperty("group");
			$configBaseUrl = $objRegistry->getConfig("BASE_URL", true);
			
			// sort the merged result set
			
			$objSearch = $this->getSearchObject($objRequest, $objRegistry);
			$objSearch->sort( $strGroup, $strSortKeys );
			
			// update search status xml to indicate present sort value for merged result
			
			$objXml = $this->getCache($strGroup, "group", "SimpleXML");
							
			foreach ( $objXml->xpath("//base_info") as $base_info )
			{
				if ( $base_info->base == "MERGESET" )
				{
					$base_info->sort = $strSortKeys;	
					$strResultSet = (string) $base_info->set_number;
				}
			}
			
			// set back in the cache
			
			$this->setCache($strGroup, "group", $objXml);
					 
		 	// redirect to results page
		 	
		 	$objRequest->setRedirect($configBaseUrl . 
		 		"/?base=metasearch&action=results&group=$strGroup&resultSet=$strResultSet");
			
			return 1;
		}
	}

?>