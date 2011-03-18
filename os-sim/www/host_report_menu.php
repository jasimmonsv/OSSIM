<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<link href="../style/jquery.contextMenu.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.contextMenu.js" type="text/javascript"></script>

<? if (!$noready) { ?>
<script type="text/javascript">
	function load_contextmenu() {
		$('.HostReportMenu').contextMenu({
				menu: 'myMenu'
			},
				function(action, el, pos) {
					if (action=='filter') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_qry_main.php?new=2&hmenu=Forensics&smenu=Forensics&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_both&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D=+";
						document.location.href = url;
					} else if (action=='edit') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../host/modifyhostform.php?hmenu=Assets&smenu=Hosts&ip="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='unique') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../forensics/base_stat_alerts.php?clear_allcriteria=1&sort_order=occur_d&hmenu=Forensics&smenu=Forensics&ip_addr_cnt=1&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_both&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D=+";
						top.frames['main'].document.location.href = url;
					} else if (action=='tickets') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets&with_text="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='alarms') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../control_panel/alarm_console.php?hide_closed=1&hmenu=Alarms&smenu=Alarms&src_ip="+ip+"&dst_ip="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='sem') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../sem/index.php?hmenu=SEM&smenu=SEM&query="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='report') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						var hostname = aux[1];
						var url = "../report/host_report.php?host="+ip+"&hostname="+hostname+"&greybox=0";
						if (hostname == ip) var title = "Host Report: "+ip;
						else var title = "Host Report: "+hostname+"("+ip+")";
						//GB_show(title,url,'90%','95%');
						top.frames['main'].document.location.href = url;
						//var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
						wnd.focus()
					} else if (action=='search') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../inventorysearch/userfriendly.php?hmenu=Assets&smenu=AssetSearch&ip="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='vulns') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../vulnmeter/index.php?hmenu=Vulnerabilities&smenu=Vulnerabilitie&sortby=t1.results_sent+DESC%2C+t1.name+DESC&submit=Find&type=hn&value="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='kndb') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../repository/index.php?hmenu=Repository&smenu=Repository&search_bylink="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='ntop') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "/ntop/"+ip+".html";
						var wnd = window.open(url,'htop_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					} else if (action=='flows') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "../nfsen/index.php?tab=2&hmenu=Network&smenu=Traffic&ip="+ip;
						top.frames['main'].document.location.href = url;
					} else if (action=='nagios') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "/secured_nagios3/cgi-bin/status.cgi?host="+ip;
						var wnd = window.open(url,'nagios_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					} else if (action=='whois') {
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];
						url = "http://www.dnsstuff.com/tools/whois/?ip="+ip;
						var wnd = window.open(url,'whois_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					}
				}
			);
		$('.NetReportMenu').contextMenu({
				menu: 'myMenuNet'
			},
				function(action, el, pos) {
					var aux = $(el).attr('id').split(/;/);
					var ip = aux[0];
					var hostname = aux[1];
					var url = "../report/host_report.php?host="+ip+"&hostname="+hostname+"&greybox=0";
					if (hostname == ip) var title = "Host Report: "+ip;
					else var title = "Network Report: "+hostname+"("+ip+")";
					//GB_show(title,url,'90%','95%');
					var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					wnd.focus()
				}
			);
	}
	$(document).ready(function(){
		load_contextmenu();
		if (typeof postload == 'function') postload();
	});
</script>
<? } ?>
<ul id="myMenu" class="contextMenu">
<li class="report"><a href="#report"><?=_("Asset Report")?></a></li>
<li class="assetsearch"><a href="#search"><?=_("Asset Search")?></a></li>
<li class="edit"><a href="#edit"><?=_("Configure Asset")?></a></li>
<li class="whois"><a href="#whois"><?=_("Whois")?></a></li>
<li class="tickets"><a href="#tickets"><?=_("Tickets")?></a></li>
<li class="alarms"><a href="#alarms"><?=_("Alarms")?></a></li>
<?
if (!isset($conf)) {
	require_once ('ossim_conf.inc');
	$conf = $GLOBALS["CONF"];
}
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) ? true : false;
if (!$opensource) { ?>
<li class="sem"><a href="#sem"><?=_("Logger")?></a></li>
<? } ?>
<? if ($ipsearch) { ?>
<li class="search"><a href="#filter"><?=_("Filter by IP")?></a></li>
<li class="search"><a href="#unique"><?=_("Analyze Asset")?></a></li>
<? } else { ?>
<li class="sim"><a href="#filter"><?=_("SIEM Events")?></a></li>
<? } ?>
<li class="vulns"><a href="#vulns"><?=_("Vulnerabilities")?></a></li>
<li class="kndb"><a href="#kndb"><?=_("Knownledge DB")?></a></li>
<li class="ntop"><a href="#ntop"><?=_("Net Profile")?></a></li>
<li class="flows"><a href="#flows"><?=_("Traffic")?></a></li>
<li class="nagios"><a href="#nagios"><?=_("Availability")?></a></li>
</ul>
<ul id="myMenuNet" class="contextMenu">
<li class="report"><a href="#report">Network Report</a></li>
</ul>
