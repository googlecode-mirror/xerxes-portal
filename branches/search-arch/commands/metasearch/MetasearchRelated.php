<?php	
	
	/**
	 * bX recommendations
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.5.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchRelated extends Xerxes_Command_Metasearch
	{
		public function doExecute()
		{
			$configBX = $this->registry->getConfig("BX_SERVICE_URL", false, "http://recommender.service.exlibrisgroup.com/service");
			$configToken = $this->registry->getConfig("BX_TOKEN", false, "5AFBDC5E424D636257CD34476BBCA6DC");
			$configBXPassword = $this->registry->getConfig("BX_SERVICE_URL", false, "1234:");
			$configLinkResolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
			$configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
			
			if ( $configToken != "" && $configBXPassword != "" )
			{
				$open_url = $this->request->getData("//openurl_kev_co");
				
				$url = $configBX . "/recommender/openurl?token=" . $configToken . "&" . $open_url;
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_USERPWD, $configBXPassword);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				$xml = curl_exec($ch);
				
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
						
						$objImport = $objXml->importNode($record->toXML()->documentElement, true);
						$objXml->documentElement->appendChild($objImport);
						
						$strOpenURL = $record->getOpenURL($configLinkResolver, $configSID);
						
						$objOpenURL = $objXml->createElement("open_url", Xerxes_Parser::escapeXML($strOpenURL));
						$objImport->appendChild($objOpenURL);
					}
				}
				
				$this->request->addDocument($objXml);
			}
			
			return 1;
		}
	}

?>