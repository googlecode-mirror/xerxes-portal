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
		
		protected function select($strSQL, $arrValues = null)
		{
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
		
		protected function update($strSQL, $arrValues = null)
		{
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
		 * @return mixed				status of the request, as set by PDO
		 */
		
		protected function insert($strSQL, $arrValues = null)
		{
			$this->update($strSQL, $arrValues);
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
			$this->update($strSQL, $arrValues);
		}
	}

?>