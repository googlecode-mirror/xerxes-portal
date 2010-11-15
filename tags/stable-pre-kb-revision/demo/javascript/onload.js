/**
 * simple add event function
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */


function addEvent(obj, evType, fn)
{ 
	if (obj.addEventListener)
	{ 
		obj.addEventListener(evType, fn, false); 
		return true; 
	} 
	else if (obj.attachEvent)
	{ 
		var r = obj.attachEvent("on"+evType, fn); 
		return r; 
	} 
	else
	{ 
		return false; 
	} 
}
