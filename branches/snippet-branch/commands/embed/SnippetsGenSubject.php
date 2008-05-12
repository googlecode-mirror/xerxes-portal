<?php	
	
  /**
   *  Kind of lame and un-objected oriented, but we need to define a global
   *  function so we can call it from within the XSLT view, to load
   *  the embedable content as a sample. 
   */
   function getEmbedContent($foo) {         
     $output = '';
     $handle = fopen($foo, 'r');
     while(!feof($handle)) {
      //read file line by line into variable
      $output = $output . fgets($handle, 4096);
     }
     fclose ($handle);
     return $output; 
   }

	/**
	 * Little helper controller for the embed function
	 * 
	 */
	
	class Xerxes_Command_SnippetsGenSubject extends Xerxes_Command_Snippets
	{
    
    
		/**
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
            
      // Default embed css to false, because it's awful. 
      if ( $objRequest->getProperty("disp_embed_css") == "") {
        $objRequest->setProperty("disp_embed_css", "false");
      }
      
			$objXml = new DOMDOcument();
			$objXml->loadXML("<embed_info />");

      $properties = array_keys($objRequest->getAllProperties());
      $display_properties = array();
      foreach ($properties as $p) {
        if (substr($p,0,5) == 'disp_' ) {
          array_push($display_properties, $p); 
        }
      }
      $action_map = $this->generator_actions();
      $url_params = array( "base" => "snippets",
                         "action" => "subject",
                         "gen_full_urls" => "true",
                         "subject" => $objRequest->getProperty("subject"));;
      
      //Base embedded action url
      $raw_embedded_action_url = $objRequest->url_for( $url_params, true);
      if ( strpos($raw_embedded_action_url, '?') == 0) {
        $raw_embedded_action_url .= "?";
      }
      $objXml->documentElement->appendChild( $objXml->createElement("raw_embedded_action_url", $raw_embedded_action_url));      
      $objRequest->addDocument( $objXml );
      
      //Direct embed url
      $url_params["disp_embed"] = "true";
      foreach ( $display_properties as $p ) {
        $url_params[$p] = $objRequest->getProperty($p);
      }
      $embed_ssi_url = $objRequest->url_for( $url_params , true);              
      
      $objXml->documentElement->appendChild( $objXml->createElement("embed_direct_url", $embed_ssi_url));
      
      //Now the JS snippet url
      $url_params["disp_embed_js"] = "true";
      $embed_js_call_url = $objRequest->url_for( $url_params, true);
      $objXml->documentElement->appendChild( $objXml->createElement("embed_js_call_url", $embed_js_call_url));      
      $objRequest->addDocument( $objXml );
      
      return 1;
		}
	}	
?>