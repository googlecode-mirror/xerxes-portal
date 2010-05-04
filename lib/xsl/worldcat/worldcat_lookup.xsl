<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: $Id$
 package: Worldcat
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

<xsl:import href="../includes.xsl" />
<xsl:import href="worldcat.xsl" />

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">

	<xsl:call-template name="holdings_lookup">
		<xsl:with-param name="isbn"><xsl:value-of select="request/isbn" /></xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="request/oclc" /></xsl:with-param>	
		<xsl:with-param name="type"><xsl:value-of select="request/display" /></xsl:with-param>
	</xsl:call-template>
		
</xsl:template>

</xsl:stylesheet>
