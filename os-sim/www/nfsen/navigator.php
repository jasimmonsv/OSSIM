<?php
function navigator () {

	global $self;
	global $TabList;
	global $GraphTabs;

	$numtabs = count($TabList);

	$plugins  = GetPlugins ();
	$profiles = GetProfiles();
	$profileswitch = $_SESSION['profileswitch'];

	switch ( $_SESSION['profileinfo']['type'] & 3 ) {
		case 0:
			$profile_type = 'live';
			break;
		case 1:
			$profile_type = 'history';
			break;
		case 2:
			$profile_type = 'continuous';
			break;
		default:
			$type = 'unknown';
	}
	$profile_type .= ($_SESSION['profileinfo']['type'] & 4) > 0  ? '&nbsp;/&nbsp;shadow' : '';

    global $conf;
    echo "<link rel='stylesheet' type='text/css' href='../style/style.css'/>";
    include("../hmenu.php");
?>
    <div style="position:absolute;right:95px;top:11px;vertical-align:bottom;width:450px;">
        <table border=0 align="right" style="margin:0px;padding:0px;background-color:transparent;border:0px none">
        <tr><td align="right" class="white" nowrap style="background-color:transparent;border:0px none">
            <a class="white<?=($_SESSION['tab']==2) ? "n" : ""?>" href="index.php?tab=2&hmenu=Network&smenu=Network"><?=_("Details")?></a> |
			<a class="white<?=($_SESSION['tab']==0) ? "n" : ""?>" href="index.php?tab=0&hmenu=Network&smenu=Network"><?=_("Overview")?></a> |
            <a class="white<?=($_SESSION['tab']==1) ? "n" : ""?>" href="index.php?tab=1&hmenu=Network&smenu=Network"><?=_("Graphs")?></a>
            
        </td>
        <td align="right" class="white" nowrap style="padding-left:40px;padding-right:20px;background-color:transparent;border:0px none">
            <!--<?php echo $profile_type;?>--> <?=_("Profile")?>:
                <a class="select_pullup" id="profilemenu" href="javascript:void(0);" 
                onclick="openSelect(this);" onMouseover="openSelect(this);" 
                onMouseout="selectMouseOut();"></a> | 
            <a class="white<?=($_SESSION['tab']==3) ? "n" : ""?>" href="index.php?tab=3&hmenu=Network&smenu=Network"><?=_("Alerts")?></a> |
            <a class="white<?=($_SESSION['tab']==4) ? "n" : ""?>" href="index.php?tab=4&hmenu=Network&smenu=Network"><?=_("Stats")?></a> |
            <a class="white<?=($_SESSION['tab']==5) ? "n" : ""?>" href="index.php?tab=5&hmenu=Network&smenu=Network"><?=_("Plugins")?></a> |
            <a class="white" href="../sensor/sensor.php?hmenu=SIEM+Components&smenu=SIEM+Components"><?=_("Sensors")?></a>
        </td></tr>
        </table>
    </div>
	<form action="<?php echo $self?>" name='navi' method="POST">
	<input type="hidden" id="profilemenu_field" name="profileswitch" value="<?php echo $profileswitch;?>"> 
	<div class="shadetabs" style="display:none"><br>
	<table border='0' cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<ul>
<?php
			for ( $i = 0; $i <  $numtabs; $i++ ) {
				if ( $i == $_SESSION['tab'] ) {
					print "<li class='selected'><a href='$self?tab=$i'>" . $TabList[$i] . "</a></li>\n";
				} else {
					print "<li><a href='$self?tab=$i'>" . $TabList[$i] . "</a></li>\n";
				}
			}
?>
			</ul>
		</td>
		<td class="navigator">
<?php echo $profile_type;?>
		</td>
		<td class="navigator">
<?php 		print "<a href='$self?bookmark=" . $_SESSION['bookmark'] . "'>"._("Bookmark URL")."</a>\n"; ?>
		</td>
		<td class="navigator"><?=_("Profile")?>:</td>
		<td>
			<!-- <a class="select_pullup" id="profilemenu" href="javascript:void(0);" 
				onclick="openSelect(this);" onMouseover="selectMouseOver();" 
				onMouseout="selectMouseOut();"></a> -->
		</td>
	</tr>
	</table>
 	</div>

<?php 
	$_tab = $_SESSION['tab'];
	if ( $TabList[$_tab] == 'Graphs' ) {
		$_sub_tab = $_SESSION['sub_tab'];
?>
		<div class="shadetabs">
		<table border='0' cellpadding="0" cellspacing="0" class="noborder" align="center">
		<tr>
			<td class="noborder" style="padding-bottom:5px;text-align:center">
<?php
                for ( $i = 0; $i <  count($GraphTabs); $i++ ) {
                    if ($i>0) echo "&nbsp;";
                    if ( $i == $_sub_tab ) {
                        print "<b>[ <a href='$self?sub_tab=$i'>" . $GraphTabs[$i] . "</a> ]</b>\n";
                    } else {
                        print "[ <a href='$self?sub_tab=$i'>" . $GraphTabs[$i] . "</a> ]\n";
                    }
                }
?>
			</td>
		</tr>
		</table>
		</div>
<?php

	}
	if ( $TabList[$_tab] == 'Plugins' ) {
		if ( count($plugins) == 0 ) {
?>
			<div class="shadetabs"><br>
				<h3 style='margin-left: 10px;margin-bottom: 2px;margin-top: 2px;'><?=_("No plugins available!")?></h3>
			</div>
<?php
		} else {
?>
		<div class="shadetabs"><br>
		<table border='0' cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<ul>
<?php
					for ( $i = 0; $i <  count($plugins); $i++ ) {
						if ( $i == $_SESSION['sub_tab'] ) {
							print "<li class='selected'><a href='$self?sub_tab=$i'>" . $plugins[$i] . "</a></li>\n";
						} else {
							print "<li><a href='$self?sub_tab=$i'>" . $plugins[$i] . "</a></li>\n";
						}
					}
?>
				</ul>
			</td>
		</tr>
		</table>
		</div>
<?php
		}
	}
	print "</form>\n";
	print "<script language='Javascript' type='text/javascript'>\n";
	print "selectMenus['profilemenu'] = 0;\n";

	$i = 0;
	$savegroup = '';
	$groupid = 0;
    foreach ( $profiles as $profileswitch ) {
		if ( preg_match("/^(.+)\/(.+)/", $profileswitch, $matches) ) {
			$profilegroup = $matches[1];
			$profilename  = $matches[2];
			if ( $profilegroup == '.' ) {
				print "selectOptions[selectOptions.length] = '0||$profilename||./$profilename'; \n";
			} else {
				if ( $profilegroup != $savegroup ) {
					$savegroup = $profilegroup;
					print "selectOptions[selectOptions.length] = '0||$profilegroup||@@0.$i'; \n";
					$groupid = $i;
					$i++;
				}
				print "selectOptions[selectOptions.length] = '0.$groupid||$profilename||$profilegroup/$profilename'; \n";
			}
		} else {
			print "selectOptions[selectOptions.length] = '0||$profileswitch||$profileswitch'; \n";
		}
		$i++;
    }

	print "selectRelateMenu('profilemenu', function() { document.navi.submit(); });\n";
	// print "selectRelateMenu('profilemenu', false );\n";

	print "</script>\n";
	print "<noscript><h3 class='errstring'>"._("Your browser does not support JavaScript! NfSen will not work properly!")."</h3></noscript>\n";
	$bk = base64_decode(urldecode($_SESSION['bookmark']));

} // End of navigator

