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

class Xerxes_Command_ReorderSubcats extends Xerxes_Command_Collections
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
		
		// make sure they are logged in as the user they are trying to save as. 
		
		Xerxes_Helper::ensureSpecifiedUser( $strUsername, $this->request, $this->registry, "You must be logged in as $strUsername to save to a personal database collection owned by that user." );
		
		$objData = new Xerxes_DataMap( );
		
		$category = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUsername );
		
		// find any new assigned numbers, and reorder. 
		
		$orderedSubcats = $category->subcategories;
		
		// we need to through the assignments in sorted order by sequence choice,
		// for this to work right. 
		
		$sortedProperties = $this->request->getAllProperties();
		asort( $sortedProperties );
		
		foreach ( $sortedProperties as $name => $new_sequence )
		{
			$matches = array();
			
			if ( ! empty( $new_sequence ) && preg_match( '/^subcat_seq_(\d+)$/', $name, $matches ) )
			{
				$subcatID = $matches[1];
				$old_index = null;
				$subcategory = null;
				
				for ( $i = 0 ; $i < count( $orderedSubcats ) ; $i ++ )
				{
					$candidate = $orderedSubcats[$i];
					if ( $candidate->id == $subcatID )
					{
						$old_index = $i;
						$subcategory = $candidate;
					}
				}
				
				// if we found it. 
				
				if ( $subcategory )
				{
					// remove it from the array, then add it back in
					array_splice( $orderedSubcats, $old_index, 1 );
					array_splice( $orderedSubcats, $new_sequence - 1, 0, array ($subcategory ) );
				}
			}
		}
		
		// okay, we've re-ordered $orderedSubcats, now update the sequence #s
		
		for ( $i = 0 ; $i < count( $orderedSubcats ) ; $i ++ )
		{
			$subcategory = $orderedSubcats[$i];
			$subcategory->sequence = $i + 1;
			$objData->updateUserSubcategoryProperties( $subcategory );
		}
		
		$this->returnWithMessage( $this->getLabel("text_collections_section_order_changed"), $arrDefaultReturn );
		
		return 1;
	}
}
?>