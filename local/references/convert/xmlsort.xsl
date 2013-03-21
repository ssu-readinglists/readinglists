<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output omit-xml-declaration="no" standalone="no" indent="no" encoding="utf-8"></xsl:output>
    <xsl:param name="sortby" select="''"/>
    <xsl:param name="sorttype" select="'text'"/>
    <!-- When sorting convert all uppercase to lower as xslt was putting lowercase titles after upper, not in order -->
    <xsl:variable name="smallcase" select="'abcdefghijklmnopqrstuvwxyz'" />
    <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

    <!-- standard copy template -->
    <xsl:template match="references">
        <xsl:copy>
            <xsl:apply-templates select="reference">
                <xsl:sort select="translate(*[local-name()=string($sortby)],$uppercase,$smallcase)" data-type="{$sorttype}"/>
            </xsl:apply-templates>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="reference">
        <xsl:copy-of select=".">
        </xsl:copy-of>
    </xsl:template>
</xsl:stylesheet>