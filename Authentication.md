Not long after getting Xerxes set-up, you'll need to add authentication.  Unlike Metalib, Xerxes does not support creating users directly in Xerxes.  You must have an external authentication system.

## Supported authentication options ##

Xerxes comes with several authentication options built-in:

  * CAS (Central Authentication System)
  * Shibboleth
  * Innovative Interfaces Patron API
  * LDAP

Simply set the configuration value [authentication\_source](Configuration#authentication_source.md) to one of the above.

Note that the LDAP option performs a simple bind to your directory server using the user's supplied credentials.  If you need to connect to your directory server using some type of super-user credentials, and then search for a user, you'll need to write your own custom authentication.

## Custom authentication ##

If your institution uses an authentication scheme not currently supported in Xerxes, or you need to make a custom change to one of the above schemes, then you can write your own code using the custom authentication option. ( Do consider contributing your code back, however, so we can add it to the main distribution. )

To do that, you'll need to set [authentication\_source](Configuration#authentication_source.md) to `custom`, and add your code to this file:

> `demo/config/authentication/custom.php`

You'll place your code in various functions based on the Xerxes Authentication framework, which defines different events in the process of when users login and logout.  Which functions you use, depend on how your authentication works, in particular if you send the user to an _external_ site to login (when then redirects the user back to Xerxes), or the user submits their username and password to Xerxes, which then queries the external authentication system.

### onLogin() ###

If a user clicks on the link to login, Xerxes first calls this function first, _before_ it shows the user the login form.

Single sign on schemes (SSO), like CAS or OpenSSL, will use this function to redirect the user to the external SSO system for login.

'Local' authentication options -- that is, those that use the Xerxes login form -- would leave this blank.

### onCallBack() ###

This gets called after the user has _returned_ to Xerxes from the local/remote login form.

For the SSO schemes, that will be via a different action called 'validate'.  For the local schemes, it will be via the same 'login' action as before, but with a query string parameter called 'postback' that lets Xerxes know the user has supplied a user name and password.

CAS uses this function to validate the login request with the SSO service, for example.  The local authentication schemes would use this function to actually send the user's credential to the authentication system (via LDAP, HTTP, etc.) and decide whether the user has supplied the correct credentials.

In both cases, if the user is deemed to be 'good', then the scheme 'registers' the user by assigning properties to a `Xerxes_User` object, which the abstract class will make available to the sub-classes as one if its properties ( the other properties will be the familiar registry and request objects ).

The scheme will at a minimum need to assign the username, but, if other data is available -- like email, last name, first name, etc. -- then it can assign those as well.

### onLogOut() ###

This gets called when the user logs out of Xerxes.

If need be, you can put code here to perform a clean-up or logout action with the external authentication system.   Xerxes will destroy the session itself.

### onEveryRequest() ###

This gets called on every page load in Xerxes.

You might use this to periodically check with an external authentication source to make sure the user hasn't logged out using another system.

For performance reasons, you may want to save this information in session with a timestamp, and then only perform the actual check with the external system after a certain amount of time has elapsed (say five minutes), so that you aren't literally checking it on _every_ page load, but frequently enough.

## No authentication ##

Alternately, you can simply choose not to use any authentication for Xerxes.

Xerxes will, as with the other methods, allow on-campus users to search without a login challenge.  You can provide off-campus access to the system by simply using EZProxy (or WAM, or via VPN client), just as you do now with your subscription databases.

One major draw-back to this approach is that users won't be able to save records beyond the duration of their session, and won't be able to save databases at all (since this requires a login).

Simply add the following CSS to your local.css file to remove the login option, my saved databases, and other login notes, from the interface:

```
#login_option, #my_databases, .temporary_login_note { 
	display: none; 
}
```