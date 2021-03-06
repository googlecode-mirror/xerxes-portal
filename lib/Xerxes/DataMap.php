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

class Xerxes_DataMap extends Xerxes_Framework_DataMap
{
	protected $primary_language = "eng";
	protected $searchable_fields;
	
	public function __construct($connection = null, $username = null, $password = null)
	{
		$objRegistry = Xerxes_Framework_Registry::getInstance();
		
		// make it a member variable so other functions can get it easier
		
		$this->registry = $objRegistry;
		
		// set primary language
		
		$languages = $this->registry->getConfig("languages");
		
		if ( $languages != "")
		{
			$this->primary_language = (string) $languages->language["code"];
		}
		
		// searchable fields
		
		$this->searchable_fields = explode(",", $this->registry->getConfig("DATABASE_SEARCHABLE_FIELDS", false, 
			"title_display,title_full,description,keyword,title_alternate"));
		
		// pdo can't tell us which rdbms we're using exactly, especially for 
		// ms sql server, since we'll be using odbc driver, so we make this
		// explicit in the config
		
		$this->rdbms = $this->registry->getConfig("RDBMS", false, "mysql");
		
		// take conn and credentials from config, unless overriden in constructor
		
		if ( $connection == null) $connection = $objRegistry->getConfig( "DATABASE_CONNECTION", true );
		if ( $username == null ) $username = $objRegistry->getConfig( "DATABASE_USERNAME", true );
		if ( $password == null ) $password = $objRegistry->getConfig( "DATABASE_PASSWORD", true );
		
		$this->init( 
			$connection, 
			$username, 
			$password 
		);
	}
	
	public function upgradeKB()
	{
		$dir = $this->registry->getConfig("PATH_PARENT_DIRECTORY");
		$sql_file_base = "$dir/sql/" . $this->rdbms . "/";
		
		$files = array("migrate/migrate-1.7-to-1.8.sql","create-kb.sql");
		
		foreach ( $files as $file )
		{
			$sql_file = $sql_file_base . $file;
			
			$sql =  file_get_contents($sql_file);
			
			$sql = str_replace("CREATE DATABASE IF NOT EXISTS xerxes;", "", $sql);
			$sql = str_replace("USE xerxes;", "", $sql);
	
			$pdo = $this->getDatabaseObject();
			
			$queries = explode(";", $sql);
			
			foreach ( $queries as $query )
			{
				$query = trim($query);
				
				if ( $query != "" )
				{
					$pdo->query($query);
				}
			}
		}
	}
	
	
	### KNOWLEDGEBASE ADD FUNCTIONS ###
	

	/**
	 * Deletes data from the knowledgebase tables; should only be done
	 * while using transactions
	 */
	
	public function clearKB()
	{
		// delete main kb tables, others will cascade

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
		// load our data into xml object
		
		$xml = simplexml_load_string($objDatabase->data);
		
		// these fields have boolen values in metalib
		
		$boolean_fields = array("proxy","searchable","guest_access",
			"subscription","sfx_suppress","new_resource_expiry");

		// normalize boolean values
		
		foreach ( $xml->children() as $child )
		{
			$name = (string) $child->getName();
			$value = (string) $child;
			
			if ( in_array( $name, $boolean_fields) )
			{
				$xml->$name = $this->convertMetalibBool($value);
			}
		}
		
		// remove empty nodes
		
		$dom = new DOMDocument();
		$dom->loadXML($xml->asXML());
		
		$xmlPath = new DOMXPath($dom);
		$xmlNullNodes = $xmlPath->query('//*[not(node())]');
		
		foreach($xmlNullNodes as $node)
		{
			$node->parentNode->removeChild($node);
		}
		
		$objDatabase->data = $dom->saveXML();
		
		// add the main database entries
		
		$this->doSimpleInsert( "xerxes_databases", $objDatabase );
		
		// now also extract searchable fields so we can populate the search table
		
		// get fields from config
		
		foreach ( $this->searchable_fields as $search_field )
		{
			$search_field = trim($search_field);
			
			foreach ( $xml->$search_field as $field )
			{
				$searchable_terms = array();
				
				foreach ( explode(" ", (string) $field) as $term )
				{
					// only numbers and letters please
					
					$term = preg_replace('/[^a-zA-Z0-9]/', '', $term);
					$term = strtolower($term);
					
					// anything over 50 chars is likley a URL or something
					
					if ( strlen($term) > 50 )
					{
						continue;
					}
					
					array_push($searchable_terms, $term);
				}
				
				// remove duplicate terms
				
				$searchable_terms = array_unique($searchable_terms);
				
				// insert em
				
				$strSQL = "INSERT INTO xerxes_databases_search ( database_id, field, term ) " . 
					"VALUES ( :metalib_id, :field, :term )";
				
				foreach ( $searchable_terms as $unique_term )
				{
					$this->insert( $strSQL, array (
						":metalib_id" => $objDatabase->metalib_id, 
						":field" => $search_field, 
						":term" => $unique_term ) 
					);
				}			
			}
		}
	}
	
	/**
	 * Add a type to the local knowledgebase
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
		
		$this->commit(); //commit transaction
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
	
	public function getCategories($lang = "")
	{
		if ( $lang == "" )
		{
			$lang = $this->primary_language;
		}
				
		$arrCategories = array ( );
		
		$strSQL = "SELECT * from xerxes_categories WHERE lang = :lang ORDER BY UPPER(name) ASC";
		
		$arrResults = $this->select( $strSQL, array(":lang" => $lang) );
		
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
				"extra_select" => "", 
				"extra_where" => " AND lang = :lang " 
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
	 * @param string $old			old normalzied category name, for comp with Xerxes 1.0. Often can be left null in call. Only applicable to metalibMode. 
	 * @param string $mode  		one of constants metalibMode or userCreatedMode, for metalib-imported categories or user-created categories, using different tables.
	 * @param string $username 		only used in userCreatedMode, the particular user must be specified, becuase normalized subject names are only unique within a user. 
	 * @param string $lang 			language code, can be empty string
	 * @return Xerxes_Data_Category		a Xerxes_Data_Category object, filled out with subcategories and databases. 
	 */
	
	public function getSubject($normalized, $old = null, $mode = self::metalibMode, $username = null, $lang = "")
	{
		if ( $mode == self::userCreatedMode && $username == null )
			throw new Exception( "a username argument must be supplied in userCreatedMode" );
		
		$lang_query = $lang;	
		
		if ( $lang_query == "" )
		{
			$lang_query = $this->primary_language;
		}
			
		// This can be used to fetch personal or metalib-fetched data. We get
		// from different tables depending. 
		$schema_map = $this->schema_map_by_mode( $mode );
		
		// we'll use the new 'categories' normalized scheme if available, but 
		// otherwise get the old normalized scheme with the capitalizations for 
		// compatibility with xerxes 1.0 release.
		

		$column = "normalized";
		
		if ( $normalized == null && $old != null )
		{
			$normalized = $old;
			$column = "old";
		}
		
		$strSQL = "SELECT $schema_map[categories_table].id as category_id, 
			$schema_map[categories_table].name as category,
			$schema_map[subcategories_table].$schema_map[subcategories_pk] as subcat_id,
			$schema_map[subcategories_table].sequence as subcat_seq, 
			$schema_map[subcategories_table].name as subcategory, 
			$schema_map[database_join_table].sequence as sequence,
			xerxes_databases.*
			$schema_map[extra_select]
			FROM $schema_map[categories_table]
			LEFT OUTER JOIN $schema_map[subcategories_table] ON $schema_map[categories_table].id = $schema_map[subcategories_table].category_id
			LEFT OUTER JOIN $schema_map[database_join_table] ON $schema_map[database_join_table].subcategory_id = $schema_map[subcategories_table].$schema_map[subcategories_pk]
			LEFT OUTER JOIN xerxes_databases ON $schema_map[database_join_table].database_id = xerxes_databases.metalib_id
			WHERE $schema_map[categories_table].$column = :value
			AND 
			($schema_map[subcategories_table].name NOT LIKE UPPER('All%') OR
			$schema_map[subcategories_table].name is NULL)
			$schema_map[extra_where]
			ORDER BY subcat_seq, sequence";
		  
		$args = array (":value" => $normalized );
		
		if ( $username )
		{
			$args[":username"] = $username;
		}
		else
		{
			$args[":lang"] = $lang_query;
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
				// then we've come to a unique value, so add it

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
			
			$strSubcatInclude = $this->registry->getConfig( "SUBCATEGORIES_INCLUDE", false, null, $lang );
			
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
	 * Get the starting letters for database titles
	 *
	 * @return array of letters
	 */	
	
	public function getDatabaseAlpha()
	{
		$strSQL = "SELECT DISTINCT alpha FROM " .
			"(SELECT SUBSTRING(UPPER(title_display),1,1) AS alpha FROM xerxes_databases) AS TEMP " .
			"ORDER BY alpha";
			
		$letters = array();
		$results = $this->select( $strSQL );
		
		foreach ( $results as $result )
		{
			array_push($letters, $result['alpha']);	
		}
		
		return $letters;
	}

	/**
	 * Get databases that start with a particular letter
	 *
	 * @param string $alpha letter to start with 
	 * @return array of Xerxes_Data_Database objects
	 */	

	public function getDatabasesStartingWith($alpha)
	{
		return $this->getDatabases(null, null, $alpha);	
	}
	
	/**
	 * Get one or a set of databases from the knowledgebase
	 *
	 * @param mixed $id			[optional] null returns all database, array returns a list of databases by id, 
	 * 							string id returns single id
	 * @param string $query   user-entered query to search for dbs. 
	 * @return array			array of Xerxes_Data_Database objects
	 */
	
	public function getDatabases($id = null, $query = null, $alpha = null)
	{
		$configDatabaseTypesExclude = $this->registry->getConfig("DATABASES_TYPE_EXCLUDE_AZ", false);
		$configAlwaysTruncate = $this->registry->getConfig("DATABASES_SEARCH_ALWAYS_TRUNCATE", false, false);		
		
		$arrDatabases = array ( );
		$arrResults = array ( );
		$arrParams = array ( );
		$where = false;
		$sql_server_clean = null;
		
		// lowercase the query
		
		$query = strtolower($query);
		
		$strSQL = "SELECT * from xerxes_databases";

		// single database
		
		if ( $id != null && ! is_array( $id ) )
		{
			$strSQL .= " WHERE xerxes_databases.metalib_id = :id ";
			$arrParams[":id"] = $id;
			$where = true;
		} 		
		
		// databases specified by an array of ids
		
		elseif ( $id != null && is_array( $id ) )
		{
			$strSQL .= " WHERE ";
			$where = true;
			
			for ( $x = 0 ; $x < count( $id ) ; $x ++ )
			{
				if ( $x > 0 )
				{
					$strSQL .= " OR ";
				}
				
				$strSQL .= "xerxes_databases.metalib_id = :id$x ";
				$arrParams[":id$x"] = $id[$x];
			}
		} 
		
		// alpha query
		
		elseif ( $alpha != null )
		{
			$strSQL .= " WHERE UPPER(title_display) LIKE :alpha ";
			$arrParams[":alpha"] = "$alpha%";
			$where = true;
		}
		
		// user-supplied query
		
		elseif ( $query != null )
		{
			$where = true;
			$sql_server_clean = array();
			
			$arrTables = array(); // we'll use this to keep track of temporary tables
			
			// we'll deal with quotes later, for now 
			// and gives us each term in an array
			
			$arrTerms = explode(" ", $query);
			
			// grab databases that meet our query
			
			$strSQL .= " WHERE metalib_id IN  (
				SELECT database_id FROM ";
			
			// by looking for each term in the xerxes_databases_search table 
			// making each result a temp table
			
			for ( $x = 0; $x < count($arrTerms); $x++ )
			{
				$term = $arrTerms[$x];
				
				// to match how they are inserted
				
				$term = preg_replace('/[^a-zA-Z0-9\*]/', '', $term);
				
				// do this to reduce the results of the inner table to just one column
				
				$alias = "database_id";
				
				if ( $x > 0 )
				{
					$alias = "db";
				}
				
				// wildcard
				
				$operator = "="; // default operator is equal
				
				// user supplied a wildcard
				
				if ( strstr($term,"*") )
				{
					$term = str_replace("*","%", $term);
					$operator = "LIKE";
				}
				
				// site is configured for truncation
				
				elseif ($configAlwaysTruncate == true )
				{
					$term .= "%";
					$operator = "LIKE";					
				}
				
				$arrParams[":term$x"] = $term;
				array_push($sql_server_clean, ":term$x");
				
				$strSQL .= " (SELECT distinct database_id AS $alias FROM xerxes_databases_search WHERE term $operator :term$x) AS table$x ";
				
				// if there is another one, we need to add a comma between them
				
				if ( $x + 1 < count($arrTerms))
				{
					$strSQL .= ", ";
				}
				
				// this essentially AND's the query by requiring results from all tables
				
				if ( $x > 0 )
				{
					for ( $y = 0; $y < $x; $y++)
					{
						$column = "db";
						
						if ( $y == 0 )
						{
							$column = "database_id";
						}
						
						array_push($arrTables, "table$y.$column = table" . ($y + 1 ). ".db");
					}
				}
			}
			
			// add the AND'd tables to the SQL
			
			if ( count($arrTables) > 0 )
			{
				$strSQL .= " WHERE " . implode(" AND ", $arrTables);
			}
			
			$strSQL .= ")";
		}
		
		// remove certain databases based on type(s), if so configured
		// unless we're asking for specific id's, yo	
	
		if ( $configDatabaseTypesExclude != null && $id == null )
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
			
			$joiner = "WHERE";
			
			if ( $where == true )
			{
				$joiner = "AND";
			}
			
			$strSQL .= " $joiner ( (" . implode (" AND ", $arrTypeQuery) . ") OR xerxes_databases.type IS NULL )";
		}
			
		$strSQL .= " ORDER BY UPPER(title_display)";
		
		// echo $strSQL; print_r($arrParams); // exit;
		
		$arrResults = $this->select( $strSQL, $arrParams, $sql_server_clean );
		
		// transform to internal data objects
		
		if ( $arrResults != null )
		{
			foreach ( $arrResults as $arrResult )
			{
				$objDatabase = new Xerxes_Data_Database();
				$objDatabase->load( $arrResult );
				array_push($arrDatabases, $objDatabase);
			}
		}
		
		// limit to quoted phrases
		
		if ( strstr($query, '"') )
		{
			// unload the array, we'll only refill the ones that match the query
			
			$arrCandidates = $arrDatabases;
			$arrDatabases = array();
			
			$found = false;
			
			$phrases = explode('"', $query);
			
			foreach ( $arrCandidates as $objDatabase )
			{
				foreach ( $phrases as $phrase )
				{
					$phrase = trim($phrase);
					
					if ( $phrase == "" )
					{
						continue;
					}
					
					$text = " ";
						
					foreach ( $this->searchable_fields as $searchable_field )
					{
						$text .= $objDatabase->$searchable_field . " ";
					}
					
					if ( ! stristr($text,$phrase) )
					{
						$found = false;
						break;
					}
					else
					{
						$found = true;
					}
				}
				
				if ( $found == true )
				{
					array_push($arrDatabases, $objDatabase);
				}
			}
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
		// ensure data
		
		if ( $objCache->id == "" )
		{
			throw new Exception("cached object must contain id");
		}

		if ( $objCache->source == "" )
		{
			throw new Exception("cached object must contain source identifier");
		}
		
		// set timestamp if not specified

		if ( $objCache->timestamp == null )
		{
			$objCache->timestamp = time();
		}
		
		// if no expiry specified, set a 6 hour cache

		if ( $objCache->expiry == null )
		{
			$objCache->expiry = time() + (6 * 60 * 60);
		}
		
		// delete any previously stored value under this id

		$this->beginTransaction();
		
		$arrParams = array ( );
		$arrParams[":source"] = $objCache->source;
		$arrParams[":id"] = $objCache->id;
		
		$strSQL = "DELETE FROM xerxes_cache WHERE source = :source AND id = :id";
		$this->delete( $strSQL, $arrParams );
		
		// now insert the new value

		$this->doSimpleInsert( "xerxes_cache", $objCache );
		$this->commit();
	}
	
	public function getCache($source, $id)
	{
		$now = time();
		$arrParams = array();
		$arrCache = array ();
		
		if ( is_array($id) )
		{
			if ( count($id) == 0 )
			{
				throw new Exception("no id specified in cache call");
			}
		}
		elseif ( $id == "" )
		{
			throw new Exception("no id specified in cache call");
		}
		
		$arrParams[":source"] = $source;
		
		$strSQL = "SELECT * FROM xerxes_cache WHERE expiry > $now AND source = :source";
		
		if ( is_array($id) )
		{
			$strSQL .= " AND (";
			
			for ( $x = 0 ; $x < count( $id ) ; $x ++ )
			{
				if ( $x > 0 )
				{
					$strSQL .= " OR";
				}
				
				$strSQL .= " id = :id$x ";
				$arrParams[":id$x"] = $id[$x];
			}
			
			$strSQL .= ")";
		}
		else
		{
			$strSQL .= " AND id = :id";
			$arrParams[":id"] = $id;
		}
		
		$arrResults = $this->select( $strSQL, $arrParams );
		
		foreach ( $arrResults as $arrResult )
		{
			$objCache = new Xerxes_Data_Cache();
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
				
				$num = sprintf("%04d", $x); // pad it to keep id's unique for mssql
				
				$strCriteria .= " id = :id$num ";
				$arrParams[":id$num"] = $arrID[$x];
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
		
		$sql_server_clean = null;
		
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
				
			$sql_server_clean = array(":username",":tag",":format");
			                        
			for ( $x = 0 ; $x < count( $arrID ) ; $x ++ )
			{
					$num = sprintf("%04d", $x); // pad it to keep id's unique for mssql
			        array_push($sql_server_clean, ":id$num");
			}
		}

		
		#### return the objects
		
		$arrResults = array ( ); // results as array
		$arrRecords = array ( ); // records as array
		
		$arrResults = $this->select( $strSQL, $arrParams, $sql_server_clean );
		
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
						if ( $arrResult["record_type"] == "xerxes_record")
						{
							// new-style saved record
							
							$objRecord->xerxes_record = unserialize($arrResult["marc"]);
						}
						else
						{
							// old style
							
							$objXerxes_Record = new Xerxes_MetalibRecord();
							$objXerxes_Record->loadXML( $arrResult["marc"] );
							$objRecord->xerxes_record = $objXerxes_Record;
						}
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
			$objRecord = new Xerxes_Data_RecordFormat( );
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
			$objRecord = new Xerxes_Data_RecordTag( );
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
	 * If any attributes in Xerxes_Framework_Authenticate_User are set other than
	 * username, those will also be written to db over-riding anything that may
	 * have been there.  Returns Xerxes_Framework_Authenticate_User filled out with information matching
	 * db. 
	 *
	 * @param Xerxes_Framework_Authenticate_User $user
	 * @return Xerxes_Framework_Authenticate_User $user
	 */
	
	public function touchUser(Xerxes_Framework_Authenticate_User $user)
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
			// use any data specified in our Xerxes_Framework_Authenticate_User record to overwrite. Start
			// with what's already there, overwrite with anything provided in
			// the Xerxes_Framework_Authenticate_User object. 
			
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
		$iRefereed = 0;
		
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
			( source, original_id, timestamp, username, nonsort, title, author, year, format, refereed, record_type, marc )
			VALUES 
			( :source, :original_id, :timestamp, :username, :nonsort, :title, :author, :year, :format, :refereed, :record_type, :marc)";
		
		$arrValues[":source"] = $source;
		$arrValues[":original_id"] = $id;
		$arrValues[":timestamp"] = date( "Y-m-d H:i:s" );
		$arrValues[":username"] = $username;
		$arrValues[":nonsort"] = $objXerxesRecord->getNonSort();
		$arrValues[":title"] = substr($strTitle, 0, 90);
		$arrValues[":author"] = $objXerxesRecord->getPrimaryAuthor( true );
		$arrValues[":year"] = $iYear;
		$arrValues[":format"] = $objXerxesRecord->getFormat();
		$arrValues[":refereed"] = $iRefereed;
		
		$arrValues[":marc"] = serialize($objXerxesRecord);
		$arrValues[":record_type"] = "xerxes_record"; 			
		
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
	 * Delete all records for refereed journals
	 */
	
	public function flushRefereed()
	{
		$this->delete( "DELETE FROM xerxes_refereed" );
	}
	
	/**
	 * Add a refereed title
	 * 
	 * @param Xerxes_Data_Refereed $objTitle peer reviewed journal object
	 */
	
	public function addRefereed(Xerxes_Data_Refereed $objTitle)
	{
		$objTitle->issn = str_replace("-", "", $objTitle->issn);
		// $objTitle->timestamp = date("Ymd");
		
		$this->doSimpleInsert("xerxes_refereed", $objTitle);
	}
	
	public function getAllRefereed()
	{
		$arrPeer = array();
		$arrResults = $this->select( "SELECT * FROM xerxes_refereed");
		
		foreach ( $arrResults as $arrResult )
		{
			$objPeer = new Xerxes_Data_Refereed( );
			$objPeer->load( $arrResult );
			
			array_push( $arrPeer, $objPeer );
		}		
		
		return $arrPeer;
	}
	
	
	/**
	 * Get a list of journals from the refereed table
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
