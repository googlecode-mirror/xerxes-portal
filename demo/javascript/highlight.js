/**
 * FUNCTIONS FOR HIGHLIGHTING
 */

// in Xerxes 1.x, don't use the $() abbreviation to avoid conflict with prototype.js
// we are moving to jQuery in version 2

jQuery.noConflict(); 
			
addEvent(window, 'load', highlightSearchTerms );

var highlighting = true;
 
function highlightTerms(terms)
{
	for (var i=0; i < terms.length; i++)
	{
		jQuery('div').highlight(terms[i]);
	}
}

function highlightSearchTerms()
{
	if (jQuery('#query').length) { // if such element exists
		terms = jQuery('#query').val().split(' ');
		highlightTerms(terms);
	}
}