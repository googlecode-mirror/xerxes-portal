<?php

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
	public $subcategories = array();
}

class Xerxes_Data_Subcategory extends Xerxes_Framework_DataValue
{
	public $metalib_id;
	public $name;
	public $sequence;
	public $category_id;
	public $databases = array();
}

class Xerxes_Data_Type extends Xerxes_Framework_DataValue
{
	public $id;
	public $name;
	public $normalized;
	public $databases = array();
}

class Xerxes_Data_Database extends Xerxes_Framework_DataValue
{
	public $metalib_id;
	public $title_full;
	public $title_display;
	public $institute;
	public $filter;
	public $creator;
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
	public $subscription;
	public $sfx_suppress;
	public $new_resource_expiry;
	public $updated;
	public $number_sessions;
	
	public $keywords = array();
	public $notes = array();
	public $languages = array();
	public $alternate_publishers = array();
	public $alternate_titles = array();
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
	public $xerxes_record;		// not part of table!
}


/**
 * Functions for inserting, updating, and deleting data from the database
 *
 */

class Xerxes_DataMap extends Xerxes_Framework_DataMap
{
	public function __construct()
	{
		$objRegistry = Xerxes_Framework_Registry::getInstance(); $objRegistry->init();
		
		$this->init(
			$objRegistry->getConfig("DATABASE_CONNECTION", true), 
			$objRegistry->getConfig("DATABASE_USERNAME", true),
			$objRegistry->getConfig("DATABASE_PASSWORD", true)
		);
	}
	
	
	### KNOWLEDGEBASE ADD FUNCTIONS ###
	
	/**
	 * Deletes data from the knowledgebase tables; should only be done
	 * while using transactions
	 */
	
	public function clearKB()
	{
		// xerxes_databases and xerxes_subcategories will
		// cascade delete to join tables
		
		$this->delete("DELETE FROM xerxes_databases");
		$this->delete("DELETE FROM xerxes_subcategories");
		$this->delete("DELETE FROM xerxes_categories");
		$this->delete("DELETE FROM xerxes_types");
	}
	
	/**
	 * Add a database to the local knowledgebase
	 *
	 * @param Xerxes_Data_Database $objDatabase
	 */
	
	public function addDatabase(Xerxes_Data_Database $objDatabase)
	{
		// clean-up metalib types
		
		$objDatabase->proxy = $this->convertMetalibBool($objDatabase->proxy);
		$objDatabase->searchable = $this->convertMetalibBool($objDatabase->searchable);
		$objDatabase->subscription = $this->convertMetalibBool($objDatabase->subscription);
		$objDatabase->sfx_suppress = $this->convertMetalibBool($objDatabase->sfx_suppress);
		$objDatabase->new_resource_expiry = $this->convertMetalibDate($objDatabase->new_resource_expiry);
		$objDatabase->updated = $this->convertMetalibDate($objDatabase->updated);
		
		// basic single-value fields
		
		$this->doSimpleInsert("xerxes_databases", $objDatabase);
		
		// keywords
		
		foreach ( $objDatabase->keywords as $keyword )
		{
			$strSQL = "INSERT INTO xerxes_database_keywords ( database_id, keyword ) " .
					  "VALUES ( :metalib_id, :keyword )";
					  
			$this->insert($strSQL, array(":metalib_id" => $objDatabase->metalib_id, ":keyword" => $keyword));
		}
		
		// notes

		foreach ( $objDatabase->notes as $note )
		{
			$strSQL = "INSERT INTO xerxes_database_notes ( database_id, note ) " .
					  "VALUES ( :metalib_id, :note )";
					  
			$this->insert($strSQL, array(":metalib_id" => $objDatabase->metalib_id, ":note" => $note));
		}

		// languages

		foreach ( $objDatabase->languages as $language )
		{
			$strSQL = "INSERT INTO xerxes_database_languages ( database_id, language ) " .
					  "VALUES ( :metalib_id, :language )";
					  
			$this->insert($strSQL, array(":metalib_id" => $objDatabase->metalib_id, ":language" => $language));
		}

		// alternate publishers

		foreach ( $objDatabase->alternate_publishers as $alternate_publisher )
		{
			$strSQL = "INSERT INTO xerxes_database_alternate_publishers ( database_id, alt_publisher ) " .
					  "VALUES ( :metalib_id, :alt_publisher )";
					  
			$this->insert($strSQL, array(":metalib_id" => $objDatabase->metalib_id, ":alt_publisher" => $alternate_publisher));
		}	

		// alternate titles

		foreach ( $objDatabase->alternate_titles as $alternate_title )
		{
			$strSQL = "INSERT INTO xerxes_database_alternate_titles ( database_id, alt_title ) " .
					  "VALUES ( :metalib_id, :alt_title )";
					  
			$this->insert($strSQL, array(":metalib_id" => $objDatabase->metalib_id, ":alt_title" => $alternate_title));
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
		return $this->doSimpleInsert("xerxes_types", $objType);
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
		$this->doSimpleInsert("xerxes_categories", $objCategory);
		
		$s = 1;
		
		foreach ( $objCategory->subcategories as $objSubcategory )
		{
			$objSubcategory->category_id = $objCategory->id;
			$objSubcategory->sequence = $s;
			
			$this->doSimpleInsert("xerxes_subcategories", $objSubcategory);
			
			$d = 1;
			
			foreach ( $objSubcategory->databases as $objDatabase )
			{
				$strSQL = "INSERT INTO xerxes_subcategory_databases ( database_id, subcategory_id, sequence ) " .
						  "VALUES ( :database_id, :subcategory_id, :sequence )";
				
				$arrValues = array(
					":database_id" => $objDatabase->metalib_id, 
					":subcategory_id" => $objSubcategory->metalib_id, 
					":sequence" => $d
					);
				
				$this->insert($strSQL, $arrValues);
				$d++;
			}
			
			$s++;
		}
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
		$arrDate = array();
							
		if ( preg_match("/([0-9]{4})([0-9]{2})([0-9]{2})/", $strValue, $arrDate) != 0 )
		{										
			if ( checkdate($arrDate[2], $arrDate[3], $arrDate[1]) )
			{
				$strDate =  $arrDate[1] . "-" . $arrDate[2] . "-" . $arrDate[3];
			}
		}
		
		return $strDate;
	}
	
	/**
	 * Convert metalib boolean values to 1 or 0
	 *
	 * @param string $strValue	'yes' will become 1, all others 0
	 * @return int				1 or 0
	 */
	
	private function convertMetalibBool($strValue)
	{
		if ( $strValue == "yes" )
		{										
			return 1;
		}
		else
		{
			return 0;
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
		$arrCategories = array();
		
		$strSQL = "SELECT * from xerxes_categories ORDER BY UPPER(name) ASC";
		
		$arrResults = $this->select($strSQL);
		
		foreach ( $arrResults as $arrResult )
		{
			$objCategory = new Xerxes_Data_Category();
			$objCategory->load($arrResult);
			
			array_push($arrCategories, $objCategory);
		}
		
		return $arrCategories;
	}
	
	/**
	 * Get an inlined set of subcategories and databases for a subject
	 *
	 * @param string $normalized		normalized category name
	 * @param string $old				old normalzied category name, for comp with Xerxes 1.0
	 * @return array					array of Xerxes_Data_Subcategory objects, with databases
	 */
	
	public function getSubject($normalized, $old = null)
	{
		// we'll use the new 'categories' normalized scheme if available, but 
		// otherwise get the old normalized scheme with the capitalizations for 
		// compatability with xerxes 1.0 release.
		
		$column = "normalized";
		
		if ( $normalized == null && $old != null )
		{
			$normalized = $old;
			$column = "old";
		}
		
		
		// note that we aren't using the outter join here as we
		// do with the getDatabases function, for speed; consider
		// revising
		
		$strSQL = 
			"SELECT xerxes_categories.id as category_id, 
		            xerxes_categories.name as category, 
		            xerxes_subcategories.metalib_id as subcat_id,
		            xerxes_subcategories.sequence as subcat_seq, 
		            xerxes_subcategories.name as subcategory, 
		            xerxes_subcategory_databases.sequence as sequence,
		            xerxes_databases.* 
			   FROM xerxes_categories,
			        xerxes_databases, 
			        xerxes_subcategory_databases, 
			        xerxes_subcategories
			 WHERE xerxes_categories.$column = :value
			   AND xerxes_subcategories.name NOT LIKE 'All%'
			   AND xerxes_subcategory_databases.database_id = xerxes_databases.metalib_id
			   AND xerxes_subcategory_databases.subcategory_id = xerxes_subcategories.metalib_id
			   AND xerxes_categories.id = xerxes_subcategories.category_id
		  ORDER BY subcat_seq, sequence";
				   
		$arrResults = $this->select($strSQL, array(":value" => $normalized));
		
		if ( $arrResults != null )
		{
			$objCategory = new Xerxes_Data_Category();
			$objCategory->id = $arrResults[0]["category_id"];
			$objCategory->name = $arrResults[0]["category"];
			
			$objSubcategory = new Xerxes_Data_Subcategory();
			$objSubcategory->metalib_id = $arrResults[0]["subcat_id"];
			$objSubcategory->name = $arrResults[0]["subcategory"];
			
			foreach ($arrResults as $arrResult)
			{
				// if the current row's subcategory name does not match the previous
				// one, then push the previous one onto category obj and make a new one
				
				if ( $arrResult["subcategory"] != $objSubcategory->name )
				{
					array_push($objCategory->subcategories, $objSubcategory );
					
					$objSubcategory = new Xerxes_Data_Subcategory();
					$objSubcategory->id = $arrResult["subcat_id"];
					$objSubcategory->name = $arrResult["subcategory"];
				}
				
				$objDatabase = new Xerxes_Data_Database();
				$objDatabase->load($arrResult);
				
				array_push($objSubcategory->databases, $objDatabase);
			}
			
			array_push($objCategory->subcategories, $objSubcategory );

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
		$arrResults = $this->getDatabases($id);
		
		if ( count($arrResults) > 0 )
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
	 * @return array			array of Xerxes_Data_Database objects
	 */
	
	public function getDatabases($id = null)
	{
		$arrDatabases = array();
		$arrResults = array();
		
		$strSQL = "SELECT * from xerxes_databases " .
		          " LEFT OUTER JOIN xerxes_database_notes ON xerxes_databases.metalib_id = xerxes_database_notes.database_id " .
		          " LEFT OUTER JOIN xerxes_database_keywords ON xerxes_databases.metalib_id = xerxes_database_keywords.database_id " .
		          " LEFT OUTER JOIN xerxes_database_languages ON xerxes_databases.metalib_id = xerxes_database_languages.database_id " .
		          " LEFT OUTER JOIN xerxes_database_alternate_titles ON xerxes_databases.metalib_id = xerxes_database_alternate_titles.database_id " .
		          " LEFT OUTER JOIN xerxes_database_alternate_publishers ON xerxes_databases.metalib_id = xerxes_database_alternate_publishers.database_id ";
		
		if ( $id != null )
		{
			if ( is_array($id) )
			{
				// databases specified by an array of ids
				
				$arrParams = array();
				$strSQL .= " WHERE ";
				
				for ($x = 0; $x < count($id); $x++)
				{
					if ( $x > 0 )
					{
						$strSQL .= " OR ";
					}

					$strSQL .= "xerxes_databases.metalib_id = :id$x ";
					$arrParams[":id$x"] = $id[$x];
				}
				
				$strSQL .= " ORDER BY xerxes_databases.metalib_id";
				
				$arrResults = $this->select($strSQL, $arrParams);
			}
			else
			{
				// single database query
				
				$strSQL .= " WHERE xerxes_databases.metalib_id = :id ";
				$arrResults= $this->select($strSQL, array(":id" => $id));
			}
		}
		else
		{
			// all databases, sorted alphabetically
			
			$strSQL .= " ORDER BY UPPER(title_display)";
			$arrResults= $this->select($strSQL);
		}
		
		if ( $arrResults != null )
		{
			$objDatabase = new Xerxes_Data_Database();
								
			foreach ($arrResults as $arrResult)
			{
				// if the previous row has a different id, then we've come 
				// to a new database, otherwise these are values from the outter join
				
				if ( $arrResult["metalib_id"] != $objDatabase->metalib_id )
				{
					if ( $objDatabase->metalib_id != null )
					{
						array_push($arrDatabases, $objDatabase );
					}
					
					$objDatabase = new Xerxes_Data_Database();
					$objDatabase->load($arrResult);
				}
				
				// if the current row's outter join value is not already stored,
				// then then we've come to a unique value, so add it
				
				$arrColumns = array(
					"keyword" => "keywords", 
					"language" =>"languages", 
					"note" => "notes", 
					"alt_title" => "alternate_titles", 
					"alt_publisher" => "alternate_publishers"
				);
				
				foreach ( $arrColumns as $column => $identifier )
				{
					if ( array_key_exists($column, $arrResult) )
					{
						if ( ! in_array($arrResult[$column], $objDatabase->$identifier) )
						{
							array_push($objDatabase->$identifier, $arrResult[$column]);
						}
					}
				}
			}
			
			// get the last one
			
			array_push($arrDatabases, $objDatabase );
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
		$arrTypes = array();
		
		$strSQL = "SELECT * from xerxes_types ORDER BY UPPER(name) ASC";
		
		$arrResults = $this->select($strSQL);
		
		foreach ( $arrResults as $arrResult )
		{
			$objType = new Xerxes_Data_Type();
			$objType->load($arrResult);
			
			array_push($arrTypes, $objType);
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

		$arrParams = array();
		$arrParams[":grouping"] = $objCache->grouping;
		$arrParams[":id"] = $objCache->id;
		$arrParams[":timestamp"] = $objCache->timestamp;
		
		$strSQL = "DELETE FROM xerxes_cache WHERE grouping = :grouping AND id = :id and timestamp < :timestamp";
		
		$this->delete($strSQL, $arrParams);
		
		// now insert the new value
		
		$this->doSimpleInsert("xerxes_cache", $objCache);
		
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
		$arrCache = array();
		$arrParams = array();
		$arrParams[":group"] = $group;
		
		$strSQL = "SELECT * FROM xerxes_cache WHERE grouping = :group ";
		
		if ( $expiry != null )
		{
			$strSQL .= " AND expiry <= :expiry";
			$arrParams[":expiry"] = $expiry;
		}
		
		$arrResults = $this->select($strSQL, $arrParams);
		
		foreach ( $arrResults as $arrResult )
		{
			$objCache = new Xerxes_Data_Cache();
			$objCache->load($arrResult);
			
			array_push($arrCache, $objCache);
		}
		
		return $arrCache;
	}
	
	
	### SAVED RECORD FUNCTIONS ###
	
	/**
	 * Get the total number of saved records for the user
	 *
	 * @param string $strUsername	username under which records are saved
	 * @return int					number of saved records
	 */
	
	public function totalRecords( $strUsername )
	{
		$strSQL = "SELECT count(*) as total FROM xerxes_records WHERE username = :user";
			
		$arrResults = $this->select($strSQL, array( ":user" => $strUsername));

		return (int) $arrResults[0]["total"];
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
		
		return $this->update($strSQL, array(":old" => $old, ":new" => $new));
	}
	
	/**
	 * Get a set of records from the user's saved records table
	 *
	 * @param string $strUsername		username under which the records are saved
	 * @param string $strView			'brief' or 'full'
	 * @param string $strOrder			[optional] sort order of the results" 'year', 'author' or 'title', defaults to date added (desc)
	 * @param array $arrID				[optional] array of id values
	 * @param int $iStart				[optional] offset to start from, defaults to 1, unless $arrID specified
	 * @param int $iCount				[optional] number of records to return, defaults to all, unless $arrID specified
	 * @return array					array of Xerxes_Data_Record objects
	 */
	
	public function getRecords($strUsername, $strView, $strOrder = null, $arrID = null, $iStart = 1, $iCount = null )
	{
		$strSQL = "";			// sql query
		$strLimit = "";			// row limit
		$arrResults = array();	// results as array
		$arrRecords = array();	// records as array
			
		// type and default clean-up
			
		if ( ! is_array($arrID) && $arrID != null ) $arrID = array($arrID);
		if ( $iStart == null ) $iStart = 1;

		// sql query
			
		if ( $strView == "brief")
		{
			$strSQL = "SELECT id, original_id, source, username, nonsort, title, author, format, year, refereed FROM xerxes_records";		
		}
		else
		{
			$strSQL = "SELECT * FROM xerxes_records";
		}
			
		if ( $strUsername != null )
		{
			$strSQL .= " WHERE username = :username";
		}
		else
		{
			$strSQL .= " WHERE username LIKE '%'";
		}
		
		if ( $iCount != null )
		{
			$iStart--;
			$strLimit = " LIMIT $iStart, $iCount";	
		}
			
		// limit the results to those records specified
			
		if ( $arrID != null )
		{
			$strSQL .= " AND (";
			
			for( $x = 0; $x < count($arrID); $x++ )
			{
				if ( $x > 0 )
				{
					$strSQL .= " OR";
				}
				
				$strSQL .= " id = " . $arrID[$x];
			}
			
			$strSQL .= " )";
		}
			
		// order by supplied sort criteria otherwise by id
		// to show most recently added first
		
		switch ( $strOrder )
		{
			case "year": $strSQL .= " ORDER BY year DESC"; break;
			case "author": $strSQL .= " ORDER BY author"; break;
			case "title": $strSQL .= " ORDER BY title"; break;
			default: $strSQL .= " ORDER BY id DESC"; break;
		}
		
		$strSQL .= $strLimit;
		
		$arrResults = $this->select($strSQL, array( ":username" => $strUsername));
		
		foreach ( $arrResults as $arrResult )
		{
			$objRecord = new Xerxes_Data_Record();
			$objRecord->load($arrResult);
			
			// only full display will include marc records
			
			if ( array_key_exists("marc", $arrResult) )
			{
				$objXerxes_Record = new Xerxes_Record();
				$objXerxes_Record->loadXML($arrResult["marc"]);
				$objRecord->xerxes_record = $objXerxes_Record;
			}
			
			array_push($arrRecords, $objRecord);
		}
		
		return $arrRecords;
		
	}
	
	/**
	 * Update the user table to include the last date of login
	 *
	 * @param string $username		username
	 * @return int status
	 */
	
	public function touchUser( $username )
	{
		$strTimeStamp = date("Y-m-d H:i:s");
		
		$strSQL = "SELECT username FROM xerxes_users WHERE username = :username";
		
		$arrResults = $this->select($strSQL, array(":username" => $username));
		
		if ( count($arrResults) == 1 )
		{
			// user already exists in database, so update the last_login time

			$strSQL = "UPDATE xerxes_users SET last_login = :login WHERE username = :username";
			return $this->update($strSQL, array(":username" => $username, ":login" => $strTimeStamp));
		}
		else
		{
			// add em otherwise
			
			$strSQL = "INSERT INTO xerxes_users ( username, last_login) VALUES (:username, :login)";
			return $this->insert($strSQL, array(":username" => $username, ":login" => $strTimeStamp));
		}
	}
	
	/**
	 * Add a record to the user's saved record space
	 *
	 * @param string $username					username to save the record under
	 * @param string $source					name of the source database
	 * @param string $id						identifier for the record
	 * @param Xerxes_Record $objXerxesRecord	xerxes record object to save
	 * @return int status
	 */
	
	public function addRecord( $username, $source, $id, Xerxes_Record $objXerxesRecord )
	{
		$arrValues = array();
		$strTitle = "";
		$strSubTitle = "";
		$iRefereed = 0;
		$iYear = 0;
			
		$iYear = (int) $objXerxesRecord->getYear();
		$strTitle = $objXerxesRecord->getMainTitle();
		$strSubTitle = $objXerxesRecord->getSubTitle();
			
		if ( $strSubTitle != "" ) $strTitle .= ": " . $strSubTitle;
			
		// peer-reviwed look-up
			
		if ( $objXerxesRecord->getISSN() != null )
		{				
			$arrResults = $this->getRefereed($objXerxesRecord->getISSN());
			
			if ( count($arrResults) > 0 )
			{
				$iRefereed = 1;
			}
		}

		$strSQL = "INSERT INTO xerxes_records " .
			" ( source, original_id, timestamp, username, nonsort, title, author, year, format, refereed, marc ) " .
			" VALUES " .
			" ( :source, :original_id, :timestamp, :username, :nonsort, :title, :author, :year, :format, :refereed, :marc)";
				 
		$arrValues[":source"] = $source;
		$arrValues[":original_id"] = $id;
		$arrValues[":timestamp"] = date("Y-m-d H:i:s");
		$arrValues[":username"] = $username;
		$arrValues[":nonsort"] = $objXerxesRecord->getNonSort();
		$arrValues[":title"] = $strTitle;
		$arrValues[":author"] = $objXerxesRecord->getPrimaryAuthor(true);
		$arrValues[":year"] = $iYear;
		$arrValues[":format"] = $objXerxesRecord->getFormat();
		$arrValues[":refereed"] = $iRefereed;
		$arrValues[":marc"] = $objXerxesRecord->getMarcXMLString();
		
		return $this->insert($strSQL, $arrValues);
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
		
		return $this->delete($strSQL, array(":username" => $username, ":source" => $source, ":original_id" => "$id"));
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
		
		return $this->delete($strSQL, array(":username" => $username, ":original_id" => "$id"));
	}
	
	
	### AVAILABILTY FUNCTIONS ###
	
	/**
	 * Delete all records from the sfx table; should only be done while in
	 * transaction
	 */
	
	public function clearFullText()
	{
		$this->delete("DELETE FROM xerxes_sfx");
	}
	
	/**
	 * Get a list of journals from the sfx table by issn
	 *
	 * @param string $issn		ISSN
	 * @return array			array of Xerxes_Data_Fulltext objects
	 */
	
	public function getFullText($issn)
	{
		$arrFull = array();
		$issn = str_replace("-","",$issn);
		
		$strSQL = "SELECT * FROM xerxes_sfx WHERE issn = :issn";
			
		$arrResults = $this->select($strSQL, array( ":issn" => $issn));

		foreach ( $arrResults as $arrResult )
		{
			$objFull = new Xerxes_Data_Fulltext();
			$objFull->load($arrResult);
			
			array_push($arrFull, $objFull);
		}
		
		return $arrFull;
		
	}
	
	/**
	 * get a list of journals from the refereed table
	 *
	 * @param string $issn		ISSN
	 * @return array			array of Xerxes_Data_Refereed objects
	 */
	
	public function getRefereed($issn)
	{
		$arrPeer = array();
		$issn = str_replace("-","",$issn);
		
		$strSQL = "SELECT * FROM xerxes_refereed WHERE issn = :issn";
	
		$arrResults = $this->select($strSQL, array( ":issn" => $issn));

		foreach ( $arrResults as $arrResult )
		{
			$objPeer = new Xerxes_Data_Refereed();
			$objPeer->load($arrResult);
			
			array_push($arrPeer, $objPeer);
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
		return $this->doSimpleInsert("xerxes_sfx", $objValueObject);
	}
	
	
	### BASIC ###
	
	/**
	 * A utility method for adding single-value data to a table
	 *
	 * @param string $strTableName		table name
	 * @param mixed $objValueObject		object derived from Xerxes_Framework_DataValue
	 * @return unknown
	 */
	
	private function doSimpleInsert( $strTableName, $objValueObject )
	{	
		$arrProperties = array();
		
		foreach ( $objValueObject->properties() as $key => $value )
		{
			$arrProperties[":$key"] = $value;
		}
		
		$fields = implode(",", array_keys($objValueObject->properties()));
		$values = implode(",", array_keys($arrProperties));
		
		$strSQL = "INSERT INTO $strTableName ( $fields ) VALUES ( $values )";
		
		return $this->insert($strSQL, $arrProperties);
	}
}

?>