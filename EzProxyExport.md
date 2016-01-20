Available in Xerxes 1.4 and on.

## Introduction ##

The Metalib KB, imported into Xerxes, is a list of a lot of licensed databases. Those with EZProxy often need to list all these databases in the EZProxy config file.

Xerxes offers a function to automatically create a "best guess" EZProxy config file from the Metalib/Xerxes KB. This process is definitely not perfect, as sometimes manual customization is needed in an EZProxy config. This feature just produces a sort of stock default best guess config.  Nonetheless, some institutions have found it useful.  The export does take account of Metalib "secondary affiliation" and maps those to EZProxy proxy groups as desired, for restricting access to a subset of your users.

If there are particular databases you want to exclude from the EZProxy config, because they do need more complex manual configuration, specific IRDs (database records) can be excluded from the export with a Xerxes config variable (ezp\_exp\_resourceid\_omit). Several other Xerxes config variables are available.

The export is output by the databases/ezproxy-export action. In many cases, it is useful and sufficient to have this available at the URL for that action. You can also run this from the php command line and save to disk if you like:
```
php /path/to/your/xerxes/index.php base=databases action=ezproxy-export > my_ezproxy_config.txt
```

For more information on the EZProxy config file format, see:
  * http://www.oclc.org/us/en/support/documentation/ezproxy/cfg/database.htm
  * http://www.oclc.org/us/en/support/documentation/ezproxy/cfg/groups.htm


## Which databases (IRDs) in the KB are included? ##

Any record that has it's Proxy flag set to "Yes" in the Metalib IRD admin will be included in the EZProxy export **unless** you have included it in the **ezp\_exp\_resourceid\_omit** Xerxes config variable. This takes a comma-delimited list of Metalib IRD IDs. Example:

```
<config name="ezp_exp_resourceid_omit">JHU04344,JHU04729</config>
```

This is useful if you have a database that needs more complex manual setup in EZProxy. Exclude it from the automated export, and then configure it by hand in another included EZProxy config file.

## Domain Grouping ##

An EZProxy config file typically has one entry for an individual **domain** , not one for each URL. URLs from the same domain belong to the same EZProxy config entry, and the domain typically must be given in an EZProxy config entry.

But in the Metalib KB, all we have is URLs for native interfaces. To group by domain, Xerxes first calculates a domain for the URL, and then makes groups of URLs with the same domain.

To calculate the domain, first the hostname is extracted from the native interface URL. If the hostname can not be extracted (perhaps the URL is mal-formed), a warning is output and the particular db is skipped. Otherwise, to find the domain:

  1. If the hostname is given as a numeric IP address, then the hostname is the domain
  1. If the hostname has only two components (ie, "university.edu" rather than "machine.university.edu"), then the hostname is the domain.
  1. If the hostname has at least three components, then take off the least significant component to make the hostname (ie, "machine.vendor.com" turns to "vendor.com", and "machine.sub.vendor.com" turns to "sub.vendor.com)
  1. If the domain you end up with by above is included in the Xerxes config variable "ezp\_exp\_domain\_avoid" (comma-delimited list of domains), then use the full hostname as the domain. If the full hostname too is in included in ezp\_exp\_domain\_avoid, then omit this database, and print a warning.

## Output of a Domain Group ##

Once databases have been grouped into domains, one EZProxy config entry is output for each domain.

### Title ###

First, the title and metalib ID of the first (arbitrary) database in the domain is output as an EZProxy "Title " line. If more than one database is included in this group, "(and others)" is appended to the title.

```
Title 17th & 18th Century Burney Collection Newspapers (JHU05062) (and others) 
```

### Comment ###

Secondly, in an EZProxy comment, the metalib IDs of all databases (IRDs) included in this domain group are output. This can be useful for debugging EZProxy.

```
# Complete list of included Metalib IRD IDs: JHU05062 JHU04343 JHU04344 JHU04354 
```

### URL ###

Next, the URL of the first database included is output in an EZProxy URL statement. (In EZProxy config, this URL statement also implies a Host statement with the same host found in the URL).

```
URL http://infotrac.galegroup.com/itweb/balt85423?db=BBCN
```

### Domain ###

Next, the Domain for the entry is output:

```
Domain galegroup.com
```

### Host ###

Next, a series of EZProxy Host statements. If several of the databases in this domain group all have different complete hostnames, those need to be included in Host statements to tell EZProxy to allow starting links with those hostnames. Any hostnames found in the domain group that are different from the URL will be included as seperate Host lines:

```
Host galenet.galegroup.com
Host find.galegroup.com
Host go.galegroup.com
```

### Derived Host ###

Lastly, two **derived** hostnames are included. One matching the Domain (I'm not sure if this is neccesary, but it doesn't hurt). Secondly, we take the domain and add "www." to the front of it to create a new hostname. Experience shows this is a likely enough needed hostname to include it automatically.

```
Host galegroup.com
Host www.galegroup.com
```

### Group header ###

Metalib databases with no "secondary affiliation" set to restrict access will be output after a single EZProxy config "Group" statement. By default, this will be:

```
Group Default
```

However, you can set the Xerxes config variable **ezp\_exp\_default\_group** to another value to use this for default set.

## Restricted Access ##

If you have some databases set in Metalib with further restrictions using the Metalib "secondary affiliation" feature, that will be respected in the EZProxy export.

The EZProxy domain entries will be put into groups sharing the same secondary affilationm and each of these clusters will be output together, with a Group heading. If more than one secondary affiliation is listed, they will both be included in the Group statement, seperated by the "+" delimiter EZProxy uses in Group statements.

By default, the Group heading for a particular 'secondary affiliation' uses the name of the Metalib secondary affiliation. However, you can map a Metalib secondary affiliation to a particular EZProxy group name as part of a Xerxes config group tag:

```
 <groups>
      <group id="Metalib_secondary_affiation">
        <display_name>Some library Library</display_name>
        <ezp_exp_group>EZPROXY GROUP VALUE</ezp_exp_group>
```

**NOTE WELL**:  An EZProxy entry, grouped by domain, may include several databases (IRDs) from Metalib/Xerxes.  If two databases with the same domain have conflicting "secondary affiliation" restrictions, EZProxy is not capable of enforcing that accurately. See http://www.oclc.org/us/en/support/documentation/ezproxy/cfg/groups.htm

Consequently, the config generated by Xerxes will be generous enough to provide access to everything. That is, if one url at vendor.com is restricted to Science\_Users and another is restricted to Humanities\_Users, then the entire domain (both URLs) will allow both Science\_Users and Humanities\_Users in.  You may be allowing users in to some resources who you didn't intend to, but this is a limitation of EZProxy. Any domains that were "expanded" like this will be noted in the warnings at the bottom of the generated EZProxy config.

## Warnings ##

At the very bottom of the EZProxy config file generated may be some EZProxy comments beginning with "#" containing warnings from the Xerxes generation script. If a particular database can not be included due to an error, it will be mentioned here. Also, if two databases with the same domain had conflicting "secondary affiliation" restrictions, it will be noted here.