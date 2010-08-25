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
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "CorrelationDirectives");
require_once ('ossim_conf.inc');
require_once ('classes/Security.inc');
$conf = $GLOBALS["CONF"];
if (version_compare(PHP_VERSION, '5', '>=') && extension_loaded('xsl')) {
    require_once ('domxml-php4-to-php5.php');
}
$XML_FILE = '/etc/ossim/server/directives.xml';
$XSL_FILE = $conf->get_conf("base_dir") . '/directives/directivemenu.xsl';
if (GET('css_stylesheet')) {
    $css_stylesheet = GET('css_stylesheet');
} else {
    $css_stylesheet = 'directives.css';
}
$array_params = array(
    'css_stylesheet' => $css_stylesheet
);
if (!function_exists('domxml_xslt_stylesheet_file')) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("PHP_DOMXML");
}
if (!is_file($XSL_FILE)) {
    die(_("Missing required XSL file") . " '$XSL_FILE'");
}
if (!is_file($XML_FILE)) {
    die(_("Missing required XML file") . " '$XML_FILE'");
}
$xslt = domxml_xslt_stylesheet_file($XSL_FILE);
$xml = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES);
$html = $xslt->process($xml, $array_params);
echo $html->dump_mem(true);
?>
