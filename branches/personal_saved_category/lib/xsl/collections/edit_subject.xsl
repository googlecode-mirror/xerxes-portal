<?xml version="1.0" encoding="UTF-8"?>

<!--

Edit subject page for user-created subjects. Only used for non-AJAX version.
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
  Edit: <xsl:value-of select="//category/@name" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<form name="form1" method="get" action="{$base_url}/" onSubmit="return databaseLimit(this)">
	<input type="hidden" name="base" value="collections" />
	<input type="hidden" name="action" value="edit" />
	
	<div id="container">
		<div id="searchArea">
	
			<div class="subject">
				<h1>Edit: <xsl:value-of select="//category/@name" /></h1>
			</div>
			
      <div class="subject_edit_commands">
        <p><a href="./?base=collections&amp;action=rename_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
        [Change name]</a> 
        
        <a href="./?base=collections&amp;action=reorder_subcats_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}"> 
        [Change section order]</a></p>
      </div>
      
			<div id="search">
        <xsl:variable name="should_lock_nonsearchable" select=" (/*/request/authorization_info/affiliated = 'true' or /*/request/session/role = 'guest')" />
			</div>
			
			<div class="subjectDatabases">
        <!-- defined in includes.xsl -->
				<xsl:call-template name="subject_databases_list">
          <xsl:with-param name="edit_mode" select="true()"/>
        </xsl:call-template>
			</div>
		</div>
		<div id="sidebar">
			
		</div>
	</div>

	</form>
	
</xsl:template>

</xsl:stylesheet>
