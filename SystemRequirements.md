## PHP 5.1 or higher ##

We recommend you use the latest, stable release of PHP 5.3.

Xerxes also requires these PHP extensions:

  * pdo – for communication with the database
  * xsl – for a number of tasks, including the interface
  * cli – command line interface for scheduling tasks via cron

And will optionally require these PHP extensions:

  * pdo\_mysql – if you plan to use MySQL
  * pdo\_odbc – if you plan to use MS SQL Server
  * ldap – if you plan to authenticate users against a directory server
  * openssl - if you plan to use CAS authentication
  * curl - if you need Xerxes to use an http proxy or need curl for security reasons


## MySQL 5.0 or higher --_or_-- MS SQL Server 2005 or higher ##

There are scripts to create tables on both MySQL and MS SQL Server.

Although Xerxes is tested against, and used in production with, MS SQL Server, all development work is done against MySQL.  MySQL is therefore the preferred database, even on Windows servers.

## Web Server ##

You can pretty much run Xerxes on whatever web server you want, so long as it
can support PHP.  People have successfully set it up using Apache 1.3, Apache 2.x and IIS 6 on both Windows and Linux servers.