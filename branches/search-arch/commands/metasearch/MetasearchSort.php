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
		public function doExecute()
		{
			$strResultSet = "";				// merged result set number to send back in return url
			
			// get paramters and configuration settings
			
			$strSortKeys = $this->request->getProperty("sortKeys");
			$strGroup = $this->request->getProperty("group");
			
			// sort the merged result set
			
			$objSearch = $this->getSearchObject();
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
		 	
			$arrParams = array(
				"base" => "metasearch",
				"action" => "results",
				"group" => $this->request->getProperty("group"),
				"resultSet" => $strResultSet
			);
			
		 	$this->request->setRedirect($this->request->url_for($arrParams));
			
			return 1;
		}
	}

?>