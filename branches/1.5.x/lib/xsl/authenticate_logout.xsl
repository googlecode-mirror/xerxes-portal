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
	xmlns:php="http://php.net/xsl">
<xsl:import href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/authenticate">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
	Logout
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="return"		select="request/return" />
	
	<form name="form1" method="post" action="./?base=authenticate">
	<input name="action" type="hidden" value="logout" />
	<input name="return" type="hidden" value="{$return}" />
	<input name="postback" type="hidden" value="true" />
	
	<h1><xsl:call-template name="page_name" /></h1>
	<p>Are you sure you want to end your session? </p>
	<p><input type="submit" name="Submit" value="Log out" /></p>
	</form>
	
</xsl:template>
</xsl:stylesheet>