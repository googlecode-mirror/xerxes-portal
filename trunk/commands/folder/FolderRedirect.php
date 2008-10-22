<?php	
	
	/**
	 * Redirecting the user to SFX or Full-text, principally from emailed
	 * or saved full-text links
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderRedirect extends Xerxes_Command_Folder
	{
		/**
		 * Request paramaters include 'id' the id of the saved record, and the 'type'
		 * of redirect, either sfx or a full-text link
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// get request parameters and configuration settings
			
			$strID = $objRequest->getProperty("id");
			$strType = $objRequest->getProperty("type");
      
			$configLinkResolver = $objRegistry->getConfig("LINK_RESOLVER_ADDRESS", true);
			$configSID = $objRegistry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
			$configBaseUrl  = $objRegistry->getConfig("BASE_URL", true);
		
			// get the record from database
			
			$objData = new Xerxes_DataMap();
			$objDataRecord = $objData->getRecordByID($strID);
			
			if ( $objDataRecord == null  ) throw new Exception("cannot find record");
			
			$objRecord = $objDataRecord->xerxes_record;
			
			// redirect to the resource based on type
			
			if ( $strType == "openurl" )
			{					
				$objRequest->setRedirect($objRecord->getOpenURL($configLinkResolver, $configSID));
			}
			else
			{
				$strUrl = $configBaseUrl . "/?base=databases&action=proxy";
				
				if ( $strType == "html" || $strType == "pdf" || $strType == "online" || $strType == "construct")
				{
					$link = $objRecord->getFullText(true);
					
					foreach ( $link as $arrLink )
					{
						if ( $arrLink[2] == $strType)
						{
							// see if this is a construct link, in which case pass it back thru
							// proxy for construction
							
							if ( is_array($arrLink[1]) )
							{
								foreach ( $arrLink[1] as $strField => $strValue )
								{
									$strUrl .= "&param=$strField=$strValue";
								}						
							}
							else
							{
								$strUrl .= "&url=" . urlencode($arrLink[1]);	
							}
							
							$strUrl .= "&database=" .  $objRecord->getMetalibID();								
						}
					}
				}
				else
				{
					throw new Exception("unsupported redirect type");
				}
				
				$objRequest->setRedirect($strUrl);
			}
			
			return 1;					
		}
	}

?>
