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

var AVLogs_show = false;
/**
 * insert content into table
 * @param {jQuery} xml plugin-XML
 */
 
function toogle_details(id) {
    $('.log'+id).toggle();
    if ($('#img'+id).attr('src').match(/plus/)){
        $('#img'+id).attr('src','../pixmaps/minus-small.png');
        $('#img'+id).attr('title','Hide details');
        $('#img'+id).attr('alt','Hide details');
    }
    else {
        $('#img'+id).attr('src','../pixmaps/plus-small.png');
        $('#img'+id).attr('title','Show details');
        $('#img'+id).attr('alt','Show details');
    }
}

function AVLogs_populate(xml) {
    var html = "";
    var color = "";
    $("#Plugin_AVLogsTable").html(" ");
    $("Plugins Plugin_AVLogs Files Item", xml).each(function AVLogs_getitem(idp) {
        color = "";
        if(idp%2==1) {
            color = " class=\"even\"";
        }
        html += "    <tr" + color + ">\n";
        html += "      <th style=\"font-weight:normal;text-align:right;width:150px;\">";
        html +=             $(this).attr("Name") + ":<a href=\"\" onclick=\"toogle_details('" + idp + "');return false;\"><img id=\"img" +idp+ "\" width=\"16\" align=\"absmiddle\" src=\"../pixmaps/plus-small.png\" border=\"0\" alt=\"Show details\" title=\"Show details\">\n";
        html += "      </th>\n";
        html += "      <th>&nbsp;</th>\n";
        html += "    </tr>\n";
        
        if(parseInt($("Log", this).size())==0) {
            html += "    <tr class=\"log"+idp+" even\" style=\"display:none;\">\n";
            html += "      <th style=\"width:150px\">&nbsp;</th>\n";
            html += "      <th style=\"text-align:left;font-weight:normal;color:#AAAAAA;\">" +  genlang(2, true, "AVLogs") + "</th>\n";
            html += "    </tr>\n";
        }
        else {
            $("Log", this).each(function AVLogs_getlog(ldp) {
                html += "    <tr class=\"log"+idp+" even\" style=\"display:none\">\n";
                html += "      <th style=\"width:150px\">&nbsp;</th>\n";
                html += "      <th style=\"text-align:left;font-weight:normal;\">" +  $(this).attr("Line") + "</th>\n";
                html += "    </tr>\n";
            });
        }
        AVLogs_show = true;
    });

    $("#Plugin_AVLogsTable").append(html);
}

function AVLogs_buildTable() {
    var html = "";

    html += "<table id=\"Plugin_AVLogsTable\" cellspacing=\"0\">\n";
    html += "  <thead>\n";
    html += "  </thead>\n";
    html += "  <tbody>\n";
    html += "  </tbody>\n";
    html += "</table>\n";
    $("#Plugin_AVLogs").append(html);
}

/**
 * load the xml via ajax
 */
function AVLogs_request() {
    $.ajax({
        url: "xml.php?plugin=AVLogs",
        dataType: "xml",
        error: function AVLogs_error() {
        $.jGrowl("Error loading XML document for Plugin AVLogs!");
    },
    success: function AVLogs_buildblock(xml) {
        populateErrors(xml);
        AVLogs_populate(xml);
        if (AVLogs_show) {
            plugin_translate("AVLogs");
            $("#Plugin_AVLogs").show();
        }
    }
    });
}

$(document).ready(function AVLogs_buildpage() {
    $("#footer").before(buildBlock("AVLogs", 1, true)); 
    $("#Plugin_AVLogs").css("width", "100%").css("border","1px solid #AAAAAA").css("margin-left", "0px");

    AVLogs_buildTable();

    AVLogs_request();

    $("#Reload_AVLogsTable").click(function AVLogs_reload(id) {
        AVLogs_request();
        $("#DateTime_AVLogs").html("(" + genlang(1, true, "AVLogs") + ":&nbsp;" + datetime() + ")");
    });
});
