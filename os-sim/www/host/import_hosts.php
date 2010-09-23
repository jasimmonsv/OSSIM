<?php
require_once ('classes/Session.inc');
require_once ('classes/CIDR.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/RRD_config.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");

//Functions 

function print_form($msg_errors=''){
		
	if (is_array ($msg_errors) && !empty ($msg_errors) )
			echo "<div class='sep'><span class='error'>".implode("<br/>", $msg_errors)."</span><br/></div>";
	
	echo "<form name='form_csv' id='form_csv' method='post' enctype='multipart/form-data'>			
			<table align='center' id='form_container'>
					<tr><td class='headerpr'>"._("Import Hosts from CSV")."</td></tr>
					<tr><td class='nobborder'><div id='file_csv'><input name='file_csv' id='file_csv' type='file' size='35'></div></td></tr>
					<tr>
						<td class='nobborder'>
							<span style='font-weight: bold;'>"._("Format allowed").":</span><br/>
							<div id='format'>"._("IP;hostname;FQDNs(FQDN1,FQDN2,... );Description;Asset;NAT;Sensors(Sensor1,Sensor2,...);Operating System")."</div>
						</td>
					</tr>
					<tr>
						<td class='nobborder'>
							<div style='padding-top: 10px'>
								<span style='font-weight: bold; font-style: normal'>"._("Example").":</span><br/>
								<div id='example'>"._("192.168.10.3*;Host_1;www.example-1_esp.es,www.example-2_esp.es;Short description of host;2;;192.168.10.2,192.168.10.3;Windows**")."</div>
						    </div>
						</td>
					</tr>
					<tr>
						<td class='nobborder'><div style='padding-top: 30px'>(*)&nbsp;&nbsp; "._("Only IP field is mandatory")."</div></td>
					</tr>
					<tr>
						<td class='nobborder'><div style='padding-top: 10px'>(**) "._("Valid Operating System values").": Windows, Linux, FreeBSD, NetBSD, OpenBSD, MacOS, Solaris, Cisco, AIX,HP-UX, Tru64, IRIX, BSD/OS, SunOS, Plan9 or IPhone</div></td>
					</tr>
					<tr>
						<td class='nobborder'><div id='send'><input type='submit' value='"._("Import")."' name='submit'class='button'></div></td>
					</tr>
			</table>
		</form>";
			
}

function print_results($res){
	
	$num_errors = count($res['line_errors']);
	 
	echo "<table align='center' id='result_container'>
		<tr><td class='headerpr' colspan='2'>"._("Import Results")."</td></tr>
		<tr>
			<td class='line nobborder'>
				<span class='label' valign='absmiddle'>"._("Read Assests lines").":</span>
			</td>
			<td class='nobborder result'>".$res['read_line']."</td>
		</tr>
		<tr>
			<td class='line nobborder' valign='absmiddle'>
				<span class='label ok'>"._("Correct Assests lines").":</span>
			</td>
			<td class='nobborder result ok'>".($res['read_line']-$num_errors)."</td>
		</tr>
		<tr>
			<td class='line nobborder' valign='absmiddle'>
				<span class='label error'>"._("Wrong Assests lines").":</span>";
				 
				if ( $num_errors > 0 ){ 
					echo "<a onclick=\"javascript: $('#errors_csv').toggle();\">
						<span>["._("View errors")."]</span>
					</a>";
				} 
			echo "</td>
			<td class='nobborder result error'>".$num_errors."</td>
		</tr>";
		
		if ( $num_errors > 0 ){
			echo "<tr>
				<td class='nobborder' colspan='2' id='errors_csv'>
					<table id='list_errors'>";
						
						foreach ($res['line_errors'] as $k => $v) {
							unset ($txt_errors);
							echo "<tr >
									<td class='nobborder label_error pb10' valign='absmiddle'>"._("Line")." ".$k.":</td>";
									foreach ($v as $j => $error)
										$txt_errors .="<span>"._($error[0]).": </span><span class='error'>"._($error[1])."</span><br/>"; 
									
									echo "<td class='nobborder p5' valign='absmiddle'>".$txt_errors."</td>
								</tr>";
						}
					echo "</table>					
				</td>
			</tr>";
		} 
		echo "</table>";
	
}	

function is_allowed_format ($type_uf){
	$types = '/octet-stream|text|csv|plain|spreadsheet|excel|comma-separated-values/';
	
	if (preg_match ($types, $type_uf, $match) == false)
		return false;
	else
		return true;
}


function import_assets_csv($filename){
	
	require_once('classes/Util.inc');
	$response= array();
	$db = new ossim_db();
	$conn = $db->connect();
		
		
	if (($content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) == false )
	{
		$response ['file_errors'] = "Failed to read file";
		$response ['status'] = false;
		return $response;
	}
	else
	{
		foreach ($content as $k => $v)
			$data[] = explode(";", $v);
		
	}
		
	$cont = 0;
	
	ini_set('max_execution_time', 180);
	ids_valid($data);	
	
	if (count($data) <= 0)
	{
		$response ['file_errors'] = "Incompatible file format";
		$response ['status'] = false;
		return $response;
	}
	
	foreach ($data as $k => $v)
	{
		$response ['status'] = true;
		$response ['read_line'] = $cont;
		$cont++;
		
		
		if (count($v) != 8)
		{
			$response ['line_errors'][$cont][] = array("Line", "Format not allowed");
			$response ['status'] = false;
		}
		
		$param = array();
		
		foreach ($v as $i => $field)
		{
			$parameter = trim($field);
			$pattern = '/^\"|\"$|^\'|\'$/';
			$param[] = preg_replace($pattern, '', $parameter);
		}
		
				
		//IP
		if ( !ossim_valid($param[0], OSS_IP_ADDR, 'illegal:' . _("IP")) )
		{
			$response ['line_errors'][$cont][] = array("IP", ossim_get_error_clean());
			$response ['status'] = false;
		}
				
		
		//Hostname
		if ( empty ($param[1]))
			$param[1] = $param[0];
		else
		{ 
			if ( !ossim_valid($param[1], OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Hostname")) )
			{
				$response ['line_errors'][$cont][] = array("Hostname", ossim_get_error_clean());
				$response ['status'] = false;
				ossim_clean_error();
			}
		}
		
		//FQDNs
		if ( !empty ($param[2]) )
		{
			$fqdns_list = explode(",", $param[2]);
			
			foreach ($fqdns_list as $k => $fqdn)
			{
				if ( !ossim_valid(trim($fqdn), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("FQDN/Aliases")) )
				{
					$response ['line_errors'][$cont][] = array("FQDN/Aliases", ossim_get_error_clean());
					$response ['status'] = false;
					ossim_clean_error();
				}
			}
		}
		
		//Description
		if ( !ossim_valid($param[3], OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("Description")) )
		{
			$response ['line_errors'][$cont][] = array("Description", ossim_get_error_clean());
			$response ['status'] = false;
			ossim_clean_error();
		}
		
		//Asset
		if ( $param[4] == '' )
			$param[4] = 2;
		else
		{
			if ( !ossim_valid($param[4], OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Asset")) )
			{
				$response ['line_errors'][$cont][] = array("Asset", ossim_get_error_clean());
				$response ['status'] = false;
				ossim_clean_error();
			}
		}
		
		//NAT
		if ( !ossim_valid($param[5], OSS_NULLABLE, OSS_IP_ADDR, 'illegal:' . _("NAT")) )
		{
			$response ['line_errors'][$cont][] = array("NAT", ossim_get_error_clean());
			$response ['status'] = false;
			ossim_clean_error();
		}
		
		//Sensors
		if ( !empty ($param[6]) )
		{
			$sensor_name = array();
			$sensor_list = explode(",", $param[6]);
			
			foreach ($sensor_list as $k => $sensor)
			{
				$sensor = trim($sensor);
				if ( !ossim_valid($sensor, OSS_IP_ADDR, 'illegal:' . _("Sensor")) )
				{
					$response ['line_errors'][$cont][] = array("Sensors", ossim_get_error_clean());
					$response ['status'] = false;
					ossim_clean_error();
				}
				else
				{
					if ( Sensor::sensor_exists($conn, $sensor) == false )
					{
						$response ['line_errors'][$cont][] = array("Sensors", "IP $sensor isn't a sensor");
						$response ['status'] = false;
						ossim_clean_error();
					}
					else{
					   $sensor_name[] = Sensor::get_sensor_name($conn, $sensor);
					}
				}
			}
				
		}
		
					
		$list_os = array("Windows","Linux","FreeBSD","NetBSD","OpenBSD","MacOS","Solaris","Cisco","AIX","HP-UX","Tru64","IRIX","BSD/OS","SunOS","Plan9","IPhone");
		
		
		//Operating System
		if ( !empty($param[7]) && !in_array($param[7], $list_os) )
			$param[7] = "Unknown";
		
		if ( $response ['status'] == true )
		{
			//Parameters
			$ip= $param[0];
			$hostname= $param[1];
			$asset= $param[4];
			$threshold_c = 60;
            $threshold_a = 60;
			$rrd_profile="";
			$alert=0;
			$persistence=0;
			$nat=$param[5];
			$sensors=$sensor_name;
			$descr=$param[3];
          	$os = $param[7];
          	$fqdns= $param[2];
			$latitude = ''; 
			$longitude= '';  
			
						
			if (!Host::in_host($conn, $ip)) 
				Host::insert($conn, $ip, $hostname, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $nat, $sensors, $descr, $os, $mac, $mac_vendor, $latitude, $longitude, $fqdns);
			else
				Host::update($conn, $ip, $hostname, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $nat, $sensors, $descr, $os, $mac, $mac_vendor, $latitude, $longitude, $fqdns);
			
		}
		
	}
	
	$response ['read_line'] = $cont;
	return $response;
	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <title>'<?=_("Import Hosts from CSV")?>'</title>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	
	<style type="text/css">
	a {cursor:pointer; font-weight: bold;}
	#container{ width: 90%; text-align: center; margin: 5px auto 0px auto; padding: 10px;}
	#form_container {background:#f2f2f2; width:100%;}
	#form_container td { padding-left: 15px;}
	
	#result_container {background:#f2f2f2; width:80%; margin-top: 30px;}
	#result_container .line { height: 25px; padding-left: 15px; text-align: left; width: 70%;}
	
	#file_csv { padding: 15px 0px 15px 15px; width: 70%; margin: auto; text-align: center;}
	#send{ padding: 20px 0px 20px 0px; text-align: center; margin:auto;}
	#format { padding: 5px 0px 0px 20px; font-style: italic;}
	#example { padding: 5px 0px 0px 20px; font-style: italic;}
	
	.sep {border: 3px dotted rgb(255, 191, 0); margin: auto; margin-bottom: 20px; padding: 5px; text-align: center; background-color: rgb(255, 242, 131);}
	.error { font-weight: bold; color: #C92323;}
	.ok { font-weight: bold; color: #179D53;}
	.label {font-weight: bold; text-align: left; font-size: 13px;}
	.result {font-weight: bold; text-align: right; font-size: 14px; padding-right: 20px;}
	
	#list_errors { font-weight: bold; font-size: 12px; width:95%; margin: auto;}
	#list_errors tr{ padding-bottom: 4px;}
	.label_error {padding-left: 10px; width:20%;}
	#errors_csv { display: none;}
	.p5 { padding: 5px 0px;}
	
	</style>
</head>

<body>
<?php include ("../hmenu.php"); ?>


	<div id='container'>
	
	<?php
	
	$path = "../tmp/";
	$current_user = md5(Session::get_session_user());
	$file_csv = $path.$current_user."_assest_import.csv";
	$msg_errors = '';
			
	if ( isset($_POST['submit']) && !empty($_POST['submit']) )
	{
		
		if ( !empty ($_FILES['file_csv']['name']) )
		{
			if ($_FILES['file_csv']['"error'] > 0 )
			{
				$error = true;
				$msg_errors[]  = _("Unable to upload file. Return Code: ").$_FILES["file_csv"]["error"];
			}
			else
			{
				if ( !is_allowed_format ($_FILES['file_csv']['type']) )
				{
					$error = true;
					$msg_errors[]  = _("File type \"".$_FILES['file_csv']['type']."\" not allowed");
				}
								
				
				if ( @move_uploaded_file($_FILES["file_csv"]["tmp_name"], $file_csv ) == false  && !$error)
				{
					$error = true;
					$msg_errors[] = ( empty ($msg_errors) ) ? _("Unable to upload file") : $msg_errors;
				}
							
			}
			
						
			if ($error == false)
			{
				$res = import_assets_csv ($file_csv); 
				@unlink($file_csv);
			}
			
		}
		else
			$msg_errors[]  = _("Filename is empty");
	
	}
	
	
	if ( isset ($res['status']) && !empty($res['file_errors']) )
		$msg_errors[]  = $res['file_errors'];
	
	
	print_form($msg_errors);
	
		
	if ( isset ($res['status']) && empty($res['file_errors']) )
		print_results($res);
		

	?>
	
	</div>
	
</body>
</html>

