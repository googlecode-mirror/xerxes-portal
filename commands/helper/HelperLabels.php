<?php

/**
 * Labels to JS
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_HelperLabels extends Xerxes_Command_Helper
{
	public function doExecute()
	{
		$labels = Xerxes_Framework_Labels::getInstance();
		$xml = $labels->getXML();
		$this->request->addDocument($xml);
	}
}
?>