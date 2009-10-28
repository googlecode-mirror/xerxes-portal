<?php

/**
 * Database access mapper
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */


class Xerxes_Data_Record_Tag extends Xerxes_Framework_DataValue
{
	public $label;
	public $total;
}

class Xerxes_Data_Record_Format extends Xerxes_Framework_DataValue
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
	public $subtitle;
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
		$strNormalized = strtolower( $strSubject );
		
		$strNormalized = str_replace( "&amp;", "", $strNormalized );
		$strNormalized = str_replace( "'", "", $strNormalized );
		$strNormalized = str_replace( "+", "-", $strNormalized );
		
		$strNormalized = preg_replace( "/\W/", "-", $strNormalized );
		
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

/**
 * Functions for inserting, updating, and deleting data from the database
 *
 */

class Xerxes_DataMap extends Xerxes_Framework_DataMap
{
	public function __construct()
	{
		$objRegistry = Xerxes_Framework_Registry::getInstance();
		$objRegistry->init();
		
		// make it a member variable so other functions can get it easier
		
		$this->registry = $objRegistry;
		
		// pdo can't tell us which rdbms we're using exactly, especially for 
		// ms sql server, since we'll be using odbc driver, so we make this
		// explicit in the config
		
		$this->rdbms = $this->registry->getConfig("RDBMS", false, "mysql");
		
		$this->init( 
			$objRegistry->getConfig( "DATABASE_CONNECTION", true ), 
			$objRegistry->getConfig( "DATABASE_USERNAME", true ), 
			$objRegistry->getConfig( "DATABASE_PASSWORD", true ) 
		);
	}
	
	### KNOWLEDGEBASE ADD FUNCTIONS ###
	

	/**
	 * Deletes data from the knowledgebase tables; should only be done
	 * while using transactions
	 */
	
	public function clearKB()
	{
		// delete join tables in the event mysql is set-up with myisam
		// storage engine -- this should be fixed in xerxes 1.2 since 
		// sql scripts for mysql will specifically set to innodb

		$this->delete( "DELETE FROM xerxes_database_alternate_publishers" );
		$this->delete( "DELETE FROM xerxes_database_alternate_titles" );
		$this->delete( "DELETE FROM xerxes_database_keywords" );
		$this->delete( "DELETE FROM xerxes_database_group_restrictions" );
		$this->delete( "DELETE FROM xerxes_database_languages" );
		$this->delete( "DELETE FROM xerxes_database_notes" );
		$this->delete( "DELETE FROM xerxes_subcategory_databases" );
		
		// delete parent tables

		$this->delete( "DELETE FROM xerxes_databases" );
		$this->delete( "DELETE FROM xerxes_subcategories" );
		$this->delete( "DELETE FROM xerxes_categories" );
		$this->delete( "DELETE FROM xerxes_types" );
	}
	
	/**
	 * Remove orphaned my saved database associations
	 */
	
	public function synchUserDatabases()
	{
		// user saved databases sit loose to the databases table, so we use this
		// to manually enforce an 'ON CASCADE DELETE' to ensure we don't abandon
		// databases in the my saved databases tables
		
		$this->delete( "DELETE FROM xerxes_user_subcategory_databases WHERE " .
			" database_id NOT IN ( SELECT metalib_id FROM xerxes_databases )");
	}
	
	/**
	 * Add a database to the local knowledgebase
	 *
	 * @param Xerxes_Data_Database $objDatabase
	 */
	
	public function addDatabase(Xerxes_Data_Database $objDatabase)
	{
		// clean-up metalib types
		// basic single-value fields

		$objDatabase->proxy = $this->convertMetalibBool( $objDatabase->proxy );
		$objDatabase->searchable = $this->convertMetalibBool( $objDatabase->searchable );
		$objDatabase->guest_access = $this->convertMetalibBool( $objDatabase->guest_access );
		$objDatabase->subscription = $this->convertMetalibBool( $objDatabase->subscription );
		$objDatabase->sfx_suppress = $this->convertMetalibBool( $objDatabase->sfx_suppress );
		$objDatabase->new_resource_expiry = $this->convertMetalibDate( $objDatabase->new_resource_expiry );
		$objDatabase->updated = $this->convertMetalibDate( $objDatabase->updated );
		
		$this->doSimpleInsert( "xerxes_databases", $objDatabase );
		
		// keywords

		foreach ( $objDatabase->keywords as $keyword )
		{
			$strSQL = "INSERT INTO xerxes_database_keywords ( database_id, keyword ) " . "VALUES ( :metalib_id, :keyword )";
			
			$this->insert( $strSQL, array (":metalib_id" => $objDatabase->metalib_id, ":keyword" => $keyword ) );
		}
		
		// usergroups/"secondary affiliations". Used as access restrictions.

		foreach ( $objDatabase->group_restrictions as $usergroup )
		{
			$strSQL = "INSERT INTO xerxes_database_group_restrictions ( database_id, usergroup ) " . "VALUES ( :metalib_id, :usergroup )";
			
			$this->insert( $strSQL, array (":metalib_id" => $objDatabase->metalib_id, ":usergroup" => $usergroup ) );
		}
		
		// notes

		foreach ( $objDatabase->notes as $note )
		{
			$strSQL = "INSERT INTO xerxes_database_notes ( database_id, note ) " . "VALUES ( :metalib_id, :note )";
			
			$this->insert( $strSQL, array (":metalib_id" => $objDatabase->metalib_id, ":note" => $note ) );
		}
		
		// languages

		foreach ( $objDatabase->languages as $language )
		{
			$strSQL = "INSERT INTO xerxes_database_languages ( database_id, language ) " . "VALUES ( :metalib_id, :language )";
			
			$this->insert( $strSQL, array (":metalib_id" => $objDatabase->metalib_id, ":language" => $language ) );
		}
		
		// alternate publishers

		foreach ( $objDatabase->alternate_publishers as $alternate_publisher )
		{
			$strSQL = "INSERT INTO xerxes_database_alternate_publishers ( database_id, alt_publisher ) " . "VALUES ( :metalib_id, :alt_publisher )";
			
			$this->insert( $strSQL, array (":metalib_id" => $objDatabase->metalib_id, ":alt_publisher" => $alternate_publisher ) );
		}
		
		// alternate titles

		foreach ( $objDatabase->alternate_titles as $alternate_title )
		{
			$strSQL = "INSERT INTO xerxes_database_alternate_titles ( database_id, alt_title ) " . "VALUES ( :metalib_id, :alt_title )";
			
			$this->insert( $strSQL, array (":metalib_id" => $objDatabase->metalib_id, ":alt_title" => $alternate_title ) );
		}
	}
	
	/**
	 * Add a type to the local knowldgebase
	 *
	 * @param Xerxes_Data_Type $objType
	 * @return int status
	 */
	
	public function addType(Xerxes_Data_Type $objType)
	{
		return $this->doSimpleInsert( "xerxes_types", $objType );
	}
	
	/**
	 * Add a category to the local knowledgebase; should also include
	 * Xerxes_Data_Subcategory subcategories ( as array in subcategory property) 
	 * and databases Xerxes_Data_Database as array in subcategory property.
	 *
	 * @param Xerxes_Data_Category $objCategory
	 */
	
	public function addCategory(Xerxes_Data_Category $objCategory)
	{
		$this->doSimpleInsert( "xerxes_categories", $objCategory );
		
		$s = 1;
		
		foreach ( $objCategory->subcategories as $objSubcategory )
		{
			$objSubcategory->category_id = $objCategory->id;
			$objSubcategory->sequence = $s;
			
			$this->doSimpleInsert( "xerxes_subcategories", $objSubcategory );
			
			$d = 1;
			
			foreach ( $objSubcategory->databases as $objDatabase )
			{
				$strSQL = "INSERT INTO xerxes_subcategory_databases ( database_id, subcategory_id, sequence ) " . "VALUES ( :database_id, :subcategory_id, :sequence )";
				
				$arrValues = array (":database_id" => $objDatabase->metalib_id, ":subcategory_id" => $objSubcategory->metalib_id, ":sequence" => $d );
				
				$this->insert( $strSQL, $arrValues );
				$d ++;
			}
			
			$s ++;
		}
	}
	
	/**
	 * Add a user-created category; Does not add subcategories or databases,
	 * just the category. Category should not have an 'id' property, will be
	 * supplied by auto-incremented db column.
	 *
	 * @param Xerxes_Data_Category $objCategory
	 */
	
	public function addUserCreatedCategory(Xerxes_Data_Category $objCategory)
	{
		// We don't use metalib-id or old for user-created categories
		unset( $objCategory->metalib_id );
		unset( $objCategory->old );
		
		$new_pk = $this->doSimpleInsert( "xerxes_user_categories", $objCategory, true );
		
		$objCategory->id = $new_pk;
		return $objCategory;
	}
	
	/**
	 * Does not update subcategory assignments, only the actual category
	 * values, at present. Right now, just name and normalized!
	 * 
	 * @param Xerxes_Data_Category $objCategory 	a category object
	 */

	public function updateUserCategoryProperties(Xerxes_Data_Category $objCategory)
	{
		$objCategory->normalized = Xerxes_Data_Category::normalize( $objCategory->name );
		
		$sql = "UPDATE xerxes_user_categories SET name = :name, normalized = :normalized, published = :published WHERE id = " . $objCategory->id;
		
		return $this->update( $sql, array (":name" => $objCategory->name, ":normalized" => $objCategory->normalized, ":published" => $objCategory->published ) );
	}
	
	/**
	 * Add a user-created subcategory; Does not add databases joins,
	 * just the subcategory. Category should not have an 'id' property, will be
	 * supplied by auto-incremented db column.
	 *
	 * @param Xerxes_Data_Subcategory $objSubcat
	 * @return Xerxes_Data_Subcategory subcategory
	 */
	
	public function addUserCreatedSubcategory(Xerxes_Data_Subcategory $objSubcat)
	{
		//We don't use metalib-id, we use id instead, sorry. 
		unset( $objSubcat->metalib_id );
		
		$new_pk = $this->doSimpleInsert( "xerxes_user_subcategories", $objSubcat, true );
		$objSubcat->id = $new_pk;
		return $objSubcat;
	}
	
	/**
	 * Delete a user subcategory
	 *
	 * @param Xerxes_Data_Subcategory $objSubcat	subcategort oject
	 * @return int 									delete status
	 */
	
	public function deleteUserCreatedSubcategory(Xerxes_Data_Subcategory $objSubcat)
	{
		$sql = "DELETE FROM xerxes_user_subcategories WHERE ID = :subcategory_id";
		return $this->delete( $sql, array (":subcategory_id" => $objSubcat->id ) );
	}
	
	/**
	 * Delete a user created category
	 *
	 * @param Xerxes_Data_Category $objCat		category object
	 * @return int 								detelete status
	 */
	
	public function deleteUserCreatedCategory(Xerxes_Data_Category $objCat)
	{
		$sql = "DELETE FROM xerxes_user_categories WHERE ID = :category_id";
		return $this->delete( $sql, array (":category_id" => $objCat->id ) );
	}
	
	/**
	 * Add a database to a user-created subcategory; 
	 *
	 * @param String $databaseID the metalib_id of a Xerxes database object
	 * @param Xerxes_Data_Subcategory $objSubcat object representing user created subcat
	 * @param int sequence optional, will default to end of list if null. 
	 */

	public function addDatabaseToUserCreatedSubcategory($databaseID, Xerxes_Data_Subcategory $objSubcat, $sequence = null)
	{
		if ( $sequence == null )
			$sequence = count( $objSubcat->databases ) + 1;
		
		$strSQL = "INSERT INTO xerxes_user_subcategory_databases ( database_id, subcategory_id, sequence ) " . "VALUES ( :database_id, :subcategory_id, :sequence )";
		
		$arrValues = array (":database_id" => $databaseID, ":subcategory_id" => $objSubcat->id, ":sequence" => $sequence );
		
		$this->insert( $strSQL, $arrValues );
	}
	
	/**
	 * Does not update database assignments, only the actual subcat values. 
	 * Right now, just name and sequence!
	 *
	 * @param Xerxes_Data_Subcategory $objSubcat	subcatgeory object
	 * @return int 									update status
	 */
	
	public function updateUserSubcategoryProperties(Xerxes_Data_Subcategory $objSubcat)
	{
		$sql = "UPDATE xerxes_user_subcategories SET name = :name, sequence = :sequence WHERE id = " . $objSubcat->id;
		return $this->update( $sql, array (":name" => $objSubcat->name, ":sequence" => $objSubcat->sequence ) );
	}
	
	/**
	 * Remove database from user created subcategory
	 *
	 * @param string $databaseID					database id		
	 * @param Xerxes_Data_Subcategory $objSubcat	subcategory object
	 */
	
	public function removeDatabaseFromUserCreatedSubcategory($databaseID, Xerxes_Data_Subcategory $objSubcat)
	{
		$strDeleteSql = "DELETE from xerxes_user_subcategory_databases WHERE database_id = :database_id AND subcategory_id = :subcategory_id";
		$this->delete( $strDeleteSql, array (":database_id" => $databaseID, ":subcategory_id" => $objSubcat->id ) );
	}
	
	/**
	 * Update the 'sequence' number of a database in a user created category
	 *
	 * @param Xerxes_Data_Database $objDb			database object 
	 * @param Xerxes_Data_Subcategory $objSubcat	subcategory
	 * @param int $sequence							sequence number
	 */
	
	public function updateUserDatabaseOrder(Xerxes_Data_Database $objDb, Xerxes_Data_Subcategory $objSubcat, $sequence)
	{
		$this->beginTransaction();
		
		//first delete an existing join object.
		$this->removeDatabaseFromUserCreatedSubcategory( $objDb->metalib_id, $objSubcat );
		
		// Now create our new one with desired sequence. 
		$this->addDatabaseToUserCreatedSubcategory( $objDb->metalib_id, $objSubcat, $sequence );
		
		$this->commit();
		
	//commit transaction
	}
	
	/**
	 * Convert metalib dates to something MySQL can understand
	 *
	 * @param string $strValue		metalib date
	 * @return string				newly formatted date
	 */
	
	private function convertMetalibDate($strValue)
	{
		$strDate = null;
		$arrDate = array ( );
		
		if ( preg_match( "/([0-9]{4})([0-9]{2})([0-9]{2})/", $strValue, $arrDate ) != 0 )
		{
			if ( checkdate( $arrDate[2], $arrDate[3], $arrDate[1] ) )
			{
				$strDate = $arrDate[1] . "-" . $arrDate[2] . "-" . $arrDate[3];
			}
		}
		
		return $strDate;
	}
	
	/**
	 * Convert metalib boolean values to 1 or 0
	 *
	 * @param string $strValue	'yes' or 'Y' will become 1. "no" or "N" will become 0. All others null. 
	 * @return int				1 or 0 or null
	 */
	
	private function convertMetalibBool($strValue)
	{
		if ( $strValue == "yes" || $strValue == "Y" )
		{
			return 1;
		} 
		elseif ( $strValue == "no" || $strValue == "N" )
		{
			return 0;
		} 
		else
		{
			return null;
		}
	}
	
	### KNOWLEDGEBASE GET FUNCTIONS ###
	

	/**
	 * Get the top level categories (subjects) from the knowledgebase
	 *
	 * @return array		array of Xerxes_Data_Category objects
	 */
	
	public function getCategories()
	{
		$arrCategories = array ( );
		
		$strSQL = "SELECT * from xerxes_categories ORDER BY UPPER(name) ASC";
		
		$arrResults = $this->select( $strSQL );
		
		foreach ( $arrResults as $arrResult )
		{
			$objCategory = new Xerxes_Data_Category( );
			$objCategory->load( $arrResult );
			
			array_push( $arrCategories, $objCategory );
		}
		
		return $arrCategories;
	}
	
	/**
	 * Get user-created categories for specified user. 
	 * @param string $username
	 * @return array		array of Xerxes_Data_Category objects
	 */
	
	public function getUserCreatedCategories($username)
	{
		if ( ! $username )
			throw new Exception( "Must supply a username argument" );
		
		$arrCategories = array ( );
		$strSQL = "SELECT * from xerxes_user_categories WHERE username = :username ORDER BY UPPER(name) ASC";
		$arrResults = $this->select( $strSQL, array (":username" => $username ) );
		
		foreach ( $arrResults as $arrResult )
		{
			$objCategory = new Xerxes_Data_Category( );
			$objCategory->load( $arrResult );
			
			array_push( $arrCategories, $objCategory );
		}
		
		return $arrCategories;
	}
	
	const metalibMode = 'metalib';
	const userCreatedMode = 'user_created';
	
	/**
	 * ->getSubject can be used in two modes, metalib-imported  categories, or user created categories. 
	 * We take from different db tables depending
	 *
	 * @param string $mode		'metalib' or 'user_created' mode 
	 * @return array
	 */
	
	protected function schema_map_by_mode($mode)
	{
		if ( $mode == self::metalibMode )
		{
			return array (
				"categories_table" => "xerxes_categories", 
				"subcategories_table" => "xerxes_subcategories", 
				"database_join_table" => "xerxes_subcategory_databases", 
				"subcategories_pk" => "metalib_id", 
				"extra_select" => "", "extra_where" => "" 
			);
		} 
		elseif ( $mode == self::userCreatedMode )
		{
			return array (
				"categories_table" => "xerxes_user_categories", 
				"subcategories_table" => "xerxes_user_subcategories", 
				"database_join_table" => "xerxes_user_subcategory_databases", 
				"subcategories_pk" => "id", 
				"extra_select" => ", xerxes_user_categories.published AS published, xerxes_user_categories.username AS username", 
				"extra_where" => " AND xerxes_user_categories.username = :username "
			);
		} 
		else
		{
			throw new Exception( "unrecognized mode" );
		}
	}
	
	/**
	 * Get an inlined set of subcategories and databases for a subject. In
	 * metalibMode, empty subcategories are not included. In userCreatedMode,
	 * they are. 
	 *
	 * @param string $normalized		normalized category name
	 * @param string $old				old normalzied category name, for comp with Xerxes 1.0. Often can be left null in call. Only applicable to metalibMode. 
	 * @param string $mode  			one of constants metalibMode or userCreatedMode, for metalib-imported categories or user-created categories, using different tables.
	 * @param string $username 			only used in userCreatedMode, the particular user must be specified, becuase normalized subject names are only unique within a user. 
	 * @return Xerxes_Data_Category		a Xerxes_Data_Category object, filled out with subcategories and databases. 
	 */
	
	public function getSubject($normalized, $old = null, $mode = self::metalibMode, $username = null)
	{
		if ( $mode == self::userCreatedMode && $username == null )
			throw new Exception( "a username argument must be supplied in userCreatedMode" );
			
		//This can be used to fetch personal or metalib-fetched data. We get
		// from different tables depending. 
		$schema_map = $this->schema_map_by_mode( $mode );
		
		// we'll use the new 'categories' normalized scheme if available, but 
		// otherwise get the old normalized scheme with the capitalizations for 
		// compatability with xerxes 1.0 release.
		

		$column = "normalized";
		
		if ( $normalized == null && $old != null )
		{
			$normalized = $old;
			$column = "old";
		}
		
		// we're outer joining only to group_restrictions, because it's
		// really confusing and complicated to write this SQL, and that's all
		// we need right now.

		$strSQL = "SELECT $schema_map[categories_table].id as category_id, 
			$schema_map[categories_table].name as category,
			$schema_map[subcategories_table].$schema_map[subcategories_pk] as subcat_id,
			$schema_map[subcategories_table].sequence as subcat_seq, 
			$schema_map[subcategories_table].name as subcategory, 
			$schema_map[database_join_table].sequence as sequence,
			xerxes_databases.*,
			xerxes_database_group_restrictions.usergroup
			$schema_map[extra_select]
			FROM $schema_map[categories_table]
			LEFT OUTER JOIN $schema_map[subcategories_table] ON $schema_map[categories_table].id = $schema_map[subcategories_table].category_id
			LEFT OUTER JOIN $schema_map[database_join_table] ON $schema_map[database_join_table].subcategory_id = $schema_map[subcategories_table].$schema_map[subcategories_pk]
			LEFT OUTER JOIN xerxes_databases ON $schema_map[database_join_table].database_id = xerxes_databases.metalib_id
			LEFT OUTER JOIN xerxes_database_group_restrictions ON xerxes_databases.metalib_id = xerxes_database_group_restrictions.database_id
			WHERE $schema_map[categories_table].$column = :value
			AND 
			($schema_map[subcategories_table].name NOT LIKE 'All%' OR
			$schema_map[subcategories_table].name is NULL)
			$schema_map[extra_where]
			ORDER BY subcat_seq, sequence";
		  
		$args = array (":value" => $normalized );
		
		if ( $username )
		{
			$args[":username"] = $username;
		}
		
		$arrResults = $this->select( $strSQL, $args );
		
		if ( $arrResults != null )
		{
			$objCategory = new Xerxes_Data_Category( );
			$objCategory->id = $arrResults[0]["category_id"];
			$objCategory->name = $arrResults[0]["category"];
			$objCategory->normalized = $normalized;
			
			// these two only for user-created categories, will be nil otherwise.
			
			if ( array_key_exists( "username", $arrResults[0] ) )
			{
				$objCategory->owned_by_user = $arrResults[0]["username"];
			}
			
			if ( array_key_exists( "published", $arrResults[0] ) )
			{
				$objCategory->published = $arrResults[0]["published"];
			}
			
			$objSubcategory = new Xerxes_Data_Subcategory( );
			//$objSubcategory->metalib_id = $arrResults[0]["subcat_id"];
			$objSubcategory->id = $arrResults[0]["subcat_id"];
			$objSubcategory->name = $arrResults[0]["subcategory"];
			$objSubcategory->sequence = $arrResults[0]["subcat_seq"];
			
			$objDatabase = new Xerxes_Data_Database( );
			
			foreach ( $arrResults as $arrResult )
			{
				// if the current row's subcategory name does not match the previous
				// one, then push the previous one onto category obj and make a new one

				if ( $arrResult["subcat_id"] != $objSubcategory->id )
				{
					// get the last db in this subcategory first too.
					
					if ( $objDatabase->metalib_id != null )
					{
						array_push( $objSubcategory->databases, $objDatabase );
					}
					
					$objDatabase = new Xerxes_Data_Database( );
					
					// only add subcategory if it actually has databases, to
					// maintain consistency with previous semantics.
					
					if ( ($mode == self::userCreatedMode && $objSubcategory->id) || ! empty( $objSubcategory->databases ) )
					{
						array_push( $objCategory->subcategories, $objSubcategory );
					}
					
					$objSubcategory = new Xerxes_Data_Subcategory( );
					$objSubcategory->id = $arrResult["subcat_id"];
					$objSubcategory->name = $arrResult["subcategory"];
					$objSubcategory->sequence = $arrResult["subcat_seq"];
				}
				
				// if the previous row has a different id, then we've come 
				// to a new database, otherwise these are values from the outer join

				if ( $arrResult["metalib_id"] != $objDatabase->metalib_id )
				{
					// existing one that isn't empty? save it.
					
					if ( $objDatabase->metalib_id != null )
					{
						array_push( $objSubcategory->databases, $objDatabase );
					}
					
					$objDatabase = new Xerxes_Data_Database( );
					$objDatabase->load( $arrResult );
				}
				
				// if the current row's outter join value is not already stored,
				// then then we've come to a unique value, so add it

				$arrColumns = array ("usergroup" => "group_restrictions" );
				
				foreach ( $arrColumns as $column => $identifier )
				{
					if ( array_key_exists( $column, $arrResult ) && ! is_null( $arrResult[$column] ) )
					{
						if ( ! in_array( $arrResult[$column], $objDatabase->$identifier ) )
						{
							array_push( $objDatabase->$identifier, $arrResult[$column] );
						}
					}
				}
			
			}
			
			// last ones
			
			if ( $objDatabase->metalib_id != null )
			{
				array_push( $objSubcategory->databases, $objDatabase );
			}
			
			if ( ($mode == self::userCreatedMode && $objSubcategory->id) || ! empty( $objSubcategory->databases ) )
			{
				array_push( $objCategory->subcategories, $objSubcategory );
			}
			
			// subcategories excluded by config
			
			$strSubcatInclude = $this->registry->getConfig( "SUBCATEGORIES_INCLUDE", false );
			
			if ( $strSubcatInclude != "" && $mode == self::metalibMode)
			{							
				// this is kind of funky, but if we simply unset the subcategory, the array keys get out
				// of order, and the first one may therefore not be 0, which is a problem in higher parts of 
				// the system where we look for the first subcategory as $category->subcategories[0], so
				// we take them all out and put them all back in, including only the ones we want
				
				$arrInclude = explode(",", $strSubcatInclude);
				
				$arrSubjects =  $objCategory->subcategories;
				$objCategory->subcategories = null;
				
				foreach ( $arrSubjects as $subcat )
				{
					foreach ( $arrInclude as $strInclude )
					{
						$strInclude = trim($strInclude);
						
						if ( stristr($subcat->name, $strInclude) )
						{
							$objCategory->subcategories[] = $subcat;
							break;
						}
					}
				}
			}
			
			return $objCategory;
		} 
		else
		{
			return null;
		}
	
	}
	
	/**
	 * Get a single database from the knowledgebase
	 *
	 * @param string $id				metalib id
	 * @return Xerxes_Data_Database
	 */
	
	public function getDatabase($id)
	{
		$arrResults = $this->getDatabases( $id );
		
		if ( count( $arrResults ) > 0 )
		{
			return $arrResults[0];
		} 
		else
		{
			return null;
		}
	}
	
	/**
	 * Get one or a set of databases from the knowledgebase
	 *
	 * @param mixed $id			[optional] null returns all database, array returns a list of databases by id, 
	 * 							string id returns single id
	 * @param string $query   user-entered query to search for dbs. 
	 * @return array			array of Xerxes_Data_Database objects
	 */
	
	public function getDatabases($id = null, $query = null)
	{
		$arrDatabases = array ( );
		$arrResults = array ( );
		$arrParams = array ( );
		
		$strSQL = "SELECT * from xerxes_databases
			LEFT OUTER JOIN xerxes_database_notes ON xerxes_databases.metalib_id = xerxes_database_notes.database_id
			LEFT OUTER JOIN xerxes_database_group_restrictions ON xerxes_databases.metalib_id = xerxes_database_group_restrictions.database_id
			LEFT OUTER JOIN xerxes_database_keywords ON xerxes_databases.metalib_id = xerxes_database_keywords.database_id
			LEFT OUTER JOIN xerxes_database_languages ON xerxes_databases.metalib_id = xerxes_database_languages.database_id 
			LEFT OUTER JOIN xerxes_database_alternate_titles ON xerxes_databases.metalib_id = xerxes_database_alternate_titles.database_id
			LEFT OUTER JOIN xerxes_database_alternate_publishers ON xerxes_databases.metalib_id = xerxes_database_alternate_publishers.database_id ";
		
		if ( $id != null && is_array( $id ) )
		{
			// databases specified by an array of ids

			$strSQL .= " WHERE ";
			
			for ( $x = 0 ; $x < count( $id ) ; $x ++ )
			{
				if ( $x > 0 )
				{
					$strSQL .= " OR ";
				}
				
				$strSQL .= "xerxes_databases.metalib_id = :id$x ";
				$arrParams[":id$x"] = $id[$x];
			}
			
			$strSQL .= " ORDER BY xerxes_databases.metalib_id";
		} 
		elseif ( $id != null && ! is_array( $id ) )
		{
			// single database query

			$strSQL .= " WHERE xerxes_databases.metalib_id = :id ";
			$arrParams[":id"] = $id;
		} 
		elseif ( $query != null )
		{
			$strSQL .= "WHERE xerxes_databases.title_display LIKE :query1 OR " .
				" xerxes_databases.title_full LIKE :query2 OR " .
				" xerxes_databases.description LIKE :query3 OR " .
				" xerxes_database_keywords.keyword LIKE :query4 OR " .
				" xerxes_database_alternate_titles.alt_title LIKE :query5 " .
				" ORDER BY UPPER(title_display) ";
				
			$searchParam = '%' . $query . '%';

			$arrParams[":query1"] = $searchParam;
			$arrParams[":query2"] = $searchParam;
			$arrParams[":query3"] = $searchParam;
			$arrParams[":query4"] = $searchParam;
			$arrParams[":query5"] = $searchParam;
		} 
		else
		{
			// all databases, sorted alphabetically
			
			// remove certain database types, if so configured
			
			$configDatabaseTypesExclude = $this->registry->getConfig("DATABASES_TYPE_EXCLUDE_AZ", false);
			
			if ( $configDatabaseTypesExclude != null )
			{
				$arrTypes = explode(",", $configDatabaseTypesExclude);
				$arrTypeQuery = array();
				
				// specify that the type NOT be one of these
				
				for ( $q = 0; $q < count($arrTypes); $q++ )
				{
					array_push($arrTypeQuery, "xerxes_databases.type != :type$q");
					$arrParams[":type$q"] = trim($arrTypes[$q]);
				}
				
				// AND 'em but then also catch the case where type is null
				
				$strSQL .= " WHERE (" . implode (" AND ", $arrTypeQuery) . ") OR xerxes_databases.type IS NULL ";
			}
			
			$strSQL .= " ORDER BY UPPER(title_display)";
		}
		
		// echo $strSQL; exit;
		
		$arrResults = $this->select( $strSQL, $arrParams );
		
		// read sql and transform to internal data objs.
		
		if ( $arrResults != null )
		{
			$objDatabase = new Xerxes_Data_Database( );
			
			foreach ( $arrResults as $arrResult )
			{
				// if the previous row has a different id, then we've come 
				// to a new database, otherwise these are values from the outter join

				if ( $arrResult["metalib_id"] != $objDatabase->metalib_id )
				{
					if ( $objDatabase->metalib_id != null )
					{
						array_push( $arrDatabases, $objDatabase );
					}
					
					$objDatabase = new Xerxes_Data_Database( );
					$objDatabase->load( $arrResult );
				}
				
				// if the current row's outter join value is not already stored,
				// then we've come to a unique value, so add it

				$arrColumns = array ("keyword" => "keywords", "usergroup" => "group_restrictions", "language" => "languages", "note" => "notes", "alt_title" => "alternate_titles", "alt_publisher" => "alternate_publishers" );
				
				foreach ( $arrColumns as $column => $identifier )
				{
					if ( array_key_exists( $column, $arrResult ) && ! is_null( $arrResult[$column] ) )
					{
						if ( ! in_array( $arrResult[$column], $objDatabase->$identifier ) )
						{
							array_push( $objDatabase->$identifier, $arrResult[$column] );
						}
					}
				}
			}
			
			// get the last one
			
			array_push( $arrDatabases, $objDatabase );
		}
		
		return $arrDatabases;
	}
	
	/**
	 * Get the list of types
	 *
	 * @return array	array of Xerxes_Data_Type objects
	 */
	
	public function getTypes()
	{
		$arrTypes = array ( );
		
		$strSQL = "SELECT * from xerxes_types ORDER BY UPPER(name) ASC";
		
		$arrResults = $this->select( $strSQL );
		
		foreach ( $arrResults as $arrResult )
		{
			$objType = new Xerxes_Data_Type( );
			$objType->load( $arrResult );
			
			array_push( $arrTypes, $objType );
		}
		
		return $arrTypes;
	}
	
	### CACHE FUNCTIONS ###
	

	/**
	 * Set data into the cache table, will add a timestamp and an expiry
	 * if none is supplied in the cache data object
	 *
	 * @param Xerxes_Data_Cache $objCache
	 */
	
	public function setCache(Xerxes_Data_Cache $objCache)
	{
		// set timestamp if not specified

		if ( $objCache->timestamp == null )
		{
			$objCache->timestamp = time();
		}
		
		// if no expiry specified, set a 6 hour cache

		if ( $objCache->expiry == null )
		{
			$objCache->expiry = 6 * 60 * 60;
		}
		
		// delete any previously stored value under this group + id

		$this->beginTransaction();
		
		$arrParams = array ( );
		$arrParams[":grouping"] = $objCache->grouping;
		$arrParams[":id"] = $objCache->id;
		$arrParams[":timestamp"] = $objCache->timestamp;
		
		$strSQL = "DELETE FROM xerxes_cache WHERE grouping = :grouping AND id = :id and timestamp < :timestamp";
		
		$this->delete( $strSQL, $arrParams );
		
		// now insert the new value

		$this->doSimpleInsert( "xerxes_cache", $objCache );
		
		$this->commit();
	}
	
	/**
	 * Get a group of cached data by grouping identifier
	 *
	 * @param string $group			id that identifies the group
	 * @param int $expiry			timestamp expiry data that the data should be no older than
	 * @return array				array of Xerxes_Data_Cache objects
	 */
	
	public function getCacheGroup($group, $expiry = null)
	{
		$arrCache = array ( );
		$arrParams = array ( );
		$arrParams[":group"] = $group;
		
		$strSQL = "SELECT * FROM xerxes_cache WHERE grouping = :group ";
		
		if ( $expiry != null )
		{
			$strSQL .= " AND expiry <= :expiry";
			$arrParams[":expiry"] = $expiry;
		}
		
		$arrResults = $this->select( $strSQL, $arrParams );
		
		foreach ( $arrResults as $arrResult )
		{
			$objCache = new Xerxes_Data_Cache( );
			$objCache->load( $arrResult );
			
			array_push( $arrCache, $objCache );
		}
		
		return $arrCache;
	}
	
	/**
	 * Clear out old items in the cache
	 *
	 * @param string $source		[optional] clear only objects from a named source
	 * @param int $timestamp		[optional] clear only objects older than a given timestamp
	 * @return int					SQL status code
	 */
	
	public function pruneCache($source = "", $timestamp = "")
	{
		$arrParams = array ( );
		
		if ( $timestamp == null )
		{
			// default timestamp is two days previous
			$timestamp = time() - (2 * 24 * 60 * 60);
		}
		
		$arrParams[":timestamp"] = $timestamp;
		
		$strSQL = " DELETE FROM xerxes_cache WHERE timestamp < :timestamp ";
		
		if ( $source != "" )
		{
			$strSQL .= " AND source = :source";
			$arrParams[":source"] = $source;
		}
		
		return $this->delete( $strSQL, $arrParams );
	}
	
	### SAVED RECORD FUNCTIONS ###
	

	/**
	 * Get the total number of saved records for the user
	 *
	 * @param string $strUsername	username under which records are saved
	 * @param string $strLabel		[optional] limit count to a specific tag label
	 * @param string $strFormat		[optional] limit count to a specific format
	 * @return int					number of saved records
	 */
	
	public function totalRecords($strUsername, $strLabel = null, $strFormat = null)
	{
		$arrParams = array ( );
		
		// labels are little different, since we need to make sure they
		// include the tags table 

		if ( $strLabel != null )
		{
			$strSQL = "SELECT count(*) as total FROM xerxes_records, xerxes_tags " . 
				" WHERE xerxes_tags.record_id = xerxes_records.id AND xerxes_records.username = :user " . 
				" AND xerxes_tags.tag = :tag";
			
			$arrParams[":user"] = $strUsername;
			$arrParams[":tag"] = $strLabel;
		} 
		else
		{
			// faster to get all or format-specific group just from the records table
			

			$strSQL = "SELECT count(*) as total FROM xerxes_records WHERE username = :user";
			$arrParams[":user"] = $strUsername;
			
			if ( $strFormat != null )
			{
				$strSQL .= " AND ( format = :format ) ";
				$arrParams[":format"] = $strFormat;
			}
		}
		
		$arrResults = $this->select( $strSQL, $arrParams );
		
		return ( int ) $arrResults[0]["total"];
	}
	
	/**
	 * Reassign records previously saved under a temporary (or old) username to a new one
	 *
	 * @param string $old			old username
	 * @param string $new			new username
	 * @return int status
	 */
	
	public function reassignRecords($old, $new)
	{
		$strSQL = "UPDATE xerxes_records SET username = :new WHERE username = :old";
		
		return $this->update( $strSQL, array (":old" => $old, ":new" => $new ) );
	}
	
	public function getRecords($strUsername, $strView = null, $strOrder = null, $iStart = 1, $iCount = 20)
	{
		return $this->returnRecords( $strUsername, $strView, null, $strOrder, $iStart, $iCount );
	}
	
	public function getRecordsByLabel($strUsername = null, $strLabel, $strOrder = null, $iStart = 1, $iCount = null)
	{
		return $this->returnRecords( $strUsername, null, null, $strOrder, $iStart, $iCount, null, $strLabel );
	}
	
	public function getRecordsByFormat($strUsername = null, $strFormat, $strOrder = null, $iStart = 1, $iCount = null)
	{
		return $this->returnRecords( $strUsername, null, null, $strOrder, $iStart, $iCount, $strFormat );
	}
	
	public function getRecordByID($strID)
	{
		$arrResults = $this->getRecordsByID( array ($strID ) );
		if ( count( $arrResults ) == 0 )
		{
			return null;
		} 
		elseif ( count( $arrResults ) == 1 )
		{
			return $arrResults[0];
		} 
		else
		{
			throw new Exception( "More than one saved record found for id $strID !" );
		}
	}
	
	public function getRecordsByID($arrID, $strOrder = null)
	{
		return $this->returnRecords( null, null, $arrID, $strOrder );
	}
	
	/**
	 * Get a set of records from the user's saved records table 
	 *
	 * @param string $strUsername		[optional] username under which the records are saved
	 * @param string $strView			[optional] 'brief' or 'full', defaults to 'full'.
	 * @param string $strOrder			[optional] sort order of the results" 'year', 'author' or 'title', defaults to date added (desc)
	 * @param array $arrID				[optional] array of id values
	 * @param int $iStart				[optional] offset to start from, defaults to 1, unless $arrID specified
	 * @param int $iCount				[optional] number of records to return, defaults to all, unless $arrID specified
	 * @param string $strFormat			[optional] limit records to specific format
	 * @param string $strLabel			[optiional] limit record to specific tag
	 * @return array					array of Xerxes_Data_Record objects
	 */
	
	private function returnRecords($strUsername = null, $strView = "full", $arrID = null, $strOrder = null, $iStart = 1, $iCount = null, $strFormat = null, $strLabel = null)
	{
		// esnure that we don't just end-up with a big database dump

		if ( $arrID == null && $strUsername == null && $iCount == null )
		{
			throw new Exception( "query must be limited by username, id(s), or record count limit" );
		}
		
		#### construct the query

		$arrParams = array ( ); // sql paramaters
		$strSQL = ""; // main sql query
		$strTable = ""; // tables to include
		$strColumns = ""; // column portion of query
		$strCriteria = ""; // where clause in query
		$strLimit = ""; // record limit and off-set
		$strSort = ""; // sort part query
		

		// set the start record, limit and offset; mysql off-set is zero-based

		if ( $iStart == null )
		{
			$iStart = 1;
		}
		
		$iStart --;
		
		// we'll only apply a limit if there was a count

		if ( $iCount != null )
		{
			$strLimit = " LIMIT $iStart, $iCount ";
		}
		
		// which columns to include -- may not actually use brief any more
		
		$strTable = " xerxes_records ";
		$strColumns = " * ";
		
		if ( $strView == "brief" )
		{
			$strColumns = " xerxes_records.id, xerxes_records.original_id, xerxes_records.source, 
				xerxes_records.username, xerxes_records.nonsort, xerxes_records.title, xerxes_records.author, 
				xerxes_records.format, xerxes_records.year, xerxes_records.refereed ";
		} 
		else
		{
			$strColumns = " xerxes_records.* ";
		}
		
		// limit to a specific user

		if ( $strUsername != "" )
		{
			$strCriteria = " WHERE xerxes_records.username = :username ";
			$arrParams[":username"] = $strUsername;
		} 
		else
		{
			$strCriteria = " WHERE xerxes_records.username LIKE '%' ";
		}
		
		// limit to specific tag

		if ( $strLabel != "" )
		{
			// need to include the xerxes tags table

			$strTable .= ", xerxes_tags ";
			
			// and limit the results to only those where the tag matches!

			$strCriteria .= " AND xerxes_tags.record_id = xerxes_records.id ";
			$strCriteria .= " AND xerxes_tags.tag = :tag ";
			
			$arrParams[":tag"] = $strLabel;
		}
		
		// limit to specific format
		
		if ( $strFormat != "" )
		{
			$strCriteria .= " AND format = :format ";
			$arrParams[":format"] = $strFormat;
		}
		
		// limit to specific records by id

		if ( $arrID != null )
		{
			// make sure we've got an array 
			
			if ( ! is_array( $arrID ) )
			{
				$arrID = array ($arrID );
			}
			
			$strCriteria .= " AND (";
			
			for ( $x = 0 ; $x < count( $arrID ) ; $x ++ )
			{
				if ( $x > 0 )
				{
					$strCriteria .= " OR";
				}
				
				$strCriteria .= " id = :id$x ";
				$arrParams[":id$x"] = $arrID[$x];
			}
			
			$strCriteria .= ")";
		}
		
		// sort option
		// order by supplied sort criteria otherwise by id
		// to show most recently added first
		
		switch ( $strOrder )
		{
			case "year" :
				$strSort = " ORDER BY year DESC";
				break;
			case "author" :
				$strSort = " ORDER BY author";
				break;
			case "title" :
				$strSort = " ORDER BY title";
				break;
			default :
				$strSort = " ORDER BY id DESC";
				break;
		}
		
		// kind of a funky query, but do it this way to limit to 10 (or whatever) records
		// per page, while joining in as many tags as exist

		$strSQL = "SELECT * FROM 
			(SELECT $strColumns FROM $strTable $strCriteria $strSort $strLimit ) as xerxes_records
			LEFT OUTER JOIN xerxes_tags on xerxes_records.id = xerxes_tags.record_id";

		// ms sql server specific code
		
		if ( $this->rdbms == "mssql")
		{
			// mimicking the MySQL LIMIT clause
			
			$strMSPage = "";
			
			if ( $iCount != null)
			{
				$strMSLimit = $iStart + $iCount;
				$strMSPage = "WHERE row > $iStart and row <= $strMSLimit";
			}
			
			$strSQL = "SELECT * FROM
				( SELECT * FROM ( SELECT $strColumns , ROW_NUMBER() OVER ( $strSort ) as row FROM $strTable $strCriteria ) 
					as tmp $strMSPage ) as xerxes_records 
				LEFT OUTER JOIN xerxes_tags on xerxes_records.id = xerxes_tags.record_id";
				
			// a bug in the pdo odbc driver ??? makes this necessary
			
			// clean the input
			
			$dirtystuff = array("\"", "\\", "/", "*", "'", "=", "-", "#", ";", "<", ">", "+", "%");
			
			$strUsername = str_replace($dirtystuff, "", $strUsername); 
			$strLabel = str_replace($dirtystuff, "", $strLabel);
			$strFormat = str_replace($dirtystuff, "", $strFormat);
			
			// replace the paramater with the value
				
			$strSQL = str_replace(":username", "'$strUsername'", $strSQL); unset($arrParams[":username"]);
			$strSQL = str_replace(":tag", "'$strLabel'", $strSQL); unset($arrParams[":tag"]);
			$strSQL = str_replace(":format", "'$strFormat'", $strSQL); unset($arrParams[":format"]);
			
			for ( $x = 0 ; $x < count( $arrID ) ; $x ++ )
			{
				$strID = str_replace($dirtystuff, "", $arrID[$x]); 
				$strSQL = str_replace(":id$x", "'$strID'", $strSQL); unset($arrParams[":id$x"]);
			}
		}

		
		#### return the objects
		
		$arrResults = array ( ); // results as array
		$arrRecords = array ( ); // records as array
		
		$arrResults = $this->select( $strSQL, $arrParams );
		
		if ( $arrResults != null )
		{
			$objRecord = new Xerxes_Data_Record( );
			
			foreach ( $arrResults as $arrResult )
			{
				// if the previous row has a different id, then we've come 
				// to a new database, otherwise these are values from the outter join

				if ( $arrResult["id"] != $objRecord->id )
				{
					if ( $objRecord->id != null )
					{
						array_push( $arrRecords, $objRecord );
					}
					
					$objRecord = new Xerxes_Data_Record( );
					$objRecord->load( $arrResult );
					
					// only full display will include marc records

					if ( array_key_exists( "marc", $arrResult ) )
					{
						$objXerxes_Record = new Xerxes_MetalibRecord( );
						$objXerxes_Record->loadXML( $arrResult["marc"] );
						$objRecord->xerxes_record = $objXerxes_Record;
					}
				}
				
				// if the current row's outter join value is not already stored,
				// then then we've come to a unique value, so add it

				$arrColumns = array ("tag" => "tags" );
				
				foreach ( $arrColumns as $column => $identifier )
				{
					if ( array_key_exists( $column, $arrResult ) )
					{
						if ( ! in_array( $arrResult[$column], $objRecord->$identifier ) )
						{
							array_push( $objRecord->$identifier, $arrResult[$column] );
						}
					}
				}
			}
			
			// get the last one

			array_push( $arrRecords, $objRecord );
		}
		
		return $arrRecords;
	}
	
	/**
	 * Retrive format-based record counts for saved records
	 *
	 * @param string $strUsername		username under which the records are saved
	 * @return array					array of Xerxes_Data_Record_Facet objects
	 */
	
	public function getRecordFormats($strUsername)
	{
		$arrFacets = array ( );
		
		$strSQL = "SELECT format, count(id) as total from xerxes_records WHERE username = :username GROUP BY format ORDER BY format";
		$arrResults = $this->select( $strSQL, array (":username" => $strUsername ) );
		
		foreach ( $arrResults as $arrResult )
		{
			$objRecord = new Xerxes_Data_Record_Format( );
			$objRecord->load( $arrResult );
			array_push( $arrFacets, $objRecord );
		}
		
		return $arrFacets;
	}
	
	/**
	 * Retrieve listing and count of labels for saved records
	 *
	 * @param unknown_type $strUsername
	 * @return unknown
	 */
	
	public function getRecordTags($strUsername)
	{
		$arrFacets = array ( );
		
		$strSQL = "SELECT tag as label, count(record_id) as total from xerxes_tags WHERE username = :username GROUP BY tag ORDER BY label";
		$arrResults = $this->select( $strSQL, array (":username" => $strUsername ) );
		
		foreach ( $arrResults as $arrResult )
		{
			$objRecord = new Xerxes_Data_Record_Tag( );
			$objRecord->load( $arrResult );
			array_push( $arrFacets, $objRecord );
		}
		
		return $arrFacets;
	}
	
	/**
	 * Associate tags with a saved record
	 *
	 * @param string $strUsername		username 
	 * @param array $arrTags			array of tags supplied by user
	 * @param int $iRecord				record id tags are associated with
	 */
	
	public function assignTags($strUsername, $arrTags, $iRecord)
	{
		// data check

		if ( $strUsername == "" ) throw new Exception( "param 1 'username' must not be null" );
		if ( ! is_array( $arrTags ) ) throw new Exception( "param 2 'tags' must be of type array" );
		if ( $iRecord == "" ) throw new Exception( "param 3 'record' must not be null" );
			
		// wrap it in a transaction, yo!

		$this->beginTransaction();
		
		// first clear any old tags associated with the record, so 
		// we can 'edit' and 'add' on the same action

		$strSQL = "DELETE FROM xerxes_tags WHERE record_id = :record_id AND username = :username";
		$this->delete( $strSQL, array (":record_id" => $iRecord, ":username" => $strUsername ) );
		
		// now assign the new ones to the database
		
		foreach ( $arrTags as $strTag )
		{
			if ( $strTag != "" )
			{
				$strSQL = "INSERT INTO xerxes_tags (username, record_id, tag) VALUES (:username, :record_id, :tag)";
				$this->insert( $strSQL, array (":username" => $strUsername, ":record_id" => $iRecord, ":tag" => $strTag ) );
			}
		}
		
		$this->commit();
	}
	
	/**
	 * Update the user table to include the last date of login and any other
	 * specified attributes. Creates new user if neccesary.
	 * If any attributes in Xerxes_User are set other than
	 * username, those will also be written to db over-riding anything that may
	 * have been there.  Returns Xerxes_User filled out with information matching
	 * db. 
	 *
	 * @param Xerxes_User $user
	 * @return Xerxes_User $user
	 */
	
	public function touchUser(Xerxes_User $user)
	{
		// array to pass to db updating routines. Make an array out of our
		// properties. 

		$update_values = array ( );
		
		foreach ( $user->properties() as $key => $value )
		{
			$update_values[":" . $key] = $value;
		}
		
		// don't use usergroups though. 
		
		unset( $update_values[":usergroups"] );
		$update_values[":last_login"] = date( "Y-m-d H:i:s" );
		
		$this->beginTransaction();
		
		$strSQL = "SELECT * FROM xerxes_users WHERE username = :username";
		$arrResults = $this->select( $strSQL, array (":username" => $user->username ) );
		
		if ( count( $arrResults ) == 1 )
		{
			// user already exists in database, so update the last_login time and
			// use any data specified in our Xerxes_User record to overwrite. Start
			// with what's already there, overwrite with anything provided in
			// the Xerxes_User object. 
			
			$db_values = $arrResults[0];
			
			foreach ( $db_values as $key => $value )
			{
				if ( ! (is_null( $value ) || is_numeric( $key )) )
				{
					$dbKey = ":" . $key;
					
					// merge with currently specified values
					

					if ( ! array_key_exists( $dbKey, $update_values ) )
					{
						$update_values[$dbKey] = $value;
						
					//And add it to the user object too
					//$user->$key = $value;
					

					}
				}
			}
			
			$strSQL = "UPDATE xerxes_users SET last_login = :last_login, suspended = :suspended, first_name = :first_name, last_name = :last_name, email_addr = :email_addr WHERE username = :username";
			$status = $this->update( $strSQL, $update_values );
		} 
		else
		{
			// add em otherwise
			

			$strSQL = "INSERT INTO xerxes_users ( username, last_login, suspended, first_name, last_name, email_addr) VALUES (:username, :last_login, :suspended, :first_name, :last_name, :email_addr)";
			$status = $this->insert( $strSQL, $update_values );
		}
		
		// add let's make our group assignments match, unless the group
		// assignments have been marked null which means to keep any existing ones
		// only.

		if ( is_null( $user->usergroups ) )
		{
			// fetch what's in the db and use that please.

			$fetched = $this->select( "SELECT usergroup FROM xerxes_user_usergroups WHERE username = :username", array (":username" => $user->username ) );
			if ( count( $fetched ) )
			{
				$user->usergroups = $fetched[0];
			} 
			else
			{
				$user->usergroups = array ( );
			}
		} 
		else
		{
			$status = $this->delete( "DELETE FROM xerxes_user_usergroups WHERE username = :username", array (":username" => $user->username ) );
			
			foreach ( $user->usergroups as $usergroup )
			{
				$status = $this->insert( "INSERT INTO xerxes_user_usergroups (username, usergroup) VALUES (:username, :usergroup)", array (":username" => $user->username, ":usergroup" => $usergroup ) );
			}
		}
		$this->commit();
		
		return $user;
	}
	
	/**
	 * Add a record to the user's saved record space. $objXerxesRecord will be
	 * updated with internal db id and original id.. 
	 *
	 * @param string $username					username to save the record under
	 * @param string $source					name of the source database
	 * @param string $id						identifier for the record
	 * @param Xerxes_Record $objXerxesRecord	xerxes record object to save
	 * @return int  status
	 */
	
	public function addRecord($username, $source, $id, Xerxes_Record $objXerxesRecord)
	{
		$arrValues = array ( );
		$strTitle = "";
		$strSubTitle = "";
		$iRefereed = 0;
		$iYear = 0;
		
		$iYear = ( int ) $objXerxesRecord->getYear();
		$strTitle = $objXerxesRecord->getMainTitle();
		$strSubTitle = $objXerxesRecord->getSubTitle();
		
		if ( $strSubTitle != "" ) $strTitle .= ": " . $strSubTitle;
			
		// peer-reviwed look-up

		if ( $objXerxesRecord->getISSN() != null )
		{
			$arrResults = $this->getRefereed( $objXerxesRecord->getISSN() );
			
			if ( count( $arrResults ) > 0 )
			{
				$iRefereed = 1;
			}
		}
		
		$strSQL = "INSERT INTO xerxes_records 
			( source, original_id, timestamp, username, nonsort, title, author, year, format, refereed, marc )
			VALUES 
			( :source, :original_id, :timestamp, :username, :nonsort, :title, :author, :year, :format, :refereed, :marc)";
		
		$arrValues[":source"] = $source;
		$arrValues[":original_id"] = $id;
		$arrValues[":timestamp"] = date( "Y-m-d H:i:s" );
		$arrValues[":username"] = $username;
		$arrValues[":nonsort"] = $objXerxesRecord->getNonSort();
		$arrValues[":title"] = $strTitle;
		$arrValues[":author"] = $objXerxesRecord->getPrimaryAuthor( true );
		$arrValues[":year"] = $iYear;
		$arrValues[":format"] = $objXerxesRecord->getFormat();
		$arrValues[":refereed"] = $iRefereed;
		$arrValues[":marc"] = $objXerxesRecord->getMarcXMLString();
		
		$status = $this->insert( $strSQL, $arrValues );
		
		// get the internal xerxes record id for the saved record, and fill record
		// with it, so caller can use. 
		
		$getIDSql = "SELECT id FROM xerxes_records WHERE original_id = :original_id";
		$getIDParam = array (":original_id" => $id );
		$getIDResults = $this->select( $getIDSql, $getIDParam );
		$objXerxesRecord->id = $getIDResults[0]["id"];
		
		$objXerxesRecord->original_id = $id;
		
		return $status;
	}
	
	/**
	 * Remove a record from the user's saved record space by the source and id
	 *
	 * @param string $username			username under which the record is saved
	 * @param string $source			source from which the record came
	 * @param string $id				id of the record
	 * @return int status
	 */
	
	public function deleteRecordBySource($username, $source, $id)
	{
		$strSQL = "DELETE FROM xerxes_records WHERE username = :username AND source = :source AND original_id = :original_id";
		
		return $this->delete( $strSQL, array (":username" => $username, ":source" => $source, ":original_id" => "$id" ) );
	}
	
	/**
	 * Delete record by the local internal id
	 *
	 * @param string $username			username under which the record is saved
	 * @param int $id					internal id number
	 * @return int status
	 */
	
	public function deleteRecordByID($username, $id)
	{
		$strSQL = "DELETE FROM xerxes_records WHERE username = :username AND id = :id";
		
		return $this->delete( $strSQL, array (":username" => $username, ":original_id" => "$id" ) );
	}
	
	### AVAILABILTY FUNCTIONS ###
	

	/**
	 * Delete all records from the sfx table; should only be done while in
	 * transaction
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
	 * get a list of journals from the refereed table
	 *
	 * @param mixed $issn		[string or array] ISSN or multiple ISSNs
	 * @return array			array of Xerxes_Data_Refereed objects
	 */
	
	public function getRefereed($issn)
	{
		$arrPeer = array ( );
		$arrResults = array ( );
		$strSQL = "SELECT * FROM xerxes_refereed WHERE ";
		
		if ( is_array( $issn ) )
		{
			if ( count( $issn ) == 0 )	throw new Exception( "issn query with no values" );
			
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
			$objPeer = new Xerxes_Data_Refereed( );
			$objPeer->load( $arrResult );
			
			array_push( $arrPeer, $objPeer );
		}
		
		return $arrPeer;
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
	
	### BASIC ###
	

	/**
	 * A utility method for adding single-value data to a table
	 *
	 * @param string $strTableName		table name
	 * @param mixed $objValueObject		object derived from Xerxes_Framework_DataValue
	 * @param boolean $boolReturnPk  default false, return the inserted pk value?
	 * @return  false if failure. on success, true or inserted pk based on $boolReturnPk
	 */
	
	private function doSimpleInsert($strTableName, $objValueObject, $boolReturnPk = false)
	{
		$arrProperties = array ( );
		
		foreach ( $objValueObject->properties() as $key => $value )
		{
			if ( ! is_int($value) && $value == "" )
			{
				unset($objValueObject->$key);
			}
			else
			{
				$arrProperties[":$key"] = $value;
			}
		}
		
		$fields = implode( ",", array_keys( $objValueObject->properties() ) );
		$values = implode( ",", array_keys( $arrProperties ) );
		
		$strSQL = "INSERT INTO $strTableName ( $fields ) VALUES ( $values )";
		
		return $this->insert( $strSQL, $arrProperties, $boolReturnPk );
	}

}

?>