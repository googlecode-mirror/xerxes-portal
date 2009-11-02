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

class Xerxes_Command_EmbedGenSubject extends Xerxes_Command_Embed
{
	public function doExecute()
	{
		// define url params for subject display action, and call helper
		
		$embed_url_params = array ("base" => "embed", "action" => "subject", "subject" => $this->request->getProperty( "subject" ) );
		
		// direct to resource without embed
		
		$direct_url_params = array ("base" => "databases", "action" => "subject", "subject" => $this->request->getProperty( "subject" ) );
		
		// the meet of it in this helper method in superclass!
		
		$this->doExecuteHelper( $embed_url_params, $direct_url_params );
		return 1;
	}
}
?>