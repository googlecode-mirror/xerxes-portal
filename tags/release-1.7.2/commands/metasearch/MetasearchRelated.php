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
				$configBX = $this->registry->getConfig("BX_SERVICE_URL", false, "http://recommender.service.exlibrisgroup.com/service");
				$configLinkResolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
				$configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
				
				$open_url = $this->request->getData("//openurl_kev_co");
				
				$url = $configBX . "/recommender/openurl?token=" . $configToken . "&" . $open_url;
				
				$xml = Xerxes_Framework_Parser::request($url);

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