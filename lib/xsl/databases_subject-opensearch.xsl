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
	<Description>Search across selected <xsl:value-of select="$subject_name"/> databases.</Description>
	<Url type="text/html">
	<xsl:attribute name="template">
		<xsl:value-of select="$base_url" />?base=metasearch;action=search;subject=<xsl:value-of select="$subject_id"/>;field=WRD;query={searchTerms}</xsl:attribute>
	</Url>
	</OpenSearchDescription>
</xsl:template>

</xsl:stylesheet>
