<?xml version="1.0"?>

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <div class="phpScenario-results">
    <h1>phpScenario Test Results</h1>
    <xsl:apply-templates />
  </div>
</xsl:template>

<xsl:template name="tokenize_th">
    <xsl:param name="string" />
    <xsl:param name="delimiter" select="':'" />
    <xsl:choose>
        <xsl:when test="$delimiter and contains($string, $delimiter)">
            <th class="t-name">
                <xsl:value-of select="substring-before($string, $delimiter)" />
            </th>
            <xsl:call-template name="tokenize_th">
                <xsl:with-param name="string" select="substring-after($string, $delimiter)" />
                <xsl:with-param name="delimiter" select="$delimiter" />
            </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
            <th class="t-name"><xsl:value-of select="$string" /></th>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="experiment">
    <xsl:variable name="multivar" select="not(not(@multivar))" />
    <div class="experiment" id="experiment-{translate(normalize-space(@name),' ','-')}">
        <h2>Experiment &quot;<xsl:value-of select="@name" />&quot;</h2>
        <ul class="overall-stats">
            <li class="stat-tResults"><span><xsl:value-of select="@total" /></span> overall results</li>
            <li class="stat-tConversions"><span><xsl:value-of select="@converted" /></span> overall conversions</li>
            <li class="stat-tConversinRate"><span><xsl:value-of select="format-number(number(@conversionrate),'#0.##%')" /></span> overall conversion rate</li>
        </ul>
        <table class="experiment-results">
            <caption>Experiment &quot;<xsl:value-of select="@name" />&quot; Results</caption>
            <thead>
                <tr class="exp-header">
                    <xsl:if test="$multivar">
                        <xsl:call-template name="tokenize_th">
                            <xsl:with-param name="string" select="@multivar" />
                        </xsl:call-template>
                    </xsl:if>
                    <xsl:if test="not($multivar)">
                        <th class="t-name">Treatment</th> 
                    </xsl:if>
                    <th class="percent_tested">Pct.Tested</th>
                    <th class="tested">Tested</th>
                    <th class="completed">Completed</th>
					<th class="converted">Conv. Rate</th>
					<th class="within">Within</th>
					<th class="stderror">Std.Error</th>
                    <th class="zscore">Z-Score</th>
                </tr>
            </thead>
            <xsl:for-each select="treatment">
                <tr class="treatment-results">
                    <xsl:if test="$multivar">
                        <xsl:call-template name="tokenize_th">
                            <xsl:with-param name="string" select="@name" />
                        </xsl:call-template>
                    </xsl:if>
                    <xsl:if test="not($multivar)">
                        <th class="t-name"><xsl:value-of select="@name" /></th> 
                    </xsl:if>
                    <td class="percent_tested"><xsl:value-of select="format-number(number(@percenttested),'#0.##%')" /></td>
                    <td class="tested"><xsl:value-of select="@total" /></td>
                    <td class="completed"><xsl:value-of select="@converted" /></td>
										<td class="converted"><xsl:value-of select="format-number(number(@conversionrate),'##0.##%')" /></td>
										<td class="within"><xsl:value-of select="format-number(number(@highconfidence) div 2.0, '##0.##%')" /></td>
										<td class="stderror"><xsl:value-of select="format-number(number(@stderror), '##0.##%')" /></td>
                    <td class="zscore">
                        <xsl:choose>
                            <xsl:when test="not(@control='control')">
                                <xsl:value-of select="format-number(number(@zscore),'##0.#####')" />
                            </xsl:when>
                            <xsl:otherwise>(control)</xsl:otherwise>
                        </xsl:choose>
                    </td>
                </tr>
            </xsl:for-each>
        </table>
    </div>
</xsl:template>

</xsl:stylesheet>