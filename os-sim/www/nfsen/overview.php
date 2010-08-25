<?php

function DisplayOverview () {

	global $self;

	$profile      = $_SESSION['profile'];
	$profilegroup = $_SESSION['profilegroup'];

	if ( $profilegroup == '.' ) 
		print "<h2>"._("Overview Profile").": $profile, "._("Group: (nogroup)")."</h2>\n";
	else 
		print "<h2>"._("Overview Profile").": $profile, "._("Group")." $profilegroup</h2>\n";

	if ( $_SESSION['profileinfo']['graphs'] != 'ok' ) {
		print "<h2>"._("No data available!")."</h2>\n";
		return;
	}

    $menutab = "&hmenu=Network&smenu=Network";
	$profileswitch = "$profilegroup/$profile";
	print "<center><a href='$self?tab=2&type=flows$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=flows-day' width='328' height='163' border='0' alt='"._("flows-day")."'></a>\n";
	print "<a href='$self?tab=2&type=packets$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=packets-day' width='328' height='163' border='0' alt='"._("packets-day")."'></a>\n";
	print "<a href='$self?tab=2&type=traffic$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=traffic-day' width='328' height='163' border='0' alt='"._("traffic-day")."'></a>\n";
	print "<br>";
	print "<a href='$self?tab=2&type=flows$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=flows-week' width='328' height='163' border='0' alt='"._("flows-week")."'></a>\n";
	print "<a href='$self?tab=2&type=packets$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=packets-week' width='328' height='163' border='0' alt='"._("packets-week")."'></a>\n";
	print "<a href='$self?tab=2&type=traffic$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=traffic-week' width='328' height='163' border='0' alt='"._("traffic-week")."'></a>\n";
	print "<br>";
	print "<a href='$self?tab=2&type=flows$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=flows-month' width='328' height='163' border='0' alt='"._("flows-month")."'></a>\n";
	print "<a href='$self?tab=2&type=packets$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=packets-month' width='328' height='163' border='0' alt='"._("packets-month")."'></a>\n";
	print "<a href='$self?tab=2&type=traffic$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=traffic-month' width='328' height='163' border='0' alt='"._("traffic-month")."'></a>\n";
	print "<br>";
	print "<a href='$self?tab=2&type=flows$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=flows-year' width='328' height='163' border='0' alt='"._("flows-year")."'></a>\n";
	print "<a href='$self?tab=2&type=packets$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=packets-year' width='328' height='163' border='0' alt='"._("packets-year")."'></a>\n";
	print "<a href='$self?tab=2&type=traffic$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=traffic-year' width='328' height='163' border='0' alt='"._("traffic-year")."'></a>\n";
	print "</center>";    

} // End of DisplayOverview

function DisplayGraphs ($type) {

	global $self;

	$profile      = $_SESSION['profile'];
	$profilegroup = $_SESSION['profilegroup'];

	if ( $profilegroup == '.' ) 
		print "<h2>"._("Profile").": $profile, "._("Group: (nogroup)")." - $type</h2>\n";
	else
		print "<h2>"._("Profile").": $profile, "._("Group").": $profilegroup - $type</h2>\n";

	if ( $_SESSION['profileinfo']['graphs'] != 'ok' ) {
		print "<h2>"._("No data available!")."</h2>\n";
		return;
	}

    $menutab = "&hmenu=Network&smenu=Network";
	$profileswitch = "$profilegroup/$profile";
	print "<center><a href='$self?tab=2&win=day&type=$type$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=${type}-day' width='669' height='281' border='0'></a>\n";
	print "<br><br>";
	print "<a href='$self?tab=2&win=week&type=$type$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=${type}-week' width='669' height='281' border='0'></a>\n";
	print "<br><br>";
	print "<a href='$self?tab=2&win=month&type=$type$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=${type}-month' width='669' height='281' border='0'></a>\n";
	print "<br><br>";
	print "<a href='$self?tab=2&win=year&type=$type$menutab'> <IMG src='pic.php?profileswitch=$profileswitch&amp;file=${type}-year' width='669' height='281' border='0'></a>\n";
	print "<br></center>";

} # End of DisplayHistory

?>
