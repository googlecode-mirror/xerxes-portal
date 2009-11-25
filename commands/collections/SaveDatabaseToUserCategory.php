<?php

/**
 * Save database to user category
 */

class Xerxes_Command_SaveDatabaseToUserCategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$strNormalizedSubject = $this->request->getProperty( "subject" );
		
		// If that was set to "NEW" then, we have a newly created subject,
		// already created by a prior command in execution chain, but we
		// need to make sure to look it up properly. 
		
		if ( $strNormalizedSubject == "NEW" )
		{
			$strNormalizedSubject = Xerxes_Data_Category::normalize( $this->request->getProperty( "new_subject_name" ) );
		}
		
		$strUsername = $this->request->getProperty( "username" );
		$strDatabaseID = $this->request->getProperty( "id" );
		
		$strSubcatSelection = $this->request->getProperty( "subcategory" );
		$strNewSubcat = $this->request->getProperty( "new_subcategory_name" );
		
		// make sure they are logged in as the user they are trying to save as. 
		
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		$subcategory = null;
		
		// find the category
		
		$category = $objData->getSubject( $strNormalizedSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		// to do. Create silently if not found?
		
		if ( ! $category )
		{
			throw new Exception( "Selected category not found in database." );
		}
			
		// were we directed to create a new one?
		
		if ( $strNewSubcat || ($strSubcatSelection == "NEW") )
		{
			if ( empty( $strNewSubcat ) )
			{
				$strNewSubcat = "Databases";
			}
			
			$subcategory = new Xerxes_Data_Subcategory( );
			$subcategory->name = $strNewSubcat;
			$subcategory->category_id = $category->id;
			
			// just put it at the end
			
			$last_one = $category->subcategories[count( $category->subcategories ) - 1];
			$subcategory->sequence = $last_one->sequence + 1;
			
			$subcategory = $objData->addUserCreatedSubcategory( $subcategory );
		}
		
		// if no db id was provided, all we needed to do was create a subcategory.
		
		if ( ! $strDatabaseID )
		{
			$this->returnWithMessage( "New section created" );
			return 1;
		}
		
		// if we don't have a subcategory object from having just created one, find it from categories children. 
		
		if ( ! $subcategory )
		{
			foreach ( $category->subcategories as $s )
			{
				if ( $s->id == $strSubcatSelection )
					$subcategory = $s;
			}
		}
		
		// now we better have one. 
		
		if ( ! $subcategory )
			throw new Exception( "Selected section not found." );
			
		// and add the db to it, unless it already is there. 
		
		foreach ( $subcategory->databases as $db )
		{
			if ( $db->metalib_id == $strDatabaseID )
			{
				$this->returnWithMessage( "Database was already saved to  " . $subcategory->name . " in " . $category->name );
				return 1;
			}
		}
		
		$objData->addDatabaseToUserCreatedSubcategory( $strDatabaseID, $subcategory );
		
		// send them back where they came from, with a message.
		 
		$this->returnWithMessage( "Saved database in " . $category->name );
		
		return 1;
	}
}
?>