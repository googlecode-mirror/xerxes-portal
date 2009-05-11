<?php

/**
 *  Delete a user-created category 
 *
 *  In all cases, you need a 'subject' and 'username' param, and 'return'. 
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
		$this->returnWithMessage( "Deleted '" . $category->name . "'", array ("base" => "collections", "action" => "list", "username" => $strUsername ) );
		
		return 1;
	}
}
?>