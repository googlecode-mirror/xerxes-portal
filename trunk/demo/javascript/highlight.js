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
	// add this link if javascript is on
	jQuery("#toggleHighlightingDiv").html('<a href="javascript:toggleHighlighting()" id="toggleHighlighting"> ' + xerxes_labels['text_results_highlighting_turn_off'] + '</a>');
	
	if (jQuery('#query').length) { // if such element exists
		terms = jQuery('#query').val().split(' ');
		highlightTerms(terms);
	}
}

function toggleHighlighting()
{
	if (highlighting) {
		jQuery('#toggleHighlighting').html(xerxes_labels['text_results_highlighting_turn_on']);
		jQuery('div').removeHighlight();
		highlighting = false;
	} else {
		jQuery('#toggleHighlighting').html(xerxes_labels['text_results_highlighting_turn_off']);
		highlightSearchTerms();
		highlighting = true;
	}
}
