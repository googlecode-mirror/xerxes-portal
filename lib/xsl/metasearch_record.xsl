<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
 version 1.5
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:import href="includes.xsl" />
<xsl:include href="record.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:choose>
		<xsl:when test="//request/return"> 
			<xsl:call-template name="breadcrumb_metasearch">
				<xsl:with-param name="condition">3</xsl:with-param>
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="breadcrumb_metasearch">
				<xsl:with-param name="condition">2</xsl:with-param>
			</xsl:call-template>
		</xsl:otherwise>
	</xsl:choose>
	<xsl:copy-of select="$text_record_breadcrumb" />
</xsl:template>

<xsl:template name="main">
		
	<xsl:call-template name="record" />
	
</xsl:template>
</xsl:stylesheet>
