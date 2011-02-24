<?php

/**
 * Database access mapper for sfx institutioanl holdings (google scholar) full-text cache
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_DataMap_Availability extends Xerxes_Framework_DataMap
{
	/**
	 * Delete all records from the sfx table
	 */
	
	public function clearFullText()
	{
		$this->delete( "DELETE FROM xerxes_sfx" );
	}
	
	/**
	 * Get a list of journals from the sfx table by issn
	 *
	 * @param mixed $issn		[string or array] ISSN or multiple ISSNs
	 * @return array			array of Xerxes_Data_Fulltext objects
	 */
	
	public function getFullText($issn)
	{
		$arrFull = array ( );
		$arrResults = array ( );
		$strSQL = "SELECT * FROM xerxes_sfx WHERE ";
		
		if ( is_array( $issn ) )
		{
			if ( count( $issn ) == 0 ) throw new Exception( "issn query with no values" );
			
			$x = 1;
			$arrParams = array ( );
			
			foreach ( $issn as $strIssn )
			{
				$strIssn = str_replace( "-", "", $strIssn );
				
				if ( $x == 1 )
				{
					$strSQL .= " issn = :issn$x ";
				} 
				else
				{
					$strSQL .= " OR issn = :issn$x ";
				}
				
				$arrParams["issn$x"] = $strIssn;
				
				$x ++;
			}
			
			$arrResults = $this->select( $strSQL, $arrParams );
		} 
		else
		{
			$issn = str_replace( "-", "", $issn );
			$strSQL .= " issn = :issn";
			$arrResults = $this->select( $strSQL, array (":issn" => $issn ) );
		}
		
		foreach ( $arrResults as $arrResult )
		{
			$objFull = new Xerxes_Data_Fulltext( );
			$objFull->load( $arrResult );
			
			array_push( $arrFull, $objFull );
		}
		
		return $arrFull;
	}
	
	/**
	 * Add a Xerxes_Data_Fulltext object to the database
	 *
	 * @param Xerxes_Data_Fulltext $objValueObject
	 * @return int status
	 */
	
	public function addFulltext(Xerxes_Data_Fulltext $objValueObject)
	{
		return $this->doSimpleInsert( "xerxes_sfx", $objValueObject );
	}
}
