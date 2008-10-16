<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:marc="http://www.loc.gov/MARC21/slim"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:output method="xml" encoding="utf-8"/>

<xsl:template match="category">
	
	<category>
	<category_name><xsl:value-of select="@name" /></category_name>
	
	<xsl:for-each select="//subcategory">
		<xsl:variable name="name" select="@name" />
		<subcategory name="{$name}">
		
		<xsl:for-each select="marc:record">
			<xsl:call-template name="database" />
		</xsl:for-each>
		
		</subcategory>
	</xsl:for-each>
	</category>
	
</xsl:template>

<xsl:template match="collection">
	
	<collection>
		
		<xsl:for-each select="//marc:record">
			<xsl:call-template name="database" />
		</xsl:for-each>
		
	</collection>
	
</xsl:template>

<xsl:template name="database">

	<database>
		
		<metalib_id><xsl:value-of select="marc:controlfield[@tag=001]" /></metalib_id>
		
		<!-- title -->
		<xsl:comment>title</xsl:comment>
		
		<title_full><xsl:value-of select="marc:datafield[@tag=245]/marc:subfield[@code='a']" /></title_full>
		
		<title_display><xsl:value-of select="marc:datafield[@tag=210]/marc:subfield[@code='a']" /></title_display>	
		
		<xsl:for-each select="marc:datafield[@tag=246]">
			<title_alternate><xsl:value-of select="marc:subfield[@code='a']" /></title_alternate>
		</xsl:for-each>
		
		<!-- access -->
		<xsl:comment>access</xsl:comment>
		
		<institute><xsl:value-of select="marc:datafield[@tag='AF1']/marc:subfield[@code='a']" /></institute>
		
		<!-- Sorry, have to go to php to conveniently seperate out the comma-delimited string of assigned groups. 
		splitToNodeset function defined in PopulateDatabases.php. "GUEST" in the group list isn't a restriction,
		but instead an indication that the resource is publically/guest searchable. That's how metalib does it. 
		We translate to something more sane.-->
		
		<xsl:variable name="groups" select="php:functionString('Xerxes_Command_PopulateDatabases::splitToNodeset', marc:datafield[@tag='AF3']/marc:subfield[@code='a'])/item" />
		
		<!-- we're going to need to a case shift. sigh. -->
		<xsl:variable name="uc" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
		<xsl:variable name="lc" select="'abcdefghijklmnopqrstuvwxyz'"/>	
		
		<guest_access>
			<xsl:choose>
				<xsl:when test="count($groups[translate(@value,$lc,$uc) = 'GUEST']) > 0">yes</xsl:when>
				<xsl:otherwise>no</xsl:otherwise>
			</xsl:choose>
		</guest_access>
		
		<group_restrictions>
			<xsl:for-each select="$groups[translate(@value,$lc,$uc) != 'GUEST']">
				<group><xsl:value-of select="@value" /></group>
			</xsl:for-each>
		</group_restrictions>
		
		<filter><xsl:value-of select="marc:datafield[@tag='FTL']/marc:subfield[@code='a']" /></filter>
		
    <!-- TAR$f='n' means 'link to configuration active' has been set to 'no'
         in metalib admin. We consider that not searchable. -->
		<xsl:if test="marc:datafield[@tag='TAR']/marc:subfield[@code='a'] and not (marc:datafield[@tag='TAR']/marc:subfield[@code='f'] = 'N')">
			<searchable>yes</searchable>
		</xsl:if>
    
		<xsl:if test="marc:datafield[@tag=594]/marc:subfield[@code='a'] = 'SUBSCRIPTION'">
			<subscription>yes</subscription>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="marc:datafield[@tag='PXY']/marc:subfield[@code='a'] = 'N'">
				<proxy>no</proxy>
			</xsl:when>
			<xsl:when test="marc:datafield[@tag='PXY']/marc:subfield[@code='a'] = 'Y'">
				<proxy>yes</proxy>
			</xsl:when>
		</xsl:choose>

		
		<active><xsl:value-of select="marc:datafield[@tag='STA']/marc:subfield[@code='a']" /></active>

		<new_resource_expiry><xsl:value-of select="marc:datafield[@tag='NWD']/marc:subfield[@code='a']" /></new_resource_expiry>
		
		<number_sessions><xsl:value-of select="marc:datafield[@tag='SES']/marc:subfield[@code='a']" /></number_sessions>
		
		<sfx_suppress><xsl:value-of select="marc:datafield[@tag='SFX']/marc:subfield[@code='a']" /></sfx_suppress>
		
		<!-- creator -->
		<xsl:comment>creator</xsl:comment>

		<creator><xsl:value-of select="marc:datafield[@tag=260]/marc:subfield[@code='b']" /></creator>

		<publisher><xsl:value-of select="marc:datafield[@tag=110]/marc:subfield[@code='a']" /></publisher>
		
		<xsl:for-each select="marc:datafield[@tag=710]">
			<publisher_alternative><xsl:value-of select="marc:subfield[@code='a']" /></publisher_alternative>
		</xsl:for-each>
		
		<publisher_description><xsl:value-of select="marc:datafield[@tag=545]/marc:subfield[@code='a']" /></publisher_description>
		
		<!-- notes -->
		<xsl:comment>notes</xsl:comment>

		<description><xsl:value-of select="marc:datafield[@tag=520]/marc:subfield[@code='a']" /></description>
						
		<coverage><xsl:value-of select="marc:datafield[@tag=500]/marc:subfield[@code='a']" /></coverage>
		
		<time_span><xsl:value-of select="marc:datafield[@tag=513]/marc:subfield[@code='a']" /></time_span>

		<xsl:for-each select="marc:datafield[@tag=546]">
			<language><xsl:value-of select="marc:subfield[@code='a']" /></language>
		</xsl:for-each>
		
		<copyright><xsl:value-of select="marc:datafield[@tag=540]/marc:subfield[@code='a']" /></copyright>

		<xsl:for-each select="marc:datafield[@tag=590]">
			<note><xsl:value-of select="marc:subfield[@code='a']" /></note>
		</xsl:for-each>

		<note_cataloger><xsl:value-of select="marc:datafield[@tag=591]/marc:subfield[@code='a']" /></note_cataloger>

		<note_fulltext><xsl:value-of select="marc:datafield[@tag=902]/marc:subfield[@code='a']" /></note_fulltext>

		<!-- subjects -->
		<xsl:comment>subjects</xsl:comment>
		
		<type><xsl:value-of select="marc:datafield[@tag=655]/marc:subfield[@code='a']" /></type>
		
		<xsl:for-each select="marc:datafield[@tag=653]">
			<keyword><xsl:value-of select="marc:subfield[@code='a']"/></keyword>
		</xsl:for-each>
		
		<!-- library / librarian -->
		<xsl:comment>library / librarian</xsl:comment>
		
		<library_address><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='a']" /></library_address>
		<library_city><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='b']" /></library_city>
		<library_state><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='c']" /></library_state>
		<library_zipcode><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='e']" /></library_zipcode>
		<library_country><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='d']" /></library_country>
		<library_telephone><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='k']" /></library_telephone>
		<library_fax><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='l']" /></library_fax>
		<library_email><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='m']" /></library_email>
		<library_contact><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='p']" /></library_contact>
		<library_note><xsl:value-of select="marc:datafield[@tag=270]/marc:subfield[@code='z']" /></library_note>
		<library_hours><xsl:value-of select="marc:datafield[@tag=307]/marc:subfield[@code=8]" /></library_hours>
		
		<!-- links -->
		<xsl:comment>links</xsl:comment>
		
		<link_native_home><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=1]/marc:subfield[@code='u']" /></link_native_home>
		
		<link_native_record><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=3]/marc:subfield[@code='u']" /></link_native_record>
		
		<link_native_home_alternative><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=5]/marc:subfield[@code='u']" /></link_native_home_alternative>
		
		<link_native_record_alternative><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=6]/marc:subfield[@code='u']" /></link_native_record_alternative>
		
		<link_native_holdings><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=4]/marc:subfield[@code='u']" /></link_native_holdings>
		
		<link_guide><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=9]/marc:subfield[@code='u']" /></link_guide>
		
		<link_publisher><xsl:value-of select="marc:datafield[@tag=856][@ind1=4][@ind2=2]/marc:subfield[@code='a']" /></link_publisher>
		
		
	</database>
			
</xsl:template>

</xsl:stylesheet>	