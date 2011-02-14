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
	protected $sid; // sid for open url identification
	protected $link_resolver; // base address of link resolver
	protected $registry; // xerxes application config
	
	/**
	 * Constructor
	 */
	
	public function __construct()
	{
		// application config
		
		$this->registry = Xerxes_Framework_Registry::getInstance();
		
		// defaults for the application
				
		$this->link_resolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
		$this->sid = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
		$this->max = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
		$this->sort = $this->registry->getConfig("SORT_ORDER_PRIMARY", false, "relevance");
	}
	
	/**
	 * Return the total number of hits for the search
	 * 
	 * @return int
	 */
	
	public function getHits()
	{
		
	}
	
	/**
	 * Search and return results
	 * 
	 * @return Xerxes_Model_Search_ResultSet
	 */
	
	public function searchRetrieve()
	{
		
	}

	/**
	 * Return an individual record
	 * 
	 * @return Xerxes_Model_Search_ResultSet
	 */
	
	public function getRecord()
	{
		
	}
	
	/**
	 * Return the URL sent ot the web service
	 */
	
	public function getURL()
	{
		return $this->url;
	}
}
