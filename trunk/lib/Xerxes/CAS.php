<?php

	/**
	 * Parses a CAS validation response
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package Xerxes
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	class Xerxes_CAS
	{
		private $strUsername = "";		// username returned by validation request
		
		/**
		 * Return the extracted username
		 *
		 * @return string
		 */
		
		public function getUsername() { return $this->strUsername; }
			
		/**
		 * Parses a validation response from a CAS server to see if the returning CAS request is valid
		 *
		 * @param string $strResults		xml or plain text response from cas server
		 * @param string $strVersion		version of the cas response, either '1.0' or '2.0'
		 * @return bool						true if valid, false otherwise
		 * @exception 						throws exception if cannot parse response or invalid version
		 */
		
		public function isValid($strResults, $strVersion)
		{
			$iVersion = floor((int) $strVersion);
			
			$bolValid = false;
			
			if ( $iVersion == 1 )
			{
				$arrMessage = explode("\n", $strResults);
				
				if ( count($arrMessage) >= 2 )
				{
					if ( $arrMessage[0] == "yes")
					{
						$bolValid = true;
						$this->strUsername = $arrMessage[1];
					}
				}
				else
				{
					throw new Exception("Could not parse CAS validation response.");
				}
			}	
			elseif ( $iVersion == 2 || $iVersion == 3)
			{
				// check for username, else there was an error
				
				$objXml = new DOMDocument();
				$objXml->loadXML($strResults);
				
				$strCasNamespace = "http://www.yale.edu/tp/cas";
				
				$objUser = $objXml->getElementsByTagNameNS($strCasNamespace, "user")->item(0);
				$objFailure = $objXml->getElementsByTagNameNS($strCasNamespace, "authenticationFailure")->item(0);
				
				if ( $objUser != null )
				{
					if ( $objUser->nodeValue != "" )
					{
						$bolValid = true;
					
						$this->strUsername = $objUser->nodeValue;
					}
					else
					{
						throw new Exception("CAS validation response missing username value");
					}
				}
				elseif ( $objFailure != null )
				{
					// see if error, rather than failed authentication
					
					if ( $objFailure->getAttribute("code") == "INVALID_REQUEST")
					{
						throw new Exception("Invalid request to CAS server: " . $objFailure->nodeValue);
					}
				}
				else
				{
					throw new Exception("Could not parse CAS validation response.");
				}
			}
			else
			{
				throw new Exception("Unsupported CAS version.");
			}
			
			return $bolValid;
		}

	}


?>