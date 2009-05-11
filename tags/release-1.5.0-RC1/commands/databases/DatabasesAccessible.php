<?php

class Xerxes_Command_DatabasesAccessible extends Xerxes_Command_Helper
{
	public function doExecute()
	{
		$this->request->setSession("ada", "true");
		$this->request->setRedirect($this->request->getProperty("return"));
	}
}
?>
