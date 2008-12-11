<?php

/**
 * Base class for 'collection' commands, personal db collections
 *
 */

abstract class Xerxes_Command_Collections extends Xerxes_Framework_Command
{

  public function returnWithMessage($strMessage) {
    $url = $this->registry->getConfig( 'BASE_WEB_PATH', false, "" ) . "/".  $this->request->getProperty($return);

    $this->request->setSession("flash_message", $strMessage);
    $this->request->setRedirect( $url );
  }
}

?>