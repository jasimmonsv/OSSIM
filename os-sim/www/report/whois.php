<?
require_once('classes/Security.inc');
$host = GET('ip');
ossim_valid($host, OSS_IP_ADDR, 'illegal:' . _("Host"));	
if (ossim_error()) {
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo $title ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<table class="noborder" style="background-color:transparent" width="250">
<tr>
	<td style="text-align:center">
	<a href="javascript:;" onclick="window.open('http://ws.arin.net/cgi-bin/whois.pl?queryinput=<?=$host?>');return false;">ARIN</a> |
	<a href="javascript:;" onclick="window.open('http://www.ripe.net/perl/whois?query=<?=$host?>');return false;">RIPE</a> | 
	<a href="javascript:;" onclick="window.open('http://www.apnic.net/apnic-bin/whois.pl?search=<?=$host?>');return false;">APNIC</a> | 
	<a href="javascript:;" onclick="window.open('http://lacnic.net/cgi-bin/lacnic/whois?lg=EN&amp;qr=<?=$host?>');return false;">LACNIC</a><br>
	<a href="javascript:;" onclick="window.open('http://www.dnsstuff.com/tools/ipall/?ip=<?=$host?>');return false;">DNS</a> | 
	<a href="javascript:;" onclick="window.open('http://www.dnsstuff.com/tools/whois/?ip=<?=$host?>');return false;">Whois</a> | 
	<a href="javascript:;" onclick="window.open('http://www.whois.sc/<?=$host?>');return false;">Extended whois</a> | 
	<a href="javascript:;" onclick="window.open('http://www.dshield.org/ipinfo.php?ip=<?=$host?>&amp;Submit=Submit');return false;">DShield.org IP Info</a> | <br>
	<a href="javascript:;" onclick="window.open('http://www.trustedsource.org/query.php?q=<?=$host?>');return false;">TrustedSource.org IP Info</a> | 
	<a href="javascript:;" onclick="window.open('http://www.spamhaus.org/query/bl?ip=<?=$host?>');return false;">Spamhaus.org IP Info</a> | 
	<a href="javascript:;" onclick="window.open('http://www.spamcop.net/w3m?action=checkblock&amp;ip=<?=$host?>');return false;">Spamcop.net IP Info</a> | <br>
	<a href="javascript:;" onclick="window.open('http://www.senderbase.org/senderbase_queries/detailip?search_string=<?=$host?>');return false;">Senderbase.org IP Info</a> | 
	<a href="javascript:;" onclick="window.open('http://isc.sans.org/ipinfo.html?ip=<?=$host?>');return false;">ISC Source/Subnet Report</a> | 
	<a href="javascript:;" onclick="window.open('http://www.mywot.com/en/scorecard/<?=$host?>');return false;">WOT Security Scorecard</a> | <br>
	<a href="javascript:;" onclick="window.open('http://www.malwareurl.com/ns_listing.php?ip=<?=$host?>');return false;">MalwareURL</a> | 
	<a href="javascript:;" onclick="window.open('http://www.google.com/search?q=<?=$host?>');return false;">Google</a>
	</td>
</tr>
</table>
</body>
</html>
