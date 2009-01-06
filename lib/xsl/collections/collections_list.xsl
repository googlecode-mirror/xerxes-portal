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

<xsl:template name="main">

	
	<div id="folderArea">
		
			<xsl:call-template name="folder_header" />
		
  
      <!-- personal categories -->
      
      <div>
        <h2>Personal Collections</h2>
        <ul>
        <!-- <xsl:if test="count(/*/userCategories/category) = 0">
          include a lazily created one
          <li><a href="./?base=collections&amp;action=edit&amp;username={//request/username}&amp;new_subject_name=My%20Collection">My Collection</a></li>
        </xsl:if> -->
        <xsl:for-each select="/*/userCategories/category">
          <li><a href="{url}"><xsl:value-of select="name"/></a></li>
        </xsl:for-each>
        </ul>
      </div>
      
			
	</div>
	
</xsl:template>
</xsl:stylesheet>

