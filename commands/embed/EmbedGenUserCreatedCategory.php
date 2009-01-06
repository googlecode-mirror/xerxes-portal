<?php	
	
  
	/**
	 * Little helper controller for the embed function
	 * 
	 */
	
	class Xerxes_Command_EmbedGenUserCreatedCategory extends Xerxes_Command_Embed
	{
    
    
		/**
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
      
      //We can only embed published collections, private ones are not
      //embeddable for so many reasons. 
      $strPublished  = $objRequest->getData('/*/category/@published');
      if ( $strPublished != '1') {        
        throw new Xerxes_AccessDeniedException("Your collection must be published in order to use the 'embed' feature");
      }
      
       //Define url params for subject display action, and call helper.
       $embed_url_params = array( "base" => "collections",
                           "action" => "embed",
                           "username" => $objRequest->getProperty("username"),
                           "subject" => $objRequest->getProperty("subject"));
       
       //Direct to resource without embed
       $direct_url_params = array("base" => "collections",
                                  "action" => "subject",
                                  "username" => $objRequest->getProperty("username"),
                                  "subject" => $objRequest->getProperty("subject"));
       
       //The meet of it in this helper method in superclass!
       $this->doExecuteHelper($objRequest, $objRegistry, $embed_url_params, $direct_url_params);
       return 1;
		}
	}	
?>