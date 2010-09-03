<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2010 AlienVault
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

/***************************************************************************
 *   Copyright (C) 2008 by phpSysInfo - A PHP System Information Script    *
 *   http://phpsysinfo.sourceforge.net/                                    *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/
//
// $Id: class.ipmi.inc.php 352 2010-01-24 14:22:35Z jacky672 $
//
class AVPackages extends PSI_Plugin {
    private $_lines;

    public function __construct($enc) {
        parent::__construct(__CLASS__, $enc);

        $this->_lines = array();
    }

    /**
     * get package information
     *
     * @return array misc in array with label
     */
    private function misc()
    {
        $result = array ();
        $i = 0;
        foreach ($this->_lines as $line) if (preg_match("/ossim|alienvault/",$line)) {
            preg_match("/^(\w\w)\s+(.*?)\s+(.*?)\s+(.*)/", $line, $buffer);
            if ($buffer[1] != "" && $buffer[2] != "") {
                $result[$i]['status'] = $buffer[1];
                $result[$i]['name'] = $buffer[2];
                $result[$i]['version'] = $buffer[3];
                $result[$i]['description'] = $buffer[4];
                $i++;
            }
        }
        return $result;
    }

    public function execute() {
        $this->_lines = array();
        switch (PSI_PLUGIN_AVPACKAGES_ACCESS) {
            case 'command':
                $lines = "";
                if ((CommonFunctions::executeProgram('dpkg', '-l', $lines))&&(!empty($lines)))
                $this->_lines = preg_split("/\n/", $lines, -1, PREG_SPLIT_NO_EMPTY);
                break;
            case 'data':
                break;
            default:
                $this->error->addConfigError('__construct()', 'PSI_PLUGIN_AVPACKAGES_ACCESS');
                break;
        }
    }

    public function xml()
    {
        if ( empty($this->_lines))
        return $this->xml->getSimpleXmlElement();

        $arrBuff = $this->misc();
        if (sizeof($arrBuff) > 0) {
            $misc = $this->xml->addChild('Packages');
            foreach ($arrBuff as $arrValue) {
                $item = $misc->addChild('Item');
                $item->addAttribute('Status', $arrValue['status']);
                $item->addAttribute('Name', $arrValue['name']);
                $item->addAttribute('Version', $arrValue['version']);
                $item->addAttribute('Description', $arrValue['description']);
            }
        }
        return $this->xml->getSimpleXmlElement();
    }

}
?>
