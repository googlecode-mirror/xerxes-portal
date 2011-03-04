<?php

class Xerxes_Controller_Solr extends Xerxes_Controller_Search
{
	public function __construct()
	{
		parent::__construct();
		
		$this->config = Xerxes_Model_Solr_Config::getInstance();
		$this->query = new Xerxes_Model_Search_Query($this->request, $this->config);
		$this->engine = new Xerxes_Model_Solr_Engine($this->config);
	}
	
	public function results()
	{
		parent::results();
		$this->response->setView("xsl/solr/solr_results.xsl");
	}
}
