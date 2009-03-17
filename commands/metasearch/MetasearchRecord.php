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
		public function doExecute()
		{
			// parameters from request
			
			$strGroup =	$this->request->getProperty("group");
			$strResultSet =	$this->request->getProperty("resultSet");
			$configIncludeMarcRecord = $this->registry->getConfig("XERXES_FULL_INCLUDE_MARC", false, false);
			
			// fetch the marc record

			$objRecord = $this->getRecord();
			
			// build the response, including certain previous cached data	
						
			$objXml = new DOMDocument();
			$objXml = $this->documentElement();
			
			$objXml = $this->addSearchInfo($objXml, $strGroup);
			$objXml = $this->addStatus($objXml, $strGroup, $strResultSet);
			$objXml = $this->addRecords($objXml, array($objRecord), $configIncludeMarcRecord);
			
			$this->request->addDocument($objXml);
			
			return 1;
		}
	}

?>