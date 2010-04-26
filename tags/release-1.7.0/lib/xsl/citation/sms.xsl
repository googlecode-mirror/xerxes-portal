<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2010 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" 
	xmlns:holdings="http://www.loc.gov/standards/iso20775/"  
	exclude-result-prefixes="php">
<xsl:include href="utils.xsl" />
<xsl:include href="../includes.xsl" />
<xsl:output method="text" encoding="utf-8"/>

<xsl:template match="/*">
	<xsl:variable name="record" select="//xerxes_record" />
    <xsl:variable name="metalib_db_id" select="$record/metalib_id" />
	<xsl:if test="$record/title_normalized">
		<xsl:value-of select="$record/title_normalized" />
		<xsl:text>&#013;&#010;</xsl:text>
	</xsl:if>
	<xsl:text>Copies :</xsl:text>
	<xsl:for-each select="//holdings:copyInformation">
		Location: <xsl:value-of select="holdings:sublocation" />
		Call Number: <xsl:value-of select="holdings:shelfLocator" />
		Status: <xsl:value-of select="holdings:note" />
		<xsl:text>&#013;&#010;</xsl:text>
	</xsl:for-each>
</xsl:template>

</xsl:stylesheet>