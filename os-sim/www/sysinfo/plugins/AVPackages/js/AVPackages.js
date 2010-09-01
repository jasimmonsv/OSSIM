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

//$Id: ipmi.js 352 2010-01-24 14:22:35Z jacky672 $


/*global $, jQuery, buildBlock, datetime, plugin_translate, genlang, appendcss, createBar */

"use strict";

var AVPackages_show = false;
/**
 * insert content into table
 * @param {jQuery} xml plugin-XML
 */
function AVPackages_populate(xml) {
    var color = "";
    var html = "";
    $("#Plugin_AVPackagesTable").html(" ");
    html += "   <thead>\n";
    html += "    <tr>\n";
    html += "     <th>" + genlang(2, true, "AVPackages") + "</th>\n";
    html += "     <th>" + genlang(3, true, "AVPackages") + "</th>\n";
    html += "     <th>" + genlang(4, true, "AVPackages") + "</th>\n";
    html += "     <th>" + genlang(5, true, "AVPackages") + "</th>\n";
    html += "    </tr>\n";
    html += "   </thead>";
    $("Plugins Plugin_AVPackages Packages Item", xml).each(function AVPackages_getitem(idp) {
        //if(idp==0) {
        //    html += "<tr><th style=\"font-weight:bold\">" + genlang(1, true, "AVPackages") + "</th></tr>\n";
        //}
        if(idp%2==0) color = "#FFFFFF";
        else color = "#D6D6D6";
        html += "    <tr bgcolor=\""+color+"\">\n";
        html += "      <th style=\"font-weight:normal\">" +  $(this).attr("Status") + "</th>\n";
        html += "      <th style=\"font-weight:normal\">" +  $(this).attr("Name") + "</th>\n";
        html += "      <th style=\"font-weight:normal\">" +  $(this).attr("Version") + "</th>\n";
        html += "      <th style=\"font-weight:normal\">" +  $(this).attr("Description") + "</th>\n";
        html += "    </tr>\n";
        AVPackages_show = true;
    });

    $("#Plugin_AVPackagesTable").append(html);
}

function AVPackages_buildTable() {
    var html = "";

    html += "<table id=\"Plugin_AVPackagesTable\" cellspacing=\"0\">\n";
    html += "  <thead>\n";
    html += "  </thead>\n";
    html += "  <tbody>\n";
    html += "  </tbody>\n";
    html += "</table>\n";
    $("#Plugin_AVPackages").append(html);
}

/**
 * load the xml via ajax
 */
function AVPackages_request() {
    $.ajax({
        url: "xml.php?plugin=AVPackages",
        dataType: "xml",
        error: function AVPackages_error() {
        $.jGrowl("Error loading XML document for Plugin AVPackages!");
    },
    success: function AVPackages_buildblock(xml) {
        populateErrors(xml);
        AVPackages_populate(xml);
        if (AVPackages_show) {
            plugin_translate("AVPackages");
            $("#Plugin_AVPackages").show();
        }
    }
    });
}

$(document).ready(function AVPackages_buildpage() {
    $("#footer").before(buildBlock("AVPackages", 1, true)); 
    $("#Plugin_AVPackages").css("width", "100%").css("border","1px solid #AAAAAA").css("margin-left", "0px");

    AVPackages_buildTable();

    AVPackages_request();

    $("#Reload_AVPackagesTable").click(function AVPackages_reload(id) {
        AVPackages_request();
        $("#DateTime_AVPackages").html("(" + genlang(1, true, "AVPackages") + ":&nbsp;" + datetime() + ")");
    });
});
