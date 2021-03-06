/**
 * Take the status/feedback message from the page (id='message_display'), and
 * take it out of normal page flow, and use cool scriptaculous effects to fade
 * it in and out in a fixed position in browser window.
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

   addEvent(window, 'load', fancyMessageDisplay);
   
   function fancyMessageDisplay() {
     if ($('message_display')) {
        // Take it out of the page, and put it into position
        $('message_display').hide(); 

       /* some wacky conditional comment way to detect IE6 */
        var IE6 = IE6=(navigator.userAgent.toLowerCase().indexOf('msie 6') != -1) && (navigator.userAgent.toLowerCase().indexOf('msie 7') == -1)
        if (! IE6 ) {                
          $('message_display').style.position = 'fixed';
        }
        else {
          $('message_display').style.position = 'absolute';
        }
        $('message_display').style.zIndex = '100';  
        $('message_display').style.top = '35px'; 
        $('message_display').style.right = '20px'; 
        
        // fancy effects!
        $('message_display').appear({ duration: 1.5, from:0, to:0.9,  queue: { position: 'end', scope:'messagedisplayscope' } });  
        $('message_display').fade({ duration:1.5, from:0.9, to:0,  delay: 3, queue: { position: 'end', scope:'messagedisplayscope' } });
     }
   }
