/* Add umlaut data directly to a record detail page
 *
 *  Expects global js variables to be defined:
 * openurl_kev_co  :   an OpenURL KEV context object representing this record
 * umlaut_base     :   The Umlaut base URL. Not the OpenURL base including /resolve, but just eg "http://findit.library.jhu.edu/"
 */
 
 if (typeof(jsDisplayConstants) == "undefined" ) {
   jsDisplayConstants = new Array(); 
 }
 
 if (! ('link_resolver_name' in jsDisplayConstants)) {
   jsDisplayConstants['link_resolver_name'] = 'Link Resolver';
 }
 if (!('link_resolver_load_message' in jsDisplayConstants)) {
   jsDisplayConstants['link_resolver_load_message'] = "Loading content from";
 }
 if (!('link_resolver_direct_link_prefix' in jsDisplayConstants)) {
   jsDisplayConstants['link_resolver_direct_link_prefix'] = "Full-Text Available: ";
 }
 
 document.observe("dom:loaded",  function() {
     // Add a spinner please, and preserve original content.
     spinner = '<div class="recordAction linkResolverLink umlautLoad"><img src="' + umlaut_base + '/images/spinner.gif" alt=""/> '+jsDisplayConstants['link_resolver_load_message']+' <a href="' + umlaut_base + '/resolve?' + openurl_kev_co + '">'+jsDisplayConstants['link_resolver_name']+'</a></div>';
     $$('.recordAction.linkResolverLink').each ( function(item) {
        
         item.hide();
         item.insert({'after': spinner});       
      });
 });
   
 
   
   
   function show_umlaut_content(count, div_id) {
     if (count > 0 && $(div_id) && ! $(div_id).visible()) {
       // tried sciptaculous SlideDown, but did weird things in IE. 
       $(div_id).show();
     }
   }
   
   
   // Umlaut needs this global js variable. 
   umlaut_openurl_kev_co = openurl_kev_co;


   
   
   // Now we need to map Umlaut sections to divs on our page.
   // Keys are umlaut section names, values are div's on the Xerxes page.
   // Some elements use Umlaut js callbacks to only show them if 
   // there are items to show. 
   umlaut_section_map = {
     'fulltext': {
       'host_div_id': 'umlaut_fulltext',
       'after_update': function(count) {
         // only show if we don't have any xerxes-native fulltext
         if ( count > 0 &&
             $$('#recordFullText .fullTextLink').length == 0) {
             // Hide section heading 
             $('umlaut_fulltext').down('.section_heading').hide();
             // Make the links more like Xerxes's patterns
             $$('#umlaut_fulltext .response_item a').each( function(link) {
                 link.insert({'top': jsDisplayConstants['link_resolver_direct_link_prefix']}); 
             });
             // No spinner please
             $$('#umlaut_fulltext .background_progress_spinner').each(    
               function(spinner) { spinner.hide();}
               );
             show_umlaut_content(count, 'umlaut_fulltext');
         }
       }
     },
     'highlighted_link' : { 
        'host_div_id': 'see_also',
        'after_update': function(count) { show_umlaut_content(count, 'see_also');}
     },     
     'excerpts' : {
        'host_div_id': 'limited_preview',
        'after_update': function(count) {  show_umlaut_content(count, 'limited_preview');}
        
     },     
     'search_inside': {
        'host_div_id': 'search_inside',
        'after_update': function(count) {  show_umlaut_content(count, 'search_inside');}
     },
     'related_items': {
       'host_div_id': 'similar_items',
       'after_update': function(count) {  show_umlaut_content(count, 'similar_items');}
     },
     'holding' : {
       'host_div_id': 'library_copies',
       'after_update': function(count) {  show_umlaut_content(count, 'library_copies');}
     },
     'document_delivery': {
       'host_div_id': 'document_delivery',
       'after_update': function(count) {
          if ($$('#recordFullText .fullTextLink').length == 0) show_umlaut_content(count, 'document_delivery');
       }
     }
   };
   
   umlaut_options = {
     'all-complete-callback': function() {
            $$('.recordAction.linkResolverLink').each ( function(item) {            
                item.show();     
            });
            $$('.recordAction.umlautLoad').each ( function(item) {            
                item.remove();     
            });            
    }
   };
   
   embedUmlaut(umlaut_base, openurl_kev_co, umlaut_section_map, umlaut_options);
