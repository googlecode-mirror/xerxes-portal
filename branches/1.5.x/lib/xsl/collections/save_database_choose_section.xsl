<?xml version="1.0" encoding="UTF-8"?>



<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
	Save to personal collection <xsl:value-of select="/*/category/@name" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar"/>
	<xsl:call-template name="collections_sidebar"/>
</xsl:template>

<xsl:template name="main">
	<xsl:variable name="id" select="/*/databases/database/metalib_id" />
	<xsl:variable name="username" select="//request/username" />
	<xsl:variable name="return" select="//request/return" />
	
	<h1><xsl:call-template name="page_name" /></h1>

	<xsl:for-each select="/*/category">           
	
		<div id="subcategory_choice" class="miniForm">
			<h2>Database: <xsl:value-of select="/*/databases/database/title_display" /></h2>
			
			<h3>Choose a section</h3>
			
			<form method="GET" action="{$base_url}">
			<input type="hidden" name="base" value="collections"/>
			<input type="hidden" name="action" value="save_complete"/>
			<input type="hidden" name="id" value="{$id}" />
			<input type="hidden" name="username" value="{$username}" />
			<input type="hidden" name="subject" value="{@normalized}" />
			<input type="hidden" name="return" value="{$return}" />
			
			<p>
			<select name="subcategory">
				<!-- if no existing ones, use our default name -->
				
				<xsl:if test="count(/*/category/subcategory) = 0">
					<option value="NEW"><xsl:copy-of select="$text_collection_default_new_section_name"/></option>
				</xsl:if>
				
				<xsl:for-each select="/*/category/subcategory">
					<option value="{@id}"><xsl:value-of select="@name"/></option>
				</xsl:for-each>
			</select>
			</p>

			<p><input type="submit" value="save" name="save"/></p>
			</form>
		</div>
	
	</xsl:for-each>

</xsl:template>
</xsl:stylesheet>
