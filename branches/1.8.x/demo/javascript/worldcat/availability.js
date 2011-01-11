/** 
 * functions for availability
 *
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

	addEvent(window, 'load', fillAvailability);
	addEvent(window, 'load', setNoImage);
	addEvent(window, 'load', prepareSMS);
	
	function prepareSMS()
	{
		if ( $('sms') )
		{
			$("sms").hide();
			
			$("smsLink").onclick = function() {
				return showSMS()
			}	
			
			document.smsForm.onsubmit = function() {
				return checkForm();
			}
					
			return false;
		}
	}
	
	function showSMS()
	{
		$("sms").show();

		$("smsLink").onclick = function() {
			return prepareSMS()
		}	

		return false;
	}

	function checkForm()
	{
		var provider = document.smsForm.provider.value;
		var phone = document.smsForm.phone.value;
		
		if ( phone == '' )
		{
			alert('Please enter a phone number');
			return false;
		}
		
		
		if ( provider == '' )
		{
			alert('Please choose your cell phone provider');
			return false;
		}
		
		return true;	
		
	}

	function fillAvailability()
	{		
		var divs = document.getElementsByTagName('div');
		
		// fill each div with look-up information
		// will be either based on isbn or oclc number
		
		for ( i = 0; i < divs.length; i++ )
		{
			if ( /availabilityLoad/.test(divs[i].className) )
			{
				$(divs[i]).update("<img src=\"images/loading.gif\" alt=\"loading\" /> Checking availability . . .");
			
				var url = "";		// final url to send to server
				
				arrElements = divs[i].id.split(":");
				requester = arrElements[0];
				id = arrElements[1];
				isbn = arrElements[2];
				oclc = arrElements[3];
				view = arrElements[4];
				base = arrElements[5];
				
				url = ".?base=" + base + "&action=lookup&id=" + id + "&isbn=" + isbn + "&oclc=" + oclc + "&source=" + requester + "&display=" + view;

				new Ajax.Updater(divs[i].id, url);
			}
		}
	}

	
	function setNoImage()
	{
		var imgs = document.getElementsByTagName('img');
		
		for ( i = 0; i < imgs.length; i++ )
		{
			if ( /book-jacket-large/.test(imgs[i].className) )
			{			
				if ( imgs[i].width != 1 )
				{
					$("worldcatRecordBookCover").show();
					$("worldcatRecord").setStyle({'marginLeft': (imgs[i].width + 20) + 'px'});
				}
			}
			else ( /book-jacket/.test(imgs[i].className) )
			{
				if ( imgs[i].width == 1 )
				{
					imgs[i].src = "images/no-image.gif";
				}
			}
		}
		
	}
