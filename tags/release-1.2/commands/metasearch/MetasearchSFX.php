<?php	
	
	/**
	 * Redirects the user to SFX with OpenURL for record
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchSFX extends Xerxes_Command_Metasearch
	{
		/**
		 * Fetches single record from Metalib, constructs OpenURL, and redirects the user to SFX
		 * Request should include params for: 'group' the search group number; 
		 * 'resultSet' the result set from which the record came; and 'startRecord' the records 
		 * position in that resultset
		 * 
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$configLinkResolver = $objRegistry->getConfig("LINK_RESOLVER_ADDRESS", true);
			$configSID = $objRegistry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
			
			$objXerxesRecord = new Xerxes_Record();
			$objXerxesRecord->loadXML($this->getRecord($objRequest, $objRegistry));
			
			$objRequest->setRedirect($objXerxesRecord->getOpenURL($configLinkResolver, $configSID));
			
			return 1;
		}
	}

?>