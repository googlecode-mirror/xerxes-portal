<?php

/**
 * Base class for common functions for my saved records commands
 *
 */


abstract class Xerxes_Command_Folder extends Xerxes_Framework_Command
{	
  
  
  
  /**
		 * Adds export links and information to XML. Subclasses should over-ride
     * and call super. 
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
	*/
		
	  
	/**
	 * Ensure that the username stored in session matches the one being requested by url params
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return mixed		string with a redirect url, null otherwise
	 */
	
	function enforceUsername( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
		if ( $objRequest->getProperty("username") == "" || $objRequest->getProperty("username") != $objRequest->getSession("username") )
		{
      
      return $objRequest->url_for( 
                         array( "base" => "folder",
                                "action" => "home",
                                "username" => $objRequest->getSession("username")));
     			
		}
	}
}


?>