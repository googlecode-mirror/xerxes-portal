<?php

/**
 *  Delete a user-created category 
 *
 *  In all cases, you need a 'subject' and 'username' param, and 'return'. 
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_DeleteUserCategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$strNormalizedSubject = $this->request->getProperty( "subject" );
		$strUsername = $this->request->getProperty( "username" );
		
		// Make sure they are logged in as the user they are trying to save as. 
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		
		// Find the category
		
		$category = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		$objData->deleteUserCreatedCategory( $category );
		
		// Send them back where they came from, with a message. 
		$this->returnWithMessage( $this->getLabel("text_collections_deleted_category", $category->name), array ("base" => "collections", "action" => "list", "username" => $strUsername ) );
		
		return 1;
	}
}
?>