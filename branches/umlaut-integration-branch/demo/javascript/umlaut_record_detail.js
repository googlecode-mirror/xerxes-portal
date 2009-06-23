/* Add umlaut data directly to a record detail page
 *
 *  Expects global js variables to be defined:
 * openurl_kev_co  :   an OpenURL KEV context object representing this record
 * umlaut_base     :   The Umlaut base URL. Not the OpenURL base including /resolve, but just eg "http://findit.library.jhu.edu/"
 */
 
 addEvent(window, 'load', loadUmlautContent);
 
 function loadUmlautContent() {
   function show_umlaut_content(count, div_id) {
     if (count > 0 && $(div_id) && ! $(div_id).visible()) {
       // tried sciptaculous SlideDown, but did weird things in IE. 
       $(div_id).show();
     }
   }
   
   
   // Umlaut needs this global js variable. 
   umlaut_openurl_kev_co = openurl_kev_co;
   
   // Add a spinner please, and preserve original content.
   spinner = '<div class="recordAction linkResolverLink umlautLoad"><img src="' + umlaut_base + '/images/spinner.gif" alt=""/> Loading content from <a href="' + umlaut_base + '/resolve?' + openurl_kev_co + '">Find It</a></div>';
   $$('.recordAction.linkResolverLink').each ( function(item) {
      
       item.hide();
       item.insert({'after': spinner});       
    });
   
   
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
                 link.insert({'top': 'Full-Text Available: '}); 
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
     
     // not a section map, but a final all-complete callback
     'all-complete-callback': function() {
            $$('.recordAction.linkResolverLink').each ( function(item) {            
                item.show();     
            });
            $$('.recordAction.umlautLoad').each ( function(item) {            
                item.remove();     
            });            
          }
   };
   

   
   
 }
