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
	<xsl:value-of select="category/@name" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_collection">
		<xsl:with-param name="condition">2</xsl:with-param>
	</xsl:call-template>
	<xsl:value-of select="$text_collections_edit" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar"/>
	<xsl:call-template name="collections_sidebar"/>
	<xsl:call-template name="snippet_sidebar" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<!-- We don't show certain 'advanced' editing functions for the default collection -->
	
	<xsl:variable name="show_advanced_options" select="not(/*/category/@is_default_collection = 'yes')"/>

	<h1><xsl:call-template name="page_name" /></h1>
	
	<div class="editSubject">
		[
		<a href="{/*/category/url}"><xsl:copy-of select="$text_collections_done_editing" /></a>
		]
	</div>
	
	<xsl:if test="/*/category/@published = '1'">
		<p id="collectionsPublicURL"><xsl:copy-of select="$text_collections_public_url" /><xsl:text> </xsl:text> 
			<a href="{/*/category/url}"> 
			<xsl:choose>
				<xsl:when test="//server/https = 'on'">
				https://
				</xsl:when><xsl:otherwise>http://</xsl:otherwise>
			</xsl:choose>
			<xsl:value-of select="//server/http_host"/><xsl:value-of select="/*/category/url"/>
			</a>
		</p>
	</xsl:if>
	
	<ul class="editCommands">
	
		<xsl:if test="$show_advanced_options">
			<li>
			<a class="iconCommand rename" href="./?base=collections&amp;action=rename_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
				<xsl:copy-of select="$text_collections_change_name" /></a>
			</li>
		</xsl:if>
		
		<xsl:if test="$show_advanced_options">
			<li>
				<a class="iconCommand delete" href="./?base=collections&amp;action=delete_category&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
					<xsl:copy-of select="$text_collections_delete_collection" />
				</a>
			</li>
		</xsl:if>
		
		<li>
    <span class="iconCommand public">
			<xsl:copy-of select="$text_collections_publish" /><xsl:text> </xsl:text> 
			<xsl:choose>
				<xsl:when test="//category/@published = '1'">
					<a href="{$base_url}/?base=collections&amp;action=edit&amp;username={//category/@owned_by_user}&amp;subject={//category/@normalized}&amp;published=false&amp;return={php:function('urlencode', string(//server/request_uri))}">
					<xsl:copy-of select="$text_collections_private" /></a> | <strong><xsl:copy-of select="$text_collections_public" /></strong>
				</xsl:when>
				<xsl:otherwise>
					<strong><xsl:copy-of select="$text_collections_private" /></strong> | 
					<a href="{$base_url}/?base=collections&amp;action=edit&amp;username={//category/@owned_by_user}&amp;subject={//category/@normalized}&amp;published=true&amp;return={php:function('urlencode', string(//server/request_uri))}">
						<xsl:copy-of select="$text_collections_public" />
					</a>
					
				</xsl:otherwise>
			</xsl:choose>
      </span>
		</li>

		<xsl:if test="count(/*/category[1]/subcategory) &gt; 1">
			<li>
				<a class="iconCommand reorder" href="./?base=collections&amp;action=reorder_subcats_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">
					<xsl:copy-of select="$text_collections_change_section_order" /></a>
			</li>
		</xsl:if>

		<xsl:if test="$show_advanced_options">
			<li id="addNewSection">
				<form action="{$base_url}" METHOD="GET">
					<input type="hidden" name="base" value="collections"/>
					<input type="hidden" name="action" value="save_complete"/>
					<input type="hidden" name="username" value="{//request/username}" />
					<input type="hidden" name="return" value="{//server/request_uri}" />
					<input type="hidden" name="subject" value="{/*/category/@normalized}" />
						
					<label for="new_subcategory_name">
						<xsl:copy-of select="$text_collections_add_section" /><xsl:text> </xsl:text>
					</label>
					<input type="text" id="new_subcategory_name" name="new_subcategory_name" />	
					<xsl:text> </xsl:text><input type="submit" name="save" value="{$text_header_my_collections_add}"/>
				</form>
			</li>
		</xsl:if>
		
	</ul>

	<div id="searchArea" class="editCategory">
		<div id="search">
			<xsl:variable name="should_lock_nonsearchable" select=" (/*/request/authorization_info/affiliated = 'true' or /*/request/session/role = 'guest')" />
		</div>
		
		<div class="subjectDatabases">
			<xsl:for-each select="category/subcategory">
				<a name="section_{@id}"/>
				<fieldset class="subjectSubCategory">
					<legend><xsl:value-of select="@name" /></legend>
					
          <div class="editCommands">
					<ul>
					
						<xsl:if test="$show_advanced_options">
							<li>
								<a class="iconCommand rename" href="./?base=collections&amp;action=rename_form&amp;subject={../@normalized}&amp;subcategory={@id}&amp;username={../@owned_by_user}">
									<xsl:copy-of select="$text_collections_change_section_name" /></a>
							</li>
						</xsl:if>
						
						<!-- don't let them delete the last remaining section, it's confusing -->
						
						<xsl:if test="$show_advanced_options and (count(/*/category/subcategory) &gt; 1)">
							<li>
								<a class="iconCommand delete" href="./?base=collections&amp;action=delete_subcategory&amp;subject={//category/@normalized}&amp;subcategory={@id}&amp;username={//category/@owned_by_user}">
									<xsl:copy-of select="$text_collections_delete_section" /></a>
							</li>
						</xsl:if>

						<xsl:if test="count(database) &gt; 1">
							<li>
								<a class="iconCommand reorder" href="./?base=collections&amp;action=reorder_databases_form&amp;subject={//category/@normalized}&amp;subcategory={@id}&amp;username={//category/@owned_by_user}">
								<xsl:copy-of select="$text_collections_change_database_order" /></a>			
							</li>		 
						</xsl:if>

						<li>
							<a class="iconCommand add" href="./?base=collections&amp;action=edit_form&amp;username={../@owned_by_user}&amp;subject={../@normalized}&amp;add_to_subcategory={@id}#section_{@id}">
								<xsl:copy-of select="$text_collections_add_database" /></a>
						</li>
						
					</ul>
					
					<xsl:if test="/*/request/add_to_subcategory = @id">
						<xsl:call-template name="addDatabases" />
					</xsl:if>
					</div>
          
					<ul>
						<xsl:for-each select="database">
						<li>
							<xsl:variable name="id_meta" select="metalib_id" />
							<xsl:variable name="remove_text">
								<xsl:call-template name="text_collections_add_database_section" />
							</xsl:variable>

							<a href="./?base=collections&amp;action=remove_db&amp;username={//request/username}&amp;subject={//category[1]/@normalized}&amp;subcategory={../@id}&amp;id={metalib_id}&amp;return={php:function('urlencode', string(//server/request_uri))}#section_{../@id}"><img 
								src="{$base_url}/images/delete.gif" title="{$remove_text}" alt="{$remove_text}"/></a>

							<span class="subjectDatabaseTitle">
								<a>
									<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
									<xsl:value-of select="title_display" />
								</a>
								<xsl:text> </xsl:text>
							</span>
							<span class="subjectDatabaseInfo">
								<a>
								<xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
									<img alt="{$text_databases_az_hint_info}" src="images/info.gif" >
										<xsl:attribute name="src"><xsl:value-of select="//config/base_url" />/images/info.gif</xsl:attribute>
									</img>
								</a>
							</span>
							<xsl:if test="group_restriction">
								<span class="subjectDatabaseRestriction"><xsl:call-template name="db_restriction_display" /></span>
							</xsl:if>
						</li>
						</xsl:for-each>
					</ul>
					
				</fieldset>
			</xsl:for-each>
		</div>
	</div>

</xsl:template>

<xsl:template name="addDatabases">

	<div id="addDatabases">
	
		<form method="GET" action="{base_url}#section_{//request/add_to_subcategory}">
		<input type="hidden" name="base" value="collections" />
		<input type="hidden" name="action" value="edit_form" />
		<input type="hidden" name="username" value="{/*/category[1]/@owned_by_user}" />
		<input type="hidden" name="subject" value="{/*/category[1]/@normalized}" />
		<input type="hidden" name="add_to_subcategory" value="{//request/add_to_subcategory}" />
		
			<p>
				[ <a  href="./?base=collections&amp;action=edit_form&amp;username={/*/category[1]/@owned_by_user}&amp;subject={/*/category[1]/@normalized}&amp;id={metalib_id}#section_{/*/request/add_to_subcategory}">
          <xsl:copy-of select="$text_collections_remove_searchbox" />
				</a> ]
			</p>	
			
			
			<p>
				<label for="collections_database_query">
					<xsl:copy-of select="$text_collections_list_databases" /><xsl:text> </xsl:text>
				</label>
				
				<input type="text" id="collections_database_query" name="query" value="{/*/request/query}"/><xsl:text> </xsl:text>
				<input type="submit" value="{$text_searchbox_search}"/>
			</p>
		
		</form>
		
		<xsl:if test="/*/request/query and not( /*/databases/database )">
			<p><xsl:copy-of select="$text_collections_no_matches" /><xsl:text> "</xsl:text><xsl:value-of select="/*/request/query"/>"</p>
		</xsl:if>
				
		<xsl:if test="count(/*/databases/database)">
			<ul class="addDatabasesMatches">
				<xsl:for-each select="/*/databases/database">
					<xsl:variable name="add_text">
						<xsl:call-template name="text_collections_add_database_section" />
					</xsl:variable>
					<li>
						<a href="./?base=collections&amp;action=save_complete&amp;username={/*/category[1]/@owned_by_user}&amp;subject={/*/category[1]/@normalized}&amp;subcategory={/*/request/add_to_subcategory}&amp;id={metalib_id}&amp;return={php:function('urlencode', string(//server/request_uri))}#section_{/*/request/add_to_subcategory}">
							<img src="{$base_url}/images/famfamfam/add.png" title="{$add_text}" alt="{$add_text}" />
						</a>
					
						<xsl:text> </xsl:text><xsl:value-of select="title_display"/>
										
						<xsl:if test="searchable = '1'">
							<xsl:text> </xsl:text>
							<img alt="searchable" title="searchable" class="miniIcon" src="{$base_url}/images/famfamfam/magnifier.png"/>
						</xsl:if>
						
					</li>
				</xsl:for-each>
			</ul>
		</xsl:if>
	</div>
</xsl:template>


</xsl:stylesheet>
