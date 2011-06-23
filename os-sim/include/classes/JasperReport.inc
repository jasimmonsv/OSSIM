<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class:
* Classes list:
* - Wizard
* - JasperClient
* - UpdateSql
* - FormatText
* - UpdateReports
*/

class Wizard {

}

class JasperClient {
  private $url;
  private $username;
  private $password;
  private $bd;

  public function __construct($conf) {
    $this->url = "http://".$conf->get_conf("bi_host").":8080/jasperserver/services/repository";
    $this->username = $conf->get_conf("bi_user");
    $this->password = $conf->get_conf("bi_pass");
  }

  public function requestReport($report, $format, $params, $operationName) {
    $params_xml = "";

    foreach ($params as $name => $value) {
        if($name=='isListItem'){
          $isListItem=$value;
          continue;
        }
        if(isset($isListItem)){
            if(strstr($name, $isListItem.'_')){
                $params_xml .= "<parameter name=\"$isListItem\" isListItem=\"true\">'$value'</parameter>\n";
            }else{
                unset($isListItem);
                $params_xml .= "<parameter name=\"$name\"><![CDATA[$value]]></parameter>\n";
            }
        }else{
            $params_xml .= "<parameter name=\"$name\"><![CDATA[$value]]></parameter>\n";
        }
    }
    switch($operationName){
          case 'list':
          case 'listSubreport':
              $vOperationName = "list";
              break;
          case 'runReport':
              $vOperationName = $operationName;
              break;
          case 'get':
              $vOperationName = "get";
              break;
          default:
              break;
    }

    $request = "<request operationName=\"$vOperationName\" locale=\"en\">";
    switch($operationName){
          case 'list':
              $request .= "<resourceDescriptor name=\"\" wsType=\"folder\" uriString=\"/\">";
              break;
          case 'listSubreport':
              $request .= "<resourceDescriptor name=\"\" wsType=\"reportUnit\" uriString=\"/$report\">";
              break;
          case 'runReport':
              $request .= "<argument name=\"RUN_OUTPUT_FORMAT\">$format</argument>
            <resourceDescriptor name=\"\" wsType=\"\"
            uriString=\"$report\"
            isNew=\"false\">";
              break;
          case 'get':
              $request .= "<resourceDescriptor name=\"\" wsType=\"\" uriString=\"$report\">";
              break;
          default:
              break;
    }
    $request .= "
        <label>null</label>
        $params_xml
        </resourceDescriptor>
      </request>
    ";

    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));

    $return = array();
    try {
        $result = $client->__soapCall($vOperationName, array(
            new SoapParam($request,"requestXmlString")
        ));
        switch($operationName){
          case 'list':
              $return = $this->parseReponseWithList(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'listSubreport':
              $return = $this->parseReponseWithListSubreport(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'runReport':
              $return = $this->parseReponseWithReportData(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'get':
              $return = $this->parseReponseWithListResource(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          default:
              break;
        }
    } catch(SoapFault $exception) {
      $responseHeaders = $client->__getLastResponseHeaders();
      if ($exception->faultstring == "looks like we got no XML document" &&
         strpos($responseHeaders, "Content-Type: multipart/related;") !== false) {
         $return = $this->parseReponseWithReportData($responseHeaders, $client->__getLastResponse());
      } else {
          echo '<p>'._("Unable to connect to the Jasper Server, please wait a few seconds an try again. If the problem persists, make sure you have theossim-compliance package installled and the Jasper Server is up and running.").'</p>';
         //throw $exception;
      }
    }
    return $return;
    /*
    if ($pdf)
      return $pdf;
    else
      throw new Exception("Jasper did not return PDF data. Instead got: \n$result");*/
  }
  protected function jasperException($xml){

      $domDocument=DOMDocument::loadXML($xml);
      echo '<html>
        <head>
            <title>&nbsp;</title>
            <link rel="stylesheet" type="text/css" href="../style/style.css"/>
        </head>
        <body>
            <p>'._("Report no exist").'</p>
            <form method="POST" action="#">';
      foreach($domDocument->childNodes as $nodos){
          $xml2 = simplexml_load_string($nodos->nodeValue);

          $result=explode("<returnMessage><![CDATA[", $xml2->returnMessage->asXML());
          $result=explode("]]></returnMessage>", $result[1]);

          echo "<p><strong>"._("Error Jasper").":</strong> ".$result[0]."</p>\n";
      }
      echo '
            <p><input class="btn center" type="button" value="Close" onclick="javascript:window.close();parent.GB_hide();" /></p>
            </form>
         </body>
    </html>';
      die();
  }
  protected function parseReponseWithReportData($responseHeaders, $responseBody) {
      /*
       * Parsea el xml y devuelve datos de un report en pdf, rtf o xls
       */
        preg_match('/boundary="(.*?)"/', $responseHeaders, $matches);
        $boundary = $matches[1];
        if($boundary==''){
            $this->jasperException($responseBody);
        }
        $parts = explode($boundary, $responseBody);
        $export = null;
        foreach($parts as $part) {
            //print_r($part);
          if (strpos($part, "Content-Type: application/pdf") !== false) {
            $export = substr($part, strpos($part, '%PDF-'));
            break;
          }elseif (strpos($part, "Content-Type: application/rtf") !== false) {
            $export = substr($part, strpos($part, '{\rtf1'));
            break;
          }elseif (strpos($part, "Content-Type: application/xls") !== false) {
              //$pdf = substr($part, (strpos($part, '<report>')+8));
              //print_r($pdf);
              //die();
              $export = substr($part, (strpos($part, '<report>')+8));
              //$pdf = substr($part, strpos($part, 'Java Excel API v2.6'));

              break;
          }if (strpos($part, "Content-Type: application/octet-stream") !== false) {
            $export = substr($part, strpos($part, 'GIF89a'));
            break;
          }
        }

        return $export;
  }

  protected function parseReponseWithList($responseHeaders, $responseBody) {
      /*
       * Parsea un listado de reportes
       */
      $domDocument=DOMDocument::loadXML($responseBody);
      foreach($domDocument->childNodes as $nodos){
          $xml = simplexml_load_string($nodos->nodeValue);
          $array_temp=new ArrayObject();
          foreach ($xml->xpath('//resourceDescriptor') as $xml2){
              if($xml2['wsType']=='reportUnit'){
                  $array_temp[]=array('uriString'=>(string)$xml2['uriString'],'name'=>(string)$xml2['name'], 'label'=>(string)$xml2->label, 'creationDate'=>(string)$xml2->creationDate);
              }
          }

      }

      return $array_temp;
  }

  protected function parseReponseWithListSubreport($responseHeaders, $responseBody) {
      $domDocument=DOMDocument::loadXML($responseBody);
//echo html_entity_decode($responseBody);
      foreach($domDocument->childNodes as $nodos){
          $xml = simplexml_load_string($nodos->nodeValue);

          $array_temp=new ArrayObject();

          foreach ($xml->xpath('//resourceDescriptor') as $xml2){

              foreach($xml2->xpath('//resourceProperty') as $xml3){
                  if($xml3['name']=='PROP_QUERY'){
                      //echo $xml3->value;
                  }
              }

              if($xml2['wsType']=='jrxml'){
                  $array_temp[]=$this->getJrxml($xml2);
              }
              if($xml2['wsType']=='inputControl'){
                  // obtenemos los inputControl
                  $array_temp[]=$this->getInputControl($xml2);
              }
          }

      }

      return $array_temp;
  }

    protected function parseReponseWithResource($responseHeaders, $responseBody, $wsType) {
        /*
         * Parseamos el xml para obtener un recurso en concreto
         */
       $domDocument=DOMDocument::loadXML($responseBody);

      foreach($domDocument->childNodes as $nodos){
          $xml = simplexml_load_string($nodos->nodeValue);
          $array_temp=new ArrayObject();
          foreach ($xml->xpath('//resourceDescriptor') as $xml2){
              if($xml2['wsType']==$wsType){
                  switch($wsType){
                      case 'jdbc':
                          $array_temp[]=array('name'=>(string)$xml2['name']);
                          break;
                      default:
                          $array_temp='';
                          break;
                  }
              }
         }
      }
      
      return $array_temp;
    }
  /* Métodos definitivos */

  protected function getResourceProperty($xml,$name) {
      /*
       * Obtenemos el contenido del resource property
       */
      $return='';

      foreach ($xml->xpath('resourceProperty') as $xml2){
          if($xml2['name']==$name){
            $return=$xml2->value;
          }
      }

      return $return;
  }

  protected function getJrxml($xml){
    /*
     *
     */

      $array_temp=new ArrayObject(array('wsType'=>(string)$xml['wsType'],'name'=>(string)$xml['name'],'uriString'=>(string)$xml['uriString'], 'label'=>(string)$xml->label, 'creationDate'=>(string)$xml->creationDate));

      return $array_temp;
  }
  
  protected function getInputControl($xml){
    /*
     * ----------------------
     * PROP_INPUTCONTROL_TYPE
     * Valores que puede tomar
     * 1 -> Boolean
     
     * 3 -> Single-select List of Values
     * 8 -> Single-select List of Values (radio)
     * 6 -> Multi-select List of Values
     * 10 -> Multi-select List of Values (check box)
     * 4 -> Single-select Query
     * 9 -> Single-select Query (radio)
     * 7 -> Multi-select Query
     * ------------------------------------
     */
      $array_temp=new ArrayObject(array('wsType'=>(string)$xml['wsType'],'uriString'=>(string)$xml['uriString'],'name'=>(string)$xml['name'],'label'=>(string)$xml->label));

      foreach ($xml->xpath('resourceProperty') as $xml2){
          if($xml2['name']=='PROP_INPUTCONTROL_IS_MANDATORY'){
              $array_temp->offsetset('PROP_INPUTCONTROL_IS_MANDATORY',(bool)$xml2->value);
          }
          if($xml2['name']=='PROP_INPUTCONTROL_TYPE'){
              $array_temp->offsetset('PROP_INPUTCONTROL_TYPE',(string)$xml2->value);
          }
          /*
           * 11 -> Multi-select Query (check box)
           */
          if($xml2['name']=='PROP_QUERY_VALUE_COLUMN'){
              $array_temp->offsetset('PROP_QUERY_VALUE_COLUMN',(string)$xml2->value);
          }
          if($xml2['name']=='PROP_QUERY_VISIBLE_COLUMNS'){
              foreach ($xml2->xpath('resourceProperty') as $xml3){
                $array_temp->offsetset('PROP_QUERY_VISIBLE_COLUMN_NAME',(string)$xml3->value);
              }
          }
          /**/
      }
      switch($array_temp['PROP_INPUTCONTROL_TYPE']){
          case 2:
              /*
               * 2 -> Single Value
               */
              break;
          case 11:
              /*
               * 11 -> Multi-select Query (check box)
               */
               $result=$this->getDatawarehouse($this->getBd($this->getDatasource($xml)),$this->getQuery($xml));
               foreach($result as $value){
                   $array_temp2[]=array(
                           'PROP_QUERY_VALUE_COLUMN'=>(int)$value[0][$array_temp['PROP_QUERY_VALUE_COLUMN']],
                           'PROP_QUERY_VISIBLE_COLUMN_NAME'=>(string)$value[0][$array_temp['PROP_QUERY_VISIBLE_COLUMN_NAME']]
                   );
                   $array_temp->offsetset('Multi-select_Query',$array_temp2);
               }
              break;
      }
      
      return $array_temp;
  }

  protected function getInputControlFilter($PROP_INPUTCONTROL_TYPE,$name){
    /*
     * 1 -> Boolean
     * 3 -> Single-select List of Values
     * 8 -> Single-select List of Values (radio)
     * 6 -> Multi-select List of Values
     * 10 -> Multi-select List of Values (check box)
     * 4 -> Single-select Query
     * 9 -> Single-select Query (radio)
     * 7 -> Multi-select Query
     * 11 -> Multi-select Query (check box)
     * ------------------------------------
     */
      switch($PROP_INPUTCONTROL_TYPE){
          case 2:
              /*
               * 2 -> Single Value
               */
              switch($name){
                  case 'Month':
                        return date("m", time());
                      break;
                  case 'Year':
                        return date("Y", time());
                      break;
                  default:
                    return;
                    break;
              }
              break;
          case 11:
              /*
               * 11 -> Multi-select Query (check box)
               */
              switch($name){
                  case 'TYPE_CONTROL':
                      return true;
                      break;
                  default:
                      return false;
                      break;
              }
              break;
          default:
              return;
              break;
      }

  }

  private function getQuery($xml){
      foreach ($xml->xpath('resourceDescriptor') as $xml2){
          if($xml2['wsType']=='query'){
              foreach ($xml2->xpath('resourceProperty') as $xml3){
                  if($xml3['name']=='PROP_QUERY'){
                      return (string)$xml3->value;
                  }
              }
          }
      }
  }

  private function getDatasource($xml){
      foreach ($xml->xpath('resourceDescriptor') as $xml2){
          if($xml2['wsType']=='query'){
              foreach ($xml2->xpath('resourceDescriptor') as $xml3){
                  if($xml3['wsType']=='datasource'){
                      foreach ($xml3->xpath('resourceProperty') as $xml4){
                          if($xml4['name']=='PROP_REFERENCE_URI'){
                              return (string)$xml4->value;
                          }
                      }
                  }
              }
          }
      }
  }

  private function getBd($dataSource){
    $return=$this->getResource($dataSource,'jdbc');
    
    return $return[0]['name'];
  }

private function getDatawarehouse($bd,$sql){

    if($bd=='datawarehouse'){
        require_once ('ossim_db.inc');
        $db = new ossim_db();
        $conn = $db->connect();
        $conn->Execute('use datawarehouse');
        $sql = $sql;
        if ($rs_files = & $conn->Execute($sql)) {
            while (!$rs_files->EOF) {
                $filelist[] = array($rs_files->fields);
                $rs_files->MoveNext();
            }
        }
        
        $db->close($conn);
        return $filelist;
    }
    
    return;
}

public function getResource($uriString,$wsType) {

    $request = '<request operationName="get" locale="en">';
    $request .= '<resourceDescriptor name="" wsType="'.$wsType.'" uriString="'.$uriString.'">';

    $request .= "
        <label>null</label>
        </resourceDescriptor>
      </request>
    ";

    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));

    $return = null;
    try {
        try{
            $result = $client->__soapCall('get', array(
                new SoapParam($request,"requestXmlString")
            ));
        } catch(SoapFault $e){
             //$exception;
        }
        switch($wsType){
          case 'jdbc':
              $return = $this->parseReponseWithResource(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse(),
                $wsType);
              break;
          case 'img':
              $return = $this->parseReponseWithReportData(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'jrtx':
              $return = $this->getAttachment(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse(),
                $wsType);
              break;
          default:
              break;
        }
      //print_r($client->__getLastResponse());
    } catch(SoapFault $exception) {
      $responseHeaders = $client->__getLastResponseHeaders();
      if ($exception->faultstring == "looks like we got no XML document" &&
          strpos($responseHeaders, "Content-Type: multipart/related;") !== false) {
        $return = $this->parseReponseWithReportData($responseHeaders, $client->__getLastResponse());
      } else {
        throw $exception;
      }
    }
    
    return $return;
  }

  private function getAttachment($responseHeaders, $responseBody, $wsType){
      if (strpos($responseBody, "![CDATA[The resource was not found]]") == true) {
          return null;
      }
      preg_match('/boundary="(.*?)"/', $responseHeaders, $matches);
        $boundary = $matches[1];
        $parts = explode($boundary, $responseBody);
        $export = null;
        foreach($parts as $part) {
          if($wsType=='jrtx'){
              if (strpos($part, "Content-Type: application/octet-stream") !== false) {
                $export = substr($part, strpos($part, '<?xml version="1.0" encoding="UTF-8"?>'));
                $export = mb_substr($export,0,(strpos($export, '</jasperTemplate>')+strlen('</jasperTemplate>')));
                break;
              }
          }
        }
        if($wsType=='jrtx'){
            $xml = simplexml_load_string($export);
            $i=0;
              foreach($xml->style as $xml2){
                $xml3 = simplexml_load_string($xml2->asXML());
                foreach($xml3->attributes() as $key => $valor){
                    $array_temp2[$key]=(string)$valor;
                }
                $array_temp[$i]=$array_temp2;
                $i++;
              }
                return $array_temp;
            }
  }

  public function getFormatExport($format){
      switch($format){
            case 'pdf':
                $report_format = "PDF";
                break;
            case 'rtf':
                $report_format = "RTF";
                break;
            case 'xls':
                $report_format = "XLS";
                break;
            default:
                return false;
                break;
        }

        return $report_format;
  }

  private function deleteResource($uriString,$uriParent,$wsType) {

      switch($wsType){
          case 'img':
              $resourceDescriptorName='head.gif';
              break;
          case 'jrtx':
              $resourceDescriptorName='Style.jrtx';
              break;
          default:
              return null;
              break;
      }

    $request = '<request operationName="delete" locale="en">';
    $request .= '<argument name="MODIFY_REPORTUNIT_URI">/'.$uriParent.'</argument>';
    $request .= '<resourceDescriptor name="'.$resourceDescriptorName.'" wsType="'.$wsType.'" uriString="/'.$uriString.'">';
    $request .= "
        </resourceDescriptor>
      </request>
    ";
      
    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));

    $return = null;
    try {
        try{
            $result = $client->__soapCall('delete', array(
                new SoapParam($request,"requestXmlString")
            ));
        } catch(SoapFault $e){
             //$exception;
        }
    } catch(SoapFault $exception) {
      
    }

    return $return;
  }

  public function putResource($uriString,$uriParent,$wsType,$file) {
        /*
         * Posible Bug al hacer un put - actualiza las tablas JIFileResource y JIResource pero no actualiza la tabla JIReportUnitResource
         * Temporalmente actualizamos directamente en la bd
         */
         $temp_conn=$this->connectJasperBd();
        // obtenemos el id del reporte
        $sql='SELECT * FROM JIResourceFolder WHERE uri LIKE ?';
        $nameReport='/'.$uriParent.'_files';
        if (!$result = & $temp_conn->Execute($sql,array($nameReport))) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $idReport=$result->fields["id"]
            );
            $result->MoveNext();
        }
        // obtenemos el id del recurso
        $sql='SELECT * FROM JIResource WHERE name LIKE ? AND parent_folder=?';
        $nameResource=explode($nameReport.'/', '/'.$uriString);
        if (!$result = & $temp_conn->Execute($sql,array($nameResource[1],$idReport))) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $idResource=$result->fields["id"]
            );
            $result->MoveNext();
        }
        // guardamos el recurso en la bd
        $sql='UPDATE JIFileResource SET data=? WHERE id=? LIMIT 1';
        if (!$result = & $temp_conn->Execute($sql,array($file,$idResource))) {
            print $temp_conn->ErrorMsg();
            return;
        }
        //print_r($result);
        $this->db->close($temp_conn);

        return $result;
         //die();
      /*
      require_once('SOAP/Client.php');
      switch($wsType){
          case 'img':
              $resourceDescriptorName='head.gif';
              $iii='true';
              break;
          case 'jrtx':
              $resourceDescriptorName='Style.jrtx';
              $iii='true';
              break;
          default:
              return null;
              break;
      }
    $request = '<request operationName="put" locale="en">'."\n";
    $request .= '<resourceDescriptor name="'.$resourceDescriptorName.'" wsType="'.$wsType.'" uriString="'.'/'.$uriString.'" isNew="true">'."\n";
    $request .= '
        <label>'.$resourceDescriptorName.'</label>
        <description>'.$resourceDescriptorName.'</description>
        <resourceProperty name="PROP_HAS_DATA">
            <value><![CDATA[true]]></value>
        </resourceProperty>';
      if($wsType=='jrtx'){
      $request .= '
      <resourceProperty name="PROP_VERSION">
			<value><![CDATA[1]]></value>
		</resourceProperty>';
      }
    $request .= '
        <resourceProperty name="PROP_PARENT_FOLDER">
            <value><![CDATA['.'/'.$uriParent.'_files]]></value>
        </resourceProperty>
        </resourceDescriptor>
      </request>
    ';
//echo $request;
      $client = new SOAP_client($this->url, false, false, array("user" => $this->username, "pass" => $this->password));

      $attachment = array("body"=>$file, "content_type"=>"application/octet-stream", "cid"=>"123456".$wsType);

      $client->_options['attachments'] = 'Dime';
      //var_dump($attachment);
      $client->_attachments = array( $attachment );
      
      $params = array("request" => $request);
      $this->deleteResource($uriString,$uriParent,$wsType);
      $response = $client->call("put",$params,array('namespace' => "http://www.jaspersoft.com/namespaces/php"));
     //die();
    // echo $response;
      return $response;
       * */
  }

  public function copyResource($uriString,$uriParent,$wsType,$file) {
    switch($wsType){
          case 'img':
              $resourceDescriptorName='head.gif';
              break;
          case 'jrtx':
              $resourceDescriptorName='Style.jrtx';
              break;
          default:
              return null;
              break;
      }
    $request = '<request operationName="copy" locale="en">'."\n";
    $request .= '<argument name="DESTINATION_URI">'.'/'.$uriString.'</argument>'."\n";
    $request .= '<resourceDescriptor name="'.$resourceDescriptorName.'" wsType="'.$wsType.'" uriString="'.'/'.$file.'">'."\n";
    $request .= '
        </resourceDescriptor>
      </request>
    ';


    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));
    $this->deleteResource($uriString,$uriParent,$wsType);
    try {
        try{
            $result = $client->__soapCall('copy', array(
                new SoapParam($request,"requestXmlString")
            ));
        } catch(SoapFault $e){
             //$exception;
        }
    } catch(SoapFault $exception) {

    }

    return $result;
  }
  public function setJrtx($uriStyle,$uriParent,$parameters){

      $xml='<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE jasperTemplate PUBLIC "-//JasperReports//DTD Template//EN" "http://jasperreports.sourceforge.net/dtds/jaspertemplate.dtd">

<jasperTemplate>
	<style name="background_title" isDefault="false" backcolor="'.$parameters['backgroundTitle'].'"/>
	<style name="txt_title" isDefault="false" forecolor="'.$parameters['colorTitle'].'"/>
	<style name="background_subtitle" isDefault="false" backcolor="'.$parameters['backgroundSubtitle'].'"/>
	<style name="txt_subtitle" isDefault="false" forecolor="'.$parameters['colorSubtitle'].'"/>
        <style name="txt_content" isDefault="false" forecolor="'.$parameters['colorContent'].'"/>
</jasperTemplate>';

      $this->putResource($uriStyle,$uriParent,'jrtx',$xml);
      return;
  }
/*
 *
 * List Helpers
 *
 */
  public function getInputControlHTML($xml, $elements = null){
      /*
       * 
       */
      $html='';

      $iterator = $xml->getIterator();

      while($iterator->valid()){
          $iterator2=$iterator->current();

          if($iterator2['wsType']=='inputControl'){
              switch($iterator2['PROP_INPUTCONTROL_TYPE']){
                  case 2:
                      /*
                       * 2 -> Single Value
                       */
                      if($elements!=null || $iterator2['name']=='reportUser' || $iterator2['name']=='reportWWW'){
                          break;
                      }
                      $html.='<li>';
                      $id_temp=str_replace('/','_',$iterator2['uriString']);
                      if($iterator2['name']=='Year'||$iterator2['name']=='Month'){
                        $html.='<div class="widget">';
                      }
                      $html.='<label for="'.$id_temp.'">'._($iterator2['label']).'</label>';
                      $html.='<input type="text" id="'.$id_temp.'"name="'.$iterator2['name'].'" value="'.$this->getInputControlFilter($iterator2['PROP_INPUTCONTROL_TYPE'],$iterator2['name']).'" />';
                      if($iterator2['name']=='Year'||$iterator2['name']=='Month'){
                        $html.='<div class="widgetCalendar" id="'.$id_temp.'_widgetCalendar"></div></div>';
                      }
                      $html.='</li>';
                      break;
                  case 11:
                      /*
                       *  11 -> Multi-select Query (check box)
                       */
                      if($iterator2['name']=='TYPE_CONTROL'&&$elements==null){
                          break;
                      }
                      $html.='<input type="hidden" name="isListItem" value="'.$iterator2['name'].'" />';
                      foreach($iterator2['Multi-select_Query'] as $key => $value){
                          $html.='<li>';
                          $html.='<input type="checkbox" class="inputCheckbox" name="'.$iterator2['name'].'_'.$key.'" value="'.$value['PROP_QUERY_VALUE_COLUMN'].'"';
                          if($this->getInputControlFilter($iterator2['PROP_INPUTCONTROL_TYPE'],$iterator2['name'])){
                              $html.='checked';
                          }
                          $html.='> '._($value['PROP_QUERY_VISIBLE_COLUMN_NAME']).'<br>';
                          $html.='</li>';
                      }
                      break;
                  default:
                     break;
              }
          }
        $iterator->next();
      }
      return $html;

  }

  public function getSubReportHtml($xml){
      /*
       *
       */
      $html='';

      $iterator = $xml->getIterator();
      while($iterator->valid()){
          $iterator2=$iterator->current();
          if($iterator2['wsType']=='jrxml'){
              $html.='<li style="text-align: left">';
              $html.='<a href="jasper-report-pdf.php?reportUnit='.$iterator2['uriString'].'" target="_blank">';
              $html.=_($iterator2['label']);
              $html.='</a> <i> ('.date("Y-m-d H:i:s",$iterator2['creationDate']/1000).')</i>';
              $html.='</li>';
          }
        $iterator->next();
      }

      return $html;

  }

  public function getHeaderExportHtml($report_unit,$format,$params = null,$attachment = false){
      switch($format){
            case 'PDF':
                $contentType = 'application/pdf';
                $extension = '.pdf';
                break;
            case 'RTF':
                $contentType = 'application/msword';
                $extension = '.rtf';
                break;
            case 'XLS':
                $contentType = 'application/vnd.ms-excel';
                $extension = '.xls';
                break;
            default:
                return false;
                break;
      }
      header('Content-type: '.$contentType);
      header("Cache-Control: public, must-revalidate");
      $fname = $report_unit.$params.$extension;
      header("Pragma: hack");
      if ($attachment) {
      	 header('Content-Disposition: attachment; filename="'.$fname.'"');
      	 header("Content-Transfer-Encoding: binary ");
      } else {
      	header('Content-Disposition: inline; filename="'.$fname.'"');
      }
      return;
  }

  public function getParameterHtml($report_unit){
        $url='jasper_include/parameter/';
        $file=$url.$report_unit.'.inc';

        if (file_exists($file)) {
            return $file;
        } else {
            return false;
        }
    }
  public function getPermission($report_unit){
      switch($report_unit){
          case 'Alarms_Report':
              $permission1='MenuIncidents';
              $permission2='ControlPanelAlarms';
              break;
          case 'Business_and_Compliance_ISO_PCI_Report':
              $permission1='MenuReports';
              $permission2='ReportsReportServer';
              break;
          case 'Geographic_Report':
              $permission1='MenuEvents';
              $permission2='EventsForensics';
              break;
          case 'Incidents_Report':
              $permission1='MenuReports';
              $permission2='ReportsReportServer';
              break;
          case 'Metrics_Report':
              $permission1='MenuControlPanel';
              $permission2='ControlPanelMetrics';
              break;
          case 'Security_DB_Destination_Port':
          case 'Security_DB_Events':
          case 'Security_DB_Sensors':
          case 'Security_DB_Source_Port':
          case 'Security_DB_Unique_Address':
          case 'Security_DB_Unique_Country_Events':
          case 'Security_DB_Unique_Events':
          case 'Security_DB_Unique_Plugin':
          case 'Security_Report':
          case 'SIEM_Events_Unique_IP_Links':
              $permission1='MenuEvents';
              $permission2='EventsForensics';
              break;
          case 'SEM_Report':
              $permission1='MenuEvents';
              $permission2='ControlPanelSEM';
              break;
          default:
              return true;
              break;

      }
      Session::logcheck($permission1, $permission2);
  }

  private function connectJasperBd(){
      /*
       * Conectamos con BD de Jasper
       */
      require_once ('ossim_conf.inc');
      $path_conf = $GLOBALS["CONF"];
      require_once ('ossim_db.inc');
      /* database connect */
      $this->db = new ossim_db();
      $temp_conn = $this->db->connect();
      //$temp_conn = $this->db->custom_connect('localhost',$path_conf->get_conf("ossim_user"),$path_conf->get_conf("ossim_pass"));
      $temp_conn->Execute('use jasperserver');

      return $temp_conn;
  }

  public function addReportUnit($name,$newReports) {
      /*
       * Añadimos un reporte
       */
      // conectamos con la bd de Jasper
      $temp_conn=$this->connectJasperBd();
      //
      //1. Añadimos un directorio para los archivos del reporte "name_files"
      //
      // obtenemos el directorio padre del reporte
      $idParentFolder=$this->getResourceFolderId($newReports['parentFolder'],$temp_conn);
      // montamos la uri "name_files"
      $uriJIResourceFolder='/'.$name.'_files';
      // lo insertamos en la bd y nos devuelve su id
      $idJIResourceFolder=$this->setResourceFolder($newReports['version'],$uriJIResourceFolder,$newReports['hidden'],$name.'_files',$newReports['label'],$newReports['description'],$idParentFolder,$newReports['creation_date'],$newReports['update_date'],$temp_conn);
      //
      // 2. Añadimos el recurso Main jrxml y nos devuelve su id
      //
      $idMainReport=$this->setResource($newReports[$name.'_jrxml']['version'],$name.'_jrxml',$idJIResourceFolder,'NULL',$newReports[$name.'_jrxml']['label'],$newReports[$name.'_jrxml']['description'],$newReports[$name.'_jrxml']['creation_date'],$newReports[$name.'_jrxml']['update_date'],$temp_conn);
      //
      // 3. Subimos el archivo Maing jrxml
      //
      $this->setFileResource($name,$newReports[$name.'_jrxml']['resourceUrl'],$idMainReport,"jrxml",$newReports[$name.'_jrxml']['reference'],$idReport,$temp_conn);
      //
      // 4. Añadimos el reporte como recurso
      //
      $idReport=$this->setResource($newReports['version'],$name,$idParentFolder,$idJIResourceFolder,$newReports['label'],$newReports['description'],$newReports['creation_date'],$newReports['update_date'],$temp_conn);
      //
      // 5. Añadimos el report Unit
      //
      // obtenemos la id de la base de datos que usa el reporte
      $idDataSource=$this->getDataSourceId($newReports['dataSource'],$temp_conn);
      //
      $this->setReportUnit($idReport,$idDataSource,$idMainReport,$temp_conn);
      // desconectamos la conexión con la bd
      $temp_conn->Close();

      // devolvemos la id del Resource        
      return $idReport;
  }

  public function addInputControl($name,$newReports,$nameParent,$idReport) {
      // conectamos con la bd de Jasper
         $temp_conn=$this->connectJasperBd();
     //
        // añadimos JIResourceFolder
        $uriJIResourceFolder='/'.$nameParent.'_files';
        $idParentFolder=$this->getResourceFolderId($uriJIResourceFolder,$temp_conn);
        $uriJIResourceFolder.='/'.$name.'_files';
        $idJIResourceFolder=$this->setResourceFolder($uriJIResourceFolder,$name,$newReports['label'],$idParentFolder,$newReports['creation_date'],$newReports['update_date'],$temp_conn);
        // añadimos JIResource del Input Control
        $idResource=$this->setResource($name,$idParentFolder,$idJIResourceFolder,$newReports['label'],$newReports['creation_date'],$newReports['update_date'],$temp_conn);
        // añadimos JIResource del Datasource
        $nameDataSource=key($newReports['datatype']);
        $idResource=$this->setResource($nameDataSource,$idJIResourceFolder,'NULL',$newReports['datatype'][$nameDataSource]['label'],$newReports['creation_date'],$newReports['update_date'],$temp_conn);
        // añadimos JIDataType del Input Control
        $this->setDataType($idResource,$newReports['datatype'][$nameDataSource]['type'],$temp_conn);
        // añadimos JIInputControl del Input Control
        $idInputControl=$this->setInputControl($idResource,$newReports['type'],$newReports['mandatory'],$newReports['visible'],$idResource,$temp_conn);
        // añadimos el JIReportUnitInputControl
        $idReportUnitInputControl=$this->setReportUnitInputControl($idReport,$idResource,$temp_conn);


        echo '$name= '.$name."\n";
        echo '$newReports= ';
        print_r($newReports);
        echo "\n";
        echo '$nameParent= '.$nameParent."\n";
        echo '$uriJIResourceFolder= '.$uriJIResourceFolder."\n";
        echo '$idParentFolder= '.$idParentFolder."\n";
        echo '$uriJIResourceFolder= '.$uriJIResourceFolder."\n";
        echo '$idJIResourceFolder= '.$idJIResourceFolder."\n";
        echo '$idReport= '.$idReport."\n";
        echo '$nameDataSource= '.$nameDataSource."\n";
        echo '$idInputControl= '.$idInputControl."\n";
        //

        /*
        // añadimos JIFileResource del Report
        $this->setFileResource($name,$newReports[$name.'_jrxml']['resourceUrl'],$idMainReport,"jrxml",$idReport,$temp_conn);
        // añadimos JIReportUnit del Report
        $idDataSource=$this->getDataSourceId($newReports['dataSource'],$temp_conn);
        $this->setReportUnit($idReport,$idDataSource,$idMainReport,$temp_conn);
*/
        $temp_conn->Close();
        
        return true;
  }

  private function getDataSourceId($reportDataSource,$temp_conn){
      /*
       * Obtiene la id de un DataSource (base de datos que usa un reporte)
       * Le damos los parametros:
       * - $reportDataSource: del tipo /Data_Sources/datawarehouse
       * Usa el campo:
       * - name: varchar(100) - NOTNULL - UNIQUE. Nombre interno del recurso
       * - parent_folder: bigint(20) - NOTNULL - UNIQUE. id del directorio padre
       * y nos devuelve su id
       */
        // separamos "Data_Sources" de "datawarehouse"    $uri{ [0] =>  [1] => Data_Sources [2] => datawarehouse }
        $uri = explode('/', $reportDataSource);
        // obtenemos la id del folder "Data_Sources"
        $idResourceFolder=$this->getResourceFolderId('/'.$uri[1],$temp_conn);
        //
        $sql='SELECT * FROM JIResource WHERE name="'.$uri[2].'" AND parent_folder='.$idResourceFolder;
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $id=$result->fields["id"]
            );
            $result->MoveNext();
        }

        return $id;
  }

  private function setReportUnitInputControl($report_unit_id,$input_control_id,$temp_conn){
      $sql='INSERT INTO JIReportUnitInputControl (report_unit_id,input_control_id,control_index)VALUES('.$report_unit_id.','.$input_control_id.',0);';
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }

        return true;
  }

  private function setInputControl($id,$type,$mandatory,$visible,$dataType,$temp_conn){
      /*
       * añadimos JIResourceFolder y devuelve su id
       */
      switch($type){
          case 'SingleValue':
              $type=2;
              break;
          default:
              return false;
              break;
      }
      if($mandatory=='true'){
          $mandatory='1';
      }else{
          $mandatory='0';
      }
      if($visible=='true'){
          $visible='1';
      }else{
          $visible='0';
      }
      $sql='INSERT INTO JIInputControl (id,type,mandatory,visible,data_type)VALUES('.$id.','.$type.',"'.$mandatory.'","'.$visible.'",'.$dataType.');';
      echo $sql."\n\n";
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        // devolvemos la id de la última inserción
        return $temp_conn->Insert_ID();
  }

  private function setDataType($idReport,$type,$temp_conn){
      // añadimos JIDataType del Input Control
      switch($type){
          case 'Text':
              $type=1;
              break;
          case 'Number':
              $type=2;
              break;
          case 'Date':
              $type=3;
              break;
          case 'Date/Time':
              $type=4;
              break;
          default:
              return false;
              break;
      }
        $sql='INSERT INTO JIDataType (id,type,strictMin,strictMax)VALUES('.$idReport.','.$type.',"\0", "\0");';
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }

        return true;
  }

  private function setReportUnit($idReport,$idDataSource,$id,$temp_conn){
      /*
       * Guarda un ReportUnit en la bd, es el reporte principal, el main
       * Le damos los parametros:
       * - id: bigint(20) - NOTNULL. Tiene la misma id que su resource en JIResource
       * - data: longblob - NULL. Aquí guardamos el archivo
       * - file_type: varchar(20) - NULL. Tipo de archivo por ejemplo jrxml
       * - reference: bigint(20) - NULL. Si tiene valor no NULL está relacionado con la id de su misma tabla.
       * y nos devuelve su id
       */
      // añadimos JIReportUnit del Report
        $sql='INSERT INTO JIReportUnit (id,reportDataSource,mainReport,promptcontrols,controlslayout)VALUES('.$idReport.','.$idDataSource.','.$id.',"\0",1);';
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
  }

  private function setFileResource($name,$resourceUrl,$id,$type,$reference,$idReport,$temp_conn){
      /*
       * Guarda un ResourceFolder en la bd
       * Le damos los parametros:
       * - id: bigint(20) - NOTNULL. Tiene la misma id que su resource en JIResource
       * - data: longblob - NULL. Aquí guardamos el archivo
       * - file_type: varchar(20) - NULL. Tipo de archivo por ejemplo jrxml
       * - reference: bigint(20) - NULL. Si tiene valor no NULL está relacionado con la id de su misma tabla.
       * y nos devuelve su id
       */
       // Validadores, filtros
       if($reference==NULL){
           $reference='NULL';
       }
       //
        // obtenemos el archivo del directorio "resources"
        if($file=file_get_contents('resources/'.$name.'/'.$resourceUrl)){
            // se inserta en la bd
            $sql='INSERT INTO JIFileResource (id,data,file_type,reference)VALUES('.$id.',?,"'.$type.'",'.$reference.');';
            if (!$result = & $temp_conn->Execute($sql,array($file))) {
                print $temp_conn->ErrorMsg();
                return;
            }

            return true;
        }else{
            // no se ha podido cargar el archivo
            
            return false;
        }
  }
  
  private function setResourceFolder($version,$uriJIResourceFolder,$hidden,$name,$label,$description,$idParentFolder,$creation_date,$update_date,$temp_conn){
      /*
       * Guarda un ResourceFolder en la bd
       * Le damos los parametros:
       * - version: int(11) - NOTNULL. Creo que es el número de veces que se actualiza
       * - uri: varchar(250) - NOTNULL - UNIQUE. Con el nombre del Recurso  ejemplo: /name_files
       * - hidden: bit(1) - NULL. Verdadero o falso, se activa verdadero con b'01'
       * - name: varchar(100) - NOTNULL. Nombre interno del recurso En muchos casos es name_files
       * - label: varchar(100) - NOTNULL. Nombre a mostrar
       * - description: varchar(250) - NULL. No se suele introducir nada
       * - parent_folder: bigint(20) - NULL. id del directorio padre
       * - creation_date: datetime - NOTNULL. Fecha de creación
       * - update_date: datetime - NOTNULL. Fecha de la última actualización
       * y nos devuelve su id
       */
       // Validadores, filtros
       if($version==NULL){
           $version=0;
       }
       if($hidden==NULL){
           $hidden="b'01'";
       }
       if($description==NULL){
           $description='NULL';
       }else{
           $description='"'.$description.'"';
       }
       //
       $sql='INSERT INTO JIResourceFolder (version,uri,hidden,name,label,description,parent_folder,creation_date,update_date) VALUES('.$version.',"'.$uriJIResourceFolder.'","'.$hidden.'","'.$name.'","'.$label.'",'.$description.','.$idParentFolder.',"'.$creation_date.'", "'.$update_date.'");';
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        // obtenemos el id del JIResourceFolder
        $sql='SELECT * FROM JIResourceFolder WHERE uri="'.$uriJIResourceFolder.'"';
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $id=$result->fields["id"]
            );
            $result->MoveNext();
        }

        return $id;
  }

  private function setResource($version,$name,$parent_folder,$childrenFolder,$label,$description,$creation_date,$update_date,$temp_conn){
      /*
       * Guarda un Resource en la bd
       * Le damos los parametros:
       * - version: int(11) - NOTNULL. Creo que es el número de veces que se actualiza
       * - name: varchar(100) - NOTNULL - UNIQUE. Nombre interno del recurso
       * - parent_folder: bigint(20) - NOTNULL - UNIQUE. id del directorio padre
       * - childrenFolder: bigint(20) - NULL. id del directorio padre
       * - label: varchar(100) - NOTNULL. Nombre a mostrar
       * - description: varchar(250) - NULL. No se suele introducir nada
       * - creation_date: datetime - NOTNULL. Fecha de creación
       * - update_date: datetime - NOTNULL. Fecha de la última actualización
       * y nos devuelve su id
       */
       // Validadores, filtros
       echo $label."\n";
       if($version==NULL){
           $version=0;
       }
       if($childrenFolder==NULL){
           $childrenFolder='NULL';
       }
       if($description==NULL){
           $description='NULL';
       }else{
           $description='"'.$description.'"';
       }
       //
      $sql='INSERT INTO JIResource (version,name,parent_folder,childrenFolder,label,description,creation_date,update_date)VALUES('.$version.',"'.$name.'",'.$parent_folder.','.$childrenFolder.',"'.$label.'",'.$description.',"'.$creation_date.'", "'.$update_date.'");';
     if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        // obtenemos el id del recurso
        $sql='SELECT * FROM JIResource WHERE name="'.$name.'" AND parent_folder='.$parent_folder;
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $id=$result->fields["id"]
            );
            $result->MoveNext();
        }

        return $id;
  }

  private function getResourceFolderId($uri,$temp_conn){
      /*
       * Obtiene la id de un ResourceFolder
       * Le damos los parametros:
       * - uri: varchar(250) - NOTNULL - UNIQUE. Con el nombre del Recurso ejemplo: /name_files
       * y nos devuelve su id
       */
        $sql='SELECT * FROM JIResourceFolder WHERE uri="'.$uri.'"';
        if (!$result = & $temp_conn->Execute($sql)) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                // id del JIResourceFolder
                $id=$result->fields["id"]
            );
            $result->MoveNext();
        }

        return $id;
  }
}

class UpdateSql {
  private $name;
  private $url='jasper_include/updateSql/';
  private $file;

  public function __construct($name) {
    $this->name=$name;
    $this->file=$this->url.$this->name.'.inc';
  }

  public function update(){
    if (file_exists($this->file)) {
		require($this->file);
    } else {
        return false;
    }
  }
}

class FormatText{
  private $name;

   public function __construct($name){
       $text = self::limpiar_acentos(trim($name));
       $text = strtolower($text);
       // strip all non word chars
       $text = preg_replace('/\W/', ' ', $text);
       // replace all white space sections with a dash
       $text = preg_replace('/\ +/', '-', $text);
       // trim dashes
       $text = preg_replace('/\-$/', '', $text);
       $text = preg_replace('/^\-/', '', $text);

       $this->name=$text;
  }
        /** ELIMINA ACENTOS **/
    private static function limpiar_acentos($s){
        $s = ereg_replace("á|à|â|ã|ª","a",$s);
        $s = ereg_replace("Á|À|Â|Ã","A",$s);
        $s = ereg_replace("Í|Ì|Î","I",$s);
        $s = ereg_replace("í|ì|î","i",$s);
        $s = ereg_replace("é|è|ê","e",$s);
        $s = ereg_replace("É|È|Ê","E",$s);
        $s = ereg_replace("ó|ò|ô|õ|º","o",$s);
        $s = ereg_replace("Ó|Ò|Ô|Õ","O",$s);
        $s = ereg_replace("ú|ù|û","u",$s);
        $s = ereg_replace("Ú|Ù|Û","U",$s);
        $s = str_replace("ç","c",$s);
        $s = str_replace("Ç","C",$s);
        $s = str_replace("ñ","n",$s);
        $s = str_replace("Ñ","N",$s);
        
        return $s;
    }
    public function getName(){
        return $this->name;
    }
}
class UpdateReports {
  private function getNew($yaml) {
      if(array_key_exists('NEW',$yaml)){
          return $yaml['NEW'];
      }else{
          return false;
      }
  }
  public function setNew($yaml,$jasperClient){
      $newReports=$this->getNew($yaml);
      if($newReports!=false){
          foreach($newReports as $key => $value){
              $idReport=$jasperClient->addReportUnit($key,$value);
              echo 'Add Report Unit: '.$key."<br />";
              foreach($value['inputControls'] as $key2 => $value2){
                  //$jasperClient->addInputControl($key2,$value2,$key,$idReport);
                  //echo 'Add Input Control: '.$key2."<br />";
              }
          }
      }else{
          return false;
      }
  }
}
?>
