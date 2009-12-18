<?php

/**
 * Fetch a single top-level category and inline its subcategories as XML;
 * Request param should be 'subject', the normalized name of the subject as
 * saved for user-created db. The 'normalized' name is the one we will show in
 * the url. 
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_UserCreatedCategory extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		$objXml = new DOMDOcument( );
		$objXml->loadXML( "<category />" );
		
		$strSubject = $this->request->getProperty( "subject" );
		$strUser = $this->request->getProperty( "username" );
		
		$objData = new Xerxes_DataMap( );
		$objCategoryData = null;
		
		//  only fetch if we actually have params, avoid the fetch-everything phenomena
		
		if ( $strSubject && $strUser )
		{
			$objCategoryData = $objData->getSubject( $strSubject, null, Xerxes_DataMap::userCreatedMode, $strUser );
		}
		
		// if there hasn't
		
		if ( ! $objCategoryData )
		{
			if ( $this->request->getRedirect() )
			{
				// nevermind, we're in the creation process, already redirected, 
				// just end now.
				 
				return 1;
			} 
			else
			{
				throw new Xerxes_Exception_NotFound( "Personal collection not found." );
			}
		}
		
		// make sure they have access
		
		if ( ! $objCategoryData->published )
		{
			Xerxes_Helper::ensureSpecifiedUser( $objCategoryData->owned_by_user, $this->request, $this->registry, "This is a private database set only accessible to the user who created it. Please log in if you are that user." );
		}
		
		$y = 1;
		
		if ( $objCategoryData != null )
		{
			$objXml->documentElement->setAttribute( "name", $objCategoryData->name );
			$objXml->documentElement->setAttribute( "normalized", $objCategoryData->normalized );
			$objXml->documentElement->setAttribute( "owned_by_user", $objCategoryData->owned_by_user );
			$objXml->documentElement->setAttribute( "published", $objCategoryData->published );
			
			// we treat the 'default' collection (usually 'My Saved Records') special
			// giving it less flexibility for simplicity, in the XSL/javascript.
			
			if ( $this->isDefaultCollection( $objCategoryData ) )
			{
				$objXml->documentElement->setAttribute( "is_default_collection", "yes" );
			}
			
			// standard url for the category 

			$arrParams = array (
				"base" => "collections", 
				"action" => "subject", 
				"username" => $strUser, 
				"subject" => $objCategoryData->normalized );
			
			$url = Xerxes_Framework_Parser::escapeXml( $this->request->url_for( $arrParams ) );
			$objElement = $objXml->createElement( "url", $url );
			$objXml->documentElement->appendChild( $objElement );
			
			//edit url for the user-created category
			
			$arrParams = array (
				"base" => "collections", 
				"action" => "edit_form", 
				"username" => $strUser, 
				"subject" => $objCategoryData->normalized );
			
			$url = Xerxes_Framework_Parser::escapeXml( $this->request->url_for( $arrParams ) );
			$objElement = $objXml->createElement( "edit_url", $url );
			$objXml->documentElement->appendChild( $objElement );
			
			// the attributes of the subcategories
			
			$db_list_index = 1;
			
			foreach ( $objCategoryData->subcategories as $objSubData )
			{
				$objSubCategory = $objXml->createElement( "subcategory" );
				$objSubCategory->setAttribute( "name", $objSubData->name );
				$objSubCategory->setAttribute( "position", $y );
				$objSubCategory->setAttribute( "id", $objSubData->id );
				
				$y ++;
				
				// the database information

				foreach ( $objSubData->databases as $objDatabaseData )
				{
					$objDatabase = Xerxes_Helper::databaseToNodeset( $objDatabaseData, $this->request, $this->registry, $db_list_index );
					$objDatabase = $objXml->importNode( $objDatabase, true );
					$objSubCategory->appendChild( $objDatabase );
				}
				
				$objXml->documentElement->appendChild( $objSubCategory );
			}
		}
		
		$this->request->addDocument( $objXml );
		
		return 1;
	}
}
?>