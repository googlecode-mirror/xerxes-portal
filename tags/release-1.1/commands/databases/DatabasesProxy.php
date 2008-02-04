<?php	
	
	/**
	 * Determines if a link should be proxied and the resulting URL that would result from that
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_DatabasesProxy extends Xerxes_Command_Databases
	{
		/**
		 * Look-up the need to proxy a full-text link, Request params include
		 * 'database' the metalib id of the database, 'url' the url to proxy
		 * or 'param' a repeatable set of paramaters from which to construct a 
		 * full-text link from a url full-text pattern
		 * 
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int	status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$bolProxy = false;				// whether to proxy or not
			$strFinal = "";					// final link to send back
			$strConstructPattern = "";		// construct link pattern
			
			// values from the url request
			
			$strMetalibID = $objRequest->getProperty("database");
			$strUrl = $objRequest->getProperty("url");
			$arrParams = $objRequest->getProperty("param", true);
			
			// configuration settings
			
			$strProxyServer = $objRegistry->getConfig("PROXY_SERVER", false);

			// if the database id is included, this is a metasearch proxy
			// request and we need to see if it should be proxied or not
			// based on database subscription info
				
			if ( $strMetalibID != null )
			{
				$objDatabase = new Xerxes_DataMap();
				$objDatabaseData = $objDatabase->getDatabase($strMetalibID);
				
				if ( $objDatabaseData != null )
				{
					if ( $objDatabaseData->subscription == "1" ) $bolProxy = true;
					$strConstructPattern = $objDatabaseData->link_native_record;
				}
			}
			else
			{
				// this came straight-up off the databse page, so proxy
				$bolProxy = true;
			}
			
			// if this is a construct request, we will use the metalib link_native_holding
			// pattern to create the link
			
			if ( $arrParams != null)
			{
				if ( $strMetalibID == null )
				{
					throw new Exception("Construct links require a Metalib ID");
				}
				elseif ( $strConstructPattern == null )
				{
					throw new Exception("Could not construct link to full-text");
				}
				else
				{
					// paramaters come in a series of key=value pairs, so need to split them
					// out and run the field key against the pattern and replace it with value
					
					$strUrl = $strConstructPattern;
					
					foreach ( $arrParams as $strParam )
					{
						$arrParamElements = explode("=", $strParam );
						
						if( count($arrParamElements) == 2)
						{
							$strUrl = str_replace("$" . $arrParamElements[0], $arrParamElements[1], $strUrl);
						}
					}
				}
			}
			
			// make sure the link doesn't include the proxy
			// server prefix already
			
			if ( preg_match("/http:\/\/[0-9]{1,3}-.*/", $strUrl) != 0 )
			{
				// WAM proxy: this is kind of a rough estimate of a WAM-style
				// proxy link, but I think sufficient for our purposes?
				
				$bolProxy = false;
			}
			elseif ( strstr($strUrl, $strProxyServer) )
			{
				// EZProxy
				
				$bolProxy = false;
			}
			
			// finally, if the proxy server entry is blank, then no proxy available
			
			if ( $strProxyServer == "" )
			{
				$bolProxy = false;
			}
			
			// if we need to proxy, prefix the proxy server url to the full-text
			// or database link and be done with it!
			
			if ( $bolProxy == true )
			{
				// if WAM proxy, take the base url and port out and 'prefix';
				// otherwise we only support EZPRoxy, so cool to take as else ?
				
				if ( strstr($strProxyServer, '{WAM}') )
				{
					$arrMatch = array();
					
					if ( preg_match("/http:\/\/([^\/]*)\/{0,1}(.*)/", $strUrl, $arrMatch) != 0 )
					{
						$strPort = "0";
						$arrPort = array();
						
						// port specifically included
						
						if ( preg_match("/:([0-9]{2,5})/", $arrMatch[1], $arrPort) != 0 )
						{
							if ( $arrPort[1] != "80") { $strPort = $arrPort[1]; }
							$arrMatch[1] = str_replace($arrPort[0], "", $arrMatch[1]);
						}
						
						$strBase = str_replace("{WAM}", $strPort . "-" . $arrMatch[1], $strProxyServer);
						
						$strFinal =  $strBase . "/" . $arrMatch[2];
					}
				}
				else
				{
					// check if this is using EZProxy qurl param, in which case urlencode that mo-fo
					
					if ( strstr($strProxyServer, "qurl=") )
					{
						$strUrl = urlencode($strUrl);
					}
					
					$strFinal = $strProxyServer . $strUrl;
				}
			}
			else 
			{
				// just send it along straight-up
				
				$strFinal = $strUrl;
			}
			
			$objRequest->setRedirect($strFinal);
			
			return 1;
		}
	}	
?>