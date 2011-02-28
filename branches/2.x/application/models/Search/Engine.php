<?php

/**
 * Search Engine
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Model_Search_Engine
{
	public $id; // identifier of this search engine
	
	protected $url; // url to the search service
	protected $max = 10; // max records per page
	protected $sort; // default sort order
	
	protected $registry; // xerxes application config
	
	/**
	 * Constructor
	 */
	
	public function __construct( Xerxes_Model_Search_Config $config )
	{
		// application config
		
		$this->registry = Xerxes_Framework_Registry::getInstance();
		
		// local config
		
		$this->config = $config;
		
		// defaults for the application
				
		$this->max = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
		$this->sort = $this->registry->getConfig("SORT_ORDER_PRIMARY", false, "relevance");
	}
	
	/**
	 * Return the total number of hits for the search
	 * 
	 * @return int
	 */
	
	abstract function getHits( Xerxes_Model_Search_Query $search );
	
	/**
	 * Return an individual record
	 * 
	 * @return Xerxes_Model_Search_ResultSet
	 */
	
	abstract function getRecord( $id );
	
	/**
	 * Search and return results
	 * 
	 * @return Xerxes_Model_Search_ResultSet
	 */
	
	abstract function searchRetrieve( Xerxes_Model_Search_Query $search, $start, $max = null, $sort = null );
	
	/**
	 * Return the URL sent ot the web service
	 */
	
	public function getURL()
	{
		return $this->url;
	}
}
