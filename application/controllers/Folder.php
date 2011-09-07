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
	
	public function citation()
	{
		parent::results();
		
		$style = $this->request->getParam("style", false, "mla");

		$items = array();
		
		$results = $this->response->get("results");
		
		// header("Content-type: application/json");
		
		$x = 1;
		
		foreach ( $results->getRecords() as $result )
		{
			$id = "ITEM=$x";
			
			$record = $result->getXerxesRecord()->toCSL();
			$record["id"] = $id;
			
			$items[$id] = $record;
			$x++;
		}
		
		$json = json_encode(array("items" => $items));
		
		// header("Content-type: application/json"); echo $json; exit;
		
		$url = "http://127.0.0.1:8085?responseformat=html&style=$style";
		
		$client = new Zend_Http_Client();
		$client->setUri($url);
		$client->setHeaders("Content-type: application/json");
		$client->setHeaders("Expect: nothing");
		$client->setRawData($json)->setEncType('application/json');
		
		$response = $client->request('POST')->getBody();;
		
		echo $response;
		exit;
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
