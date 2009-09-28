<?php

/**
 * Lists user categories
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.1
 * @package Xerxes
 */

class Xerxes_Command_ListUserCategories extends Xerxes_Command_Collections
{
	public function doExecute()
	{
		// If supplied in URL, use that (for future API use). 
		// Nevermind, this is a privacy problem until we right access
		// controls for that future API use. 
		//$username = $this->request->getProperty("username");
		//if ( ! $username ) {
		// default to logged in user
		$username = $this->request->getSession( "username" );
		//}     

		// we can only do this if we have a real user (not temp user), otherwise
		// just add no XML. 
		
		if ( $username == null || ! Xerxes_Framework_Restrict::isAuthenticatedUser( $this->request ) )
		{
			return 0;
		}
		
		$objXml = new DOMDOcument( );
		
		$objData = new Xerxes_DataMap( );
		$arrResults = $objData->getUserCreatedCategories( $username );
		
		$x = 1;
		
		if ( count( $arrResults ) > 0 )
		{
			$objXml->loadXML( "<userCategories />" );
			
			foreach ( $arrResults as $objCategoryData )
			{
				$objCategory = $objXml->createElement( "category" );
				$objCategory->setAttribute( "position", $x );
				
				foreach ( $objCategoryData->properties() as $key => $value )
				{
					if ( $value != null )
					{
						$objElement = $objXml->createElement( "$key", Xerxes_Parser::escapeXml( $value ) );
						$objCategory->appendChild( $objElement );
					}
				}
				
				// add the url for the category

				$arrParams = array (
					"base" => "collections", 
					"action" => "subject", 
					"username" => $username, 
					"subject" => $objCategoryData->normalized );
				
				$url = Xerxes_Parser::escapeXml( $this->request->url_for( $arrParams ) );
				
				$objElement = $objXml->createElement( "url", $url );
				$objCategory->appendChild( $objElement );
				$objXml->documentElement->appendChild( $objCategory );
				
				$x ++;
			}
		}
		
		$this->request->addDocument( $objXml );
		
		return 1;
	}
}
?>