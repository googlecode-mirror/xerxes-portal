<?php

/** 
 * Can be used to edit a user-created category or subcategory. 
 * If subcategory url param is there, edit a subcategory (name).
 * Of only subject param, edit a category (name and published status)
 * 
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_EditUserCategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		// cancel?
		
		$arrDefaultReturn = array ("base" => "collections", "action" => "edit_form", "subject" => $this->request->getProperty( "subject" ), "subcategory" => $this->request->getProperty( "subcategory" ), "username" => $this->request->getProperty( "username" ) );
		
		if ( $this->request->getProperty( "cancel" ) )
		{
			$this->returnWithMessage( $this->getLabel("text_collections_cancelled"), $arrDefaultReturn );
			return 1;
		}
		
		$strSubject = $this->request->getProperty( "subject" );
		$strSubcatID = $this->request->getProperty( "subcategory" );
		$strPublished = $this->request->getProperty( "published" );
		$strUsername = $this->request->getProperty( "username" );
		$strNewName = $this->request->getProperty( "new_name" );
		
		// Make sure they are logged in as the user they are trying to save as. 
		
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		
		$category = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		$message = "";
		
		if ( ! empty( $strSubcatID ) )
		{
			// edit a subcategory, rename
			
			$subcat = $this->getSubcategory( $category, $strSubcatID );
			
			if ( ! empty( $strNewName ) )
			{
				$message .= "Section name changed. ";
				$subcat->name = $strNewName;
				$objData->updateUserSubcategoryProperties( $subcat );
			}
		} 
		else
		{
			// edit a category: rename/publish
			
			if ( ! empty( $strNewName ) )
			{
				$category->name = $strNewName;
				$message .= "Collection name changed. ";
			}
			
			if ( ! empty( $strPublished ) )
			{
				$boolPublished = ( int ) ($strPublished == "true");
				$category->published = $boolPublished;
				
				if ( $boolPublished )
				{
					$message .= $this->getLabel("text_collections_made_published");
				} 
				else
				{
					$message .= $this->getLabel("text_collections_made_private");
				}
			}
			$objData->updateUserCategoryProperties( $category );
		}
		
		// new name if it's been changed!
		
		$arrDefaultReturn["subject"] = $category->normalized;
		$this->returnWithMessage( $message, $arrDefaultReturn );
		
		return 1;
	}
}
?>