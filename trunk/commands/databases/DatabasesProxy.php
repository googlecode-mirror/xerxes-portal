<?php	
	
	/**
	 * Determines if a link should be proxied and the resulting URL that would result from that.
	 * Can also be used with a "database" request property to link to a database; will be 
	 * redirected to native home url for that db, either with proxy or not as appropriate. 
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_DatabasesProxy extends Xerxes_Command_Databases
	{
		public function doExecute()
		{
			$bolProxy = false;				// whether to proxy or not
			$strFinal = "";					// final link to send back
			$strConstructPattern = "";		// construct link pattern
			
			// values from the url request
			
			$strMetalibID = $this->request->getProperty("database"); // metalib id
			$strUrl = $this->request->getProperty("url"); // a direct url to a site, typically to full text
			$arrParams = $this->request->getProperty("param", true); // a series of paramaters that we'll use to constuct a full-text linke
			
			// configuration settings
			
			$strProxyServer = $this->registry->getConfig("PROXY_SERVER", false);

			// if the database id is included, this could have come in off the 
			// metasearch page, so we need to see if it should be proxied or not
			// based on database subscription info
				
			if ( $strMetalibID != null )
			{
				$objDatabase = new Xerxes_DataMap();
				$objDatabaseData = $objDatabase->getDatabase($strMetalibID);
				
				if ( $objDatabaseData == null )
				{
					throw new Exception("Couldn't find database '$strMetalibID'");
				}
				
				// databases marked as subscription should be proxied
					
				if ( $objDatabaseData->subscription == "1" )
				{
					$bolProxy = true;
				}
				
				// override the behavior if proxy flag specifically set
				
				if ( $objDatabaseData->proxy != null )
				{
					if ( $objDatabaseData->proxy == 1 )
					{
						$bolProxy = true;
					}
					elseif ( $objDatabaseData->proxy == 0 )
					{
						$bolProxy = false;
					}
				}
				
				$strConstructPattern = $objDatabaseData->link_native_record;        

				// if no url or construct paramaters were supplied, then this came
				// from the databases page as a 'short' url (the preferred now)
				// and so we'll just take the database's native link
				
				if ( $arrParams == null && $strUrl == null )
				{
            		$strUrl = $objDatabaseData->link_native_home;
				} 				
			}
			else
			{
				// the request is to proxy this no matter what; this is largely deprecated
				// in the system as of 1.3, but could be resurrected for some purpose?
				
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
			elseif ( stristr($strUrl, $strProxyServer) )
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
					
					if ( preg_match("/http[s]{0,1}:\/\/([^\/]*)\/{0,1}(.*)/", $strUrl, $arrMatch) != 0 )
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
					else
					{
						throw new Exception("could not construct WAM link");
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
			
			$this->request->setRedirect($strFinal);
			
			return 1;
		}
	}	
?>
