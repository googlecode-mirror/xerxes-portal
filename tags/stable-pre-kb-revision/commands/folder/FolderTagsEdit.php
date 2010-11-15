<?php

/**
 * Add tags to a record
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_FolderTagsEdit extends Xerxes_Command_Folder
{
	public function doExecute()
	{
		$strUsername = $this->request->getSession( "username" );
		
		$iRecord = $this->request->getProperty( "record" );
		$strTags = $this->request->getProperty( "tags" ); // updated tags
		$strShadowTags = $this->request->getProperty( "tagsShaddow" ); // original tags

		//  split tags out on comma

		$arrShadow = explode( ",", $strShadowTags );
		$arrTags = explode( ",", $strTags );
		
		for ( $x = 0 ; $x < count( $arrTags ) ; $x ++ )
		{
			$arrTags[$x] = Xerxes_Framework_Parser::strtolower( trim( $arrTags[$x] ) );
		}
		
		for ( $x = 0 ; $x < count( $arrShadow ) ; $x ++ )
		{
			$arrShadow[$x] = Xerxes_Framework_Parser::strtolower( trim( $arrShadow[$x] ) );
		}
		
		// remove any duplicates
		
		$arrTags = array_unique( $arrTags );
		
		// update the database

		$objData = new Xerxes_DataMap( );
		$objData->assignTags( $strUsername, $arrTags, $iRecord );
		
		// now update the cached version without recalculating all the 
		// totals with a round-trip to the database

		$arrStored = $this->request->getSession( "tags" );
		
		// see which tags are new and which are actually being deleted or changed
		
		$arrDelete = array_diff( $arrShadow, $arrTags );
		$arrAdded = array_diff( $arrTags, $arrShadow );
		
		// deletes!
		
		foreach ( $arrDelete as $strTag )
		{
			foreach ( $arrStored as $strStoredKey => $iStoredValue )
			{
				if ( Xerxes_Framework_Parser::strtoupper( $strTag ) == Xerxes_Framework_Parser::strtoupper( $strStoredKey ) )
				{
					$iStoredValue = ( int ) $iStoredValue;
					
					if ( $iStoredValue > 1 )
					{
						// just deincrement it

						$iStoredValue --;
						$arrStored[$strStoredKey] = $iStoredValue;
					} else
					{
						// this was the only entry for the tag so remove it

						unset( $arrStored[$strStoredKey] );
					}
				}
			}
		}
		
		// adds!
		
		foreach ( $arrAdded as $strTag )
		{
			if ( $strTag != "" )
			{
				$bolExists = false;
				
				foreach ( $arrStored as $strStoredKey => $iStoredValue )
				{
					if ( Xerxes_Framework_Parser::strtoupper( $strTag ) == Xerxes_Framework_Parser::strtoupper( $strStoredKey ) )
					{
						// there is one in here already so increment

						$iStoredValue = ( int ) $iStoredValue;
						$iStoredValue ++;
						$arrStored[$strStoredKey] = $iStoredValue;
						
						$bolExists = true;
					}
				}
				
				// if it wasn't in there already, add it as the first

				if ( $bolExists == false )
				{
					$arrStored[$strTag] = 1;
				}
			}
		}
		
		// now store it back in session

		$this->setTagsCache( $arrStored );
		
		return 1;
	
	}
}

?>