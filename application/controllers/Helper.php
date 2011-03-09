<?php

class Xerxes_Controller_Helper extends Xerxes_Controller_Search
{
	public function labels()
	{
		$lang = $this->request->getParam("lang");
		
		$labels = Xerxes_Framework_Labels::getInstance($lang);
		$this->response->add($labels, "labels");
		
		$this->response->setView("scripts/helper/labels.phtml");
	}	
}
