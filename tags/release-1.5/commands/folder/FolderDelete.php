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
		public function doExecute()
		{
			// ensure this is the same user
			
			$strRedirect = $this->enforceUsername();
			
			if ( $strRedirect != null )
			{
				$this->request->setRedirect($strRedirect);
				return 1;
			}
			
			// get request parameters and configuration settings
			
			$strUsername = $this->request->getSession("username");
			$strSource = $this->request->getProperty("source"); 
			$strID = $this->request->getProperty("id");
			
			// params for deciding where to send the user back
			
			$strType = $this->request->getProperty("type");
			$strLabel = $this->request->getProperty("label");
			$iStart = $this->request->getProperty("startRecord");	
			$iTotal = $this->request->getProperty("total");
			$iCount = $this->request->getProperty("recordsPerPage");
			$strSort = $this->request->getProperty("sortKeys");
			
			// ensure we send user back to a page with actual results!
			
			if ( $iTotal == 1 && ( $strLabel != "" || $strType != "") )
			{
				// if this is the last result in a tag or format grouping, then
				// simply redirect back to the folder home page
				
				$arrParams = array(
					"base" => "folder",
					"action" => "home",
					"sortKeys" => $strSort,
					"username" => $this->request->getSession("username")
					
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
					"username" => $this->request->getSession("username"),
					"type" => $strType,
					"label" => $strLabel,
					"sortKeys" => $strSort,
					"startRecord" => $iStart
				);				
			}
			
			$strReturn = $this->request->url_for($arrParams);
			
			// delete the record from the database
			
			$objData = new Xerxes_DataMap();
			$objData->deleteRecordBySource($strUsername, $strSource, $strID);
			
			// update the session
      		// Sorry this gets a bit confusing, the api hasn't stayed entirely consistent.
			
			list($date, $resultSet, $recordNumber) = split(':',$strID);
			Xerxes_Helper::unmarkSaved($resultSet, $recordNumber);
			
			// send the user back out, so they don't step on this again
			
			$this->request->setRedirect($strReturn);
			
			return 1;
		}
	}

?>
