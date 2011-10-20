<?php

/**
 * MARC DatafieldList
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Marc_DataFieldList extends Xerxes_Marc_FieldList 
{
	public function subfield($code, $specified_order = false) // convenience method
	{
		if ( count($this->list) == 0 )
		{
			return new Xerxes_Marc_SubField(); // return empty subfield object
		}
		else
		{
			if ( strlen($code) == 1)
			{
				// only one subfield specified, so as a convenience to caller
				// return the first (and only the first) subfield of the 
				// first (and only the first) datafield  
				
				$subfield = $this->list[0]->subfield($code,$specified_order)->item(0);
				
				if ( $subfield == null )
				{
					return new Xerxes_Marc_SubField(); // return empty subfield object
				}
				else
				{
					return $subfield;
				}
			}
			else
			{
				// multiple subfields specified, so return them all, but 
				// again only from the first occurance of the datafield
				
				return $this->list[0]->subfield($code,$specified_order);
			}
		}
	}
}