<?php	
	
  
	/**
	 * Little helper controller for the embed function
	 * 
	 */
	
	class Xerxes_Command_EmbedGenSubject extends Xerxes_Command_Embed
	{
    
    
		/**
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
       //Define url params for subject display action, and call helper.
       $embed_url_params = array( "base" => "embed",
                           "action" => "subject",
                           "subject" => $objRequest->getProperty("subject"));
       
       //Direct to resource without embed
       $direct_url_params = array("base" => "databases",
                                  "action" => "subject",
                                  "subject" => $objRequest->getProperty("subject"));
       
       //The meet of it in this helper method in superclass!
       $this->doExecuteHelper($objRequest, $objRegistry, $embed_url_params, $direct_url_params);
       return 1;
		}
	}	
?>