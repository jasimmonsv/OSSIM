#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import locale, re

#
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger

def get_var(regex, line):
    result = re.findall(regex, line)

    if result != []:
        return result[0]

    else:
        return ""

def get_vars(regex, line):
    return re.findall(regex, line)


def isIpInNet(host, net_list):

    if type(net_list) is not list:
        return False

    for net in net_list:

        if net == 'ANY':
            return True

        if net.count('/') != 1:
            logger.debug("Don't know what to do with malformed net (%s)" % (net))
            continue

        (base, mask) = net.split('/')
        b = base.split('.')
        h = host.split('.')

        if len(b) != 4 or len(h) != 4:
            continue

        val1 = int(b[0])*256*256*256 +\
               int(b[1])*256*256 +\
               int(b[2])*256 +\
               int(b[3])
        val2 = int(h[0])*256*256*256 +\
               int(h[1])*256*256 +\
               int(h[2])*256 +\
               int(h[3])

        if ((val1 >> (32 - int(mask))) == (val2 >> (32 - int(mask)))):
            return True

    return False

def getHostThreshold(conn,host,type):
    if type == "C":
        query = "SELECT threshold_c FROM host WHERE ip = '%s';" % (host)
        value = "threshold_c"
    else: 
        query = "SELECT threshold_a FROM host WHERE ip = '%s';" % (host)
        value = "threshold_a"
    result = conn.exec_query(query)
    if result:
        return result[0][value]
        # return this value
    else:
        net = getClosestNet(conn,host)
        threshold = getNetThreshold(conn,net,value)
        # return this value or a default global value
        return threshold

def getNetThreshold(conn,net,type):
    query = "SELECT %s FROM net WHERE name = '%s';" % (type,net)
    result = conn.exec_query(query)
    if result:
        return int(result[0][type])
    else:
        from OssimConf import OssimConf
        import Const
        conf = OssimConf (Const.CONFIG_FILE)
        return int(conf["threshold"])

def getNetAsset(conn,net):
    query = "SELECT asset FROM net WHERE name = '%s';" % (net)
    result = conn.exec_query(query)
    if result:
        return int(result[0]["asset"])
    else:
        import Const
        return Const.ASSET

def getHostAsset(conn,host):
    query = "SELECT asset FROM host WHERE hostname = '%s';" % (host)
    result = conn.exec_query(query)
    if result:
        return int(result[0]["asset"])
    else:
        return False

def getClosestNet(conn,host):

    net_list = []
    query = "SELECT name,ips FROM net;" 
    net_list = conn.exec_query(query)

    narrowest_mask = 0;
    narrowest_net = "";

    for net in net_list:
        if net["ips"].count('/') != 1:
            logger.debug("Don't know what to do with malformed net (%s)" % (net["ips"]))
            continue

        (base, mask) = net["ips"].split('/')
        b = base.split('.')
        h = host.split('.')

        if len(b) != 4 or len(h) != 4:
            continue

        val1 = int(b[0])*256*256*256 +\
               int(b[1])*256*256 +\
               int(b[2])*256 +\
               int(b[3])
        val2 = int(h[0])*256*256*256 +\
               int(h[1])*256*256 +\
               int(h[2])*256 +\
               int(h[3])

        if ((val1 >> (32 - int(mask))) == (val2 >> (32 - int(mask)))):
            if int(mask) > int(narrowest_mask):
                narrowest_mask = mask
                narrowest_net = net["name"]
        if narrowest_mask > 0:
            return narrowest_net
    return False


def getLocaleFloat(value):
    # set sane default return
    ret = 0
    if isinstance(value, str):
        try:
            locale.setlocale(locale.LC_ALL, '')
            ret = locale.atof(value)
        except:
            try:
                ret = float(value)
            except:
                logger.warning("Translation did not work.")

    else:
        logger.debug("No locale conversion to float available for type %s" % str(type(value)))

    return ret

def asLocaleStr(value):
    try:
        locale.setlocale(locale.LC_ALL, '')

        if isinstance(value, int):
            return locale.str(value)
        elif isinstance(value, float):
            return locale.str(value)
        else:
            logger.debug("No locale conversion to string available for type %s" % str(type(value)))

    except:
        logger.warning("Locale translation did not work.")


# vim:ts=4 sts=4 tw=79 expandtab:
