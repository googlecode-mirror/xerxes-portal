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

	
	<div id="container">
		<div id="searchArea">
	
			<div class="subject">
				<h1>Edit: <xsl:value-of select="//category/@name" /></h1>
        <p>
        <xsl:choose>
          <xsl:when test="//category/@published = '1'">
            published <a class="categoryCommand" href="{$base_url}/?base=collections&amp;action=edit&amp;username={//category/@owned_by_user}&amp;subject={//category/@normalized}&amp;published=false&amp;return={php:function('urlencode', string(//server/request_uri))}">Make private</a>
          </xsl:when>
          <xsl:otherwise>
            private <a class="categoryCommand" href="{$base_url}/?base=collections&amp;action=edit&amp;username={//category/@owned_by_user}&amp;subject={//category/@normalized}&amp;published=true&amp;return={php:function('urlencode', string(//server/request_uri))}">Publish</a>
          </xsl:otherwise>
          </xsl:choose>
        </p>
			</div>
			
      <div class="subject_edit_commands">
        <p>
        

        <a class="categoryCommand edit" href="./?base=collections&amp;action=rename_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">Change name</a> 

        <xsl:if test="count(/*/category[1]/subcategory) &gt; 1">
        <a class="categoryCommand reorder" href="./?base=collections&amp;action=reorder_subcats_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">Change section order</a>
        </xsl:if>
        
        <a class="categoryCommand delete" href="./?base=collections&amp;action=delete_category&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">Delete collection
        </a>
        
        </p>                
      </div>
      
			<div id="search">
        <xsl:variable name="should_lock_nonsearchable" select=" (/*/request/authorization_info/affiliated = 'true' or /*/request/session/role = 'guest')" />
			</div>
			
			<div class="subjectDatabases">
        <!-- defined in includes.xsl -->
				<xsl:call-template name="subject_databases_list">
          <xsl:with-param name="edit_mode" select="true()"/>
          <xsl:with-param name="should_show_checkboxes" select="false()" />
        </xsl:call-template>
			</div>
      
      <div>
        <div id="addNewSection">
         <form action="{$base_url}" METHOD="GET">
            <input type="hidden" name="base" value="collections"/>
            <input type="hidden" name="action" value="save_complete"/>
            <input type="hidden" name="username" value="{//request/username}" />
            
            <input type="hidden" name="return" value="{//server/request_uri}" />
            
            <input type="hidden" name="subject" value="{//category/@normalized}" />
            
          Add a new section: <input type="text" name="new_subcategory_name" />  
            <input type="submit" name="save" value="add"/>
        </form></div>
      </div>
    </div>
    
		<div id="sidebar">
     <xsl:if test="/*/request/add_to_subcategory">
     <div class="addDatabaseSidebar">
       <form method="GET" action="{base_url}">
         <input type="hidden" name="base" value="collections" />
         <input type="hidden" name="action" value="edit_form" />
         <input type="hidden" name="username" value="{/*/category[1]/@owned_by_user}" />
         <input type="hidden" name="subject" value="{/*/category[1]/@normalized}" />
         <input type="hidden" name="add_to_subcategory" value="{//request/add_to_subcategory}" />
         
         <h2>Add Databases to Collection</h2>
          <p>Add to section: <xsl:value-of select="/*/category/subcategory[@id = /*/request/add_to_subcategory]/@name"/>          
          </p>  
         <p>List databases matching: <input type="text" name="query" value="{/*/request/query}"/> <input type="submit" value="GO"/>
         </p>
       </form>
       
       
       
         <ul>
          <xsl:if test="/*/request/query and not( /*/databases/database )">
            <li>No databases found matching "<xsl:value-of select="/*/request/query"/>"</li>
          </xsl:if>
          <xsl:for-each select="/*/databases/database">
            <li><a href="./?base=collections&amp;action=save_complete&amp;username={/*/category[1]/@owned_by_user}&amp;subject={/*/category[1]/@normalized}&amp;subcategory={/*/request/add_to_subcategory}&amp;id={metalib_id}&amp;return={php:function('urlencode', string(//server/request_uri))}">
            <xsl:value-of select="title_display"/></a></li>
          </xsl:for-each>
          </ul>
         </div>
       </xsl:if>
		</div>
	</div>
	
</xsl:template>

</xsl:stylesheet>
