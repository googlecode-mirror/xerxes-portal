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

class Xerxes_Command_EmbedGenUserCreatedCategory extends Xerxes_Command_Embed
{
	public function doExecute()
	{
		// we can only embed published collections, private ones are not
		// embeddable for so many reasons. 
		
		$strPublished = $this->request->getData( '/*/category/@published' );
		if ( $strPublished != '1' )
		{
			throw new Xerxes_AccessDeniedException( "Your collection must be published in order to use the 'embed' feature" );
		}
		
		// define url params for subject display action, and call helper.
		
		$embed_url_params = array ("base" => "collections", "action" => "embed", "username" => $this->request->getProperty( "username" ), "subject" => $this->request->getProperty( "subject" ) );
		
		// direct to resource without embed
		
		$direct_url_params = array ("base" => "collections", "action" => "subject", "username" => $this->request->getProperty( "username" ), "subject" => $this->request->getProperty( "subject" ) );
		
		// the meet of it in this helper method in superclass!
		
		$this->doExecuteHelper( $embed_url_params, $direct_url_params );
		return 1;
	}
}
?>