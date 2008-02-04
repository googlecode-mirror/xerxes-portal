<?php

/**
 * Base class for common functions for my saved records commands
 *
 */


abstract class Xerxes_Command_Folder extends Xerxes_Framework_Command
{	
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
			if ( $objRegistry->getConfig("REWRITE", false, false) == false )
			{
				return $objRegistry->getConfig("BASE_URL") . "/?base=folder&action=home&username=" . urlencode($objRequest->getSession("username"));
			}
			else 
			{
				return $objRegistry->getConfig("BASE_URL") . "/folder/" . urlencode($objRequest->getSession("username"));
			}
		}
	}
}


?>