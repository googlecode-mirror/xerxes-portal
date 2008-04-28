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
			if ( $objRegistry->getConfig("DISPLAY_ERRORS", false, false) )
			{
				throw $e;
			}
			
			// flag certain exception types for special handling in the xslt

			$strErrorType = "Exception";
			
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
				header(' ', true, 500);		// send back http status as internal server error
				
				// for the web, we'll convert the error message to xml along with the type
				// of exception and hand display off to the error.xsl file
				
				$objError = new DOMDocument();
				$objError->loadXML("<error />");
				
				$objMessage = $objError->createElement("message", $e->getMessage());
				$objMessage->setAttribute("type", $strErrorType);
				$objError->documentElement->appendChild($objMessage);
				
				// set the base url for the error.xsl file's benefit; don't want to assume that 
				// the earlier code to this effect was executed before an exception, so this is redundant
				
				$objBaseURL = $objError->createElement("base_url", $objRegistry->getConfig('BASE_WEB_PATH', true));
				$objError->documentElement->appendChild($objBaseURL);
				
				
				// add in the request object
				
				$objXml = new DOMDocument();
				$objXml = $objRequest->toXML(true);
				$objRequestXml = $objXml->getElementsByTagName("request")->item(0);
				
				if ( $objRequestXml != null )
				{
					$objImport = $objError->importNode($objRequestXml, true);
					$objError->documentElement->appendChild($objImport);
				}
				
				
				// display it to the user
				
				echo Xerxes_Parser::transform($objError, "xsl/error.xsl");
			}
			
			
			// need toincorporate methods for doing additional actions based on the type
			// of error -- probably a config option
	
		}
	}

?>