<?php

/**
 * Base class for 'collection' commands, personal db collections
 *
 */

abstract class Xerxes_Command_Collections extends Xerxes_Framework_Command
{

  public function returnWithMessage($strMessage) {
    $return = $this->request->getProperty("return");
    
    if ( $return ) {
      $url = "http://" . $this->request->getServer('SERVER_NAME') .  $this->request->getProperty("return");
    }
    else {
      $url = $this->registry->getConfig("BASE_WEB_PATH");
    }
    
    $this->request->setSession("flash_message", $strMessage);
    $this->request->setRedirect( $url );
  }
}

?>