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
    <div id="sidebar_float" class="sidebar_float">
      <xsl:call-template name="account_sidebar"/>
      <xsl:call-template name="collections_sidebar" />
		</div>

  <div class="navReturn">
    <img alt="" src="{$base_url}/images/back.gif"/><span class="navReturnText"><a href="{/*/category/url}">Return to <xsl:value-of select="/*/category/@name"/></a></span>
  </div>
	
  
    <div class="editSubjectHeading">
				<h1>Edit: <xsl:value-of select="//category/@name" /></h1>
        <div class="subject_edit_commands">
        <a class="categoryCommand rename" href="./?base=collections&amp;action=rename_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
        <img src="{$base_url}/images/famfamfam/tag_blue_edit.png" alt="foo"/>Change name</a> 
        <xsl:text> </xsl:text>
        <xsl:if test="count(/*/category[1]/subcategory) &gt; 1">
        <a class="categoryCommand reorder" href="./?base=collections&amp;action=reorder_subcats_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
        <img src="{$base_url}/images/reorder.gif" alt=""/>Change section order</a>
        <xsl:text> </xsl:text>
        </xsl:if>
        
        <xsl:text> </xsl:text>
        <a class="categoryCommand delete deleteCollection" href="./?base=collections&amp;action=delete_category&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
        <img src="{$base_url}/images/delete.gif" alt=""/>Delete collection
        </a>  
        <xsl:text> </xsl:text>
        <xsl:choose>
            <xsl:when test="//category/@published = '1'">
            <span class="publishedStatus"><img src="{$base_url}/images/famfamfam/lock_open.png" alt="" />Published:</span>
            <a class="categoryCommand publishToggle" href="{$base_url}/?base=collections&amp;action=edit&amp;username={//category/@owned_by_user}&amp;subject={//category/@normalized}&amp;published=false&amp;return={php:function('urlencode', string(//server/request_uri))}">
              <span>Make private</span></a>
            </xsl:when>
            <xsl:otherwise>
              <span class="privateStatus"><img src="{$base_url}/images/famfamfam/lock.png" alt="" />Private:</span> <a class="categoryCommand publishToggle" href="{$base_url}/?base=collections&amp;action=edit&amp;username={//category/@owned_by_user}&amp;subject={//category/@normalized}&amp;published=true&amp;return={php:function('urlencode', string(//server/request_uri))}">
              <span>Publish</span> 
              </a>
            </xsl:otherwise>
          </xsl:choose>
          
        </div>
        
        <xsl:if test="/*/category/@published = '1'">
        <p>Public URL: <a href="{/*/category/url}">
          <xsl:choose>
            <xsl:when test="//server/https = 'on'">
            https://
            </xsl:when><xsl:otherwise>http://</xsl:otherwise>
          </xsl:choose>
          <xsl:value-of select="//server/http_host"/><xsl:value-of select="/*/category/url"/>
        </a></p>
        </xsl:if>
        
			</div>

    
		<div id="searchArea" class="editCategory">
	
			<div id="search">
        <xsl:variable name="should_lock_nonsearchable" select=" (/*/request/authorization_info/affiliated = 'true' or /*/request/session/role = 'guest')" />
			</div>
			
			<div class="subjectDatabases">
        <xsl:for-each select="category/subcategory">
          <a name="section_{@id}"/>
          <fieldset class="subjectSubCategory">
            <legend><xsl:value-of select="@name" /></legend>
            <div class="subject_edit_commands">
              <a class="categoryCommand rename" href="./?base=collections&amp;action=rename_form&amp;subject={../@normalized}&amp;subcategory={@id}&amp;username={../@owned_by_user}">
              <img src="{$base_url}/images/famfamfam/tag_blue_edit.png" alt="foo"/>Change name</a>
              <xsl:text> </xsl:text>      
              <a class="categoryCommand add" href="./?base=collections&amp;action=edit_form&amp;username={../@owned_by_user}&amp;subject={../@normalized}&amp;add_to_subcategory={@id}#section_{@id}">
                <img src="{$base_url}/images/famfamfam/add.png" alt=""/>Add databases
              </a>
              <xsl:text> </xsl:text>
              <a class="categoryCommand delete deleteSection" href="./?base=collections&amp;action=delete_subcategory&amp;subject={//category/@normalized}&amp;subcategory={@id}&amp;username={//category/@owned_by_user}">
              <img src="{$base_url}/images/delete.gif" alt=""/>Delete section
              </a>
              
              <xsl:if test="count(database) &gt; 1">
                <xsl:text> </xsl:text>
                <a class="categoryCommand reorder" href="./?base=collections&amp;action=reorder_databases_form&amp;subject={//category/@normalized}&amp;subcategory={@id}&amp;username={//category/@owned_by_user}">
                <img src="{$base_url}/images/reorder.gif" alt=""/>Change database order</a>           
              </xsl:if>
           
              
            </div>
            <xsl:if test="/*/request/add_to_subcategory = @id">
              <xsl:call-template name="addDatabases" />
            </xsl:if>
            
            <table summary="this table lists databases you have included in your personal collection" class="subjectCheckList">
              <xsl:for-each select="database">
                <xsl:variable name="id_meta" select="metalib_id" />
                <tr valign="top">
                  <td>      
                       <a class="removeDatabase" href="./?base=collections&amp;action=remove_db&amp;username={//request/username}&amp;subject={//category[1]/@normalized}&amp;subcategory={../@id}&amp;id={metalib_id}&amp;return={php:function('urlencode', string(//server/request_uri))}#section_{../@id}"><img src="{$base_url}/images/delete.gif" alt="Remove" title="Remove database from section"/></a>                    
                  </td>
                  <td>
                    <div class="subjectDatabaseTitle">
                      <a>
                        <xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
                        <xsl:value-of select="title_display" />
                      </a>
                    </div>
                    <div class="subjectDatabaseInfo">         
                      <a>
                      <xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
                        <img alt="more information" src="images/info.gif" >
                          <xsl:attribute name="src"><xsl:value-of select="//config/base_url" />/images/info.gif</xsl:attribute>
                        </img>
                      </a>
                    </div>
                    <xsl:if test="group_restriction">
                      <div class="subjectDatabaseRestriction"><xsl:call-template name="db_restriction_display" /></div>
                    </xsl:if>
                  </td>
                </tr>
              </xsl:for-each>
              </table>
          </fieldset>        
        </xsl:for-each>            
			</div>
      
      
        <div id="addNewSection">
         <form action="{$base_url}" METHOD="GET">
            <input type="hidden" name="base" value="collections"/>
            <input type="hidden" name="action" value="save_complete"/>
            <input type="hidden" name="username" value="{//request/username}" />
            
            <input type="hidden" name="return" value="{//server/request_uri}" />
            
            <input type="hidden" name="subject" value="{/*/category/@normalized}" />
            
          Add a new section: <input type="text" name="new_subcategory_name" />  
            <input type="submit" name="save" value="add"/>
        </form>
       </div>                                
                
      
    </div>

    
    
  </div>

</xsl:template>

<xsl:template name="addDatabases">
     <div class="addDatabases" id="addDatabases">
       <form method="GET" action="{base_url}#section_{//request/add_to_subcategory}">
         <input type="hidden" name="base" value="collections" />
         <input type="hidden" name="action" value="edit_form" />
         <input type="hidden" name="username" value="{/*/category[1]/@owned_by_user}" />
         <input type="hidden" name="subject" value="{/*/category[1]/@normalized}" />
         <input type="hidden" name="add_to_subcategory" value="{//request/add_to_subcategory}" />
                  
         
         <h3><a name="addDatabases" href="./?base=collections&amp;action=edit_form&amp;username={/*/category[1]/@owned_by_user}&amp;subject={/*/category[1]/@normalized}&amp;id={metalib_id}"><img src="./images/delete.gif" alt="remove search box" title="remove search box"/></a>Add Databases</h3>
         <p>          
          List databases matching: <input type="text" name="query" value="{/*/request/query}"/> <input type="submit" value="GO"/>
         </p>
       </form>
       
       
       
        <ul>
          <xsl:if test="count(/*/databases/database)">
            <xsl:attribute name="class">addDatabasesMatches</xsl:attribute>
          </xsl:if>
          <xsl:if test="/*/request/query and not( /*/databases/database )">
            <li>No databases found matching "<xsl:value-of select="/*/request/query"/>"</li>
          </xsl:if>
          <xsl:for-each select="/*/databases/database">
            <li><a class="addToCollection" href="./?base=collections&amp;action=save_complete&amp;username={/*/category[1]/@owned_by_user}&amp;subject={/*/category[1]/@normalized}&amp;subcategory={/*/request/add_to_subcategory}&amp;id={metalib_id}&amp;return={php:function('urlencode', string(//server/request_uri))}#section_{/*/request/add_to_subcategory}">
            <img src="{$base_url}/images/famfamfam/add.png" alt=""/><xsl:value-of select="title_display"/></a><xsl:text> </xsl:text><a href="{url}"><img class="mini_icon" src="{$base_url}/images/info.gif" alt="more information" title="more information"/></a>
            
            <xsl:if test="searchable = '1'">
              <xsl:text> </xsl:text>
              <img alt="searchable" title="searchable" class="mini_icon" src="{$base_url}/images/famfamfam/magnifier.png"/>
            </xsl:if>
            
            </li>
          </xsl:for-each>
          </ul>
         </div>
</xsl:template>


</xsl:stylesheet>
