<?php
  /* Implement code in this function to authorize from shibboleth.
     User has already been authenticated when this function is called. 
     
     The complete http headers set by the Shib SP running on apache
     are passed in in $headers.  A Xerxes_User object is passed in
     in $user, filled out with possibly no more than ->username. 
     
     This function may:
      1) Throw a Xerxes_AccessDeniedException if based on attributes
         you want to deny user access to logging into Xerxes at all.
         The message should explain why. 
         
      2) Set various propertes in the $user object, and then return it,
         if you want to allow access, but fill out some more user properties
         based on attributes in headers set by Shib. You can also even pick
         a new username for the user, based on headers or the info that
         Xerxes already found. 
  */
 
  function local_shib_user_setup($headers, $user) {
    
    return $user;
  }  


return 1;
?>
