# Videos #

The screencasts below provide a visual walk-through of installing and setting-up Xerxes.  They are for somewhat older versions of Xerxes, but the steps are basically the same.

  * [Part 1: Installing Xerxes ](http://blip.tv/file/2085080)

  * [Part 2: Advanced configuration](http://blip.tv/file/2085110) ( **update**: you can select an alternative 'quick search' category in config.xml now, so ignore the instructions for changing actions.xml.  **update**: As of version 1.7, the instructions for loading the peer-reviewed data set is now different, see step 7 below. )


# Instructions #

## 1. Download the code ##

You can check out the latest stable code from SVN using the most recent branch, or grab the .zip in the downloads section

## 2. Inspect the contents of the .zip file ##

The .zip and svn should contain the following directories:

  * commands – this is where the main 'business logic' code lives.

  * demo – this is where the individual 'presentation' files live, including configuration files.  Throughout the documentation, I will refer to this as the 'demo' folder, but you can change the name of this directory to anything you like, and can even have multiple instances of this directory for different interfaces; for example, in a centrally hosted consortium with multiple libraries, as we have here.

  * lib – basic classes that Xerxes will use for communicating with Metalib and other systems.

  * sql – scripts to create the necessary local database tables.

'You will need to place the 'demo' directory in a directory accessible to the web, but the other directories can live anywhere on your server.  In the 'demo/index.php' file, you can construct the path to where these other directories are located.


## 3. Create the database tables ##

The first thing you should do is create the database tables that the application will use.  I would normally create a new database, or schema, when setting this up, and the SQL scripts will by default create a new database called 'xerxes' if one does not already exist.  But the table names should be unique enough to live in an existing schema, if necessary.  If you want to host multiple instances of Xerxes, you will need a separate schema for each instance.

Run the 'sql/create-kb.sql' and 'sql/create-serv.sql' scripts to create all the necessary tables.

Xerxes will need to select, delete, update, and insert records into these tables, so if you don't already have a user account with these permissions that Xerxes can use, you will need to create one.  Xerxes will have no need to create or drop tables, and the account it uses should never have access to other databases or tables.  The 'sql/web-user.sql' script has an example of such an account.


## 4. Edit the main config file ##

The next step is to edit the configuration files to include information specific to your implementation, including the address of Metalib, the address of your link resolver, proxy server, and other information related to authentication and application behavior.

The main configuration file is located at 'demo/config/config.xml'.  The file includes some minimal comments and examples.  Check out the [Configuration](Configuration.md) page here on the wiki for a fuller set of information on each entry.  Make sure to enter the database name and user account information you created in the previous section.

### 4b. Security issues with config.xml ###

Your web server will, by default, serve-up the config.xml file over the web, exposing your sensitive information to anyone.

**IIS**

If you are using IIS, the [Installing Xerxes with IIS](InstallingIIS.md) page includes instructions for securing config.xml using permissions.

**Apache**

If you are using Apache, the .htaccess file that comes with the source code includes `FileMatch` directives to prevent Apache from serving the file over the web.  For this to work, though, you must first grant your 'demo' directory, or one of its parents, sufficient privilege to enable these directives.

You can do this in httpd.conf by creating a `Directory` entry for your demo directory and giving it an `AllowOverride All` setting.

If for some reason you cannot make the above changes in Apache, simply change the name of the config.xml to config.php.  This will prevent others from viewing the file directly over the web.


## 5. Populate the local knowledgebase ##

Assuming you having PHP set-up with the CLI (command line interface) option, you can issue a command like this to do the initial pull-down of your Metalib knowledgebase.  Make sure to change the path accordingly.

> `php -f /path/to/xerxes/demo/index.php action=populate base=databases`

## 6. Check to see if everything is working ##

You should now be able to visit xerxes.example.edu/xerxes/demo to see a functioning implementation.

If you kept the mod\_rewrite scheme setting, and are using Apache, make sure to edit the accompanying 'demo/.htaccess' file to reflect the name and location of the 'demo' directory.

## 7. Populate the peer-reviewed journal titles ##

Contact David (dwalker at calstate dot edu ) to get the peer-reviewed data set.  This depends on you having an SFX license, so we just need to verify that.

Put that file in the `lib/data` directory.  Return to the command line, and issue the following command to load the peer-reviewed data set into the `xerxes_refereed` table:

> `php -f /path/to/xerxes/demo/index.php action=populate-refereed base=availability`

Xerxes will use this to flag articles from peer reviewed journals in the interface.

## 8. Optional: Create a cache of the SFX Knowledgebase ##

Xerxes can pull down and cache the institutional holdings export file from SFX, so long as you have registered and set this up on your SFX server for Google or other search engines to harvest.  Xerxes will use this to flag when articles are available online in another database.

To do this, first go to SFX and edit the `config/get_file_restriction.config_` file to include the IP address, or IP range, of the server you are hosting Xerxes on.

Back on your Xerxes server, issue this command from the command line:

> `php -f /path/to/xerxes/demo/index.php action=populate base=availability`

Make sure to change the path accordingly. That should populate the local sfx table.

## 9. Optional: Enable bX recommendations ##

If you license the bX recommendation service from Ex Libris, you can have Xerxes display recommendations it finds in the Xerxes full record display.

To do this, you first need to register the IP address of the server running Xerxes with the bX service. In SFX Admin, go to `bX Configuration` and then `change settings`.

This takes you to the bX admin site.  Go to `My Link Resolvers` and select your SFX instance from the available list.  This is your link resolver profile page.  Select `Add/remove IP addresses`, and enter the IP address of the server running Xerxes.

This sends a message to the Ex Libris network administrators to update the bX firewall configuration, which should be completed in a few hours.

Back on the link resolver profile page, locate the `bX authentication token`.  **Note**: There are two different bX tokens, the registration token and the authentication token.  You want to use the _authentication_ token.  If your enter the wrong one in Xerxes, you'll see an error.

Once you receive confirmation from Ex Libris that the firewall is open, add the authentication token to the `bx_token` entry in `config.xml`.  Recommendations, when available, should now start appearing in your Xerxes records.

## 10. Set-up cron jobs for the cache ##

Eventually you will want to have Xerxes automatically refresh the Metalib KB and SFX
institutional holding data on a regular basis.

I assume here you know how to add items to Linux crontab or Windows scheduled tasks in order to do this.  I always make a batch file that issues the php command, and then add a crontab entry that calls the batch file.  You could just add the php command directly to the crontab if you prefer.

I run the Metalib KB pull every hour during normal daylight hours -- that is, the time when people are most likely making changes.  We run the SFX pull at the same frequency as the institutional holding export cron on SFX, which we have set to once a week.

So, the Metalib pull might look like:

crontab:

> `0 9-20 * * * sh /path/to/xerxes/cron/pull.sh`

'pull.sh' :

> `php -f /path/to/xerxes/demo/index.php action=populate base=databases`

Make sure to change the path accordingly.