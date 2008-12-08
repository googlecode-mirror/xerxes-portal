<?php	
	
	/**
	 * Fetch and display a single record
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchRecord extends Xerxes_Command_Metasearch
	{
		/**
		 * Fetch and display a single record; Request should include params for:
		 * 'group' the search group number; 'resultSet' the result set from which
		 * the record came; and 'startRecord' the records position in that resultset
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// parameters from request
			
			$strGroup =	$objRequest->getProperty("group");
			$strResultSet =	$objRequest->getProperty("resultSet");
			$configIncludeMarcRecord = $objRegistry->getConfig("XERXES_FULL_INCLUDE_MARC", false, false);
			
			// fetch the marc record

			$objRecord = $this->getRecord($objRequest, $objRegistry);
			
			// build the response, including certain previous cached data	
						
			$objXml = new DOMDocument();
			$objXml = $this->documentElement();
			
			$objXml = $this->addSearchInfo($objXml, $strGroup);
			$objXml = $this->addStatus($objXml, $strGroup, $strResultSet);
			$objXml = $this->addRecords($objXml, array($objRecord), $configIncludeMarcRecord);
			
			$objRequest->addDocument($objXml);
			
			return 1;
		}
	}

?>