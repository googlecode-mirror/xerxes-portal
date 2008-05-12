<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<form name="form1" method="get" action="{$base_url}/" onSubmit="return databaseLimit(this)">
	<input type="hidden" name="base" value="metasearch" />
	<input type="hidden" name="action" value="search" />
	<input type="hidden" name="context" value="{$category_name}" />
	<input type="hidden" name="context_url" value="{$request_uri}" />
	
	<div id="container">
		<div id="searchArea">
	
			<div class="subject">
				<h1><xsl:value-of select="//category/@name" /></h1>
			</div>
				
			<div id="search">
        <!-- defined in includes.xsl -->
				<xsl:call-template name="search_box" />      
			</div>
			
			<div class="subjectDatabases">
        <!-- defined in includes.xsl -->
				<xsl:call-template name="subject_databases_list"/>
			</div>
		</div>
		<div id="sidebar">
			
		</div>
	</div>

	</form>
	
</xsl:template>



</xsl:stylesheet>
