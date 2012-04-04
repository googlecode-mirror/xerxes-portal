/* Add umlaut data directly to a record detail page using Umlaut 3.x jquery
 * content utility helpers.
 * 
 *  Expects global js variables to be defined: 
 *  Umlaut.umlaut_base     :   The Umlaut base URL. Not the OpenURL base including /resolve, but just eg "http://findit.library.jhu.edu/"
 *  Umlaut.openurl_kev_co  :   an OpenURL KEV context object representing this record
 *
 * @author Jonathan Rochkind
 * @copyright 2012 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */
 
 jQuery(document).ready(function($){
     // Hide original link resolver link, and add a spinner from Umlaut in it's place. 
     spinner = '<div class="recordAction linkResolverLink umlautLoad"><img src="' + Umlaut.umlaut_base + '/images/spinner.gif" alt=""/> '+xerxes_labels['link_resolver_load_message']+' <a href="' + Umlaut.umlaut_base + '/resolve?' + Umlaut.openurl_kev_co + '">'+xerxes_labels['link_resolver_name']+'</a></div>';
     $('.recordAction.linkResolverLink').hide().after(spinner);
     
     
     var updater = new Umlaut.HtmlUpdater(Umlaut.umlaut_base,  Umlaut.openurl_kev_co  );

     // Now we need to map Umlaut sections to divs on our page.
     // Keys are umlaut section names, values are div's on the Xerxes page.
     // Some elements use Umlaut js callbacks to only show them if 
     // there are items to show.
     
     // Got to work around umlaut 2.x bug that puts &umlaut.format=json
     // links erroneously in expand/contract toggle non-JS links, sorry!
     // This function will be used in every before_update, since umlaut 2.x
     // doesn't support global before_update. 
     function umlaut2x_link_fix(html) {       
        $(html).find("a.expand_contract_toggle").attr("href", function(i, href) {
          return href.replace(/[&?]?umlaut\.response_format\=json/, "")
        }); 
     }
     
     
     //when all update is complete, hide the progress message etc
     updater.complete = function() {
        $('.recordAction.linkResolverLink').show();
        $('.recordAction.umlautLoad').remove();                           
    };
     
     updater.add_section_target({
         umlaut_section_id: 'fulltext',
         selector: "#umlaut_fulltext",
         before_update: umlaut2x_link_fix,
         after_update: function(html, count) {
           // only show if we don't have any xerxes-native fulltext
           if ( count > 0 &&
             $('#recordFullText .fullTextLink').length == 0) {
             // Hide section heading 
             $(html).find('.section_heading').first().hide();
             // Make the links more like Xerxes's patterns
             $(html).append(xerxes_labels['link_resolver_direct_link_prefix']);
             
             // No spinner please
             $(html).find('.background_progress_spinner').remove();
             
             $('#umlaut_fulltext').show();             
         }
       }         
     });
     
     updater.add_section_target({
         umlaut_section_id: 'highlighted_link', 
         selector: '#see_also',
         before_update: umlaut2x_link_fix,
         after_update: function(html, count) { 
           if (count > 0 ) {
             $('#see_also').show();
           }
         }       
     });
     
     updater.add_section_target({
         umlaut_section_id: 'excerpts',
         selector: '#limited_preview',
         before_update: umlaut2x_link_fix,
         after_update: function(html, count) {
           if (count > 0) {
             $('#limited_preview').show();
           }
         }
     });
         
     updater.add_section_target({
         umlaut_section_id: 'related_items',
         selector: '#similar_items',
         before_update: umlaut2x_link_fix,
         after_update: function(html, count) {
           if (count > 0) {
             $("#similar_items").show();
           }
         }
     });
     
     updater.add_section_target({
         umlaut_section_id: 'search_inside',
         selector: '#search_inside',
         before_update: umlaut2x_link_fix,
         after_update: function(html, count) {  
           if (count > 0) {
              $("#search_inside").show();   
           }
         }      
     });
     
     updater.add_section_target({
         umlaut_section_id: 'holding',
         selector: '#library_copies',
         before_update: umlaut2x_link_fix,
         after_update: function(html, count) {  
           if (count > 0) {
              $("#library_copies").show();   
           }
         } 
     });
     
     updater.add_section_target({
       umlaut_section_id: 'document_delivery', 
       selector: '#document_delivery',
       before_update: umlaut2x_link_fix,
       after_update: function(html, count) {  
           if (count > 0) {
              $("#document_delivery").show();   
           }
         }
     });
     
     
     
     updater.update();
     
 });
