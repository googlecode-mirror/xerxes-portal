<?php	
	
	/**
	 * Check which records have already been saved for a page of results
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchCheckSaved extends Xerxes_Command_Metasearch
	{
		public function doExecute()
		{
			$arrSaved = array();
			$arrMatch = array();
			$objData = new Xerxes_DataMap();
			
			// find all of the xerxes records
			
			$objRecords = $this->request->getData("//xerxes_record", null, "DOMNodeList");
			
			if ( $objRecords->length > 0 )
			{
				foreach ( $objRecords as $objXerxesRecord)
				{
					$strResultSet = "";
					$strRecordNumber = "";
					
					$objResultSet = $objXerxesRecord->getElementsByTagName("result_set")->item(0);
					$objRecordNumber = $objXerxesRecord->getElementsByTagName("record_number")->item(0);
					
					if ( $objResultSet != null ) $strResultSet = $objResultSet->nodeValue;
					if ( $objRecordNumber != null ) $strRecordNumber = $objRecordNumber->nodeValue;
					
					if ( $strRecordNumber != "" && $strResultSet != "" )
					{
						// see if it's listed in session as being saved
						
						if ( Xerxes_Helper::isMarkedSaved( $strResultSet, $strRecordNumber ) )
						{	
							$key = Xerxes_Helper::savedRecordKey( $strResultSet, $strRecordNumber );
							$id = $_SESSION['resultsSaved'][$key]['xerxes_record_id'];
		
							array_push($arrSaved, $id);
							$arrMatch[$id] = $strResultSet . ":" . $strRecordNumber;
						}
					}
				}
				
				if ( count ($arrSaved) == 0 )
				{
					return 0;
				}
				
				// fetch all the saved records on this page in one query to the database 
				
				$arrResults = $objData->getRecordsByID($arrSaved);
				
				if ( count($arrResults) == 0 )
				{
					return 0;
				}
				else
				{
					$objXml = new DOMDocument();
					$objXml->loadXML("<saved_records />");
					
					foreach ( $arrResults as $objSavedRecord )
					{
						// id
						
						$objSavedRecordXml = $objXml->createElement( "saved" );
						$objSavedRecordXml->setAttribute( "id", $arrMatch[$objSavedRecord->id] );
	
						$objIDXml = $objXml->createElement( "id", $objSavedRecord->id );
						$objSavedRecordXml->appendChild( $objIDXml );
						
						// labels
						
						foreach ( $objSavedRecord->tags as $tag )
						{
							$objTagXml = $objXml->createElement( "tag", Xerxes_Parser::escapeXml( $tag ) );
							$objSavedRecordXml->appendChild( $objTagXml );
						}
							
						$objXml->documentElement->appendChild( $objSavedRecordXml );
					}
					
					$this->request->addDocument($objXml);
					
					return 1;
				}
			}
		}
	}

?>