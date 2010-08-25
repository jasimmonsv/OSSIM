from Logger import Logger
from time import mktime, strptime

logger = Logger.logger

class Event:

    EVENT_TYPE = 'event'
    EVENT_ATTRS = [
        "type",
        "date",
        "sensor",
        "interface",
        "plugin_id",
        "plugin_sid",
        "priority",
        "protocol",
        "src_ip",
        "src_port",
        "dst_ip",
        "dst_port",
        "username",
        "password",
        "filename",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "occurrences",
        "log",
        "data",
        "snort_sid",    # snort specific
        "snort_cid",    # snort specific
        "fdate",
        "tzone"
    ]

    def __init__(self):
        self.event = {}
        self.event["event_type"] = self.EVENT_TYPE

    def __setitem__(self, key, value):

        if key in self.EVENT_ATTRS:
            self.event[key] = self.sanitize_value(value)
            if key == "date":
                # The date in seconds anf fdate as string
                self.event["fdate"]=self.event[key]
                try:
                    self.event["date"]=int(mktime(strptime(self.event[key],"%Y-%m-%d %H:%M:%S")))
                except:
                    logger.warning("There was an error parsing date (%s)" %\
                        (self.event[key]))

        elif key != 'event_type':
            logger.warning("Bad event attribute: %s" % (key))

    def __getitem__(self, key):
        return self.event.get(key, None)

    # event representation
    def __repr__(self):
        event = self.EVENT_TYPE
        for attr in self.EVENT_ATTRS:
            if self[attr]:
                event += ' %s="%s"' % (attr, self[attr])
        return event + "\n"

    # return the internal hash
    def dict(self):
        return self.event

    def sanitize_value(self, string):
        return str(string).strip().replace("\"", "\\\"").replace("'", "")


class EventOS(Event):
    EVENT_TYPE = 'host-os-event'
    EVENT_ATTRS = [
        "host",
        "os",
        "sensor",
        "interface",
        "date",
        "plugin_id",
        "plugin_sid",
        "occurrences",
        "log",
        "fdate",
    ]

class EventMac(Event):
    EVENT_TYPE = 'host-mac-event'
    EVENT_ATTRS = [
        "host",
        "mac",
        "vendor",
        "sensor",
        "interface",
        "date",
        "plugin_id",
        "plugin_sid",
        "occurrences",
        "log",
        "fdate",
    ]

class EventService(Event):
    EVENT_TYPE = 'host-service-event'
    EVENT_ATTRS = [
        "host",
        "sensor",
        "interface",
        "port",
        "protocol",
        "service",
        "application",
        "date",
        "plugin_id",
        "plugin_sid",
        "occurrences",
        "log",
        "fdate",
    ]

class EventHids(Event):
    EVENT_TYPE = 'host-ids-event'
    EVENT_ATTRS = [
        "host",
        "hostname",
        "hids_event_type",
        "target",
        "what",
        "extra_data",
        "sensor",
        "date",
        "plugin_id",
        "plugin_sid",
        "username",
        "password",
        "filename",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "occurrences",
        "log",
        "fdate",
    ]



class WatchRule(Event):

    EVENT_TYPE = 'event'
    EVENT_ATTRS = [
        "type",
	"date",
	"fdate",
	"sensor",
	"interface",
	"src_ip",
	"dst_ip",
	"protocol",
        "plugin_id",
        "plugin_sid",
        "condition",
        "value",
        "port_from",
        "src_port",
        "port_to",
        "dst_port",
        "interval",
        "from",
        "to",
        "absolute",
	"log",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "filename",
        "username",
    ]
class Snort(Event):
    EVENT_TYPE = 'snort-event'
    EVENT_ATTRS = [
        "sensor",
        "interface",
        "gzipdata",
        "unziplen",
        "event_type",
        "plugin_id",
        "type",
        "occurrences"
    ]

# vim:ts=4 sts=4 tw=79 expandtab:
