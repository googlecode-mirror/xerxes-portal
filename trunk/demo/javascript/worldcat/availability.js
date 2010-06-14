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
				
				url = ".?base=worldcat&action=lookup&id=" + id + "&isbn=" + isbn + "&oclc=" + oclc + "&source=" + requester + "&display=" + view;

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
	
	
	
	
	
	
	
	
	
	addEvent(window, 'load', addAjaxToFacetMoreLinks);
	addEvent(window, 'load', minimizeFacets);

	function addAjaxToFacetMoreLinks()
	{		
		var links = document.getElementsByTagName('a');
		
		for ( i = 0; i < links.length; i++)
		{		
			if ( /facetMoreOption/.test(links[i].className) )
			{				
				links[i].onclick = function () {
					return showFacetMore(this.id)
				}
			}
			else if ( /facetLessOption/.test(links[i].className) )
			{				
				links[i].onclick = function () {
					return showFacetLess(this.id)
				}
			}
		}
	}
	
	function minimizeFacets()
	{	
		var lists = document.getElementsByTagName('ul');
		
		for ( i = 0; i < lists.length; i++)
		{		
			if ( /facetListMore/.test(lists[i].className) )
			{				
				lists[i].hide();
				
				id = lists[i].id.replace("facet-list-", "");
				$('facet-more-' + id).show();
			}
		}

	}
	
	
	function showFacetMore(id)
	{
		id = id.replace("facet-more-link-", "");
		
		$('facet-more-' + id).hide();
		$('facet-list-' + id).show();
		$('facet-less-' + id).show();
		
		return false;
	}
	
	function showFacetLess(id)
	{
		id = id.replace("facet-less-link-", "");
		
		$('facet-more-' + id).show();
		$('facet-list-' + id).hide();
		$('facet-less-' + id).hide();
		
		return false;
	}

	
	
	
	
