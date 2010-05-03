<?php

/**
 * Rename a user-created category or subcategory. Expects request param
 * 'new_name', subject=>normalized-name, and possibly subcategory=>id. If 
 *  subcategory is present, subcategory will be renamed. 
 *
 *  Redirects back to 'return' request param, or user created category
 *  edit page. 
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_RenameUserCategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		//Cancel?
		
		$arrDefaultReturn = array (
			"base" => "collections", 
			"action" => "edit_form", 
			"subject" => $this->request->getProperty( "subject" ), 
			"subcategory" => $this->request->getProperty( "subcategory" ), 
			"username" => $this->request->getProperty( "username" ) );
		
		if ( $this->request->getProperty( "cancel" ) )
		{
			$this->returnWithMessage( "Cancelled", $arrDefaultReturn );
			return 1;
		}
		
		$strSubject = $this->request->getProperty( "subject" );
		$strSubcatID = $this->request->getProperty( "subcategory" );
		$strUsername = $this->request->getProperty( "username" );
		$strNewName = $this->request->getProperty( "new_name" );
		
		if ( empty( $strNewName ) )
		{
			$this->returnWithMessage( "Blank name, not changed.", $arrDefaultReturn );
			return 1;
		}
		
		// make sure they are logged in as the user they are trying to save as. 
		
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		
		$category = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		if ( ! empty( $strSubcatID ) )
		{
			// rename a subcategory
			$subcat = $this->getSubcategory( $category, $strSubcatID );
			$subcat->name = $strNewName;
			$objData->updateUserSubcategoryProperties( $subcat );
		} 
		else
		{
			// rename category
			$category->name = $strNewName;
			$objData->updateUserCategoryProperties( $category );
		}
		
		// new name if it's been changed!
		
		$arrDefaultReturn["subject"] = $category->normalized;
		$this->returnWithMessage( "Renamed", $arrDefaultReturn );
		
		return 1;
	}
}
?>