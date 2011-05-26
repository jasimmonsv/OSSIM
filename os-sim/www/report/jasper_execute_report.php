<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/JasperReport.inc');
Session::useractive();

$GETReportUnit=GET('report_unit');
if( !isset($GETReportUnit) )
{
    $reportOrd[$report['name']] .= '
        <table width="100%"><tr>
            <td>
        <div class="desactiveExport">
            <table width="50%" class="noborder" style="margin:0 0 0 27px">
                <tr>
                    <td><img src="../pixmaps/pdf.gif" align="absline"></td>
                    <td><img src="../pixmaps/doc.gif" align="absline"></td>
                </tr>
                <tr>
                    <td><strong>pdf</strong></td>
                    <td><strong>rtf</strong></td>
                </tr>
                <tr>
                    <td><img src="../pixmaps/email.gif"></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td><strong>email</strong></td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </div>
            </td>
        </tr></table>
    ';

}
else
{

    $GETConsolidateData=GET('consolidate_data');
    
	if( !isset($GETConsolidateData) )
	{
        JasperClient::getPermission($GETReportUnit);
        $updateSql = new UpdateSql($GETReportUnit);
        $result=$updateSql->update();
		?>
		<script type='text/javascript'>
		//<![CDATA[
			$(document).ready(function(){
				var id="<?php echo $GETReportUnit; ?>";
				var idDiv="#ajax_"+id;
				$(idDiv).load('jasper_execute_report.php?report_unit='+id+'&consolidate_data=1');
			});
		//]]>
		</script>
		<?php 
	}
	else
	{ 
		?>
    <table width="100%">
    <tr>
        <td>
       <span><?=_('Complete')?> <img src="../pixmaps/tick.png"></span>
        </td>
    </tr>
    <tr>
        <td>
        <div class="activeExport">
            <table width="50%" class="noborder" style="margin:0 0 0 27px">
                <tr>
                    <td><a href="javascript:;" onclick="exportReport('<?php echo $GETReportUnit; ?>','pdf')"><img src="../pixmaps/pdf.gif"></a></td>
                    <td><a href="javascript:;" onclick="exportReport('<?php echo $GETReportUnit; ?>','rtf')"><img src="../pixmaps/doc.gif"></a></td>
                </tr>
                <tr>
                    <td><strong>pdf</strong></td>
                    <td><strong>rtf</strong></td>
                </tr>
                <tr>
                    <td><a href="javascript:;" onclick="emailValidate('#_<?php echo $GETReportUnit; ?>_email','<?php echo $GETReportUnit; ?>');"><img src="../pixmaps/email.gif"></a></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td><strong>email</strong></td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </div>
        </td>
    </tr>
    </table>
<?php
    }
}
?>