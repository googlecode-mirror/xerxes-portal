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
	public $grouping;
	public $id;
	public $data;
	public $timestamp;
	public $expiry;
}

class Xerxes_Data_Refereed extends Xerxes_Framework_DataValue
{
	public $issn;
	public $title;
	public $title_normal;
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
	public $subcategories = array ( );
	
	/**
	 * Converts a sting to a normalized (no-spaces, non-letters) string
	 *
	 * @param string $strSubject	original string
	 * @return string				normalized string
	 */
	public static function normalize($strSubject)
	{
		$strNormalized = Xerxes_Framework_Parser::strtolower( $strSubject );
		
		$strNormalized = str_replace( "&amp;", "", $strNormalized );
		$strNormalized = str_replace( "'", "", $strNormalized );
		$strNormalized = str_replace( "+", "-", $strNormalized );
		$strNormalized = str_replace( " ", "-", $strNormalized );
		
		$strNormalized = Xerxes_Framework_Parser::preg_replace( "/\W/", "-", $strNormalized );
		
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
	public $metalib_id;
	public $title_full;
	public $title_display;
	public $institute;
	public $filter;
	public $creator;
	public $search_hints;
	public $publisher;
	public $publisher_description;
	public $description;
	public $coverage;
	public $time_span;
	public $copyright;
	public $note_cataloger;
	public $note_fulltext;
	public $type;
	public $link_native_home;
	public $link_native_record;
	public $link_native_home_alternative;
	public $link_native_record_alternative;
	public $link_native_holdings;
	public $link_guide;
	public $link_publisher;
	public $library_address;
	public $library_city;
	public $library_state;
	public $library_zipcode;
	public $library_country;
	public $library_telephone;
	public $library_fax;
	public $library_email;
	public $library_contact;
	public $library_note;
	public $library_hours;
	public $active;
	public $proxy;
	public $searchable;
	public $guest_access;
	public $subscription;
	public $sfx_suppress;
	public $new_resource_expiry;
	public $updated;
	public $number_sessions;
	
	public $keywords = array ( );
	public $notes = array ( );
	public $languages = array ( );
	public $alternate_publishers = array ( );
	public $alternate_titles = array ( );
	public $group_restrictions = array ( );
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