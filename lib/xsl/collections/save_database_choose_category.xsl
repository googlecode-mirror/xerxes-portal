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



<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">Save to personal collection</xsl:template>

<xsl:template name="main">

<div id="container">
  <div id="sidebar_float" class="sidebar_float">
    <xsl:call-template name="account_sidebar"/>
  </div>

  <div id="searchArea">
  
    <xsl:for-each select="//database[1]">
    
    <xsl:variable name="id" select="metalib_id" />
    <!-- username in request, unless they JUST logged in, then take it from
       session -->
  
    <xsl:variable name="username"><xsl:choose><xsl:when test="string(//request/username)"><xsl:value-of select="//request/username" /></xsl:when><xsl:otherwise><xsl:value-of select="//session/username"/></xsl:otherwise></xsl:choose></xsl:variable>
    <xsl:variable name="return" select="//request/return" />
              
    <h2><xsl:value-of select="title_display" />: Save to personal collection</h2>
    
    <form method="GET" id="save_database" action="{$base_url}">
      <input type="hidden" name="base" value="collections"/>
      <input type="hidden" id="action_input" name="action" value="save_choose_subheading"/>
      <input type="hidden" name="id" value="{$id}" />
      <input type="hidden" name="username" value="{$username}" />
      <input type="hidden" name="return" value="{$return}" />
      <h3>1. Choose a collection</h3>
      <p>
        Use one of your existing collections: 
        <select id="subject" name="subject">
          <!-- if no existing ones, use our default name -->
          <xsl:if test="count(/*/userCategories/category) = 0">
            <option id="new_collection" value="NEW"><xsl:copy-of select="$text_collection_default_new_name"/></option>
          </xsl:if>
          <xsl:for-each select="/*/userCategories/category">
            <option value="{normalized}"><xsl:value-of select="name"/></option>
          </xsl:for-each>
        </select>
      </p>
      <p>
        Or create new one: <input type="text" id="new_subject_name" name="new_subject_name"></input>
      </p>
      
      <!-- hidden div that will be shown and loaded by javascript -->
      <div id="section_choice" style="display: none">
        <h3>2. Choose a section</h3>
        <p>
          Use an existing section: 
          <select id="subcategory" name="subcategory">
          </select>
        </p>
        <p>
          Or create new one: <input type="text" id="new_subcategory_name" name="new_subcategory_name"></input>
        </p>
      </div>
  
      <p>      
        <input type="submit" name="save" value="save"/>
      </p>
    </form>
    </xsl:for-each>
  </div>
</div>
</xsl:template>
</xsl:stylesheet>
