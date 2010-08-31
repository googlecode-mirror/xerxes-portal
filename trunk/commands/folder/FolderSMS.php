<?php	
	
	/**
	 * SMS saved records
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderSMS extends Xerxes_Command_Folder
	{
		public function doExecute()
		{
			// get the records from the previous results command
			
			$objXml = $this->request->toXML();
			
			// get user entered values
			
			$strProvider = $this->request->getProperty("provider");
			
			$strPhone = $this->request->getProperty("phone");
			if ( $strPhone == null ) throw new Exception("Please enter a phone number");
			$strPhone = preg_replace('/\D/', "", $strPhone);
			
			$headers = "";
			$strEmail = $strPhone . "@" . $strProvider;
			$strSubject = "test";
			
			// get configuration options
			
			$configFromEmail = $this->registry->getConfig("EMAIL_FROM", false, null);
			
			// transform the documents to a basic style for now
			// will give them citation style options in the future
      
			$strRecords = Xerxes_Framework_Parser::transform($objXml, "xsl/citation/sms.xsl");
			
			$strBody = $strRecords;
      
			// set an explcit 'from' address if configured
			
			if ( $configFromEmail != null )
			{				
				$headers .= "From: $configFromEmail \r\n";
			}

			// send the user back out, so they don't step on this again
			
			if ( mail( $strEmail, $strSubject, $strBody, $headers) )
			{
				echo "It worked!"; exit;
			}
			else
			{
				throw new Exception("Could not send email", 2);
			}
		}
	}

?>