# Upgrading between versions #

Here are the basic steps for upgrading between versions of Xerxes.

## 1. Download the latest code to a development server / space ##

Download the latest version of the code from the [download section](http://code.google.com/p/xerxes-portal/downloads/list) or check it out from the [svn repository](http://code.google.com/p/xerxes-portal/source/checkout). You'll now basically set-up a fresh Xerxes install on the dev server.

Copy any customized XSLT, CSS, or PHP files from the production server to the development server.  Then you can test the new version with your customizations, and resolve any conflicts that may occur.

One strategy for making this a little easier is to keep a shadow copy of your `demo` folder on your computer with **only** the directories and files you've changed.  In that way, you can easily push this over the new version.

## 2. Move the development code into production ##

Once everything looks good, you'll need to schedule an off time to copy the development files over to the new server.  At that point, you'll need to update the production server's MySQL database.

## 3. In MySQL, execute sql/create-kb.sql ##

Every time you upgrade, you should execute the latest sql/create-kb.sql file.  This will drop and recreate (with any new definitions) the tables that Xerxes uses to cache the Metalib knowledgebase. Do that in MySQL and then run the Xerxes populate databases command from the command line.  In that way, Xerxes will refresh its cache.

> php -f /path/to/your/app/index.php base=databases action=populate


## 4. In MySQL, execute sql/migrate/migrate-1.6-to-1.7.sql ##

_substitute 1.6 and 1.7 above with the actual version number_. This will update the other tables Xerxes uses.  Since these other tables include data that cannot be easily recreated (e.g., your users' saved records), you should **always** run the migrate sql rather than the create-serv.sql, since the latter will drop your tables.