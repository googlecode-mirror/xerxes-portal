/* Take the status/feedback message from the page (id='message_display'), and
   take it out of normal page flow, and use cool scriptaculous effects to fade
   it in and out in a fixed position in browser window. */

   addEvent(window, 'load', fancyMessageDisplay);
   
   function fancyMessageDisplay() {
     if ($('message_display')) {
        // Take it out of the page, and put it into position
        $('message_display').hide(); 
        $('message_display').style.position = 'fixed'; 
        $('message_display').style.zIndex = '100';  
        $('message_display').style.top = '35px'; 
        $('message_display').style.right = '25%'; 
        
        // fancy effects!
        $('message_display').appear({ duration: 1.5, from:0, to:0.85,  queue: { position: 'end', scope:'messagedisplayscope' } });  
        $('message_display').fade({ duration:1.5, from:0.85, to:0,  delay: 2, queue: { position: 'end', scope:'messagedisplayscope' } });
     }
   }
