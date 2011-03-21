<?php 

/**
 * the tip of the iceberg
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

chdir(dirname(__FILE__));

require_once '../library/Xerxes/Framework/FrontController.php';
Xerxes_Framework_FrontController::execute();
