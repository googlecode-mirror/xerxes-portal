<?php

/**
 * Database Value classes
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_DataMap_Data_RecordTag extends Xerxes_Framework_DataValue
{
	public $label;
	public $total;
}

class Xerxes_Model_DataMap_Data_RecordFormat extends Xerxes_Framework_DataValue
{
	public $format;
	public $total;
}

class Xerxes_Model_DataMap_Data_Refereed extends Xerxes_Framework_DataValue
{
	public $issn;
	public $title;
	public $timestamp;
}

class Xerxes_Model_DataMap_Data_Fulltext extends Xerxes_Framework_DataValue
{
	public $issn;
	public $title;
	public $startdate;
	public $enddate;
	public $embargo;
	public $updated;
	public $live;
}

class Xerxes_Model_DataMap_Data_Type extends Xerxes_Framework_DataValue
{
	public $id;
	public $name;
	public $normalized;
	public $databases = array();
}

class Xerxes_Model_DataMap_Data_Record extends Xerxes_Framework_DataValue
{
	public $id;
	public $source;
	public $original_id;
	public $timestamp;
	public $username;
	public $nonsort;
	public $title;
	public $author;
	public $year;
	public $format;
	public $refereed;
	public $marc;
	public $xerxes_record; // not part of table!

	public $tags = array();
}
