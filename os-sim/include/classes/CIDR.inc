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
* - expand_CIDR()
* Classes list:
* - CIDR
*/
class CIDR {
    // Expands a CIDR notation address into ranges.
    //
    // Thanks to M. Garkier for information and code bits on some important issues with this.
    //
    // example usage:
    // $ip_range=expand_CIDR("111.111.111.0/24","SHORT","IP");
    // print_r($ip_range);
    //
    // Author: mkr@binarywerks.dk
    // 2-Sep-2003
    //
    // Fixed flow control bug 12-Mar-2004, that resulted in excessive memory consumption, if invalid ranges was given.
    // Added parameters for controlling output - cleaned code.
    // Fixed precedence bug in IP validation - Thanks to selt!
    function expand_CIDR($ip, $return_type = "SHORT", $return_format = "LONG") {
    	require_once("classes/Host.inc");
        // validate IP address
        $num = "(1?\d\d|2[0-4]\d|25[0-5]|[0-9])";
        $range = "(1\d|2\d|3[0-2]|[1-9])";
        if (!preg_match("/^$num\.$num\.$num\.$num\/$range$/", $ip)) {
            return "IP wrong format\n";
        }
        // Separate CIDR structure into network-IP and netmask
        $ip_arr = explode("/", $ip);
        // Calculate number of hosts in the subnet
        $mask_bits = $ip_arr[1];
        if ($mask_bits > 31 || $mask_bits < 0) {
            return "Nonsense mask\n";
        }
        $host_bits = 32 - $mask_bits;
        $num_hosts = pow(2, $host_bits) - 1;
        // Netmask in decimal for use later: (hack around PHP always using signed ints)
        $netmask = Host::ip2ulong("255.255.255.255") - $num_hosts;
        // Calculate start and end
        // Store IP-addresses internally as longs, to ease compare of two
        // addresses in a sorted structure.
        $ip_start = Host::ip2ulong($ip_arr[0]) + 0;
        /*
        if($ip_start != ( $ip_start & $netmask ))
        {
        return "WARNING: Address $ip not on network boundary\n";
        }
        */
        $ip_start = $ip_start & $netmask;
        $ip_end = $ip_start + $num_hosts;
        switch ($return_type) {
            case "FULL":
                switch ($return_format) {
                    case 'LONG':
                        for ($i = 0; $i <= $num_hosts; $i++) {
                            $ip_range[] = $ip_start + $i;
                        }
                        break;

                    case 'IP':
                        for ($i = 0; $i <= $num_hosts; $i++) {
                            $ip_range[] = long2ip($ip_start + $i);
                        }
                        break;

                    default:
                        return "Unknown return format: $return_format\n";
                }
                break;

            case "SHORT":
                switch ($return_format) {
                    case 'LONG':
                        $ip_range[] = $ip_start;
                        $ip_range[] = $ip_end;
                        break;

                    case 'IP':
                        $ip_range[] = long2ip($ip_start);
                        $ip_range[] = long2ip($ip_end);
                        break;

                    default:
                        return "Unknown return format: $return_format\n";
                }
                break;

            default:
                return "Unknown return type: $return_type\n";
        }
        return $ip_range;
    }
	// 
	#
	function countSetbits($int){
		$int = $int - (($int >> 1) & 0x55555555);
		$int = ($int & 0x33333333) + (($int >> 2) & 0x33333333);
		return (($int + ($int >> 4) & 0xF0F0F0F) * 0x1010101) >> 24;
	}
	function validNetMask($netmask){
		require_once("classes/Host.inc");
		$netmask = Host::ip2ulong($netmask);
		$neg = ((~(int)$netmask) & 0xFFFFFFFF);
		return (($neg + 1) & $neg) === 0;
	}
	function maskToCIDR($netmask){
		require_once("classes/Host.inc");
		if (CIDR::validNetMask($netmask))
			return CIDR::countSetBits(Host::ip2ulong($netmask));
	}
	function maxBlock($ipinput) {
		return CIDR::maskToCIDR(long2ip(-($ipinput & -($ipinput))));
	}
	function rangeToCIDRList($startIPinput,$endIPinput) {
		/*
		$start = $startIPinput; // need long ip
		$end = $endIPinput;
		$listCIDRs = array();
		$break = 0;
		while($end >= $start && !$break) {
			$maxsize = CIDR::maxBlock($start);
			$maxdiff = 32 - intval(log($end - $start + 1)/log(2));
			$size = ($maxsize < $maxdiff)?$maxsize:$maxdiff;
			$listCIDRs[] = long2ip($start) . "/$size";
			$inc = pow(2, (32 - $size));
			if ($inc > 0) {
				$start += $inc;
			} else {
				$break = 1;
			}
		}
		return $listCIDRs;
		*/
		$ip_start = long2ip($startIPinput);
		$ip_end = long2ip($endIPinput);
		$ipl_start=$startIPinput;
		$ipl_end=$endIPinput;
		if($ipl_start>0 && $ipl_end<0) $delta=($ipl_end+4294967296)-$ipl_start;
		else $delta=$ipl_end-$ipl_start;
		$netmask=str_pad(decbin($delta),32,"0","STR_PAD_LEFT");
		if(ip2long($ip_start)==0 && substr_count($netmask,"1")==32) return "0.0.0.0/0";
		if($delta<0 or ($delta>0 && $delta%2==0)) return NULL;
		for($mask=0;$mask<32;$mask++) if($netmask[$mask]==1) break;
		if(substr_count($netmask,"0")!=$mask) return NULL;
		return "$ip_start/$mask";
	}
}
?>