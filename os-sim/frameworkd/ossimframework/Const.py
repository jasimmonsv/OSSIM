#
# TODO: move this file to a real config file (/etc/ossim/frameworkd/)
#       and merge necessary variables from framework's ossim.conf
#

# Ossim framework daemon version
VERSION = "2.1"

# default delay between iterations
# overriden with -s option
SLEEP = 300

# default configuration file
# overriden with -c option
CONFIG_FILE = "/etc/ossim/framework/ossim.conf"

# Default asset
ASSET= 2

# Default frameworkd path
FRAMEWORKD_DIR = "/usr/share/ossim-framework/ossimframework/"

# default log directory location
LOG_DIR = "/var/log/ossim/"

# default rrdtool bin path
# overriden if there is an entry at ossim.conf
RRD_BIN = "/usr/bin/rrdtool"

# don't show debug by default
# overriden with -v option
VERBOSE = 0

# default listener port
# overriden with -p option
LISTENER_PORT = 40003

# default listener ip address. Defaults to loopback only
# overriden with -l option
# Specify 0.0.0.0 for "any"
LISTENER_ADDRESS = "127.0.0.1"



# access to ossim-framework through http:// or https://
HTTP_SSL = False

# vim:ts=4 sts=4 tw=79 expandtab:
