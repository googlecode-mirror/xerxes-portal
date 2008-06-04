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
			if (self::isAuthenticatedUser($objRequest, $this->strAppName));
			{
				// redirect to authentication page
					
				header( "Location: " . $this->strReturn . urlencode( $objRequest->getServer('REQUEST_URI') ) );
				exit;
			}
		}

    // Session has a logged in authenticated user. Not "guest" or "local" role, // both of which imply a temporary session, not an authenticated user. 
    public static function isAuthenticatedUser(Xerxes_Framework_Request $objRequest) {
      $objRegistry =  Xerxes_Framework_Registry::getInstance(); 
      $application = $objRegistry->getConfig("BASE_WEB_PATH");      
                  
      return ( $objRequest->getSession("username") != null && $objRequest->getSession("application") == $application && 
				 $objRequest->getSession("role") != "local" &&
         $objRequest->getSession("role") != "guest" );
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
				
				$bolLocal = self::isIpAddrInRanges($objRequest->getServer('REMOTE_ADDR'), $this->strIPRange);
				
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
			
		private static function normalizeAddress ( $strOriginal )
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
		 * Is the ip address within the supplied ip range(s)
     * For syntax/formatting of an ip range string, see config.xml.
     * Basically, it's comma seperated ranges, where each range can use
     * wildcard (*) and hyphen to seperate endpoints. 
		 *
		 * @param string $strAddress	ip address
		 * @param string $strRanges		ip ranges
		 * @return bool					true if in range, otherwise false
		 */
		
		public static function isIpAddrInRanges($strAddress, $strRanges)
		{
			$bolLocal = false;
			
			// normalize the remote address
				
			$iRemoteAddress = self::normalizeAddress( $strAddress );
			
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
					
					$iStart = self::normalizeAddress( $arrLocalRange[0] );
					$iEnd = self::normalizeAddress( $arrLocalRange[1] );
				}
				else
				{
					// range expressed with wildcards
					
					$strStart = str_replace("*", "000", $range );
					$strEnd =  str_replace("*", "255", $range );
					
					$iStart = self::normalizeAddress( $strStart );
					$iEnd = self::normalizeAddress( $strEnd );
				
				}
				
				// see if remote address falls in between the campus range
				
				if ( $iRemoteAddress >= $iStart && $iRemoteAddress <= $iEnd )
				{
					$bolLocal = true;
				}
			}
			
			return $bolLocal;
		}
    
    /* Returns true or throws a Xerxes_DatabasesDeniedException 
      Array of Xerxes_Data_Database
    
    */
    public static function checkDbListSearchableByUser(Array $dbList, $objRequest, $objRegistry) {
      $deniedList = array();
      foreach ($dbList as $db ) {
        if (! self::dbSearchableForUser($db, $objRequest, $objRegistry)) {
          $deniedList[] = $db;
        }
      }
      if ( count($deniedList) > 0) {
         $e = new Xerxes_DatabasesDeniedException();
         $e->setDeniedDatabases( $deniedList );
         throw $e;
      }
      else {
        return true;
      }
    }
    
    
    public static function dbSearchableForUser(Xerxes_Data_Database $db, $objRequest, $objRegistry) {
               
        if (! $db->searchable) {
          //nobody can search it!
          $allowed = false;
        }
        elseif ( $db->guest_access ) {
          //anyone can search it!
          $allowed = true;
        }
        elseif ( count($db->group_restrictions) > 0) {
          // They have to be authenticated, and in a group that is included
          // in the restrictions, OR in an IP address associated with a
          // restricted group. 
          $allowed = ( Xerxes_Framework_Restrict::isAuthenticatedUser($objRequest) &&
          array_intersect($_SESSION["user_groups"], $db->group_restrictions));
          if ( ! $allowed ) {
            // Not by virtue of a login, but now check for IP address
            $ranges = array();
            foreach ( $db->group_restrictions as $group ) {
              $ranges[] = $objRegistry->getGroupLocalIpRanges($group); 
            }
            $allowed = self::isIpAddrInRanges($objRequest->getServer('REMOTE_ADDR') , implode(",",$ranges));
          }
        }
        else {          
          //Ordinary generally restricted resource. They need to be an authenticated user, or in the local ip range. 
          $allowed =  Xerxes_Framework_Restrict::isAuthenticatedUser($objRequest) || self::isIpAddrInRanges($objRequest->getServer('REMOTE_ADDR'), $objRegistry->getConfig("local_ip_range"));          
        }
        return $allowed;
    }    
}


?>