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
	private $strIPRange = ""; // set of local ip ranges
	private $strAppName = ""; // name of the application
	private $strReturn = ""; // authentication url of the application

	/**
	 * Constructor
	 *
	 * @param string $configIP				comma-delimeted list of ip ranges
	 * @param string $configAppName			unique application name. No longer used at all, not neccesary.  
	 * @param base address $configBaseURL	base url of the application
	 * @param string $configReturn			uri to the login page
	 */
	
	public function __construct($configIP, $configAppName, $configAuthPage)
	{
		$this->strIPRange = $configIP;
		$this->strAppName = $configAppName;
		
		// if the return url has a querystring mark in it, then append
		// return url to other params, otherwise it is sole param

		if ( strstr( $configAuthPage, "?" ) )
		{
			$this->strReturn = "$configAuthPage&return=";
		}
		else
		{
			$this->strReturn = "$configAuthPage?return=";
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
		if (! self::isAuthenticatedUser( $objRequest ) )
		{
			// redirect to authentication page

			header( "Location: " . $this->strReturn . urlencode( $objRequest->getServer( 'REQUEST_URI' ) ) );
			exit();
		}
	}
	
	// session has a logged in authenticated user. not "guest" or "local" role, 
	// both of which imply a temporary session, not an authenticated user.
	
	public static function isAuthenticatedUser(Xerxes_Framework_Request $objRequest)
	{
		return ($objRequest->getSession( "username" ) != null && $objRequest->getSession( "role" ) != "local" && $objRequest->getSession( "role" ) != "guest");
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
		if ( $objRequest->getSession( "username" ) == null || $objRequest->getSession( "application" ) != $this->strAppName )
		{
			// check to see if user is coming from campus range
			// adjust here to check for reverse-proxy as well
			
			$users_ip_address = "";
			
			if ( $objRequest->getServer('HTTP_X_FORWARDED_FOR') != null )
			{
				$users_ip_address = $objRequest->getServer('HTTP_X_FORWARDED_FOR');
			}
			else
			{
				$users_ip_address = $objRequest->getServer( 'REMOTE_ADDR' );
			}

			$bolLocal = self::isIpAddrInRanges( $users_ip_address, $this->strIPRange );
			
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

				header( "Location: " . $this->strReturn . urlencode( $objRequest->getServer( 'REQUEST_URI' ) ) );
				exit();
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
	
	private static function normalizeAddress($strOriginal)
	{
		$strNormalized = "";
		$arrAddress = explode( ".", $strOriginal );
		
		foreach ( $arrAddress as $subnet )
		{
			$strNormalized .= str_pad( $subnet, 3, "0", STR_PAD_LEFT );
		}
		
		return $strNormalized;
	}
	
	/**
	 * Is the ip address within the supplied ip range(s)
	 * For syntax/formatting of an ip range string, see config.xml.
	 * Basically, it's comma seperated ranges, where each range can use
	 * wildcard (*) or hyphen to seperate endpoints. 
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

		$arrRange = array ( );
		$arrRange = explode( ",", $strRanges );
		
		// loop through ranges -- can be more than one

		foreach ( $arrRange as $range )
		{
			$range = str_replace( " ", "", $range );
			$iStart = null;
			$iEnd = null;
			
			// normalize the campus range

			if ( strpos( $range, "-" ) !== false )
			{
				// range expressed with start and stop addresses

				$arrLocalRange = explode( "-", $range );
				
				$iStart = self::normalizeAddress( $arrLocalRange[0] );
				$iEnd = self::normalizeAddress( $arrLocalRange[1] );
			}
			else
			{
				// range expressed with wildcards

				$strStart = str_replace( "*", "000", $range );
				$strEnd = str_replace( "*", "255", $range );
				
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

}

?>