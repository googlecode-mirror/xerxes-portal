<?php

/**
 * Reorder subcategories. 
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_ReorderDatabases extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$arrDefaultReturn = array (
			"base" => "collections", 
			"action" => "edit_form", 
			"subject" => $this->request->getProperty( "subject" ), 
			"subcategory" => $this->request->getProperty( "subcategory" ), 
			"username" => $this->request->getProperty( "username" ) );
		
		$strSubject = $this->request->getProperty( "subject" );
		$strUsername = $this->request->getProperty( "username" );
		$strSubcategoryID = $this->request->getProperty( "subcategory" );
		
		// Make sure they are logged in as the user they are trying to save as.
		 
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		
		$category = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		$subcategory = $this->getSubcategory( $category, $strSubcategoryID );
		
		// Find any new assigned numbers, and reorder. 
		$orderedDatabases = $subcategory->databases;
		
		// We need to through the assignments in sorted order by sequence choice, for this to work right.
		 
		$sortedProperties = $this->request->getAllProperties();
		asort( $sortedProperties );
		
		foreach ( $sortedProperties as $name => $new_sequence )
		{
			$matches = array();
			
			if ( ! empty( $new_sequence ) && preg_match( '/^db_seq_(.+)$/', $name, $matches ) )
			{
				$dbID = $matches[1];
				$old_index = null;
				$database = null;
				
				for ( $i = 0 ; $i < count( $orderedDatabases ) ; $i ++ )
				{
					$candidate = $orderedDatabases[$i];
					if ( $candidate->metalib_id == $dbID )
					{
						$old_index = $i;
						$database = $candidate;
					}
				}
				
				// if we found it. 
				
				if ( $database )
				{
					// remove it from the array, then add it back in
					array_splice( $orderedDatabases, $old_index, 1 );
					array_splice( $orderedDatabases, $new_sequence - 1, 0, array ($database ) );
				}
			}
		}
		
		// Okay, we've re-ordered $orderedSubcats, now update the sequence #s
		for ( $i = 0 ; $i < count( $orderedDatabases ) ; $i ++ )
		{
			$db = $orderedDatabases[$i];
			
			$objData->updateUserDatabaseOrder( $db, $subcategory, $i + 1 );
		}
		
		$this->returnWithMessage( "Database order changed", $arrDefaultReturn );
		
		return 1;
	}
}
?>