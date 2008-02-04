<?php	
	
	/**
	 * Command class for retrieving records for a particular facet
	 * 
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @todo facets are still experimental in this version of xerxes, since ex libris
	 * is still working out bugs in the x-server
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchFacet extends Xerxes_Command_Metasearch
	{
		/**
		 * Retrieve the documents for a particular facet; Request should include params
		 * 'group' group number; 'startRecord' the offset for where to start the results
		 * 'facet' facet name; 'node' the numbered node of the facet.  Adds record plus
		 * previous cached data to the response.
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			
			$arrResults = array();		// holds returned records
			$iTotalHits = 0;			// total number of facets stored
			$arrFields = array();		// fields to return in response
			$iMaximumRecords = 10;		// maximum number of records to return
			$arrDocs = array();			// stores list of document  numbers
			$strFacetName = "";			// facet name
			
			// metalib search object
			
			$objSearch = $this->getSearchObject($objRequest, $objRegistry); 
			
			// parameters from request
			
			$strGroup =	$objRequest->getProperty("group");
			$strResultSet =	$objRequest->getProperty("resultSet");	// to correct a bug in 4.1.0
			$iStartRecord =	(int) $objRequest->getProperty("startRecord");
			$iFacet = $objRequest->getProperty("facet");
			$iNode = $objRequest->getProperty("node");
			
			// marc fields to return from metalib; we specify these here in order to keep
			// the response size as small (and thus as fast) as possible
			
			$strMarcFields = "LDR, 001, 007, 008, 016##, 020##, 022##, 035##, 072##, 100##, " .
				"245##, 242##, 260##, 500##, 505##, 513##, 514##, 520##, 546##, 6####, 773##, " .
				"856##, ERI##, SID, YR";
			
			// configuration options
			
			$configRecordPerPage = $objRegistry->getConfig("RECORDS_PER_PAGE", false, 10);
			$configMarcFields = $objRegistry->getConfig("MARC_FIELDS_BRIEF", false);
			$configIncludeMarcRecord = $objRegistry->getConfig("XERXES_BRIEF_INCLUDE_MARC", false, false);
			$configFacets = $objRegistry->getConfig("FACETS", false, false);
			
			
			// add additional marc fields specified in the config file
			
			if ( $configMarcFields != null )
			{
				$configMarcFields .= ", " . $strMarcFields;
			}
			else
			{
				$configMarcFields = $strMarcFields;
			}

			// empty querystring values will return as 0, so fix here in case

			if ( $iStartRecord == 0) $iStartRecord = 1;
			
			$iMaximumRecords = (int) $configRecordPerPage;
			
			
			// get the list of document numbers for the facet from the cached
			// facet file (full version), then get only the range we've requested;
			// should we change this to make it more efficient?

			$objFacets = $this->getCache($strGroup, "facets", "SimpleXML");
			
			$strXpath = "//cluster_facet[position() = $iFacet]/node[position() = $iNode]";
			
			foreach( $objFacets->xpath($strXpath) as $node )
			{
				// extract the name of the facet
				
				$strFacetName = $node["name"];
				
				// get the list of document numbers
				
				foreach ( $node->doc_num as $doc_num )
				{
					array_push($arrDocs, (string) $doc_num);
				}
			}
			
			$iTotalHits = count($arrDocs);
			
			if ( $iTotalHits < 1 ) throw new Exception("could not find facet!");

			// set marc fields to return in response
			
			$arrFields = split(",", $configMarcFields);
			
			// get marc-xml results from metalib
			
			$objResultsXml = $objSearch->retrieve( $strResultSet, $iStartRecord, $iMaximumRecords, null, 
				"customize", $arrFields, $arrDocs);
				
			// extract marc records, this->addRecords will convert to xerxes_record
			
			foreach( $objResultsXml->getElementsByTagName("record") as $objRecord )
			{
				array_push($arrResults, $objRecord);
			}
			
			// build the response, including previous cached data	
						
			$objXml = new DOMDocument();
			$objXml = $this->documentElement();
			
			// facet name
			
			$objXml->documentElement->appendChild($objXml->createElement("facet_name", $strFacetName));
			
			$objXml = $this->addSearchInfo($objXml, $strGroup);
			$objXml = $this->addStatus($objXml, $strGroup, $strResultSet, $iTotalHits);
			$objXml = $this->addProgress($objXml, $objRequest->getSession("refresh-$strGroup"));	
			$objXml = $this->addRecords($objXml, $arrResults, $configIncludeMarcRecord);
			$objXml = $this->addFacets($objXml, $strGroup, $configFacets);
			
			// add to master xml
			
			$objRequest->addDocument($objXml);
			
			return 1;
		}
	}

?>