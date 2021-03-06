<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: Jonathan Rochkind
 copyright: 2009 Johns Hopkins University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
-->
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
  <xsl:include href="includes.xsl" />  
  
  <xsl:output method="html" />
  
  <xsl:template match="/*">


		  {
	
		<xsl:choose>
			<xsl:when test="results/delete = '1'">
				  "deleted": true,
          "inserted": false
			</xsl:when>
			<xsl:otherwise>
          "deleted": false,
          "inserted": true,
			    "savedRecordID": <xsl:value-of select="results/savedRecordID" />	
			</xsl:otherwise>
		</xsl:choose>
      }
    		
  </xsl:template>
 </xsl:stylesheet> 
