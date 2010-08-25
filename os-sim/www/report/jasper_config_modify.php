<?php
    require_once ('classes/Session.inc');
    require_once ('classes/JasperReport.inc');
    Session::logcheck("MenuReports", "ReportsReportServer");
    $POSTReportUnit=POST('reportUnit');
    $POSTAction=POST('action');

    $file_name_param = '_'.$POSTReportUnit.'_file';
    $file_name = $POSTReportUnit.'_files/head.gif';
    if($_FILES[$file_name_param]['size']>0||$POSTAction=='RestoreOriginal'||$POSTAction=='changeColors'){
        require_once ('ossim_conf.inc');
        $client = new JasperClient($conf);
    }
    if($_FILES[$file_name_param]['size']>0){
        if($_FILES[$file_name_param]['type']!='image/gif'){
            echo "<strong>"._("Error: please only .gif")."</strong>\n";
            die();
        }else{
            $file=file_get_contents($_FILES[$file_name_param]['tmp_name']);
            $result = $client->putResource($file_name,$POSTReportUnit,'img',$file);
        }
    }else if($POSTAction=='RestoreOriginal'){
        $file_name_original = '/'.$POSTReportUnit.'_files/bk_head.gif';
        $file_original = $client->getResource($file_name_original,'img');
        $result = $client->putResource($file_name,$POSTReportUnit,'img',$file_original);
        //$result = $client->copyResource($file_name,$POSTReportUnit,'img',$file_name_original);
    }else if($POSTAction=='changeColors'){
        $uriStyle=$POSTReportUnit.'_files/Style.jrtx';
        $POSTBackgroundTitle=POST('backgroundTitle');
        $POSTColorTitle=POST('colorTitle');
        $POSTBackgroundSubtitle=POST('backgroundSubtitle');
        $POSTColorSubtitle=POST('colorSubtitle');
        $POSTColorContent=POST('colorContent');
        $parameters=array(
            'backgroundTitle'=>$POSTBackgroundTitle,
            'colorTitle'=>$POSTColorTitle,
            'backgroundSubtitle'=>$POSTBackgroundSubtitle,
            'colorSubtitle'=>$POSTColorSubtitle,
            'colorContent'=>$POSTColorContent
        );
        $uriParent=$POSTReportUnit;
        $result=$client->setJrtx($uriStyle,$uriParent,$parameters);
    }

    header('Location: jasper_config.php');
?>