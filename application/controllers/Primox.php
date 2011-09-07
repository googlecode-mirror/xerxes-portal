<?php

class Xerxes_Controller_Primox extends Xerxes_Controller_Search
{
	protected $id = "primox";
	
	public function __construct()
	{
		parent::__construct();
		
		$this->config = Xerxes_Model_Primo_Slim_Config::getInstance();
		$this->query = new Xerxes_Model_Search_Query($this->request, $this->config);
		$this->engine = new Xerxes_Model_Primo_Slim_Engine($this->config);
		
		$this->response->add("config_local", $this->config);
	}
	
	public function results()
	{
		parent::results();
		$this->response->setView("xsl/primo/primo_results.xsl");
	}

	public function record()
	{
		parent::record();
		$this->response->setView("xsl/primo/primo_record.xsl");
	}

	protected function addFacetLinks( Xerxes_Model_Search_ResultSet &$results )
	{	
		// facets

		$facets = $results->getFacets();
		
		if ( $facets != "" )
		{
			foreach ( $facets->getGroups() as $group )
			{
				if ( $group->name == "topic" )
				{
					foreach ( $group->getFacets() as $facet )
					{
						$params = array(
							'base' => $this->request->getParam("base"),
							'action' => 'results',
							'query' => $facet->name
						);
						
						$facet->url = $this->request->url_for($params);
					}
				}
			}
		}
	}
}
