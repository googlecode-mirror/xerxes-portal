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
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/folder">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">
	
	<xsl:variable name="back" select="request/server/http_referer" />
	
	<xsl:variable name="context">
		<xsl:choose>
			<xsl:when test="/*/request/context and /*/request/context != ''">
				<xsl:value-of select="/*/request/context" />
			</xsl:when>
			<xsl:otherwise>the saved records page</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<div id="resultsArea">
		<h2>Your labels have been updated</h2>
		<p>Return to <a href="{$back}"><xsl:value-of select="$context" /></a></p>	
	</div>
	
</xsl:template>
</xsl:stylesheet>
