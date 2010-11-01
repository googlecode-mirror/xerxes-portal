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
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchFacet extends Xerxes_Command_Metasearch
	{
		public function doExecute()
		{
			$arrResults = array();		// holds returned records
			$iTotalHits = 0;			// total number of facets stored
			$arrFields = array();		// fields to return in response
			$arrDocs = array();			// stores list of document  numbers
			$strFacetName = "";			// facet name
			
			// metalib search object
			
			$objSearch = $this->getSearchObject(); 
			
			// parameters from request
			
			$id = $this->request->getProperty( "group" );
			$strGroup = $this->getGroupNumber();
			$strResultSet =	$this->request->getProperty("resultSet");	// to correct a bug in 4.1.0
			$iStartRecord =	(int) $this->request->getProperty("startRecord");
			$iFacet = $this->request->getProperty("facet");
			$iNode = $this->request->getProperty("node");
			
			// marc fields to return from metalib; we specify these here in order to keep
			// the response size as small (and thus as fast) as possible
			
			// @todo factor this out to metasearch parent class
			
			$strMarcFields = $strMarcFields = self::MARC_FIELDS_BRIEF;
			
			// configuration options
			
			$configRecordPerPage = $this->registry->getConfig("RECORDS_PER_PAGE", false, self::DEFAULT_RECORDS_PER_PAGE);
			$configMarcFields = $this->registry->getConfig("MARC_FIELDS_BRIEF", false);
			$configIncludeMarcRecord = $this->registry->getConfig("XERXES_BRIEF_INCLUDE_MARC", false, false);
			$configFacets = $this->registry->getConfig("FACETS", false, false);
			
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

			$objFacets = $this->getCache($id, "facets", "SimpleXML");
			
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
			
			$arrFields = explode(",", $configMarcFields);
			
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
			
			$objXml->documentElement->appendChild($objXml->createElement("facet_name", Xerxes_Framework_Parser::escapeXML($strFacetName)));
			
			$objXml = $this->addSearchInfo($objXml, $id);
			$objXml = $this->addStatus($objXml, $id, $strResultSet, $iTotalHits);
			$objXml = $this->addProgress($objXml, $this->request->getSession("refresh-$strGroup"));	
			$objXml = $this->addRecords($objXml, $arrResults, $configIncludeMarcRecord);
			$objXml = $this->addFacets($objXml, $id, $configFacets);
			
			// add to master xml
			
			$this->request->addDocument($objXml);
			
			return 1;
		}
	}

?>