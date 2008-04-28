<?php	
	
	class Xerxes_Command_HelperNavbar extends Xerxes_Command_Helper
	{
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
      $objXml = new DOMDocument();
        $objXml->loadXML("<navbar />");
        
        # saved records link
        $saved_records = $objXml->createElement("element");
        $saved_records->setAttribute("id", "saved_records");
        $url = $objXml->createElement('url', 
                      $objRequest->url_for( array(
                        "base" => "folder",
                        "return" => $objRequest->getServer("REQUEST_URI"))));
        $saved_records->appendChild( $url );
        $objXml->documentElement->appendChild($saved_records);
        
        #login or logout, just include appropriate one. 

        $element_id;
        $action;
        if ( $objRequest->hasLoggedInUser() )
        {
          $element_id = "logout";
          $action = "logout";
        }
        else {
          $element_id = "login";
          $action = "login";
        }
       
      # login or logout
      $element = $objXml->createElement("element");
      $element->setAttribute("id", $element_id);
      $url = $objXml->createElement('url', 
                  $objRequest->url_for( array(
                    "base" => "authenticate",
                    "action" => $action,
                    "return" => $objRequest->getServer("REQUEST_URI"))));
      $element->appendChild( $url );
      $objXml->documentElement->appendChild($element);        
         
        
      $objRequest->addDocument( $objXml );
      
			return 1;
		}
	}	
?>
