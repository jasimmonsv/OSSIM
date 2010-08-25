<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" version="4.0" encoding="ISO-8859-1" indent="yes" 
	omit-xml-declaration="yes" media-type="text/html" />

<xsl:param name="css_stylesheet" select="directives.css" />

<xsl:template match="/">
	<html>
		<head>
			<title> Directives Editor </title>
			<!-- <meta http-equiv="Pragma" content="no-cache" /> -->
			<meta http-equiv="Content-Language" content="en" />
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<link rel="stylesheet" href="../style/style.css" />
			<link rel="stylesheet" href="../style/{$css_stylesheet}" />

		<script language="JavaScript1.5" type="text/javascript">
		<xsl:comment>
		function Menus(Objet)
		{
			VarDIV=document.getElementById(Objet);
			if(VarDIV.className=="menucache") {
				VarDIV.className="menuaffiche";
			} else {
				VarDIV.className="menucache";
			}
		}
		//</xsl:comment>
		</script>
		</head>

                <body>
			<h1 align="center">Directives list</h1>
			<xsl:apply-templates select="directives"/>
		</body>
	</html>
</xsl:template>


<xsl:template match="directives">
	<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
		onclick="Menus('gen')">
		<font style="font-size: 10pt;">Generic ossim</font>
	</a>
	<div id="gen" class="menuaffiche">
		<table> 
			<tr><th>Id</th><th>Name</th></tr>
			<xsl:for-each select="directive">
				<xsl:sort data-type="number" select="@id" order="ascending" />
				<xsl:if test="@id &lt; 3000">
					<tr>
						<td style="text-align: left"><xsl:value-of select="@id"/></td>
						<td style="text-align: left">
							<xsl:element name="a">
								<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
								<xsl:attribute name="target">directives</xsl:attribute>
								<xsl:value-of select="@name"/>
							</xsl:element>
						</td>
					</tr>
				</xsl:if>
			</xsl:for-each>
		</table>
	</div>
	<br />

	<xsl:if test="directive[@id >= 3000 and @id &lt; 6000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('attackcor')">
			<font style="font-size: 10pt;">Attack correlation</font>
		</a>
		<div id="attackcor" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 3000 and @id &lt; 6000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 6000 and @id &lt; 9000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('virus')">
			<font style="font-size: 10pt;">Virus</font>
		</a>
		<div id="virus" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 6000 and @id &lt; 9000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 9000 and @id &lt; 12000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('wattackcor')">
			<font style="font-size: 10pt;">Web attack correlation</font>
		</a>
		<div id="wattackcor" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 9000 and @id &lt; 12000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 12000 and @id &lt; 15000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('dos')">
			<font style="font-size: 10pt;">Denial of service</font>
		</a>
		<div id="dos" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 12000 and @id &lt; 15000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 15000 and @id &lt; 18000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('portscan')">
			<font style="font-size: 10pt;">Portscan</font>
		</a>
		<div id="portscan" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 15000 and @id &lt; 18000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 18000 and @id &lt; 21000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('anomalies')">
			<font style="font-size: 10pt;">Behaviour anomalies</font>
		</a>
		<div id="anomalies" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 18000 and @id &lt; 21000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 21000 and @id &lt; 24000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('abuse')">
			<font style="font-size: 10pt;">Network abuse and error</font>
		</a>
		<div id="abuse" class="menucache">
			<table>
				<tr><th>Id</th><th class="directive">Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 21000 and @id &lt; 24000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 24000 and @id &lt; 27000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('trojans')">
			<font style="font-size: 10pt;">Trojans</font>
		</a>
		<div id="trojans" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 24000 and @id &lt; 27000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
								<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id >= 27000 and @id &lt; 35000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('misc')">
			<font style="font-size: 10pt;">Miscellaneous</font>
		</a>
		<div id="misc" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 27000 and @id &lt; 500000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
									<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>

	<xsl:if test="directive[@id>=500000]">
		<a style="cursor:hand;" TITLE="To view or hide this type of directives click here." 
			onclick="Menus('user')">
			<font style="font-size: 10pt;">User contributed</font>
		</a>
		<div id="user" class="menucache">
			<table>
				<tr><th>Id</th><th>Name</th></tr>
				<xsl:for-each select="directive">
					<xsl:sort data-type="number" select="@id" order="ascending" />
					<xsl:if test="@id >= 500000">
						<tr>
							<td style="text-align: left"><xsl:value-of select="@id"/></td>
							<td style="text-align: left">
								<xsl:element name="a">
									<xsl:attribute name="href">directive.php?level=1&amp;directive=<xsl:value-of select="@id" /></xsl:attribute>
									<xsl:attribute name="target">directives</xsl:attribute>
								<xsl:value-of select="@name"/>
								</xsl:element>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</table>
		</div>
		<br />
	</xsl:if>



</xsl:template>

</xsl:stylesheet>
