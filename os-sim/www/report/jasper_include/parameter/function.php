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
Session::useractive();

function parserDate($decremento){
	$month   = date("m")-$decremento;
	$year    = date("Y");
	$day     = date("j");
	
	/*
	$hour    = ( $decremento == 1) ? "00" : "23";
	$minute  = ( $decremento == 1) ? "00" : "59";
	$seconds = ( $decremento == 1) ? "00" : "59";
	*/
	
	if($month<1){
		$month=12-(-$month);
		$year-=1;
	}

	if(strlen($month)==1){
		$month="0".$month;
	}

	if(strlen($day)==1){
		$day="0".$day;
	}
	
	return $year.'-'.$month.'-'.$day;
	//return $year.'-'.$month.'-'.$day.' '.$hour.":".$minute.":".$seconds;
}
?>