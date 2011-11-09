/**
 * results page
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */
 
$(document).ready(addAjaxToFacetMoreLinks);
$(document).ready(minimizeFacets);
$(document).ready(showHitCounts);
$(document).ready(setNoImage);
$(document).load(fillAvailability);

function addAjaxToFacetMoreLinks()
{
	$(".facetMoreOption").click(function() {
		return showFacetMore(this.id);
	});

	$(".facetLessOption").click(function(){
		return showFacetLess(this.id);
	});
}

function minimizeFacets()
{	
	$('ul.facetListMore').hide();
	$('.facetOptionMore').show();
}

function showFacetMore(id)
{
	id = id.replace("facet-more-link-", "");
	
	$('#facet-more-' + id).hide();
	$('#facet-list-' + id).show();
	$('#facet-less-' + id).show();
	
	return false;
}

function showFacetLess(id)
{	
	id = id.replace("facet-less-link-", "");
	
	$('#facet-more-' + id).show();
	$('#facet-list-' + id).hide();
	$('#facet-less-' + id).hide();
	
	return false;
}

function showHitCounts()
{
	if ( $('#query') )
	{		
		var query = $('#query').val();
		var field = $('#field').val();
		
		var links = document.getElementsByTagName('span');
		
		for ( i = 0; i < links.length; i++)
		{		
			if ( /tabsHitNumber/.test(links[i].className) )
			{
				hitID = links[i].id;
								
				arrElements = links[i].id.split("_");
				base = arrElements[1];
				source = arrElements[2];
									
				var url = ".?base=" + base + "&action=hits&query=" + query + "&field=" + field;
				
				if ( source != '' )
				{
					url += "&source=" +  source;
				}
				
				updateElement(url, links[i]);
			}
		}
	}
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
			$(divs[i]).html("<img src=\"images/loading.gif\" alt=\"loading\" /> Checking availability . . .");
		
			var url = "";		// final url to send to server
			
			arrElements = divs[i].id.split(":");
			requester = arrElements[0];
			id = arrElements[1];
			isbn = arrElements[2];
			oclc = arrElements[3];
			view = arrElements[4];
			base = arrElements[5];
			
			url = ".?base=" + base + "&action=lookup&id=" + id + "&isbn=" + isbn + "&oclc=" + oclc + "&source=" + requester + "&display=" + view;			
			updateElement(url, divs[i])
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
				$(".bookRecordBookCover").show();
				$(".bookRecord").css('marginLeft', (imgs[i].width + 20) + 'px');
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

function updateElement(url, element)
{
	$.get(url, function(data) {
		$(element).html(data);
	});
	//.error(function() { $(hitID).html("error") });
}