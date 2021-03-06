<?php

/**
 * Prior to displaying a category choice dialog, do we even have any category
 * to choose from? If just one, skip that choice dialog. if 0, create one,
 * and skip dialog. 
 *
 * Assumes that you ran ListUserCategories command before this command, checks
 * XML generated by ListUserCategories. 
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_ChooseCategorySkip extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$strUsername = $this->request->getProperty( "username" );
    if (empty($strUsername)) {
      //default to logged in user
      $strUsername= $this->request->getSession( "username" );
    }
		
		$objData = new Xerxes_DataMap( );
		
		$existingCategoryNames = $this->request->getData( '/*/userCategories/category/normalized', null, 'ARRAY' );
		
		if ( count( $existingCategoryNames ) <= 1 )
		{
			if ( count( $existingCategoryNames ) == 1 )
			{
				$normalized_name = $existingCategoryNames[0];
			} 
			else
			{
				// create a new one
				     
				$strNewSubject = $this->registry->getConfig( "default_collection_name", false, "My Saved Databases" );
				$strNormalizedSubject = Xerxes_Data_Category::normalize( $strNewSubject );
				
				$newCategory = new Xerxes_Data_Category( );
				$newCategory->name = $strNewSubject;
				$newCategory->username = $strUsername;
				$newCategory->normalized = $strNormalizedSubject;
				$newCategory->published = 0;
				$newCategory = $objData->addUserCreatedCategory( $newCategory );
				
				$normalized_name = $newCategory->normalized;
			}
			
			// redirect past the category selection page
			 
			$fixedUrl = $this->request->url_for( array (
				"base" => "collections", 
				"action" => "save_choose_subheading", 
				"subject" => $normalized_name, "id" => $this->request->getProperty( "id" ), 
				"username" => $strUsername, 
				"return" => $this->request->getProperty( "return" ) 
				), true ); // force full url for redirect 
				
      
			$this->request->setRedirect( $fixedUrl );
		}
		
		return 1;
	}
}
?>