<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
 version: 1.5
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:import href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
        <xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
        <xsl:value-of select="//category/@name" />
</xsl:template>

<xsl:template name="breadcrumb">
        <xsl:call-template name="breadcrumb_databases" />
        <xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="sidebar">
        <xsl:call-template name="account_sidebar" />
        <xsl:call-template name="snippet_sidebar" />
</xsl:template>

<xsl:template name="main">

	<h1><xsl:value-of select="//category/@name" /></h1>
	
	<p>[ <a href="databases/metasearch?subject={//request/subject}">Link to metasearch page</a> ]</p>
	
	<div>
		<xsl:for-each select="category/subcategory">
			<fieldset class="subjectSubCategory">
				<legend><xsl:value-of select="@name" /></legend>
		
				<ul>
				<xsl:for-each select="database">
				
					<li style="margin: 10px; margin-left: 20px">		
						<a href="{xerxes_native_link_url}">
							<xsl:value-of select="title_display" />
						</a>
						<xsl:text> </xsl:text>
						<a href="{url}">
							<img src="{$base_url}/images/info.gif" alt="information about this database" border="0" />
						</a>
					</li>							
				
				</xsl:for-each>	
				</ul>
			
			</fieldset>
		
		</xsl:for-each>
	</div>

</xsl:template>

</xsl:stylesheet>
