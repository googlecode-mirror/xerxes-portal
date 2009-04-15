<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:output indent="yes" />
<xsl:include href="includes.xsl" />

<xsl:template match="/*">
  <xsl:variable name="subject_name" select="/*/category[1]/@name" />
  <xsl:variable name="subject_id" select="/*/category[1]/@normalized" />
  
  <OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
    <ShortName><xsl:value-of select="$app_name"/>
    <xsl:text> </xsl:text><xsl:value-of select="$subject_name"/> databases</ShortName>
    <Image height="16" width="16"><xsl:value-of select="$app_mini_icon_url" /></Image>
    <Description>Use <xsl:value-of select="$app_name" /> to search across selected <xsl:value-of select="$subject_name"/> databases.</Description>
    <Url type="text/html">
      <xsl:attribute name="template">
        <xsl:value-of select="$base_url" />?base=metasearch&amp;action=search&amp;subject=<xsl:value-of select="$subject_id"/>&amp;field=WRD&amp;query={searchTerms}</xsl:attribute>
    </Url>
 </OpenSearchDescription>
</xsl:template>


</xsl:stylesheet>
