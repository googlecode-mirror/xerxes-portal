<?xml version="1.0" encoding="UTF-8"?>

<!--
  Actually used for both the alphabetical list AND the db search results list.

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

<!-- override in local xsl to false if you don't want the little magnifier glass
     icon next to searchable resources -->
<xsl:variable name="show_db_searchable_icon" select="true()" />

<!-- certain operational parameters, given in request. -->
<!-- default to true -->
<xsl:variable name="show_alpha_links" select="not(/knowledge_base/request/show_alpha_links) or /knowledge_base/request/show_alpha_links != 'false'" />
<!-- default to false -->
<xsl:variable name="show_search_box" select="/knowledge_base/config/show_search_box = 'true'" />
      
<xsl:template match="/knowledge_base">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">
	
	<a name="top" />
	
	<div id="databasesAlpha">
	
		<div class="subject">
			<h1><xsl:call-template name="page_name" /></h1>
		</div>
    
    <xsl:if test="$show_search_box">
      <div id="databaseSearchArea">
        <xsl:call-template name="databases_search_box" />
      </div>
    </xsl:if>
    
    <p><strong><xsl:value-of select="count(databases/database)" /> databases
    </strong></p> 
  
    <xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
    <xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
    
    <xsl:if test="$show_alpha_links">  
      <div class="databasesAlphaMenu">
      
      <xsl:for-each select="databases/database">
        
        <xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
        
        <xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
          <a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#<xsl:value-of select="$letter" /></xsl:attribute> 
          <xsl:value-of select="$letter" /></a>
          <span class="letterSeperator"> | </span> 
        </xsl:if>
  
      </xsl:for-each>
      
      </div>
    </xsl:if>
    
    <xsl:for-each select="databases/database">
        
      <xsl:if test="$show_alpha_links" >
        <xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
        
        <xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
          <a name="{$letter}"><h2><xsl:value-of select="$letter" /></h2></a>
          <div class="alphaBack">
            [ <a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#top</xsl:attribute>  Back to top</a> ]
          </div>
        </xsl:if>
      </xsl:if>
      <div class="alphaDatabase">
        <xsl:variable name="link_native_home" select="php:function('urlencode', string(link_native_home))" />
        <xsl:variable name="id_meta" select="metalib_id" />		
            
        <div class="alphaTitle">
          <a>
          <xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
            <xsl:value-of select="title_display" />
          </a>
          <xsl:if test="title_display">
            &#160;<a>
            <xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
            <img alt="more information" src="images/info.gif" class="mini_icon">
              <xsl:attribute name="src"><xsl:value-of select="/knowledge_base/config/base_url" />/images/info.gif</xsl:attribute>
            </img>
            
            <xsl:if test="searchable and $show_db_searchable_icon">
            <xsl:text> </xsl:text>
            <img class="mini_icon" alt="searchable" src="{$base_url}/images/famfamfam/magnifier.png"/>
            </xsl:if>
            
            </a>
            

            
            
            <xsl:if test="count(group_restriction) > 0" >
              <xsl:text> </xsl:text>(<xsl:call-template name="db_restriction_display" />)
            </xsl:if>
          </xsl:if>
        </div>
        
        <div class="alphaDescription">
          <xsl:value-of select="translate(description,'#','')" />
        </div>
      </div>

    </xsl:for-each>
	</div>
</xsl:template>
</xsl:stylesheet>