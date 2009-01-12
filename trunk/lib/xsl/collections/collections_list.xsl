<?xml version="1.0" encoding="UTF-8"?>

<!--

 
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
  My Collections
</xsl:template>

<xsl:template name="main">

	
	<div id="folderArea">
    <div id="sidebar_float">
       <xsl:call-template name="account_sidebar"/>
    </div>
  
		
		  <h1><xsl:call-template name="page_name"/></h1>
  
      <!-- personal categories -->
      
      <div>
        <ul>
        <!-- <xsl:if test="count(/*/userCategories/category) = 0">
          include a lazily created one
          <li><a href="./?base=collections&amp;action=edit&amp;username={//request/username}&amp;new_subject_name=My%20Collection">My Collection</a></li>
        </xsl:if> -->
        <xsl:for-each select="/*/userCategories/category">
          <li><a href="{url}"><xsl:value-of select="name"/></a></li>
        </xsl:for-each>
        </ul>
        

        <form method="GET" action="./" class="miniForm">
          <input type="hidden" name="base" value="collections"/>
          <input type="hidden" name="action" value="new"/>
          <input type="hidden" name="username" value="{//request/username}"/>
          
        
        Add a new collection: <input type="text" name="new_subject_name"/><input type="submit" name="add" value="Add"/>
          
        </form>
        
        
      </div>
      
			
	</div>
	
</xsl:template>
</xsl:stylesheet>

