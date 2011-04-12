<?php

class Xerxes_Controller_Folder extends Xerxes_Controller_Search
{
	protected $id = "folder";
	
	public function __construct()
	{
		parent::__construct();
		
		// make the query the username
		
		$this->request->setParam("query", $this->request->getParam("username"));
		
		// set up the objects
		
		$this->config = Xerxes_Model_SavedRecords_Config::getInstance();
		$this->query = new Xerxes_Model_Search_Query($this->request, $this->config);
		$this->engine = new Xerxes_Model_SavedRecords_Engine($this->config);
		
		$this->response->add("config_local", $this->config->toXML());
	}

	protected function currentParams()
	{
		// unset query for username
		
		$params = parent::currentParams();
		$params["username"] = $params["query"];
		unset($params["query"]);
		
		return $params;
	}
}
