<?php
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/
if ($submit == "TCP") {
    $cs->criteria['layer4']->Set("TCP");
}
if ($submit == "UDP") {
    $cs->criteria['layer4']->Set("UDP");
}
if ($submit == "ICMP") {
    $cs->criteria['layer4']->Set("ICMP");
}
if ($submit == _NOLAYER4) {
    $cs->criteria['layer4']->Set("");
}
if ($submit == _ADDTIME && $cs->criteria['time']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['time']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDADDRESS && $cs->criteria['ip_addr']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['ip_addr']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDIPFIELD && $cs->criteria['ip_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['ip_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDTCPPORT && $cs->criteria['tcp_port']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['tcp_port']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDTCPFIELD && $cs->criteria['tcp_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['tcp_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDUDPPORT && $cs->criteria['udp_port']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['udp_port']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDUDPFIELD && $cs->criteria['udp_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['udp_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDICMPFIELD && $cs->criteria['icmp_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['icmp_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == _ADDPAYLOAD && $cs->criteria['data']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['data']->AddFormItem($submit, $cs->criteria['layer4']->Get());
echo '

<!-- ************ Meta Criteria ******************** -->
<TABLE WIDTH="100%" BORDER=0 cellspacing=5>
  <TR>
      <TD WIDTH="100%" CLASS="header"><B>' . _QCMETACRIT . '</B></TD>
      </TR>
</TABLE>';
?>
<script>
	function addtocombo (myselect,text,value) {
		var elOptNew = document.createElement('option');
		elOptNew.text = text
		elOptNew.value = value
		try {
			myselect.add(elOptNew, null);
		} catch(ex) {
			myselect.add(elOptNew);
		}
	}
	function deleteall (myselect) {
		var len = myselect.options.length
		for (var i=len-1; i>=0; i--) myselect.remove(i)
	}
	function changesensor(filter) {
		combo = document.getElementById('sensor')
		deleteall(combo);
		for (var i=0; i<num_sensors; i++) {
			if (sensortext[i].match(filter)) {
				addtocombo (combo,sensortext[i],sensorvalue[i])
			}
		}
	}
</script>
<?php
echo '<TABLE WIDTH="100%" cellspacing=5 class="query">
  <TR>
      <TD COLSPAN=2>
           <B>' . _SENSOR . ': </B>';
$cs->criteria['sensor']->PrintForm();
echo '<input type="text" name="filter" size="15"> <input type="button" class="button" value="Apply filter" onclick="changesensor(this.form.filter.value)">'; // filter
echo '</TD></tr><tr><TD COLSPAN=2>';
echo '<B>' . _ALERTGROUP . ': </B>';
$cs->criteria['ag']->PrintForm();
echo '</td></tr>';
//echo '<TR>
//            <TD><B>' . _SIGNATURE . ': </B></TD>
//           <TD>';
//$cs->criteria['sig']->PrintForm();
//if ($db->baseGetDBversion() >= 103) {
//    echo '<B>' . _CHRTCLASS . ': </B>';
//    $cs->criteria['sig_class']->PrintForm();
//    echo '<B>' . _PRIORITY . ': </B>';
//    $cs->criteria['sig_priority']->PrintForm();
//}
//echo '</TD></TR>';
echo '<TR>
      <TD><B>' . _ALERTTIME . ':</B></TD>
      <TD>';
$cs->criteria['time']->PrintForm();
echo '<TR>
      <TD><B>Priority:</B></TD>
      <TD>';
echo '<B>Risk: </B>';
$cs->criteria['ossim_risk_a']->PrintForm();
echo '<B>Priority: </B>';
$cs->criteria['ossim_priority']->PrintForm();
echo '<B>Type: </B>';
$cs->criteria['ossim_type']->PrintForm();
echo '<BR><B>Asset: </B>';
$cs->criteria['ossim_asset_dst']->PrintForm();
echo '<B>Reliability: </B>';
$cs->criteria['ossim_reliability']->PrintForm();
echo '
</TABLE>
<ul id="zMenu">';
echo '
<p>    </p>
<li> <a href="#">' . _QCIPCRIT . '</a>
<ul>      
<!-- ************ IP Criteria ******************** -->
<P>

<TABLE WIDTH="90%" BORDER=0 class="query">';
echo '<TR><TD VALIGN=TOP><B>' . _ADDRESS . ':</B>';
echo '    <TD>';
$cs->criteria['ip_addr']->PrintForm();
echo '<TR><TD><B>' . _MISC . ':</B>';
echo '    <TD>';
$cs->criteria['ip_field']->PrintForm();
echo '
   <TR><TD><B>Layer-4:</B>
       <TD>';
$cs->criteria['layer4']->PrintForm();
echo '
   </TABLE>
      </ul>
<p>  </p>
</li>';
if ($cs->criteria['layer4']->Get() == "TCP") {
    echo '
    <p></p>
<li> <a href="#">' . _QCTCPCRIT . '</a>
      <ul>
<!-- ************ TCP Criteria ******************** -->
<P>


<TABLE WIDTH="90%" BORDER=0 class="query">';
    echo '<TR><TD><B>' . _PORT . ':</B>';
    echo '    <TD>';
    $cs->criteria['tcp_port']->PrintForm();
    echo '
  <TR>
      <TD VALIGN=TOP><B>' . _FLAGS . ':</B>';
    $cs->criteria['tcp_flags']->PrintForm();
    echo '<TR><TD><B>' . _MISC . ':</B>';
    echo '    <TD>';
    $cs->criteria['tcp_field']->PrintForm();
    echo '
</TABLE>         
</ul>
<p>  </p>
</li>';
}
if ($cs->criteria['layer4']->Get() == "UDP") {
    echo '
      <p></p>
<li> <a href="#">' . _QCUDPCRIT . '</a>
      <ul>
<!-- ************ UDP Criteria ******************** -->
<P>

<TABLE WIDTH="100%" BORDER=0 class="query">';
    echo '<TR><TD><B>' . _PORT . ':</B>';
    echo '    <TD>';
    $cs->criteria['udp_port']->PrintForm();
    echo '<TR><TD><B>' . _MISC . ':</B>';
    echo '    <TD>';
    $cs->criteria['udp_field']->PrintForm();
    echo '
</TABLE>
</ul>
<p>
  </p>
</li>';
}
if ($cs->criteria['layer4']->Get() == "ICMP") {
    echo '
        <p></p>
<li> <a href="#">' . _QCICMPCRIT . '</a>
      <ul>
<!-- ************ ICMP Criteria ******************** -->
<P>

<TABLE WIDTH="100%" BORDER=0 class="query">';
    echo '<TR><TD><B>' . _MISC . ':</B>';
    echo '    <TD>';
    $cs->criteria['icmp_field']->PrintForm();
    echo '
</TABLE>
</ul>
<p>  </p>
</li>';
}
echo '
      <p></p>
<li> <a href="#">' . _QCPAYCRIT . '</a>
<ul>
<!-- ************ Payload Criteria ******************** -->
<P>
<TABLE WIDTH="90%" BORDER=0>
  <TR>
      <TD WIDTH="40%" CLASS="payload"><B>' . _QCPAYCRIT . '</B></TD>
      <TD></TD></TR>
</TABLE>

<TABLE WIDTH="90%" BORDER=0 class="query">
  <TR>
      <TD>';
$cs->criteria['data']->PrintForm();
echo '
</TABLE>
</ul>
<p>  </p>
</li></ul>';
echo '<ul><INPUT TYPE="hidden" NAME="new" VALUE="1">';
//            <INPUT TYPE="radio" NAME="sort_order" 
//                   VALUE="sig" ' . chk_check($sort_order, "sig") . '> ' . _QFRMSIG . ' |
echo '<P>
        <CENTER>
        <TABLE BORDER=0>
        <TR><TD>
            <FONT>
            <B>' . _QFRMSORTORDER . ':</B>
            <INPUT TYPE="radio" NAME="sort_order" 
                   VALUE="none" ' . chk_check($sort_order, "none") . '> ' . _QFRMSORTNONE . ' |
            <INPUT TYPE="radio" NAME="sort_order" 
                   VALUE="time_a" ' . chk_check($sort_order, "time_a") . '> ' . _QFRMTIMEA . ' |
            <INPUT TYPE="radio" NAME="sort_order" 
                   VALUE="time_d" ' . chk_check($sort_order, "time_d") . '> ' . _QFRMTIMED . ' |
            <INPUT TYPE="radio" NAME="sort_order" 
                   VALUE="sip_a" ' . chk_check($sort_order, "sip_a") . '> ' . _QFRMSIP . ' |
            <INPUT TYPE="radio" NAME="sort_order" 
                   VALUE="dip_a" ' . chk_check($sort_order, "dip_a") . '> ' . _QFRMDIP . '
            <BR>
            <CENTER><INPUT TYPE="submit" class="button" NAME="submit" VALUE="' . _QUERYDB . '"></CENTER>
             </FONT>
             </TD>
        </TR>
        </TABLE>
		</CENTER>
		</ul>
		<hr>';
echo '
 <!-- ************ JavaScript for Hiding Details ******************** -->
 <script type="text/javascript">
// <![CDATA[
function loopElements(el,level){
	for(var i=0;i<el.childNodes.length;i++){
		//just want LI nodes:
		if(el.childNodes[i] && el.childNodes[i]["tagName"] && el.childNodes[i].tagName.toLowerCase() == "li"){
			//give LI node a className
			el.childNodes[i].className = "zMenu"+level
			//Look for the A and if it has child elements (another UL tag)
			childs = el.childNodes[i].childNodes
			for(var j=0;j<childs.length;j++){
				temp = childs[j]
				if(temp && temp["tagName"]){
					if(temp.tagName.toLowerCase() == "a"){
						//found the A tag - set class
						temp.className = "zMenu"+level
						//adding click event
						temp.onclick=showHide;
					}else if(temp.tagName.toLowerCase() == "ul"){
						//Hide sublevels

';
if ($show_expanded_query == 1) echo ' 
						temp.style.display = ""  ';
else echo ' 
						temp.style.display = "none"  ';
echo '
						//Set class
						temp.className= "zMenu"+level
						//Recursive - calling self with new found element - go all the way through 
						loopElements(temp,level +1) 
					}
				}
			}	
		}
	}
}

var menu = document.getElementById("zMenu") //get menu div
menu.className="zMenu"+0 //Set class to top level
loopElements(menu,0) //function call

function showHide(){
	//from the LI tag check for UL tags:
	el = this.parentNode
	//Loop for UL tags:
	for(var i=0;i<el.childNodes.length;i++){
		temp = el.childNodes[i]
		if(temp && temp["tagName"] && temp.tagName.toLowerCase() == "ul"){
			//Check status:
			if(temp.style.display=="none"){
				temp.style.display = ""
			}else{
				temp.style.display = "none"	
			}
		}
	}
	return false
}
// ]]>
</script>
';
?>


