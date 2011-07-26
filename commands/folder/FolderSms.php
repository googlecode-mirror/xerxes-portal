<?php	
	
	/**
	 * SMS saved records
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id: FolderSMS.php 1392 2010-09-17 18:42:34Z dwalker@calstate.edu $
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderSMS extends Xerxes_Command_Folder
	{
		public function doExecute()
		{
			// make sure we got a referrer from xerxes, just in case
			
			if ( $this->request->getSession("last_page") == null )
			{
				throw new Exception("Could not verify the request");
			}
			
			// get user entered values
			
			$strPhone = $this->request->getProperty("phone");
			$strProvider = $this->request->getProperty("provider");
			$strTitle = $this->request->getProperty("title");
			$strItem = $this->request->getProperty("item");
			
			if ( $strProvider == "" )
			{
				throw new Exception("Please choose your cell phone provider");
			}			
			
			if ( $strPhone == null )
			{
				throw new Exception("Please enter a phone number");
			}
			
			// save provider in session 
			// @todo phone number too?
			
			$this->request->setSession("user_provider", $strProvider);

			// only numbers, please
			
			$strPhone = preg_replace('/\D/', "", $strPhone);
			
			// did we get 10?
			
			if ( strlen($strPhone) != 10 )
			{
				throw new Exception("Please enter a 10 digit phone number, including area code");
			}
			
			// make sure the title and item info is 160 chars or less (sms standard)
			
			$title_length = strlen($strTitle);
			$item_length = strlen($strItem);
			$total_length = $title_length + $item_length;
			
			if ( $total_length > 150 )
			{
				$strTitle = substr($strTitle,0,$total_length - $item_length - 6) . "...";
			}
			
			// message
			
			$strEmail = $strPhone . "@" . $strProvider;
			$strSubject = "library";
			$strBody = $strTitle . " / " . $strItem;
			
			// headers
			
			$headers = "";
			
			// get configuration options
			
			$configFromEmail = $this->registry->getConfig("EMAIL_FROM", false, null);
      
			// set an explcit 'from' address if configured
			
			if ( $configFromEmail != null )
			{				
				$headers .= "From: $configFromEmail \r\n";
			}

			// send the user back out, so they don't step on this again

			require($this->registry->getConfig("PATH_PARENT_DIRECTORY") . "/lib/PHPMailer/class.phpmailer.php");
			
			$mail = new PHPMailer();
			
			$mail->IsSMTP();  // telling the class to use SMTP
			$mail->Host = "coweumx01.calstate.edu:25"; // SMTP server
			
			$mail->From = $this->registry->getConfig("EMAIL_FROM", true);
			$mail->FromName = "Your saved records";
			$mail->AddAddress($strEmail);
			
			$mail->Subject = $strSubject;
			$mail->Body = $strBody;
			$mail->WordWrap = 50;
			
			if ( $mail->Send() )
			{			
				$this->request->setSession( "flash_message", "Message successfully sent" );
				$this->request->setRedirect($this->request->getSession('last_page'));
			}
			else
			{
				throw new Exception("Could not send message", 2);
			}
		}
	}

?>