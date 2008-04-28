<?php	
	
	/**
	 * Email saved records
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderEmail extends Xerxes_Command_Folder
	{
		/**
		 * Email a set of records to a user; Request params include 'email'
		 * the send-to email address; 'subject' the subjectline of the email; 
		 * 'notes' any user-supplied notes; 'username' the active username. Requires
		 * that the FolderResults command be previously run.
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int		status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// ensure this is the same user
			
			$strRedirect = $this->enforceUsername($objRequest, $objRegistry);
			
			if ( $strRedirect != null )
			{
				$objRequest->setRedirect($strRedirect);
				return 1;
			}
			
			$strEmail = "";			// email address
			$strSubject = "";		// subject entered by the user
			$strBody = "";			// body of the email
			$strNotes = "";			// notes entered by the user
			$strRecords = "";		// results in citation style
			$headers = "";			// to specify html as the format of the email
			
			// get the records from the previous results command
			
			$objXml = $objRequest->toXML();
			
			// get user entered values
			
			$strEmail = $objRequest->getProperty("email");
			$strSubject = $objRequest->getProperty("subject");
			$strNotes = $objRequest->getProperty("notes");
			$strUsername = $objRequest->getSession("username");			
			
			// get configuration options
			
			$configFromEmail = $objRegistry->getConfig("EMAIL_FROM", false, null);
			
			if ( $strEmail == null ) throw new Exception("Please enter an email address", 1);
			
			// transform the documents to a basic style for now
			// will give them citation style options in the future
			
			$strRecords = Xerxes_Parser::transform($objXml, "xsl/citation/basic.xsl");
			
			// add notes and records to body
			
			$strBody = $strNotes ."\r\n\r\n";
			$strBody .= $strRecords;
			
			// set an explcit 'from' address if configured
			
			if ( $configFromEmail != null )
			{				
				$headers .= "From: $configFromEmail \r\n";
			}
			
			// send the user back out, so they don't step on this again
			
			if ( mail( $strEmail, $strSubject, $strBody, $headers) )
			{
				// send the user back out, so they don't step on this again
			
				$arrParams = array(
					"base" => "folder",
					"action" => "output_email",
					"username" => $strUsername,
					"message" =>  "done"
				);
				
				$url = $objRequest->url_for($arrParams);
				$objRequest->setRedirect($url);	
				
				return 1;
			}
			else
			{
				throw new Exception("Could not send email", 2);
			}
		}
	}

?>