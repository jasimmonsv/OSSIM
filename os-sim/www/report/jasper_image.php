<?php
    require_once ('classes/Session.inc');
    require_once ('classes/JasperReport.inc');
    Session::logcheck("MenuReports", "ReportsReportServer");
    $GETReportUnit=GET('report_unit');
    if(!is_null($GETReportUnit)){
        require_once ('ossim_conf.inc');
        $client = new JasperClient($conf);

        $result = $client->getResource($GETReportUnit,'img');
        header("Content-type: image/gif");
        echo $result;
        
    }else{
        ?>
    <html>
        <head>
            <title>&nbsp;</title>
            <link rel="stylesheet" type="text/css" href="../style/style.css"/>
            <link rel="stylesheet" type="text/css" href="../style/style.css"/>
        </head>
        <body>
            <p><?php echo _("Report no exist"); ?></p>
            <form method="POST" action="#">
                <p><input class="btn center" type="button" value="<?=_('Close')?>" onclick="javascript:window.close();" /></p>
            </form>
         </body>
    </html>
    <?php
    }

?>