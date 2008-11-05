<?php

/**
 * Add tags to a record
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.1
 * @package Xerxes
 */

class Xerxes_Command_FolderTagsEdit extends Xerxes_Command_Folder
{
	/**
	 * Add tags to a record
	 * 
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int		status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$strUsername = $objRequest->getSession( "username" );
		
		$iRecord = $objRequest->getProperty( "record" );
		$strTags = $objRequest->getProperty( "tags" ); // updated tags
		$strShadowTags = $objRequest->getProperty( "tagsShaddow" ); // original tags
		

		//  split tags out on comma
		

		$arrShadow = explode( ",", $strShadowTags );
		$arrTags = explode( ",", $strTags );
		
		for ( $x = 0 ; $x < count( $arrTags ) ; $x ++ )
		{
			$arrTags[$x] = strtolower( trim( $arrTags[$x] ) );
		}
		
		for ( $x = 0 ; $x < count( $arrShadow ) ; $x ++ )
		{
			$arrShadow[$x] = strtolower( trim( $arrShadow[$x] ) );
		}
		
		// remove any duplicates
		
		$arrTags = array_unique( $arrTags );
		
		// update the database
		

		$objData = new Xerxes_DataMap( );
		$objData->assignTags( $strUsername, $arrTags, $iRecord );
		
		// now update the cached version without recalculating all the 
		// totals with a round-trip to the database
		

		$arrStored = $objRequest->getSession( "tags" );
		
		// see which tags are new and which are actually being deleted or changed
		

		$arrDelete = array_diff( $arrShadow, $arrTags );
		$arrAdded = array_diff( $arrTags, $arrShadow );
		
		// deletes!
		

		foreach ( $arrDelete as $strTag )
		{
			foreach ( $arrStored as $strStoredKey => $iStoredValue )
			{
				if ( strtoupper( $strTag ) == strtoupper( $strStoredKey ) )
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
					if ( strtoupper( $strTag ) == strtoupper( $strStoredKey ) )
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
		

		$this->setTagsCache( $objRequest, $arrStored );
		
		return 1;
	
	}
}

?>