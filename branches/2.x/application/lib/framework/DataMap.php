<?php

/**
 * Basic functions for selecting, instering, updating, and deleting data from a 
 * database, including transactions; basically a convenience wrapper around PDO
 *
 * @abstract
 * @author David Walker
 * @copyright 2008 California State University
 * @version $Id$
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
		// options to ensure utf-8
		
		if ( $this->rdbms == "mysql" )
		{
			// php 5.3.0 and 5.3.1 have a bug where this is not defined
			
			if ( ! defined("PDO::MYSQL_ATTR_INIT_COMMAND") )
			{
				$init_command = 1002;
			}
			else
			{
				$init_command = PDO::MYSQL_ATTR_INIT_COMMAND;
			}
			
			$arrDriverOptions = array($init_command => "SET NAMES 'utf8'");
		}
		else
		{
			$arrDriverOptions = null;  // @todo: with MS SQL
		}
		
		// data access object
		
		$this->objPDO = new PDO($connection,$username, $password, $arrDriverOptions);
		
		// will force PDO to throw exceptions on error
		
		$this->objPDO->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
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
	
	public function select($strSQL, $arrValues = null, $arrClean = null)
	{
		$this->sqlServerFix($strSQL, $arrValues, $arrClean);
		
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
	
	public function update($strSQL, $arrValues = null, $arrClean = null)
	{
		$this->sqlServerFix($strSQL, $arrValues, $arrClean);
		
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
	 * 								as set by PDO. if $boolReturnPk is true, either the last inserted pk, 
	 * 								or 'false' for a failed insert. 
	 */
	
	public function insert($strSQL, $arrValues = null, $boolReturnPk = false, $arrClean = null)
	{
		$this->sqlServerFix($strSQL, $arrValues, $arrClean);
		
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
	
	private function sqlServerFix(&$strSQL, &$params, $clean = null)
	{
		// a bug in the sql server native client makes this necessary, barf!
		
		if ( $this->rdbms == "mssql")
		{
			// these values need cleaning, likely because they are in a sub-query
			
			if( is_array($clean) )
			{
				$dirtystuff = array("\"", "\\", "/", "*", "'", "=", "#", ";", "<", ">", "+");
				
				foreach ( $params as $key => $value )
				{
					if ( in_array($key, $clean) )
					{
						$value = str_replace($dirtystuff, "", $value); 
						$strSQL = str_replace($key, "'$value'", $strSQL);
						unset($params[$key]);
					}
				}
			}
		}
	}
}
