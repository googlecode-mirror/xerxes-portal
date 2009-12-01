<?php

/**
 * Little helper controller for the embed function
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_EmbedGenDatabase extends Xerxes_Command_Embed
{
	public function doExecute()
	{
		// define url params for display action, and call helper, that
		// does all the real work. 
		
		$embed_url_params = array ("base" => "embed", "action" => "database", "id" => $this->request->getProperty( "id" ) );
		$direct_url_params = array ("base" => "databases", "action" => "database", "id" => $this->request->getProperty( "id" ) );
		
		// the meet of it in this helper method in superclass!
		
		$this->doExecuteHelper( $embed_url_params, $direct_url_params );
		return 1;
	}
}
?>