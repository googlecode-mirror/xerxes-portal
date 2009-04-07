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
		public function doExecute()
		{
			// get address for refworks
			
			$url = $this->registry->getConfig("REFWORKS_ADDRESS", false, "http://www.refworks.com/express/ExpressImport.asp");
			$strAppName = $this->registry->getConfig("APPLICATION_NAME", false, "Xerxes");			
			
			// get the ids that were selected for export
			
			$arrIDs = $this->request->getData("//record/id", null, "array");
			$strID = implode(",",$arrIDs);
			
			// construct return url back to the fetch action
			
			$arrProperties = array (
				"base" => "folder",
				"action" => "fetch",
				"format" => "ris",
				"records" => $strID
			);
			
			$return = $this->request->url_for($arrProperties, true);
			
			// construct full url to refworks
			
			$url .= "?vendor=" . urlencode($strAppName);
			$url .= "&filter=RIS+Format";
			$url .= "&encoding=65001";
			$url .= "&url=" . urlencode($return);
			
			$this->request->setRedirect($url);	
		}
	}

?>