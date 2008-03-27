<?php

	/**
	 * Restict access to a portion of an application
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package Xerxes
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	class Xerxes_Framework_Restrict
	{
		private $strIPRange = "";			// set of local ip ranges
		private $strAppName = "";			// name of the application
		private $strReturn = "";			// authentication url of the application
		
		/**
		 * Constructor
		 *
		 * @param string $configIP				comma-delimeted list of ip ranges
		 * @param string $configAppName			unique application name 
		 * @param base address $configBaseURL	base url of the application
		 * @param string $configReturn			uri to the login page
		 */
		
		public function __construct( $configIP, $configAppName, $configBaseURL, $configReturn )
		{
			$this->strIPRange = $configIP;
			$this->strAppName = $configAppName;
			
			// if the return url has a querystring mark in it, then append
			// return url to other params, otherwise it is sole param
			
			if ( strstr($configReturn, "?") )
			{
				$this->strReturn = "$configBaseURL/$configReturn&return=";
			}
			else
			{
				$this->strReturn = "$configBaseURL/$configReturn?return=";
			}
		}
		
		/**
		 * Limit access to users with a named login, otherwise redirect to login page
		 *
		 * @param Xerxes_Framework_Request $objRequest	will check properties:
		 * 	- username [session] = stored username in session
		 * 	- application [session] = to ensure the application's name matched stored session value
		 * 	- role [session] = to ensure the user's role is not local
		 */

		public function checkLogin(Xerxes_Framework_Request $objRequest)
		{
			if ( $objRequest->getSession("username") == null || $objRequest->getSession("application") != $this->strAppName || 
				 $objRequest->getSession("role") == "local" )
			{
				// redirect to authentication page
					
				header( "Location: " . $this->strReturn . urlencode( $objRequest->getServer('REQUEST_URI') ) );
				exit;
			}
		}

		/**
		 * Limit access to users within the local ip range, assigning local users a temporary
		 * login id, and redirecting non-local users out to login page
		 *
		 * @param Xerxes_Framework_Request $objRequest	will check properties:
		 * 	- username [session] = stored username in session
		 * 	- application [session] = to ensure the application's name matched stored session value
		 */		
		
		public function checkIP(Xerxes_Framework_Request $objRequest)
		{
			if ( $objRequest->getSession("username") == null || $objRequest->getSession("application") != $this->strAppName )
			{
				// check to see if user is coming from campus range
				
				$bolLocal = $this->isLocal($objRequest->getServer('REMOTE_ADDR'), $this->strIPRange);
				
				if ( $bolLocal == true )
				{
					// temporarily authenticate on-campus users
					
					$_SESSION["username"] = "local@" . session_id();
					$_SESSION["role"] = "local";
					$_SESSION["application"] = $this->strAppName;
				}
				else
				{
					// redirect to authentication page
					
					header( "Location: " . $this->strReturn . urlencode( $objRequest->getServer('REQUEST_URI') ) );
					exit;
					
				}
			}
		}
		
		/**
		 * Strips periods and pads the subnets of an IP address to three spaces, 
		 * e.g., 144.37.1.23 = 144037001023, to make it easier to see if a remote 
		 * user's IP falls within a range
		 *
		 * @param string $strOriginal		original ip address
		 * @return string					address normalized with extra zeros
		 */
			
		private function normalizeAddress ( $strOriginal )
		{
			$strNormalized = "";
			$arrAddress = explode(".", $strOriginal );
			
			foreach ( $arrAddress as $subnet )
			{
				$strNormalized .= str_pad($subnet, 3, "0", STR_PAD_LEFT);
			}
			
			return $strNormalized;
		}
		
		/**
		 * Is the ip address within the supplied local ip range(s)
		 *
		 * @param string $strAddress	ip address
		 * @param string $strRanges		ip ranges
		 * @return bool					true if in range, otherwise false
		 */
		
		private function isLocal($strAddress, $strRanges)
		{
			$bolLocal = false;
			
			// normalize the remote address
				
			$iRemoteAddress = $this->normalizeAddress( $strAddress );
			
			// get the local campus ip range from config
				
			$arrRange = array();
			$arrRange = explode(",", $strRanges);
				
			// loop through ranges -- can be more than one
				
			foreach ( $arrRange as $range )
			{
				$range = str_replace(" ", "", $range);
				$iStart = null;
				$iEnd = null;
										
				// normalize the campus range
				
				if ( strpos($range, "-") !== false )
				{
					// range expressed with start and stop addresses
					
					$arrLocalRange = explode("-", $range );
					
					$iStart = $this->normalizeAddress( $arrLocalRange[0] );
					$iEnd = $this->normalizeAddress( $arrLocalRange[1] );
				}
				else
				{
					// range expressed with wildcards
					
					$strStart = str_replace("*", "000", $range );
					$strEnd =  str_replace("*", "255", $range );
					
					$iStart = $this->normalizeAddress( $strStart );
					$iEnd = $this->normalizeAddress( $strEnd );
				
				}
				
				// see if remote address falls in between the campus range
				
				if ( $iRemoteAddress >= $iStart && $iRemoteAddress <= $iEnd )
				{
					$bolLocal = true;
				}
			}
			
			return $bolLocal;
		}
	}
		
?>