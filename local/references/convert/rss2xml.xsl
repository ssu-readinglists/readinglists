<?xml version="1.0" encoding="UTF-8"?>
<!--
 Transforms a refworks rss feed to custom ref data xml format (mirrors refworks xml data structure)

 Copyright 2009 The Open University
 j.platts@open.ac.uk
 GNU Public License
-->
<xsl:stylesheet version="1.0" xmlns:rss="http://purl.org/rss/1.0/" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/" xmlns:dc="http://purl.org/dc/elements/1.1/"  xmlns:refworks="www.refworks.com/xml/">
    <xsl:output omit-xml-declaration="no" standalone="no" indent="no" encoding="utf-8"></xsl:output>
    <xsl:param name="sortby" select="''"/>
    <xsl:param name="sorttype" select="'text'"/>
    <!-- When sorting convert all uppercase to lower as xslt was putting lowercase titles after upper, not in order -->
    <xsl:variable name="smallcase" select="'abcdefghijklmnopqrstuvwxyz'" />
    <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

    <xsl:template match="rdf:RDF">
        <references>
            <xsl:apply-templates select="rss:item">
                <xsl:sort select="translate(*[local-name()=string($sortby)],$uppercase,$smallcase)" data-type="{$sorttype}"/>
            </xsl:apply-templates>
        </references>
    </xsl:template>

    <xsl:template match="rss:item">
        <reference id="{refworks:id}">
            <xsl:apply-templates></xsl:apply-templates>
        </reference>
    </xsl:template>

    <xsl:template match="refworks:rwtype"><rt><xsl:value-of select="."/></rt></xsl:template>

    <xsl:template match="rss:title"><t1><xsl:value-of select="."/></t1></xsl:template>

    <xsl:template match="refworks:sr"><sr><xsl:value-of select="."/></sr></xsl:template>

    <xsl:template match="refworks:id"><id><xsl:value-of select="."/></id></xsl:template>

    <xsl:template match="rss:description"><ab><xsl:value-of select="."/></ab></xsl:template>

    <xsl:template match="dc:creator"><a1><xsl:value-of select="."/></a1></xsl:template>

    <xsl:template match="refworks:a2"><a2><xsl:value-of select="."/></a2></xsl:template>

    <xsl:template match="refworks:YR"><yr><xsl:value-of select="."/></yr></xsl:template>

    <xsl:template match="refworks:FD"><fd><xsl:value-of select="."/></fd></xsl:template>

    <xsl:template match="refworks:no"><no><xsl:value-of select="."/></no></xsl:template>

    <xsl:template match="refworks:cn"><cn><xsl:value-of select="."/></cn></xsl:template>

    <xsl:template match="refworks:k1"><k1><xsl:value-of select="."/></k1></xsl:template>

    <xsl:template match="prism:startingPage"><sp><xsl:value-of select="."/></sp></xsl:template>

    <xsl:template match="prism:endingPage"><op><xsl:value-of select="."/></op></xsl:template>

    <xsl:template match="prism:publicationName"><jf><xsl:value-of select="."/></jf></xsl:template>

    <xsl:template match="refworks:jo"><jo><xsl:value-of select="."/></jo></xsl:template>

    <xsl:template match="prism:volume"><vo><xsl:value-of select="."/></vo></xsl:template>

    <xsl:template match="refworks:ed"><ed><xsl:value-of select="."/></ed></xsl:template>

    <xsl:template match="prism:number"><is><xsl:value-of select="."/></is></xsl:template>

    <xsl:template match="refworks:t2"><t2><xsl:value-of select="."/></t2></xsl:template>

    <xsl:template match="refworks:pp"><pp><xsl:value-of select="."/></pp></xsl:template>

    <xsl:template match="dc:publisher"><pb><xsl:value-of select="."/></pb></xsl:template>

    <xsl:template match="refworks:u1"><u1><xsl:value-of select="."/></u1></xsl:template>
    <xsl:template match="refworks:u2"><u2><xsl:value-of select="."/></u2></xsl:template>
    <xsl:template match="refworks:u3"><u3><xsl:value-of select="."/></u3></xsl:template>
    <xsl:template match="refworks:u4"><u4><xsl:value-of select="."/></u4></xsl:template>
    <xsl:template match="refworks:u5"><u5><xsl:value-of select="."/></u5></xsl:template>

    <xsl:template match="refworks:sn"><sn><xsl:value-of select="."/></sn></xsl:template>

    <xsl:template match="refworks:ad"><ad><xsl:value-of select="."/></ad></xsl:template>

    <xsl:template match="refworks:lk"><lk><xsl:value-of select="."/></lk></xsl:template>
    <xsl:template match="refworks:ul"><ul><xsl:value-of select="."/></ul></xsl:template>

    <xsl:template match="refworks:do"><do><xsl:value-of select="."/></do></xsl:template>

    <xsl:template match="refworks:an"><an><xsl:value-of select="."/></an></xsl:template>

    <xsl:template match="refworks:cr"><cr><xsl:value-of select="."/></cr></xsl:template>

    <xsl:template match="refworks:db"><db><xsl:value-of select="."/></db></xsl:template>

    <xsl:template match="refworks:ds"><ds><xsl:value-of select="."/></ds></xsl:template>

    <xsl:template match="refworks:rd"><rd><xsl:value-of select="."/></rd></xsl:template>

    <xsl:template match="refworks:la"><la><xsl:value-of select="."/></la></xsl:template>

    <xsl:template match="refworks:sf"><sf><xsl:value-of select="."/></sf></xsl:template>

    <xsl:template match="refworks:ol"><ol><xsl:value-of select="."/></ol></xsl:template>

    <xsl:template match="refworks:a4"><a4><xsl:value-of select="."/></a4></xsl:template>

    <xsl:template match="refworks:av"><av><xsl:value-of select="."/></av></xsl:template>

    <xsl:template match="refworks:cl"><cl><xsl:value-of select="."/></cl></xsl:template>

    <xsl:template match="refworks:ot"><ot><xsl:value-of select="."/></ot></xsl:template>

    <xsl:template match="refworks:ip"><ip><xsl:value-of select="."/></ip></xsl:template>

    <xsl:template match="refworks:sl"><sl><xsl:value-of select="."/></sl></xsl:template>

    <xsl:template match="refworks:wt"><wt><xsl:value-of select="."/></wt></xsl:template>

    <xsl:template match="refworks:wv"><wv><xsl:value-of select="."/></wv></xsl:template>

    <xsl:template match="refworks:wp"><wp><xsl:value-of select="."/></wp></xsl:template>

    <xsl:template match="refworks:a6"><a6><xsl:value-of select="."/></a6></xsl:template>

    <xsl:template match="refworks:ll"><ll><xsl:value-of select="."/></ll></xsl:template>

    <!-- elements that are not translated -->
    <xsl:template match="refworks:created"></xsl:template>
    <xsl:template match="refworks:modified"></xsl:template>
    <xsl:template match="@*"></xsl:template>
    <xsl:template match="rss:link"></xsl:template>

</xsl:stylesheet>