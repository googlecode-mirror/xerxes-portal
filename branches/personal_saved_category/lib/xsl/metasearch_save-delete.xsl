<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/metasearch">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">
	
	<xsl:variable name="back" select="request/server/http_referer" />
	
	<div id="resultsArea">
		<xsl:choose>
			<xsl:when test="results/delete = '1'">
				<h2>Record successfully removed from saved records</h2>
			</xsl:when>
			<xsl:otherwise>
				<h2>Record successfully added to saved records</h2>
			</xsl:otherwise>
		</xsl:choose>
		
		<p>Return to <a href="{$back}">results page</a></p>	
	</div>
	
</xsl:template>
</xsl:stylesheet>
