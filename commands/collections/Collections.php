<?php

/**
 * Base class for 'collection' commands, personal db collections
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Command_Collections extends Xerxes_Framework_Command
{
	public function returnWithMessage($strMessage, $arrDefaultUrl = null)
	{
		$return = $this->request->getProperty( "return" );
		
		if ( $return )
		{
			$url = $this->registry->getConfig( "SERVER_URL" ) . $this->request->getProperty( "return" );
		} 
		elseif ( $arrDefaultUrl )
		{
			$url = $this->request->url_for( $arrDefaultUrl );
		} 
		else
		{
			$url = $this->registry->getConfig( "BASE_WEB_PATH" );
		}
		
		$this->request->setSession( "flash_message", $strMessage );
		$this->request->setRedirect( $url );
	}
	
	public function getLabel($id, $param1 = null, $param2 = null, $param3 = null)
	{
		$label = Xerxes_Framework_Labels::getInstance();
		$value = $label->getLabel($id);
 		$value = sprintf($value, $param1, $param2, $param3);
	 	return $value;
	}
	
	
	/**
	 * Find the subcategory with the given id, from the Xerxes_Data_Category object passed in.
	 *
	 * @param unknown_type $objCategory
	 * @param unknown_type $subcatId
	 * @return unknown
	 */
	 
	public function getSubcategory($objCategory, $subcatId)
	{
		$subcategory = null;
		
		foreach ( $objCategory->subcategories as $s )
		{
			if ( $s->id == $subcatId )
				$subcategory = $s;
		}
		
		return $subcategory;
	}
	
	/**
	 * Is the collection the default one? We tell by seeing if it's name matches
	 * the default name. 
	 *
	 * @param unknown_type $objCategoryData
	 * @return unknown
	 */
	
	public function isDefaultCollection($objCategoryData)
	{
		return ($objCategoryData->normalized == Xerxes_Data_Category::normalize( $this->registry->getConfig( "default_collection_name", false, "My Saved Databases" ) ));
	}
}

?>