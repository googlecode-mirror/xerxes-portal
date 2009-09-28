<?php

	/**
	 * Basic functions for selecting, instering, updating, and deleting data from a 
	 * database, including transactions; basically a convenience wrapper around PDO
	 *
	 * @abstract
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package  Xerxes_Framework
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */
	
	abstract class Xerxes_Framework_DataMap
	{
		private $objPDO = null;				// pdo data object
		private $strSQL = null;				// sql statement, here for debugging
		private $arrValues = array();		// values passed to insert or update statement, here for debugging

		protected $registry;	// registry object, here for convenience
		protected $rdbms;		// the explicit rdbms name (should be 'mysql' or 'mssql' as of 1.5.1) 
		
		
		/**
		 * Initialize the object, should be called from the constructor of the child;
		 *
		 * @param string $connection		pdo connection string
		 * @param string $username			database username
		 * @param string $password			database password
		 */
		
		protected function init($connection, $username, $password)
		{
			// data access object
			// will force PDO to throw exceptions on error
			
			$this->objPDO = new PDO($connection, $username, $password);
			$this->objPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		
		public function __destruct()
		{
			$this->objPDO = null;
		}
		
		/**
		 * Return the pdo object for specific handling
		 *
		 * @return unknown
		 */
		
		protected function getDatabaseObject()
		{
			return $this->objPDO;
		}
		
		/**
		 * Begin the database transaction
		 *
		 */
			
		public function beginTransaction()
		{
			$this->objPDO->beginTransaction();
		}
		
		/**
		 * Commit any outstanding database transactions
		 *
		 */
	
		public function commit()
		{
			$this->objPDO->commit();
		}
		
		/**
		 * Fetch all records from a select query
		 *
		 * @param string $strSQL		SQL query
		 * @param array $arrValues		paramaterized values
		 * @return array				array of results as supplied by PDO
		 */
		
		public function select($strSQL, $arrValues = null)
		{
			$this->echoSQL($strSQL);
			
			$this->strSQL = $strSQL;
				
			$objStatement = $this->objPDO->prepare($strSQL);
			
			if ( $arrValues != null )
			{
				foreach ($arrValues as $key => $value )
				{
					$objStatement->bindValue( $key, $value);
				}
			}
			
			$objStatement->execute();
				
			return $objStatement->fetchAll();
		}
		
		/**
		 * Update rows in the database
		 *
		 * @param string $strSQL		SQL query
		 * @param array $arrValues		paramaterized values
		 * @return mixed				status of the request, as set by PDO
		 */
		
		public function update($strSQL, $arrValues = null)
		{
			$this->echoSQL($strSQL);
			
			$this->strSQL = $strSQL;
			
			$objStatement = $this->objPDO->prepare($this->strSQL);
			
			if ( $arrValues != null )
			{
				foreach ($arrValues as $key => $value )
				{
					$objStatement->bindValue( $key, $value);
				}
			}
			
			return $objStatement->execute();      
		}
		
		/**
		 * Insert rows in the database
		 *
		 * @param string $strSQL		SQL query
		 * @param array $arrValues		paramaterized values
		 * @param boolean $boolReturnPk  return the inserted pk value?
		 * @return mixed				if $boolReturnPk is false, status of the request (true or false), 
		 * as set by PDO. if $boolReturnPk is true, either the last inserted pk, or 'false' for a failed insert. 
		 */
		
		public function insert($strSQL, $arrValues = null, $boolReturnPk = false)
		{
			$status = $this->update($strSQL, $arrValues);      
			
			if ($status && $boolReturnPk)
			{
				// ms sql server specific code
				
				if ( $this->rdbms == "mssql" )
				{
					// this returns the last primary key in the 'session', per ms website,
					// which we hope to god is the id we just inserted above and not a 
					// different transaction; need to watch this closely for any racing conditions
					
					$results = $this->select("SELECT @@IDENTITY AS 'Identity'");
					
					if ( $results !== false )
					{
						return (int) $results[0][0];
					}
				}
				else
				{
					return $this->lastInsertId();
				}
			} 
			else
			{
				return $status;
			}
		}
		
		/**
		 * Delete rows in the database
		 *
		 * @param string $strSQL		SQL query
		 * @param array $arrValues		paramaterized values
		 * @return mixed				status of the request, as set by PDO
		 */
		
		protected function delete($strSQL, $arrValues = null)
		{
			return $this->update($strSQL, $arrValues);
		}
		
		protected function lastInsertId()
		{
			return $this->objPDO->lastInsertId();
		}
		
		private function echoSQL($strSQL)
		{
			// echo "<p>" . $strSQL . "</p>";
		}
	}

?>