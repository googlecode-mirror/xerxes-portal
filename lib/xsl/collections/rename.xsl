<?xml version="1.0" encoding="UTF-8"?>

<!--

Edit subject page for user-created subjects.
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
	Rename
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar"/>
	<xsl:call-template name="collections_sidebar"/>
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<xsl:variable name="old_name">
		<xsl:choose>
			<xsl:when test="string(//request/subcategory)">
				<xsl:value-of select="//category/subcategory[@id = //request/subcategory]/@name" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="//category/@name"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<form name="form1" method="get" action="{$base_url}/">
	<input type="hidden" name="base" value="collections" />
	<input type="hidden" name="action" value="edit" />
	<input type="hidden" name="subject" value="{//category/@normalized}" />
	<input type="hidden" name="username" value="{//category/@owned_by_user}" />
	<input type="hidden" name="subcategory" value="{//request/subcategory}" />
	<input type="hidden" name="return" value="{//request/return}" />


	<h1><xsl:call-template name="page_name" /> 
	<xsl:choose>
		<xsl:when test="string(//request/subcategory)">Section</xsl:when>
		<xsl:otherwise>Collection</xsl:otherwise>
	</xsl:choose>
	</h1>
	
	<p>Name: <input type="text" name="new_name" value="{$old_name}"/>
	<xsl:text> </xsl:text><input type="submit" name="save" value="save"/></p>
	
	</form>
	
</xsl:template>

</xsl:stylesheet>
