<?php	
	
	/**
	 * Send an import request to refworks
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id: FolderRefworksBounce.php 976 2009-11-02 14:22:56Z dwalker@calstate.edu $
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderEndnoteBounce extends Xerxes_Command_Folder
	{
		public function doExecute()
		{
			// get address for refworks
			
			$url = $this->registry->getConfig("ENDNOTE_ADDRESS", false, "https://www.myendnoteweb.com/EndNoteWeb.html");
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
			
			// construct full url to endnote
					
			$url .= "?partnerName=" . urlencode($strAppName);
			$url .= "&dataRequestUrl=" . urlencode($return);
			$url .= "&func=directExport&dataIdentifier=1&Init=Yes&SrcApp=CR&returnCode=ROUTER.Unauthorized";	
			
			$this->request->setRedirect($url);	
		}
	}

?>