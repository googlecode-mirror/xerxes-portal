<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/*">
	
	<xsl:call-template name="tags_display" />
	
</xsl:template>
</xsl:stylesheet>
