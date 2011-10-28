<?php

class Xerxes_Controller_Solr extends Xerxes_Controller_Search
{
	protected $id = "solr";
	
	public function init()
	{
		$this->config = Xerxes_Model_Solr_Config::getInstance();
		$this->query = new Xerxes_Model_Search_Query($this->request, $this->config);
		$this->engine = new Xerxes_Model_Solr_Engine($this->config);
		
		$this->response->add("config_local", $this->config);
		
		parent::init();
	}
}
