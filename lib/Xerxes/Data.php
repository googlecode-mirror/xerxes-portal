<?php

/**
 * Database Value classes
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */


class Xerxes_Data_RecordTag extends Xerxes_Framework_DataValue
{
	public $label;
	public $total;
}

class Xerxes_Data_RecordFormat extends Xerxes_Framework_DataValue
{
	public $format;
	public $total;
}

class Xerxes_Data_Cache extends Xerxes_Framework_DataValue
{
	public $source;
	public $id;
	public $alternate_ids = array();
	public $data;
	public $timestamp;
	public $expiry;
}

class Xerxes_Data_Refereed extends Xerxes_Framework_DataValue
{
	public $issn;
	public $title;
	public $timestamp;
}

class Xerxes_Data_Fulltext extends Xerxes_Framework_DataValue
{
	public $issn;
	public $title;
	public $startdate;
	public $enddate;
	public $embargo;
	public $updated;
	public $live;
}

class Xerxes_Data_Category extends Xerxes_Framework_DataValue
{
	public $id;
	public $name;
	public $normalized;
	public $old;
	public $lang;
	public $subcategories = array ( );
	
	/**
	 * Converts a sting to a normalized (no-spaces, non-letters) string
	 *
	 * @param string $strSubject	original string
	 * @return string				normalized string
	 */
	public static function normalize($strSubject)
	{
		$strNormalized = iconv( 'UTF-8', 'ASCII//TRANSLIT', $strSubject ); // this is influenced by the setlocale() call with category LC_CTYPE; see PopulateDatabases.php
		$strNormalized = Xerxes_Framework_Parser::strtolower( $strNormalized );
		
		$strNormalized = str_replace( "&amp;", "", $strNormalized );
		$strNormalized = str_replace( "'", "", $strNormalized );
		$strNormalized = str_replace( "+", "-", $strNormalized );
		$strNormalized = str_replace( " ", "-", $strNormalized );
		
		$strNormalized = Xerxes_Framework_Parser::preg_replace( '/\W/', "-", $strNormalized );
		
		while ( strstr( $strNormalized, "--" ) )
		{
			$strNormalized = str_replace( "--", "-", $strNormalized );
		}
		
		return $strNormalized;
	}
}

class Xerxes_Data_Subcategory extends Xerxes_Framework_DataValue
{
	public $metalib_id;
	public $name;
	public $sequence;
	public $category_id;
	public $databases = array ( );
}

class Xerxes_Data_Type extends Xerxes_Framework_DataValue
{
	public $id;
	public $name;
	public $normalized;
	public $databases = array ( );
}

class Xerxes_Data_Database extends Xerxes_Framework_DataValue
{
	public $xml;
	
	public $metalib_id;
	public $title_display;
	public $type;
	public $data;
	
	public function load($arrResult)
	{
		parent::load($arrResult);
		
		if ( $this->data != "" )
		{
			$this->xml = simplexml_load_string($this->data);
		}
	}
	
	public function __get($name)
	{
		if ( $this->xml instanceof SimpleXMLElement )
		{
			return (string) $this->xml->$name;
		}
		else
		{
			return null;
		}
	}
	
	public function get($field)
	{
		$values = array();
		
		if ( $this->xml instanceof SimpleXMLElement )
		{
			foreach ($this->xml->$field as $value)
			{
				array_push($values, $value);
			}
		}
		
		return $values;
	}

}

class Xerxes_Data_Record extends Xerxes_Framework_DataValue
{
	public $id;
	public $source;
	public $original_id;
	public $timestamp;
	public $username;
	public $nonsort;
	public $title;
	public $author;
	public $year;
	public $format;
	public $refereed;
	public $marc;
	public $xerxes_record; // not part of table!

	public $tags = array ( );
}

?>
