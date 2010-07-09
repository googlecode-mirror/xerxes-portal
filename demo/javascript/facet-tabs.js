
	
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
	
	

	addEvent(window, 'load', showHitCounts);
	
	function showHitCounts()
	{
		var links = document.getElementsByTagName('span');
		
		var query = $('query').value;
		var field = $('field').value;
		
		for ( i = 0; i < links.length; i++)
		{		
			if ( /tabsHit/.test(links[i].className) )
			{
				arrElements = links[i].id.split(":");
				base = arrElements[1];
				
				url = ".?base=" + base + "&action=hits&query=" + query + "&field=" + field;

				new Ajax.Updater(links[i].id, url);
			}
		}
	}
	