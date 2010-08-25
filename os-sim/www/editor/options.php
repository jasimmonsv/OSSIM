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
/*
- sameip
- stateless
- rawbytes
- nocase
*/
$ruleOptions = array(
    /* "msg", */
    "logto",
    "ttl",
    "tos",
    /* "id", */
    "ipoption",
    "fragbits",
    "dsize",
    "flags",
    "seq",
    "ack",
    "itype",
    "icode",
    "icmp_id",
    "icmp_seq",
    /* "content", */
    "content-list",
    "offset",
    "depth",
    "session",
    "rpc",
    "resp",
    "react",
    /* "reference", */
    /* "sid", */
    "rev",
    "classtype",
    "priority",
    "uricontent",
    "tag",
    "ip_proto",
    "regex",
    "flow",
    "fragoffset"
);
$ruleSingleOptions = array(
    "nocase",
    "sameip",
    "stateless",
    "rawbytes"
);
?>
