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
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:variable name="empty_collection_instructions">
      <fieldset class="emptyCollectionInstructions">
        <legend>Instructions</legend>
        <p>Add your own list of databases to <strong><xsl:value-of select="/*/category/@name"/></strong> by choosing the <a class="categoryCommand edit">Edit</a> option above, and then choosing the <a class="categoryCommand add">Add databases</a> option.</p>
        <p>You can also save databases by clicking the information link (<img src="{$base_url}/images/info.gif"/>) next to a database you want to save, and choosing the <a class="categoryCommand add">Save database</a> option. </p>
      </fieldset>
</xsl:variable>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
  <xsl:value-of select="/*/category/@name" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="/*/category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />
  <xsl:variable name="user_can_edit" select="/*/category/@owned_by_user = /*/request/session/username" /> 


	
	<div id="container">
    <div id="sidebar_float">
      <xsl:call-template name="account_sidebar"/>
      <xsl:if test="/*/category/@owned_by_user = //session/username">
        <xsl:call-template name="collections_sidebar"/>
      </xsl:if>
		</div>
  
    <form name="form1" method="get" action="{$base_url}/" class="metasearchForm">
    <input type="hidden" name="base" value="metasearch" />
    <input type="hidden" name="action" value="search" />
    <input type="hidden" name="context" value="{$category_name}" />
    <input type="hidden" name="context_url" value="{$request_uri}" />
		<div id="searchArea">
	
			<div class="subject">        
				<h1><xsl:value-of select="/*/category/@name" /></h1>
        <xsl:if test="not(/*/category/@owned_by_user = //session/username)">
          <p>Created by <xsl:value-of select="/*/category/@owned_by_user" /></p>
        </xsl:if>
        
        <xsl:if test="$user_can_edit" >
          <div class="subject_edit_commands">        
            <a class="categoryCommand edit" href="{/*/category/edit_url}">Edit</a>
            <xsl:text> </xsl:text>
            <xsl:choose>
            <xsl:when test="/*/category/@published = '1'">
              <span class="publishedStatus">Published</span>
            </xsl:when>
            <xsl:otherwise>
              <span class="privateStatus">Private</span>
            </xsl:otherwise>
            </xsl:choose>
          </div>
        </xsl:if>
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
			
      <!-- help text, only when the user actually owns this guy, and
           hasn't added any dbs yet. -->
      <xsl:if test="$user_can_edit and count(/*/category/subcategory/database) = 0">
        <xsl:copy-of select="$empty_collection_instructions"/>
      </xsl:if>
      
      <xsl:if test="not($user_can_edit and count(/*/category/subcategory/database) = 0)">
        <div class="subjectDatabases">
          <!-- defined in includes.xsl -->
          <xsl:call-template name="subject_databases_list"/>
        </div>
      </xsl:if>
		</div>
    </form>

	</div>

	
</xsl:template>

</xsl:stylesheet>
