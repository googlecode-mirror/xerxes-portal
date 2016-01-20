# Request XML for page #

Add `format=xerxes url` param to any page.

# Caching in Xerxes #

I'm actually doing very little caching in Xerxes.  The xml config files, for example, are reprocessed with every request!

I actually started off thinking I would do more caching -- obviously the config files, for example, would be perfect candidates for something like this.  But we've seen no performance problems in Xerxes, even in our central server, which hosts 10 campuses with ~ 150k students.  So, I've just left it as is, with the idea that, if we did see slow-downs, we would refactor the code to do more caching.


There are a couple of things related to the search that we DO cache.  Commands that need to cache data send it to and receive it from a `Xerxes_Cache` object.  That, in turn, stores the data in a table in the database called xerxes\_cache by way of the `DataMap`.

### `MetasearchSearch` ###
caches information ABOUT the search: your search terms, what category you were in, any spelling suggestions from yahoo, and also any links to the native interfaces from the database you chose, all as a single XML fragment.  Later actions keep retrieving this data for display.

### `MetasearchHits` ###
caches information about the search status as it keeps polling Metalib, again so later actions can use it in the display without having to go back to Metlib.  It also caches the facets as well.

### `MetasearchSort` ###
updates the search status cache with the current sort order.


# Access Control stuff #

## Introduction ##

Xerxes restricts access to certain parts of the application based on whether there is a logged in user, whether the IP address of the client is considered on campus, and the attributes of the logged in user.

These mechanisms also cover when Xerxes will present the user with a login screen.

This stuff is a bit confusing, but we'll try to break it down.

## Session Characteristics ##

### users, real and fake ###

A given session can have a 'user', or not. If the session has a 'user', then there's a 'username' set in the session. However, this is a bit confusing, because a 'user' can be an actual authenticated user from the Xerxes\_User database table **or** can be a temporary fake user created just for the session.

This is determined by the session property 'role'.  If the 'role' is 'named', then this is an actual authenticated user which should be in the Xerxes\_User database. Two other values of 'role' are possible:
  * 'guest'  --  A temporary session 'user' which does not have access to protected resources (only publically available ones).  A 'guest' session can be created by accessing the authenticate/guest action.
  * 'local' -- A temporary session 'user' which DOES have access to protected resources. This is intended to be used for on-campus IP addresses, and is created by the action-based 'restricted' control (see below).

Temporary session 'users' can save records in the Saved Records area, but they will only be accessible for the current session.

### groups ###

Metalib's user groups (aka 'secondary affiliations') can also be used by Xerxes by defining a 

&lt;groups&gt;

 section in the config.xml.

A session can belong to a given group by:
  * having an authenticated user who is registered in the db as belonging to the group
  * having a client IP address that belongs to the range defined for the group (this can give a session access even if there is an authenticated user who does **not** belong to the group!)

### info in Xerxes XML ###

FrontController adds information on the session's authenticatation status to the Xerxes back-end XML in an 

<authorization\_info>

 block. Info here includes whether they are considered an authorized user (and whether that is by virtue of IP address or authenticated login), and their authorization for each defined group (and again, whether that authorization is via IP address or authenticated login).


## Action-based control ##

Sections and Actions in actions.xml can be given attributes related to access control. This is best through of as providing flow control for where and when a login screen is given to the user instead of actual security.

(Any user who knows the appropriate URL can mark their session as a 'guest' session, and thus get past all action-based control, so action-based control is not really sufficient security).

### restricted ###

restricted=true

If restricted is set to true, then the user needs to be **either** logged into an authenticated account **or** from a recognized on-campus IP address **or** in a 'guest' session. (Config.xml setting local\_ip\_range controls what is considered on-campus IP).

There is a somewhat confusing thing here with how on-campus IPs are treated however.

If a user is **off** campus, then the user will be presented with the login screen when trying to use a 'restricted' action, and will not be able to proceed without a valid login.

However, if the user is **on** campus, then the user is not presented with a login screen. Instead, Xerxes will create a temporary session-account with role 'local', and then let them into the action. The purpose of this is to allow on-campus people who may not have accounts to search, and to save records in the Saved Records section during their session.

### login ###

login=true

This only has effect if 'restricted' is also true. If login is also set to true, then the user must be logged into an authenticated account (**not** a temporary session-account in their session ("local" or "guest")). If the user is not logged into an authenticated account, they will be presented with a login screen when trying to use the action.

## Command/Controller based security ##

You may have noted that this action-based control, in addition to not being actually secure, doesn't allow restriction based on the particular restrictions of particular Metalib resources (restricted to authorized users; restricted to certain groups; vs. publically accessible).

Controller (ie Command) code can provide secure protection based on access to individual database(s) by calling certain methods defined in the Xerxes\_Framework\_Restrict and Xerxes\_Helper classes.

### high level ###

  * Xerxes\_Helper::dbSearchableByUser : Checks a single database, returns true or false if it's searchable by the given session. Takes into account restrictions on the given db, and the nature of the current session. Should take into account user groups, guests, and everything else.

  * Xerxes\_Helper::checkDbListSearchableByUser : Checks a whole list of databases, and throws a Xerxes\_DatabasesDeniedException if any one of them is not accessible. Function will only return (boolean true) if all databases are searchable, otherwise an exception will be thrown. The exception includes information on exactly which dbs were problems.

> This exception can be caught by the controller, or just left to be caught by FrontController. If an access denied exception makes it up to front controller, an access denied error message will be shown to the user.  (So any controller can always throw this action manually to prevent access, if convenient).

### low level ###

Those functions in Xerxes\_Helper are usually adequate for controllers, but they are defined in terms of some lower-level functions in Xerxes\_Framework\_Restrict, including:

  * isAuthenticatedUser
  * isIpAddrInRange

### examples ###

The MetasearchSearch command calls checkDbListSearchableByUser and catches the exception thrown. If any dbs were not searchable, they are removed from the search list, but added to another list so the user can be warned that they were excluded.

This provides true security matching the restrictions of the individual databases (group-based restrictions, as well as whether the resoruce should be searchable by 'guest') users, taking account of 'guest' and 'local' sessions etc.

The MetasearchHits and MetasearchResults commands also both call checkDbListSearchableByUser against the db search list, but doesn't bother catching the exception---it percolates up to FrontController where it results in an access denied message. The purpose of the protection in these commands is to prevent a user from sharing search results with someone else that was NOT authorized to see them by giving the the URL. With this protection, even if someone else has the direct URL to the results, their session will be checked for access and possibly denied.