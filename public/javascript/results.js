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
				
				updateHitCount(url, links[i].id);
			}
		}
	}
}

function updateHitCount(url, hitID)
{
	$.get(url, function(data) {
		$('#' + hitID).html(data);
	});
	//.error(function() { $(hitID).html("error") });
}
	