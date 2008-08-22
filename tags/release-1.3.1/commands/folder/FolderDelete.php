<?php	
	
	/**
	 * Delete a record from the user's folder
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
		 * Delete a record from the user's folder
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
			$strSource = $objRequest->getProperty("source"); 
			$strID = $objRequest->getProperty("id");
			
			// params for deciding where to send the user back
			
			$strType = $objRequest->getProperty("type");
			$strLabel = $objRequest->getProperty("label");
			$iStart = $objRequest->getProperty("startRecord");	
			$iTotal = $objRequest->getProperty("total");
			$iCount = $objRequest->getProperty("recordsPerPage");
			$strSort = $objRequest->getProperty("sortKeys");
			
			// ensure we send user back to a page with actual results!
			
			if ( $iTotal == 1 && ( $strLabel != "" || $strType != "") )
			{
				// if this is the last result in a tag or format grouping, then
				// simply redirect back to the folder home page
				
				$arrParams = array(
					"base" => "folder",
					"action" => "home",
					"sortKeys" => $strSort,
					"username" => $objRequest->getSession("username")
					
				);
			}
			else
			{
				// if the last record in the results is also the last one on
				// the page (of 10 or whatever), send the user back to an 
				// earlier page with results on it
				
				if ( $iStart > $iCount && $iStart == $iTotal )
				{
					$iStart = $iStart - $iCount;
				}
				
				$arrParams = array(
					"base" => "folder",
					"action" => "home",
					"username" => $objRequest->getSession("username"),
					"type" => $strType,
					"label" => $strLabel,
					"sortKeys" => $strSort,
					"startRecord" => $iStart
				);				
			}
			
			$strReturn = $objRequest->url_for($arrParams);
			
			// delete the record from the database
			
			$objData = new Xerxes_DataMap();
			$objData->deleteRecordBySource($strUsername, $strSource, $strID);
			
			// update the cookie
			
			$objCookie = new Xerxes_Cookie("saves");
			$objCookie->updateCookie($strID, "&", true);
			
			// send the user back out, so they don't step on this again
			
			$objRequest->setRedirect($strReturn);
			
			return 1;
		}
	}

?>