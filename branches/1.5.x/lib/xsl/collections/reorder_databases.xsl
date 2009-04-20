<?xml version="1.0" encoding="UTF-8"?>

<!--

Edit subject page for user-created subjects. Only used for non-AJAX version.
 
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
	Reorder Databases
</xsl:template>

<xsl:template name="sidebar">
	<div id="sidebar">
		<xsl:call-template name="account_sidebar"/>
		<xsl:call-template name="collections_sidebar"/>
	</div>
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="subcategory_id" select="//request/subcategory" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<form name="form1" method="get" action="{$base_url}/">
	<input type="hidden" name="base" value="collections" />
	<input type="hidden" name="action" value="reorder_databases" />
	<input type="hidden" name="subject" value="{//category/@normalized}" />
	<input type="hidden" name="subcategory" value="{$subcategory_id}" />
	<input type="hidden" name="username" value="{//category/@owned_by_user}" />
	<input type="hidden" name="return" value="{//request/return}" />
	
	<h1><xsl:call-template name="page_name" /></h1>

	<table class="reorderTable">
		<xsl:for-each select="//category/subcategory[@id = $subcategory_id]/database">
			<tr>
				<td><xsl:value-of select="position()"/></td>
				<td><input class="reorder" type="text" size="2" name="db_seq_{metalib_id}" /></td>
				<td><xsl:value-of select="title_full" /></td>
			</tr>
		</xsl:for-each>
	</table>

	
	<p><input type="submit" name="save" value="Update"/></p>
	
	</form>

</xsl:template>

</xsl:stylesheet>
