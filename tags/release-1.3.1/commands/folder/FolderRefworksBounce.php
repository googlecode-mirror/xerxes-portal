<?php	
	
	/**
	 * Send an import request to refworks
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderRefworksBounce extends Xerxes_Command_Folder
	{
		/**
		 * Send an import request to refworks
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int		status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// get address for refworks
			
			$url = $objRegistry->getConfig("REFWORKS_ADDRESS", false, "http://www.refworks.com/express/ExpressImport.asp");
			$strAppName = $objRegistry->getConfig("APPLICATION_NAME", false, "Xerxes");			
			
			// get the ids that were selected for export
			
			$arrIDs = $objRequest->getData("//record/id", null, "array");
			$strID = implode(",",$arrIDs);
			
			// construct return url back to the fetch action
			
			$arrProperties = array (
				"base" => "folder",
				"action" => "fetch",
				"format" => "ris",
				"records" => $strID
			);
			
			$return = $objRequest->url_for($arrProperties, true);
			
			// construct full url to refworks
			
			$url .= "?vendor=" . urlencode($strAppName);
			$url .= "&filter=RIS+Format";
			$url .= "&encoding=65001";
			$url .= "&url=" . urlencode($return);
			
			$objRequest->setRedirect($url);	
		}
	}

?>