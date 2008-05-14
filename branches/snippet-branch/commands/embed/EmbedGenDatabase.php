<?php	
	
  
	/**
	 * Little helper controller for the embed function
	 * 
	 */
	
	class Xerxes_Command_EmbedGenDatabase extends Xerxes_Command_Embed
	{
    
    
		/**
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
       //Define url params for display action, and call helper, that
       // does all the real work. 
       $url_params = array( "base" => "embed",
                           "action" => "database",
                           "id" => $objRequest->getProperty("id"));
       //The meet of it in this helper method in superclass!
       $this->doExecuteHelper($objRequest, $objRegistry, $url_params);
       return 1;
		}
	}	
?>