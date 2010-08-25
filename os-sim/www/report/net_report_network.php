<table height="100%">
	<tr><td class="headerpr" height="20"><?=gettext("Network Traffic")?></td></tr>
	<tr>
		<td id="graph1"><img src="../pixmaps/ntop_graph_thumb_gray.gif"></td>
	</tr>
	<tr><td><img src="/ntop/graph.gif"> <a href="<?=$ntop_link?>/plugins/rrdPlugin?action=list&key=interfaces/eth0&title=interface%20eth0" target="main">Traffic Detail</a></td></tr>
	<tr><td style="text-align:right;padding-right:20px"><a style="color:black" href="<?=$ntop_link?>/index.php?opc=services&sensor=<?=$sensor_ntop["host"]?>" target="main"><b><?=_("More")?> >></b></a></td></tr>
</table>