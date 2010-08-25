# Set of functions to be used in plugin configuration

import socket, re, time, datetime, md5
from SiteProtectorMap import *
from NetScreenMap import *

HOST_RESOLV_CACHE = {}

PROTO_TABLE = {
    '1':    'icmp',
    '6':    'tcp',
    '17':   'udp',
}

# translate a host name to IPv4 address
def resolv(host):

    if HOST_RESOLV_CACHE.has_key(host):
        return HOST_RESOLV_CACHE[host]

    try:
        addr = socket.gethostbyname(host)
        HOST_RESOLV_CACHE[host] = addr
    except socket.gaierror:
        return host

    return addr

# translate an IPv4 address to host name	 
def resolv_ip(addr):	 
 	 
    try:	 
        host = socket.gethostbyaddr(addr)	 
    except socket.gaierror:	 
        return host	 
 	 
    return host

# translate a port name into it's number
def resolv_port(port):

    try:
        port = socket.getservbyname(port)
    except socket.error:
        return port

    return port

# normalize interface name
def resolv_iface(iface):

   if re.match("(ext|wan1).*",iface):
       iface = "ext"
   elif re.match("(int|port|dmz|wan).*",iface):
       iface = "int"

   return iface


def md5sum(datastring):
    return md5.new(datastring).hexdigest();

def snort_id(id):
    return str(1000 + int(id))

def normalize_protocol(protocol):

#
#   fill protocols table reading /etc/protocols
#
#    try:
#        fd = open('/etc/protocols')
#    except IOError:
#        pass
#    else:
#        pattern = re.compile("(\w+)\s+(\d+)\s+\w+")
#        for line in fd.readlines():
#            result = pattern.search(line)
#            if result:
#                proto_name   = result.groups()[0]
#                proto_number = result.groups()[1]
#                if not proto_table.has_key(proto_number):
#                    proto_table[proto_number] = proto_name
#        fd.close()
#

    if PROTO_TABLE.has_key(str(protocol)):
        return PROTO_TABLE[str(protocol)]
    
    return str(protocol).lower()


### normalize_date function ###

# convert date strings to isoformat 
# you must tag regular expressions with the following names:
# <year>, <month>, <minute>, <hour>, <minute>, <second>
# or <timestamp> for timestamps

# array of date regexp, sorted by probability
# change this order to suite your needs
DATE_REGEXPS = [
    # Syslog -- Oct 27 10:50:46
    re.compile(r'^(?P<month>\w+)\s+(?P<day>\d+)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # apache-error-log -- Fri Aug 07 17:52:19 2009
    re.compile(r'(\w+)\s+(?P<month>\w+)\s+(?P<day>\d+)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)\s+(?P<year>\d\d\d\d)'),
    # syslog-ng -- Oct 27 2007 10:50:46
    re.compile(r'(?P<month>\w+)\s+(?P<day>\d+)\s+(?P<year>\d\d\d\d)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # bind9 -- 10-Aug-2009 07:53:44
    re.compile(r'(?P<day>\d+)-(?P<month>\w+)-(?P<year>\d\d\d\d)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # Snare -- Sun Jan 28 15:15:32 2007
    re.compile(r'\S+\s+(?P<month>\S+)\s+(?P<day>\d+)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)\s+(?P<year>\d+)'),
    # snort -- 11/08-19:19:06
    re.compile(r'^(?P<month>\d\d)/(?P<day>\d\d)(/?(?P<year>\d\d))?-(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # arpwatch -- Monday, March 15, 2004 15:39:19 +0000
    re.compile(r'(\w+), (?P<month>\w+) (?P<day>\d{1,2}), (?P<year>\d{4}) (?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # heartbeat -- 2006/10/19_11:40:05
    # raslog(1581) -- 2009/03/05-11:04:36
    re.compile(r'(?P<year>\d+)/(?P<month>\d+)/(?P<day>\d+)[_-](?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # netgear -- 11/03/2004 19:45:46
    re.compile(r'(?P<day>\d+)/(?P<month>\d+)/(?P<year>\d{4})\s(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # tarantella -- 2007/10/18 14:38:03
    re.compile(r'(?P<year>\d{4})/(?P<month>\d+)/(?P<day>\d+)\s(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # OSSEC -- 2007 Nov 17 06:26:18
    # Intrushield -- 2007-Nov-17 06:26:18 CET
    re.compile(r'(?P<year>\d+)[\s-](?P<month>\w+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # ibm applications -- 11/03/07 19:22:22
    # apache -- 29/Jan/2007:17:02:20
    re.compile(r'(?P<day>\d+)/(?P<month>\w+)/(?P<year>\d+)[\s:](?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # lucent brick hhmmss
    # hhmmss,timestamp
    re.compile(r'^(?P<hour>\d\d)(?P<minute>\d\d)(?P<second>\d\d),(?P<timestamp>\d+)$'),
    re.compile(r'^(?P<hour>\d\d)(?P<minute>\d\d)(?P<second>\d\d)(?:\+|\-)$'),
    re.compile(r'^(?P<hour>\d\d)(?P<minute>\d\d)(?P<second>\d\d)$'),
    # rrd, nagios -- 1162540224
    re.compile(r'^(?P<timestamp>\d+)$'),
    #FileZilla -- 11.03.2009 19:45:46
    re.compile(r'(?P<day>\d+).(?P<month>\d+).(?P<year>\d{4})\s(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # hp eva -- 2 18 2009 14 9 52
    re.compile(r'(?P<month>\d{1,2}) (?P<day>\d{1,2}) (?P<year>\d{4}) (?P<hour>\d{1,2}) (?P<minute>\d{1,2}) (?P<second>\d{1,2})'),
    # Websense -- Wed 14 Apr 2010 12:35:10
    re.compile(r'\S+\s+(?P<day>\d+)\s+(?P<month>\S+)\s+(?P<year>\d+)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),

]

## for adding new date formats you should only
## add a new regexp in the above array
def normalize_date(string):

    for pattern in DATE_REGEXPS:
        result = pattern.search(string)
        if result is None:
            continue

        dict = result.groupdict()

        ### put here all sanity transformations you need
        if dict.has_key('timestamp'):
            (dict['year'], dict['month'], dict['day'], 
             dict['hour'], dict['minute'], dict['second'], a, b, c) = \
                time.localtime(float(dict['timestamp']))
        else:
            # year
            if dict.has_key('year') and not dict['year']:
                dict['year'] = \
                    time.strftime('%Y', time.localtime(time.time()))
            elif not dict.has_key('year'):
                dict['year'] = \
                    time.strftime('%Y', time.localtime(time.time()))
            elif len(dict['year']) == 2:
                dict['year'] = '20' + str(dict['year'])
            # month
            if not dict.has_key('month'):
                dict['month'] = \
                    time.strftime('%m', time.localtime(time.time()))
            elif dict.has_key('month') and not dict['month']:
                dict['month'] = \
                    time.strftime('%m', time.localtime(time.time()))
            elif len(dict['month']) == 1:
                dict['month'] = '0' + str(dict['month'])
            # day 
            if not dict.has_key('day'):
                dict['day'] = \
                    time.strftime('%d', time.localtime(time.time()))
            elif dict.has_key('day') and not dict['day']:
                dict['day'] = \
                    time.strftime('%d', time.localtime(time.time()))
            elif len(dict['day']) == 1:
                dict['day'] = '0' + str(dict['day'])

            # Fix month
            if not dict['month'].isdigit():
                try:
                    dict['month'] = \
                        time.strftime('%m', time.strptime(dict['month'], "%b"))
                except ValueError:
                    try:
                        dict['month'] = \
                            time.strftime('%m', time.strptime(dict['month'], "%B"))
                    except ValueError:
                        pass
            # seconds
            if not dict.has_key('second'):
                dict['second'] = 00
        ### end of transformations

        # now, let's go to translate string
        try:
            date = datetime.datetime(year   = int(dict['year']),
                                     month  = int(dict['month']),
                                     day    = int(dict['day']),
                                     hour   = int(dict['hour']),
                                     minute = int(dict['minute']),
                                     second = int(dict['second'])).isoformat(' ')
        except:
            print "There was an error in normalize_date() function"
        else:
            return date

    return string


def upper(string):
    return string.upper()


def hextoint(string):
    try:
        return int(string, 16)
    except ValueError:
        pass
        
def intrushield_sid(mcafee_sid,mcafee_name):
    # All McAfee Intrushield id are divisible by 256, and this length doesn't fit in OSSIM's table
    mcafee_sid = hextoint(mcafee_sid)/256
    mcafee_name = mcafee_name.replace('-',':')

    # Calculate hash based in event name
    mcafee_subsid=abs(mcafee_name.__hash__())

    # Ugly method to avoid duplicated sids
    mcafee_hash2 = 0
    for i in range(0,len(mcafee_name)):
        mcafee_hash2 = mcafee_hash2 + ord( mcafee_name[i] )

    ossim_sid = int(str(mcafee_hash2)[-1:]+str(int(str(mcafee_subsid)[-7:])+mcafee_sid))

    return ossim_sid


def netscreen_idp_sid(message):
    if NETSCREEN_IDP_SID_TRANSLATION_TABLE.has_key(message):
        return NETSCREEN_IDP_SID_TRANSLATION_TABLE[message]

    # missing sid
    return '99999'


#Dummy function
def checkValue(val):
    if val is not None and val != 0 and val != "0" and val != "" and val != "" and val != 1 and val != "1":
        return 1
    elif val is not None:
        return 0
    else:
        return None


def iss_siteprotector_sid(message):
	if ISS_SITEPROTECTOR_SID_TRANSLATION_MAP.has_key(message):
	    return ISS_SITEPROTECTOR_SID_TRANSLATION_MAP[message]

	return '99999'

