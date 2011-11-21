<?php	
	
	/**
	 * bX recommendations -- this is quick 'n dirty, to be replaced by search architecture
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchRelated extends Xerxes_Command_Metasearch
	{
		public function doExecute()
		{
			$configToken = $this->registry->getConfig("BX_TOKEN", false);
						
			if ( $configToken != null )
			{
				$configBX		= $this->registry->getConfig("BX_SERVICE_URL", false, "http://recommender.service.exlibrisgroup.com/service");
				$configLinkResolver	= $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
				$configSID		= $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
				$configMaxRecords	= $this->registry->getConfig("BX_MAX_RECORDS", false, "10");
				$configMinRelevance	= $this->registry->getConfig("BX_MIN_RELEVANCE", false, "0");
				
				$open_url = $this->request->getData("//openurl_kev_co");
				
				$url = $configBX . "/recommender/openurl?token=$configToken&$open_url&res_dat=source=global&threshold=$configMinRelevance&maxRecords=$configMaxRecords";
				
				try 
				{
					$xml = Xerxes_Framework_Parser::request($url, 4);
					
					if ( $xml == "" )
					{
						throw new Exception("No response from bx service");
					}
				}
				catch ( Exception $e )
				{
					trigger_error("Could not get result from bX service: " . $e->getTraceAsString(), E_USER_WARNING);
					return 1;
				}

				// header("Content-type: text/xml"); echo $xml; exit;
				
				$doc = new Xerxes_BxRecord_Document();
				$doc->loadXML($xml);
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<recommendations />");
				
				$results = $doc->records();
				$x = 0;
				
				if ( count($results) > 1 )
				{
					foreach ( $doc->records() as $record )
					{
						// first one is the same document
						
						if ( $x == 0 )
						{
							$x++;
							continue;
						}
						
						$objRecord = $objXml->createElement("record");
						$objXml->documentElement->appendChild($objRecord);
						
						$objImport = $objXml->importNode($record->toXML()->documentElement, true);
						$objRecord->appendChild($objImport);
						
						$strOpenURL = $record->getOpenURL($configLinkResolver, $configSID);
						
						$objOpenURL = $objXml->createElement("url_open", Xerxes_Framework_Parser::escapeXML($strOpenURL));
						$objRecord->appendChild($objOpenURL);
					}
				}
				
				$this->request->addDocument($objXml);
			}
			
			return 1;
		}
	}

?>
