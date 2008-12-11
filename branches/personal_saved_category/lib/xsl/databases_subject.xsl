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
<xsl:import href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
  <xsl:value-of select="//category/@name" />
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
        <xsl:variable name="should_lock_nonsearchable" select=" (/*/request/authorization_info/affiliated = 'true' or /*/request/session/role = 'guest')" />
      
        <!-- do we have any searchable databases? If we have any that are
             searchable by the particular session user, or if we aren't locking
             non-searchable dbs and have any that are searchable at all -->
        <xsl:if test="count(/*/category/subcategory/database/searchable_by_user[. = '1']) &gt; 0 or (not($should_lock_nonsearchable) and   count(/*/category/subcategory/database/searchable[. = '1']) &gt; 0)">
      
          <!-- defined in includes.xsl -->
          <xsl:call-template name="search_box" />
          
       </xsl:if>
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
