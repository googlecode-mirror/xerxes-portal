<?php	
	
	/**
	 * Delete a record from the user's saved records
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderDelete extends Xerxes_Command_Folder
	{
		/**
		 * Delete a record from the user's folder, Request params inlcude
		 * 'username' the username; 'sortkeys' the current sorting option so we 
		 * can return the user to the list sorted appropriately, 'source' the souce
		 * id of the record to delete, 'id' the identifier for the record
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// ensure this is the same user
			
			$strRedirect = $this->enforceUsername($objRequest, $objRegistry);
			
			if ( $strRedirect != null )
			{
				$objRequest->setRedirect($strRedirect);
				return 1;
			}
			
			// get request parameters and configuration settings
			
			$strUsername = $objRequest->getSession("username");
			$strSort = $objRequest->getProperty("sortKeys");
			$strSource = $objRequest->getProperty("source"); 
			$strID = $objRequest->getProperty("id");
			
			$configModRewrite = $objRegistry->getConfig("REWRITE", false, false);
			$configBaseUrl = $objRegistry->getConfig("BASE_URL", true);
			
			// delete the record from the database
			
			$objData = new Xerxes_DataMap();
			$objData->deleteRecordBySource($strUsername, $strSource, $strID);
			
			// update the cookie
			
			$objCookie = new Xerxes_Cookie("saves");
			$objCookie->updateCookie($strID, "&", true);
			
			// send the user back out, so they don't step on this again
			
			if ( $configModRewrite == false )
			{
				$objRequest->setRedirect($configBaseUrl . "/?base=folder&username=" . 
					urlencode($strUsername) . "&sortKeys=" . $strSort );			
			}
			else
			{
				$objRequest->setRedirect($configBaseUrl . "/folder/" . 
					urlencode($strUsername) . "?sortKeys=" . $strSort);
			}
			
		}
	}

?>