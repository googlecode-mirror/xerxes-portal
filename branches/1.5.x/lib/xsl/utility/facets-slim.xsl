<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:marc="http://www.loc.gov/MARC21/slim"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" encoding="utf-8"/>

<xsl:template match="/x_server_response/cluster_facet_response">
	<x_server_response>
		<cluster_facet_response>
		<xsl:for-each select="cluster_facet">
			<cluster_facet>
				<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
				<xsl:attribute name="position"><xsl:value-of select="position()" /></xsl:attribute>
				
				<no_of_nodes><xsl:value-of select="no_of_nodes" /></no_of_nodes>
				
				<xsl:for-each select="node">
					<node>
						<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
						<xsl:attribute name="node_level"><xsl:value-of select="@node_level" /></xsl:attribute>
						<xsl:attribute name="position"><xsl:value-of select="position()" /></xsl:attribute>
						<node_no_of_docs>
							<xsl:value-of select="node_no_of_docs" />
						</node_no_of_docs>
						<url>
							<!-- 
								@todo use request->url_for() to set this here rather than leaving it to the xslt
								
								base=metasearch action=facet group={$group} resultSet={$this_result_set}
								facet={$facet_number} node={$node_pos} return={$facet_return} -->
						</url>

					</node>
				</xsl:for-each>
		
			</cluster_facet>
		
		</xsl:for-each>
		</cluster_facet_response>
	</x_server_response>
</xsl:template>

</xsl:stylesheet>	