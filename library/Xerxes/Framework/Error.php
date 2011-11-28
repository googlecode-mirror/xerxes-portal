<?php

/**
 * Displays errors and performs configued actions for uncaught exceptions
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @version $Id$
 * @package  Xerxes_Framework
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 */

class Xerxes_Framework_Error
{
	/**
	 * Do something with uncaught errors
	 */
	
	public static function handle( $e )
	{
		$request = Xerxes_Framework_Request::getInstance();
		$registry = Xerxes_Framework_Registry::getInstance();
		
		if ( $registry->getConfig( "DISPLAY_ERRORS", false, false ) )
		{
			throw $e;
		}
		
		// flag certain exception types for special handling in the xslt

		$strErrorType = get_class( $e );
		
		// might be a sub-class, reset so view will catch. 

		if ( $e instanceof PDOException )
		{
			$strErrorType = "PDOException";
		}
		
		// if this is the command line, just rethrow the error so we can see it; might
		// make this a little better formatted in the future

		if ( $request->isCommandLine() )
		{
			throw $e;
		} 
		else
		{
			// translate heading and message
			
			$lang = $request->getParam("lang");
			$labels = Xerxes_Framework_Labels::getInstance($lang);

			if ( $e instanceof Xerxes_Exception )
			{
				$heading = $e->heading();
			}			
			else
			{
				$heading = "text_error";
			}
			
			$heading = $labels->getLabel($heading);
			$message = $labels->getLabel($e->getMessage());
			
			// first output to apache error log
			
			error_log( "Xerxes error: " . $message . ": " . $e->getTraceAsString() );
			
			//set proper http response code

			$resultStatus = 500;
			
			if ( $e instanceof Xerxes_Exception_AccessDenied )
			{
				$resultStatus = 403;
			} 
			else if ( $e instanceof Xerxes_Exception_NotFound )
			{
				$resultStatus = 404;
			}
			
			header( ' ', true, $resultStatus ); // send back http status as internal server error or other specified status
			
			// for the web, we'll convert the error message to xml along with the type
			// of exception and hand display off to the error.xsl file

			$objError = new DOMDocument( );
			
			$objError->loadXML( "<error />" );

			$objMessage = $objError->createElement( "message", $message );
			$objMessage->setAttribute( "type", $strErrorType );
			$objError->documentElement->appendChild( $objMessage );
			
			$objHeading = $objError->createElement( "heading", $heading );
			$objError->documentElement->appendChild( $objHeading );
			
			// make sure we're showing the main error file
			
			$registry->setConfig("XSL_PARENT_DIRECTORY", null);
			
			
			
			// set the base url for the error.xsl file's benefit; don't want to assume that 
			// the earlier code to this effect was executed before an exception, so this is redundant
			
			$base_path = $registry->getConfig( 'BASE_WEB_PATH', false, "" );
			$this_server_name = $request->getServer( 'SERVER_NAME' );
			
			// check for a non-standard port
						
			$port = $request->getServer( 'SERVER_PORT' );
			
			if ( $port == 80 || $port == 443 )
			{
			    $port = "";
			}
			else
			{
			    $port = ":" . $port;
			}
			
			$protocol = "http://";
			
			if ( $request->getServer("HTTPS") )
			{
				$protocol = "https://";
			}
			
			$web = $protocol . $this_server_name . $port . $base_path;
			
			$objBaseURL = $objError->createElement( "base_url", $web );
			$objError->documentElement->appendChild( $objBaseURL );
			
			// if it's a db denied exception, include info on dbs. 

			if ( $e instanceof Xerxes_Exception_DatabasesDenied )
			{
				$excluded_xml = $objError->createElement( "excluded_dbs" );
				$objError->documentElement->appendChild( $excluded_xml );
				foreach ( $e->deniedDatabases() as $db )
				{
					$element = Xerxes_Helper::databaseToNodeset( $db, $request, $registry );
					$element = $objError->importNode( $element, true );
					$excluded_xml->appendChild( $element );
				}
			}
			
			// add in the request object's stuff
			

			$request_xml = $request->toXML();
			
			$imported = $objError->importNode( $request_xml->documentElement, true );
			foreach ( $imported->childNodes as $childNode )
			{
				$objError->documentElement->appendChild( $childNode );
			}
			
			if ( $request->getProperty( "format" ) == "xerxes" )
			{
				header( 'Content-type: text/xml' );
				echo $objError->saveXML();
			} 
			else
			{
				// display it to the user. Transform will pick up local
				// xsl for error page too, great.
				

				echo Xerxes_Framework_Parser::transform( $objError, "xsl/error.xsl" );
			}
		}
		
	// need to incorporate methods for doing additional actions based on the type
	// of error -- probably a config option
	

	}
}
