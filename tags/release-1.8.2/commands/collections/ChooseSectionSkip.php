<?php

/**
 * Prior to displaying a section choice dialog, do we even have any sections
 * to choose from? If just one, skip that choice dialog. if 0, create one,
 * and skip dialog.
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_ChooseSectionSkip extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$strSubjectSelection = $this->request->getProperty( "subject" );
		$strUsername = $this->request->getProperty( "username" );
    if (empty($strUsername)) {
      $strUsername = $this->request->getSession("username");
    }
		
		// make sure they are logged in as the user they are trying to save as
		
		$this->ensureSpecifiedUser();
		
		$objData = new Xerxes_DataMap( );
		
		$existingSubject = $objData->getSubject( $strSubjectSelection, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		$subcats = $existingSubject->subcategories;
		
		if ( count( $subcats ) <= 1 )
		{
			if ( count( $subcats ) == 1 )
			{
				$subcat_id = $subcats[0]->id;
			} 
			else
			{
				//create one
				
				$new_subcat = new Xerxes_Data_Subcategory( );
				$new_subcat->sequence = 1;
				$new_subcat->category_id = $existingSubject->id;
				$new_subcat->name = $this->registry->getConfig( "default_collection_section_name", false, "Databases" );
				
				$new_subcat = $objData->addUserCreatedSubcategory( $new_subcat );
				$subcat_id = $new_subcat->id;
			}
			
			$fixedUrl = $this->request->url_for( array (
				"base" => "collections", 
				"action" => "save_complete", 
				"subject" => $existingSubject->normalized, 
				"subcategory" => $subcat_id, 
				"id" => $this->request->getProperty( "id" ), 
				"username" => $strUsername, 
				"return" => $this->request->getProperty( "return" ) ), true ); // force full url for redirect

			$this->request->setRedirect( $fixedUrl );
		}
		
		return 1;
	}
}
?>