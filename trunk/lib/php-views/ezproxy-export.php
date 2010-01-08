<?php

/**
 * Outputs an EZProxy config file from Metalib/Xerxes KB. See:
 * http://code.google.com/p/xerxes-portal/wiki/EzProxyExport
 *
 * @author Jonathan Rochkind
 * @copyright 2009 Johns Hopkins University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

/* 
For documentation of the EZProxy config format, see:
http://www.oclc.org/us/en/support/documentation/ezproxy/cfg/database.htm
http://www.oclc.org/us/en/support/documentation/ezproxy/cfg/groups.htm
*/

// inherits $objRegistry and $objXml

header("Content-type: text/plain");

// simpleXml is a lot easier to work with than DOM

$objDatabases = simplexml_import_dom($objXml->documentElement);


// First sort and process
$warnings = array();
$exporter = new EzProxyExportGen($objRegistry);
foreach ( $objDatabases->databases->database as $xmlDatabase) {
  
  try {
    if ($xmlDatabase->proxy == '1') {
      $exporter->addDbXml( $xmlDatabase );
    }
  }
  catch (Exception $e) {
    array_push($warnings, $e->getMessage());
  }
}

$exporter->createConfig($warnings);

if (count($warnings) > 0 ) {
  print "# NOTE: Warnings for Xerxes EZProxy output: \n#\n";
  foreach ($warnings as $w) {
    print "# " . str_replace("\n", " ", $w) . "\n#\n"; 
  }
}

class EzProxyExportGen {
  private $objRegistry = null;
  private $index_by_domain = array();
  private $index_by_restriction = array();
  
  public function __construct($argRegistry) {
    $this->objRegistry = $argRegistry; 
  }
  
  // Takes a simple xml object representing a database, in Xerxes. 
  public function addDbXml($xmlDatabase) {    
      $dbHash = $this->makeDbHash($xmlDatabase);
      
      // To begin with, index it by domain, to get urls in same
      // domain together.
      $domain = $dbHash['domain'];
      if (! array_key_exists($domain, $this->index_by_domain)) {
        $this->index_by_domain[ $domain ] = array();
      }
      array_push( $this->index_by_domain[$domain], $dbHash );
  }
  
  public function createConfig(&$warnings) {
    // We have our individual entries grouped by domains. 
    // We need to take these domain groups, and group them
    // by access control. 
    foreach ($this->index_by_domain as $domainList) {
      
      $first_entry = $domainList[0];      
      $groups = array_merge($first_entry['group_restrictions']); // array_merge to make a copy

      for ($i = 1; $i < count($domainList) - 1; $i++ ) {
          $entry = $domainList[$i];
          if ( count( array_diff($entry['group_restrictions'], $groups)) > 0) {
                        
            array_push($warnings, "Conflicting group restrictions in two resources with same domain can not be enforced. Ezproxy restrictions may be more generous than intended: 1) " . $first_entry["title"] . "(" . $first_entry["metalib_id"] . ") 2) " . $entry["title"] . ")" . $entry["metalib_id"] .")");
            
            $groups = array_unique(array_merge( $groups, $entry['group_restrictions']));            
          }
      }
      
      // We can't combine default with other restrictions, default
      // trumps it. 
      if ( in_array('Default', $groups)) {
        $restriction_key = 'Default';
      }
      else {
        sort($groups);
        $restriction_key = join(';',$groups);
      }
      
      if (! array_key_exists($restriction_key, $this->index_by_restriction)) {
        $this->index_by_restriction[$restriction_key] = array();
      }
      array_push( $this->index_by_restriction[$restriction_key], $domainList );

    }
    // Now we've grouped by restriction, let's output. 
    
    foreach (array_keys( $this->index_by_restriction ) as $restriction_key) {
      $groups = explode(';', $restriction_key);
      
      $ezproxy_groups = "";
      foreach ($groups as $group) {
        $ezproxy_groups .= $this->getEzProxyGroup($group);
      }
      if ($ezproxy_groups != "") {
        print "\n\n#EZProxy group for metalib secondary affiliations: $restriction_key\n";
        print "Group $ezproxy_groups\n\n";
      }
      
      foreach ( $this->index_by_restriction[$restriction_key] as $domainList ) {
        $first_entry = $domainList[0];
        
        print "Title ". $first_entry['title'] . " (" . $first_entry['metalib_id'] . ")";
        if ( count($domainList) > 1 ) {
          print " (and others) ";
          print "\n# Complete list of included Metalib IRD IDs: ";
          foreach ($domainList as $domainHash) {
            print $domainHash["metalib_id"] . " ";
          }
        }
        print "\n";
        
        print "URL " . $first_entry['url'] . "\n";
        print "Domain " . $first_entry['domain'] . "\n";
        
        // Any other hosts in this domain group? Find em and unique em. 
        $included_hosts = array( $first_entry['url_host'] );
        for($i = 2; $i < count($domainList) - 1 ; $i++) {
          $new_host = $domainList[$i]['url_host'];
          if ( ! in_array($new_host, $included_hosts)) {
            array_push($included_hosts, $new_host);
            print "Host " . $new_host . "\n"; 
          }
        }
        // Derived hosts. 
        $new_host = $first_entry['domain'];
        if (! in_array( $new_host, $included_hosts )) {
          array_push($included_hosts, $new_host);
          print "Host " . $new_host . "\n";
        }
        $new_host = "www." . $first_entry['domain'];
        if (! in_array($new_host, $included_hosts)) {
          array_push($included_hosts, $new_host);
          print "Host " . $new_host . "\n";         
        }
        
        
        print "\n\n";
      }
    }
    
  }
  
  
  
  // Takes a simple xml object representing a database, in Xerxes. 
  public function makeDbHash($xmlDatabase) {
    $hash = array();
    $hash['title'] = $xmlDatabase->title_display;
    if (! $hash['title'] ) $hash['title'] = 'unknown/missing';
    
    if ( $this->shouldOmitResourceID( $xmlDatabase->metalib_id ) ) {
      throw new Exception("Omitted as per Xerxes ezp_exp_resourceid_omit config: " . $xmlDatabase->metalib_id);
    }
    
    if (! $xmlDatabase->link_native_home ) {
       throw new Exception("Could not include db in ezproxy export, missing title: " . $xmlDatabase->metalib_id);
    }
    
    $parsed_url = parse_url($xmlDatabase->link_native_home);
    
    
    if (! array_key_exists('host', $parsed_url)) {
        throw new Exception("Could not include db in ezproxy export. Malformed url? " . $xmlDatabase->metalib_id . " url: " . $xmlDatabase->link_native_home );
    }
    
    $hash['metalib_id'] = (string) $xmlDatabase->metalib_id;
    $hash['url'] = (string) $xmlDatabase->link_native_home;
    $hash['url_host'] = $parsed_url['host'];
    $hash['domain'] = $this->getDomain($parsed_url);
    
    // Any access restrictions?
    $hash['group_restrictions'] = array();        
    foreach ($xmlDatabase->group_restriction as $restriction) {
      array_push( $hash['group_restrictions'], Xerxes_Framework_Parser::strtoupper((string) $restriction)); 
    }
    if (count($hash['group_restrictions']) == 0) {
      $hash['group_restrictions'] = array('Default');
    }
    
    return $hash;
  }
  
  protected function getDomain($parsed_url) {
    $host = $parsed_url['host'];
    
    
    // If host is numeric, we can't create a domain statement.
    // It's bad to use a numeric host, but oh well. 
    if (preg_match("/^(\d|\.)+$/", $host)) {
      return $host;
    }
    $components = explode("\.", $host);
    if ( count($components) > 2 ) {
      $domain = join('.', array_slice($components, 1));
      
      // Make sure it's not on our configured avoid list
      if (! $this->shouldAvoidDomain($domain)) {
        return $domain;
      }
      elseif (! $this->shouldAvoidDomain($host)) {
        return $host;
      }
      else {
        throw new Exception("Can't include resource because domain is in ezproxy domain avoid list: ". $host );
      }
    }
    elseif (! $this->shouldAvoidDomain($host)) {
      // No domain statement needed, two-element host, like "ebsco.com".  
      return $host;
    }
    else {
      throw new Exception("Can't include resource because domain is in ezproxy domain avoid list: ". $host);
    }
  }
  
  protected function shouldAvoidDomain($domain) {
     $avoidList = $this->objRegistry->getConfig('ezp_exp_domain_avoid', false, '');
      foreach( explode(',', $avoidList) as $avoid) {
        if (trim($avoid) == $domain) {
          # nevermind, can't use that domain, just use the host.
          return true;
        }
      }
      return false;
  }
  
  protected function shouldOmitResourceId($resourceID) {
   $avoidList = $this->objRegistry->getConfig('ezp_exp_resourceid_omit', false, '');
    foreach( explode(',', $avoidList) as $avoid) {
      if (trim($avoid) == $resourceID) {
        # nevermind, can't use that domain, just use the host.
        return true;
      }
    }
    return false;
  }
  

  protected function getEzProxyGroup($metalib_group) {
    if ($metalib_group == "Default") {
      $config = $this->objRegistry->getConfig("ezp_exp_default_group", false);
      if ( $config ) {
        return $config;
      }
      else {
        return "Default";
      }
    }
    else {
      $xml = $this->objRegistry->getGroupXml($metalib_group);
      if ($xml && $xml->ezp_exp_group ) {
        return $xml->ezp_exp_group;
      }
      else {
        return $metalib_group;
      }
    }
  }
}


