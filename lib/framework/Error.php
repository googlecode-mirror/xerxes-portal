<?php

/**
 * Displays errors and performs configued actions for uncaught exceptions
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @version 1.1
 * @package  Xerxes_Framework
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 */

class Xerxes_Framework_Error
{
	/**
	 * Do something with uncaught errors
	 */
	
	public static function handle($e, Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		if ( $objRegistry->getConfig( "DISPLAY_ERRORS", false, false ) )
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

		if ( $objRequest->isCommandLine() )
		{
			throw $e;
		}
		else
		{
			$resultStatus = 500;
			
			if ( $e instanceof Xerxes_AccessDeniedException )
			{
				$resultStatus = 403;
			}
			
			header( ' ', true, $resultStatus ); // send back http status as internal server error
			
			// for the web, we'll convert the error message to xml along with the type
			// of exception and hand display off to the error.xsl file

			$objError = new DOMDocument( );
			
			$objError->loadXML( "<error />" );
			
			$objMessage = $objError->createElement( "message", $e->getMessage() );
			$objMessage->setAttribute( "type", $strErrorType );
			$objError->documentElement->appendChild( $objMessage );
			
			$heading = "Sorry, there was an error";
			
			if ( $e instanceof Xerxes_Exception )
			{
				$heading = $e->heading();
			}
			
			$objHeading = $objError->createElement( "heading", $heading );
			$objError->documentElement->appendChild( $objHeading );
			
			// set the base url for the error.xsl file's benefit; don't want to assume that 
			// the earlier code to this effect was executed before an exception, so this is redundant

			$objBaseURL = $objError->createElement( "base_url", $objRegistry->getConfig( 'BASE_WEB_PATH', true ) );
			$objError->documentElement->appendChild( $objBaseURL );
			
			// if it's a db denied exception, include info on dbs. 
			
			if ( $e instanceof Xerxes_DatabasesDeniedException )
			{
				$excluded_xml = $objError->createElement( "excluded_dbs" );
				$objError->documentElement->appendChild( $excluded_xml );
				foreach ( $e->deniedDatabases() as $db )
				{
					$element = Xerxes_Helper::databaseToNodeset( $db, $objRequest, $objRegistry );
					$element = $objError->importNode( $element, true );
					$excluded_xml->appendChild( $element );
				}
			}
			
			// add in the request object's stuff
			
			$request_xml = $objRequest->toXML();
			
			$imported = $objError->importNode( $request_xml->documentElement, true );
			foreach ( $imported->childNodes as $childNode )
			{
				$objError->documentElement->appendChild( $childNode );
			}
			
			if ( $objRequest->getProperty( "format" ) == "xerxes" )
			{
				header( 'Content-type: text/xml' );
				echo $objError->saveXML();
			} 
			else
			{
				// display it to the user. Transform will pick up local
				// xsl for error page too, great.
				
				echo Xerxes_Parser::transform( $objError, "xsl/error.xsl" );
			}
		}
		
	// need to incorporate methods for doing additional actions based on the type
	// of error -- probably a config option
	

	}
}

?>