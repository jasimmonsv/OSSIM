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
import re, string, struct



def dumphexdata(data):
	l = len(data)
	offset = 0
	blocks = l / 16
	rest = l % 16
	pchar = string.letters+string.digits+string.punctuation
	for i in range(0,blocks):
		c = "%08x\t" % offset
		da = ""
		for j in range(0,16):
			(d,) = struct.unpack("B",data[16*i+j])
			cs = "%02x " % d
			if string.find(pchar,chr(d))!=-1:
				da=da+chr(d)
			else:
				da=da+"."
			c = c + cs
		print c+da
		offset = offset + 16
	da = ""
	c = "%08x\t" % offset
	for i in range(0,rest):
		(d,) = struct.unpack("B",data[blocks*16+i])
		cs = "%02x " % d
		if string.find(pchar,chr(d))!=-1:
			da = da + chr (d)
		else:
			da = da +  "." 
		c = c + cs
	c = c+"   "*(16-rest)+da+" "*(16-rest)
	print c



def get_var(regex, line):
    result = re.findall(regex, line)

    if result != []:
        return result[0]

    else:
        return ""



def get_vars(regex, line):
    return re.findall(regex, line)

