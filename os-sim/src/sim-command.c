/*
 License:

 Copyright (c) 2003-2006 ossim.net
 Copyright (c) 2007-2009 AlienVault
 All rights reserved.

 This package is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; version 2 dated June, 1991.
 You may not use, modify or distribute this program under any other version
 of the GNU General Public License.

 This package is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this package; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 MA  02110-1301  USA


 On Debian GNU/Linux systems, the complete text of the GNU General
 Public License can be found in `/usr/share/common-licenses/GPL-2'.

 Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
 */

#include <gnet.h>
#include <time.h>
#include <string.h>
#include <zlib.h>
#include "sim-command.h"
#include "sim-rule.h"
#include "sim-util.h"
#include "os-sim.h"
#include <config.h>
#include "sim-scanner-tokens.h"
#include "sim-sensor.h"
#include <assert.h>
#include <sim-session.h>
#include "sim-text-fields.h"
/*
 * Remember that when the server sends something, the keywords are written in
 * sim_command_get_string(), not here. This command_symbols are just the
 * commands that the server receives
 */
static const struct
{
  gchar *name;
  guint token;
} command_symbols[] =
  {
    { "connect", SIM_COMMAND_SYMBOL_CONNECT },
    { "session-append-plugin", SIM_COMMAND_SYMBOL_SESSION_APPEND_PLUGIN },
    { "session-remove-plugin", SIM_COMMAND_SYMBOL_SESSION_REMOVE_PLUGIN },
    { "server-get-sensors", SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS },
    { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
    { "server-get-servers", SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS },
    { "server", SIM_COMMAND_SYMBOL_SERVER },
        { "server-get-sensor-plugins",
            SIM_COMMAND_SYMBOL_SERVER_GET_SENSOR_PLUGINS },
        { "server-set-data-role", SIM_COMMAND_SYMBOL_SERVER_SET_DATA_ROLE },
        { "sensor-plugin", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN },
        { "sensor-plugin-start", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_START },
        { "sensor-plugin-stop", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_STOP },
        { "sensor-plugin-enable", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_ENABLE },
        { "sensor-plugin-disable", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_DISABLE },
        { "plugin-process-started", SIM_COMMAND_SYMBOL_PLUGIN_STATE_STARTED },
        { "plugin-process-unknown", SIM_COMMAND_SYMBOL_PLUGIN_STATE_UNKNOWN },
        { "plugin-process-stopped", SIM_COMMAND_SYMBOL_PLUGIN_STATE_STOPPED },
        { "plugin-enabled", SIM_COMMAND_SYMBOL_PLUGIN_ENABLED },
        { "plugin-disabled", SIM_COMMAND_SYMBOL_PLUGIN_DISABLED },
        { "event", SIM_COMMAND_SYMBOL_EVENT },
        { "reload-plugins", SIM_COMMAND_SYMBOL_RELOAD_PLUGINS },
        { "reload-sensors", SIM_COMMAND_SYMBOL_RELOAD_SENSORS },
        { "reload-hosts", SIM_COMMAND_SYMBOL_RELOAD_HOSTS },
        { "reload-nets", SIM_COMMAND_SYMBOL_RELOAD_NETS },
        { "reload-policies", SIM_COMMAND_SYMBOL_RELOAD_POLICIES },
        { "reload-directives", SIM_COMMAND_SYMBOL_RELOAD_DIRECTIVES },
        { "reload-all", SIM_COMMAND_SYMBOL_RELOAD_ALL },
        { "host-os-event", SIM_COMMAND_SYMBOL_HOST_OS_EVENT },
        { "host-mac-event", SIM_COMMAND_SYMBOL_HOST_MAC_EVENT },
        { "host-service-event", SIM_COMMAND_SYMBOL_HOST_SERVICE_EVENT },
        { "host-ids-event", SIM_COMMAND_SYMBOL_HOST_IDS_EVENT },
        { "agent-date", SIM_COMMAND_SYMBOL_AGENT_DATE },
        { "ok", SIM_COMMAND_SYMBOL_OK },
        { "error", SIM_COMMAND_SYMBOL_ERROR },
        { "database-query", SIM_COMMAND_SYMBOL_DATABASE_QUERY },
        { "database-answer", SIM_COMMAND_SYMBOL_DATABASE_ANSWER },
        { "snort-event", SIM_COMMAND_SYMBOL_SNORT_EVENT } };

static const struct
{
  gchar *name;
  guint token;
} connect_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "type", SIM_COMMAND_SYMBOL_TYPE },
    { "version", SIM_COMMAND_SYMBOL_AGENT_VERSION },
    { "hostname", SIM_COMMAND_SYMBOL_HOSTNAME }, //this is the name of the server or the agent connected. Just mandatory in server conns.
        { "username", SIM_COMMAND_SYMBOL_USERNAME },
        { "password", SIM_COMMAND_SYMBOL_PASSWORD } };

static const struct
{
  gchar *name;
  guint token;
} session_append_plugin_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
    { "type", SIM_COMMAND_SYMBOL_TYPE },
    { "name", SIM_COMMAND_SYMBOL_NAME },
    { "state", SIM_COMMAND_SYMBOL_STATE },
    { "enabled", SIM_COMMAND_SYMBOL_ENABLED } };

static const struct
{
  gchar *name;
  guint token;
} session_remove_plugin_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
    { "type", SIM_COMMAND_SYMBOL_TYPE },
    { "name", SIM_COMMAND_SYMBOL_NAME },
    { "state", SIM_COMMAND_SYMBOL_STATE },
    { "enabled", SIM_COMMAND_SYMBOL_ENABLED } };

static const struct
{
  gchar *name;
  guint token;
} server_get_sensors_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } //this is the server's name involved.
  };

static const struct
{
  gchar *name;
  guint token;
} server_get_servers_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } //this is the server's name involved.
  };

static const struct
{
  gchar *name;
  guint token;
} server_set_data_role_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }, //this is the server's name involved.
        { "role_correlate", SIM_COMMAND_SYMBOL_ROLE_CORRELATE },
        { "role_cross_correlate", SIM_COMMAND_SYMBOL_ROLE_CROSS_CORRELATE },
        { "role_store", SIM_COMMAND_SYMBOL_ROLE_STORE },
        { "role_qualify", SIM_COMMAND_SYMBOL_ROLE_QUALIFY },
        { "role_resend_alarm", SIM_COMMAND_SYMBOL_ROLE_RESEND_ALARM },
        { "role_resend_event", SIM_COMMAND_SYMBOL_ROLE_RESEND_EVENT } };

static const struct
{
  gchar *name;
  guint token;
} sensor_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "host", SIM_COMMAND_SYMBOL_HOST },
    { "state", SIM_COMMAND_SYMBOL_STATE },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } //this is the server's name to wich the sensor is attached
  };

static const struct
{
  gchar *name;
  guint token;
} server_symbols[] =
  { //answer to server-get-servers
      { "id", SIM_COMMAND_SYMBOL_ID },
      { "host", SIM_COMMAND_SYMBOL_HOST }, //this is the answer; this is one server attached to servername.
          { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } //this is the server's name to wich the server is attached
    };

static const struct
{
  gchar *name;
  guint token;
} server_get_sensor_plugins_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } //from what server should the sensor plugins be asked for?
  };

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }, //server name to send plugin data (multiserver architecture)
        { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
        { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
        { "state", SIM_COMMAND_SYMBOL_STATE },
        { "enabled", SIM_COMMAND_SYMBOL_ENABLED } };

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_start_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }, //server name to send plugin commands to. (multiserver)
        { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
        { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_stop_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }, //server name to send plugin data
        { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
        { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_enable_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
    { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_disable_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
    { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} plugin_state_started_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} plugin_state_unknown_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} plugin_state_stopped_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} plugin_enabled_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} plugin_disabled_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID } };

static const struct
{
  gchar *name;
  guint token;
} event_symbols[] =
  {
    { "type", SIM_COMMAND_SYMBOL_TYPE }, //="detector" / ="monitor"
        { "id", SIM_COMMAND_SYMBOL_ID }, //this ID is referring the event's id, I mean, the id assigned to the event in insert_event_alarm()
      //So this field has sense just in case the event received is from another server.
        { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
        { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
        { "date", SIM_COMMAND_SYMBOL_DATE },
        { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
        { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
        { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
        { "device", SIM_COMMAND_SYMBOL_DEVICE },
        { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
        { "priority", SIM_COMMAND_SYMBOL_PRIORITY },
        { "protocol", SIM_COMMAND_SYMBOL_PROTOCOL },
        { "src_ip", SIM_COMMAND_SYMBOL_SRC_IP },
        { "src_port", SIM_COMMAND_SYMBOL_SRC_PORT },
        { "dst_ip", SIM_COMMAND_SYMBOL_DST_IP },
        { "dst_port", SIM_COMMAND_SYMBOL_DST_PORT },
        { "condition", SIM_COMMAND_SYMBOL_CONDITION },
        { "value", SIM_COMMAND_SYMBOL_VALUE },
        { "interval", SIM_COMMAND_SYMBOL_INTERVAL },
        { "data", SIM_COMMAND_SYMBOL_DATA },
        { "log", SIM_COMMAND_SYMBOL_LOG },
        { "snort_sid", SIM_COMMAND_SYMBOL_SNORT_SID },
        { "snort_cid", SIM_COMMAND_SYMBOL_SNORT_CID },
        { "asset_src", SIM_COMMAND_SYMBOL_ASSET_SRC },
        { "asset_dst", SIM_COMMAND_SYMBOL_ASSET_DST },
        { "risk_a", SIM_COMMAND_SYMBOL_RISK_A },
        { "risk_c", SIM_COMMAND_SYMBOL_RISK_C },
        { "alarm", SIM_COMMAND_SYMBOL_ALARM },
        { "reliability", SIM_COMMAND_SYMBOL_RELIABILITY },
        { "filename", SIM_COMMAND_SYMBOL_FILENAME },
        { "username", SIM_COMMAND_SYMBOL_USERNAME },
        { "password", SIM_COMMAND_SYMBOL_PASSWORD },
        { "userdata1", SIM_COMMAND_SYMBOL_USERDATA1 },
        { "userdata2", SIM_COMMAND_SYMBOL_USERDATA2 },
        { "userdata3", SIM_COMMAND_SYMBOL_USERDATA3 },
        { "userdata4", SIM_COMMAND_SYMBOL_USERDATA4 },
        { "userdata5", SIM_COMMAND_SYMBOL_USERDATA5 },
        { "userdata6", SIM_COMMAND_SYMBOL_USERDATA6 },
        { "userdata7", SIM_COMMAND_SYMBOL_USERDATA7 },
        { "userdata8", SIM_COMMAND_SYMBOL_USERDATA8 },
        { "userdata9", SIM_COMMAND_SYMBOL_USERDATA9 },
        { "is_prioritized", SIM_COMMAND_SYMBOL_IS_PRIORITIZED }, };

static const struct
{
  gchar *name;
  guint token;
} reload_plugins_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} reload_sensors_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} reload_hosts_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} reload_nets_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} reload_policies_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} reload_directives_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} reload_all_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} host_os_event_symbols[] =
  {
    { "date", SIM_COMMAND_SYMBOL_DATE },
    { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
    { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
    { "id", SIM_COMMAND_SYMBOL_ID }, //event it, not the message id.
        { "host", SIM_COMMAND_SYMBOL_HOST },
        { "os", SIM_COMMAND_SYMBOL_OS },
        { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
        { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
        { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
        { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
        { "log", SIM_COMMAND_SYMBOL_LOG } };

static const struct
{
  gchar *name;
  guint token;
} host_mac_event_symbols[] =
  {
    { "date", SIM_COMMAND_SYMBOL_DATE },
    { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
    { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "host", SIM_COMMAND_SYMBOL_HOST },
    { "mac", SIM_COMMAND_SYMBOL_MAC },
    { "vendor", SIM_COMMAND_SYMBOL_VENDOR },
    { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
    { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
    { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
    { "log", SIM_COMMAND_SYMBOL_LOG } };

static const struct
{
  gchar *name;
  guint token;
} host_service_event_symbols[] =
  {
    { "date", SIM_COMMAND_SYMBOL_DATE },
    { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
    { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "host", SIM_COMMAND_SYMBOL_HOST },
    { "port", SIM_COMMAND_SYMBOL_PORT },
    { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
    { "protocol", SIM_COMMAND_SYMBOL_PROTOCOL },
    { "service", SIM_COMMAND_SYMBOL_SERVICE },
    { "application", SIM_COMMAND_SYMBOL_APPLICATION },
    { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
    { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
    { "log", SIM_COMMAND_SYMBOL_LOG } };

static const struct
{
  gchar *name;
  guint token;
} host_ids_event_symbols[] =
  {
    { "host", SIM_COMMAND_SYMBOL_HOST },
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "hostname", SIM_COMMAND_SYMBOL_HOSTNAME },
    { "event_type", SIM_COMMAND_SYMBOL_EVENT_TYPE },
    { "hids_event_type", SIM_COMMAND_SYMBOL_HIDS_EVENT_TYPE },
    { "target", SIM_COMMAND_SYMBOL_TARGET },
    { "what", SIM_COMMAND_SYMBOL_WHAT },
    { "extra_data", SIM_COMMAND_SYMBOL_EXTRA_DATA },
    { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
    { "date", SIM_COMMAND_SYMBOL_DATE },
    { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
    { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
    { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
    { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
    { "log", SIM_COMMAND_SYMBOL_LOG },
    { "filename", SIM_COMMAND_SYMBOL_FILENAME },
    { "username", SIM_COMMAND_SYMBOL_USERNAME },
    { "password", SIM_COMMAND_SYMBOL_PASSWORD },
    { "userdata1", SIM_COMMAND_SYMBOL_USERDATA1 },
    { "userdata2", SIM_COMMAND_SYMBOL_USERDATA2 },
    { "userdata3", SIM_COMMAND_SYMBOL_USERDATA3 },
    { "userdata4", SIM_COMMAND_SYMBOL_USERDATA4 },
    { "userdata5", SIM_COMMAND_SYMBOL_USERDATA5 },
    { "userdata6", SIM_COMMAND_SYMBOL_USERDATA6 },
    { "userdata7", SIM_COMMAND_SYMBOL_USERDATA7 },
    { "userdata8", SIM_COMMAND_SYMBOL_USERDATA8 },
    { "userdata9", SIM_COMMAND_SYMBOL_USERDATA9 } };

static const struct
{
  gchar *name;
  guint token;
} ok_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID } };

static const struct
{
  gchar *name;
  guint token;
} database_query_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "database-element-type", SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
    { "sensorname", SIM_COMMAND_SYMBOL_SENSORNAME } };

static const struct
{
  gchar *name;
  guint token;
} database_answer_symbols[] =
  {
    { "id", SIM_COMMAND_SYMBOL_ID },
    { "answer", SIM_COMMAND_SYMBOL_ANSWER },
    { "database-element-type", SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE },
    { "servername", SIM_COMMAND_SYMBOL_SERVERNAME } };

static const struct
{
  gchar *name;
  guint token;
} snort_event_symbols[] =
  {
    { "sensor", SIM_COMMAND_SYMBOL_SNORT_EVENT_SENSOR },
    { "interface", SIM_COMMAND_SYMBOL_SNORT_EVENT_IF },
    { "unziplen", SIM_COMMAND_SYMBOL_UNZIPLEN },
    { "gzipdata", SIM_COMMAND_SYMBOL_GZIPDATA },
    { "event_type", SIM_COMMAND_SYMBOL_SNORT_EVENT_TYPE },
    { "date", SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE },
    { "tzone", SIM_COMMAND_SYMBOL_SNORT_EVENT_TZONE },
    { "fdate", SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE_STRING } };
static const struct
{
  gchar *name;
  guint token;
} snort_event_data_symbols[] =
  {
    { "type", SIM_COMMAND_SYMBOL_SNORT_EVENT_DATA_TYPE },
    { "snort_gid", SIM_COMMAND_SYMBOL_SNORT_EVENT_GID },
    { "snort_sid", SIM_COMMAND_SYMBOL_SNORT_EVENT_SID },
    { "snort_rev", SIM_COMMAND_SYMBOL_SNORT_EVENT_REV },
    { "snort_classification", SIM_COMMAND_SYMBOL_SNORT_EVENT_CLASSIFICATION },
    { "snort_priority", SIM_COMMAND_SYMBOL_SNORT_EVENT_PRIORITY },
    { "packet_type", SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_TYPE } };

static const struct
{
  gchar *name;
  guint token;
} agent_date_symbols[] =
  { //date from agents
      { "agent_date", SIM_COMMAND_SYMBOL_AGENT__DATE },
      { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE } };

static const struct
{
  gchar *name;
  gint token;
} snort_event_packet_raw_symbols[] =
  {
    { "raw_packet", SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_RAW } };

static const struct
{
  gchar *name;
  guint token;
} snort_event_packet_ip_symbols[] =
  {
    { "ip_ver", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_VER },
    { "ip_tos", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TOS },
    { "ip_id", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_ID },
    { "ip_offset", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OFFSET },
    { "ip_hdrlen", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_HDRLEN },
    { "ip_len", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_LEN },
    { "ip_ttl", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TTL },
    { "ip_proto", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PROTO },
    { "ip_csum", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_CSUM },
    { "ip_src", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_SRC },
    { "ip_dst", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_DST },
    { "ip_optnum", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTNUM },
    { "ip_optcode", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTCODE },
    { "ip_optlen", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTLEN },
    { "ip_optpayload", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTPAYLOAD },
    { "ip_ippayload", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PAYLOAD } };

static const struct
{
  gchar *name;
  guint token;
} snort_event_packet_icmp_symbols[] =
  {
    { "icmp_type", SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_TYPE },
    { "icmp_code", SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_CODE },
    { "icmp_csum", SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_CSUM },
    { "icmp_id", SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_ID },
    { "icmp_seq", SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_SEQ },
    { "icmp_payload", SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_PAYLOAD } };

static const struct
{
  gchar *name;
  guint token;

} snort_event_packet_udp_symbols[] =
  {
    { "udp_sport", SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_SPORT },
    { "udp_dport", SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_DPORT },
    { "udp_len", SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_LEN },
    { "udp_csum", SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_CSUM },
    { "udp_payload", SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_PAYLOAD } };

static const struct
{
  gchar *name;
  guint token;
} snort_event_packet_tcp_symbols[] =
  {
    { "tcp_sport", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SPORT },
    { "tcp_dport", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_DPORT },
    { "tcp_seq", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SEQ },
    { "tcp_ack", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_ACK },
    { "tcp_flags", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_FLAGS },
    { "tcp_offset", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OFFSET },
    { "tcp_window", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_WINDOW },
    { "tcp_csum", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_CSUM },
    { "tcp_urgptr", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_URGPTR },
    { "tcp_optnum", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTNUM },
    { "tcp_optcode", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTCODE },
    { "tcp_optlen", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTLEN },
    { "tcp_optpayload", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTPAYLOAD },
    { "tcp_payload", SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_PAYLOAD } };

enum
{
  DESTROY, LAST_SIGNAL
};
static gboolean
sim_command_scan(SimCommand *command, const gchar *buffer, SimSession *session);
static gboolean
sim_command_connect_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_session_append_plugin_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_session_remove_plugin_scan(SimCommand *command, GScanner *scanner);

static gboolean
sim_command_server_get_sensors_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_sensor_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_server_get_servers_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_server_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_server_get_sensor_plugins_scan(SimCommand *command,
    GScanner *scanner);

static gboolean
sim_command_server_set_data_role_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_sensor_plugin_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_sensor_plugin_start_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_sensor_plugin_stop_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_sensor_plugin_enable_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_sensor_plugin_disable_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_plugin_state_started_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_plugin_state_unknown_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_plugin_state_stopped_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_plugin_enabled_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_plugin_disabled_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_event_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_event_scan_base64(SimCommand *command, GScanner *scanner);

static gboolean
sim_command_reload_plugins_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_reload_sensors_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_reload_hosts_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_reload_nets_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_reload_policies_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_reload_directives_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_reload_all_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_host_os_event_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_host_mac_event_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_host_service_event_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_host_ids_event_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_ok_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_database_query_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_database_answer_scan(SimCommand *command, GScanner *scanner);

gboolean
sim_command_snort_event_scan(SimCommand *command, GScanner *scanner);
static gboolean
sim_command_agent_date_scan(SimCommand *command, GScanner *scanner);

static GPrivate *privScanner = NULL;
static gpointer parent_class = NULL;
static gint sim_server_signals[LAST_SIGNAL] =
  { 0 };
/* Versions -> Agent map functions*/
static const struct
{
  gchar *version;
  gboolean
  (*pf)(SimCommand *, GScanner*);
} agent_parsers_table[] =
  {
    { "2.1", &sim_command_event_scan },
    { "2.3.1", &sim_command_event_scan_base64 },
    { NULL, NULL } };

/*
 * Init the TLS system for all the threads
 * must be called AFTER g_thread_init()
 * The thread local variable store the pointer to the lexical scanner
 */

void
sim_command_init_tls(void)
{
  privScanner = g_private_new((GDestroyNotify) g_scanner_destroy);
}

/* GType Functions */

static void
sim_command_impl_dispose(GObject *gobject)
{
  G_OBJECT_CLASS(parent_class)->dispose(gobject);
}

static void
sim_command_impl_finalize(GObject *gobject)
{
  SimCommand *cmd = SIM_COMMAND (gobject);

  if (cmd->buffer)
    g_free(cmd->buffer);

  switch (cmd->type)
    {
  case SIM_COMMAND_TYPE_CONNECT:
    if (cmd->data.connect.username)
      g_free(cmd->data.connect.username);
    if (cmd->data.connect.password)
      g_free(cmd->data.connect.password);
    if (cmd->data.connect.hostname)
      g_free(cmd->data.connect.hostname);
    break;

  case SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE:
    if (cmd->data.server_set_data_role.servername)
      g_free(cmd->data.server_set_data_role.servername);
    break;

  case SIM_COMMAND_TYPE_SERVER_GET_SENSORS:
    if (cmd->data.server_get_sensors.servername)
      g_free(cmd->data.server_get_sensors.servername);
    break;

  case SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS:
    if (cmd->data.server_get_sensor_plugins.servername)
      g_free(cmd->data.server_get_sensor_plugins.servername);
    break;

  case SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN:
    if (cmd->data.session_append_plugin.name)
      g_free(cmd->data.session_append_plugin.name);
    break;
  case SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN:
    if (cmd->data.session_remove_plugin.name)
      g_free(cmd->data.session_remove_plugin.name);
    break;

  case SIM_COMMAND_TYPE_SNORT_EVENT:
    g_free(cmd->snort_event.gzipdata); //no break as the snort event has to remove also all the event information.
    if (cmd->packet)
      g_object_unref(cmd->packet);
  case SIM_COMMAND_TYPE_EVENT:
    if (cmd->data.event.type)
      g_free(cmd->data.event.type);
    if (cmd->data.event.date_str)
      g_free(cmd->data.event.date_str);

    if (cmd->data.event.sensor)
      g_free(cmd->data.event.sensor);
    if (cmd->data.event.device)
      g_free(cmd->data.event.device);
    if (cmd->data.event.interface)
      g_free(cmd->data.event.interface);

    if (cmd->data.event.protocol)
      g_free(cmd->data.event.protocol);
    if (cmd->data.event.src_ip)
      g_free(cmd->data.event.src_ip);
    if (cmd->data.event.dst_ip)
      g_free(cmd->data.event.dst_ip);

    if (cmd->data.event.condition)
      g_free(cmd->data.event.condition);
    if (cmd->data.event.value)
      g_free(cmd->data.event.value);
    if (cmd->data.event.data)
      g_free(cmd->data.event.data);
    g_free(cmd->data.event.log);

    if (cmd->data.event.filename)
      g_free(cmd->data.event.filename);
    if (cmd->data.event.username)
      g_free(cmd->data.event.username);
    if (cmd->data.event.password)
      g_free(cmd->data.event.password);
    if (cmd->data.event.userdata1)
      g_free(cmd->data.event.userdata1);
    if (cmd->data.event.userdata2)
      g_free(cmd->data.event.userdata2);
    if (cmd->data.event.userdata3)
      g_free(cmd->data.event.userdata3);
    if (cmd->data.event.userdata4)
      g_free(cmd->data.event.userdata4);
    if (cmd->data.event.userdata5)
      g_free(cmd->data.event.userdata5);
    if (cmd->data.event.userdata6)
      g_free(cmd->data.event.userdata6);
    if (cmd->data.event.userdata7)
      g_free(cmd->data.event.userdata7);
    if (cmd->data.event.userdata8)
      g_free(cmd->data.event.userdata8);
    if (cmd->data.event.userdata9)
      g_free(cmd->data.event.userdata9);
    if (cmd->data.event.event)
      g_object_unref(cmd->data.event.event);

    break;

  case SIM_COMMAND_TYPE_SENSOR:
    if (cmd->data.sensor.host)
      g_free(cmd->data.sensor.host);
    break;

  case SIM_COMMAND_TYPE_SENSOR_PLUGIN:
    if (cmd->data.sensor_plugin.sensor)
      g_free(cmd->data.sensor_plugin.sensor);
    if (cmd->data.sensor_plugin.servername)
      g_free(cmd->data.sensor_plugin.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_START:
    if (cmd->data.sensor_plugin_start.sensor)
      g_free(cmd->data.sensor_plugin_start.sensor);
    if (cmd->data.sensor_plugin_start.servername)
      g_free(cmd->data.sensor_plugin_start.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP:
    if (cmd->data.sensor_plugin_stop.sensor)
      g_free(cmd->data.sensor_plugin_stop.sensor);
    if (cmd->data.sensor_plugin_stop.servername)
      g_free(cmd->data.sensor_plugin_stop.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE:
    if (cmd->data.sensor_plugin_enable.sensor)
      g_free(cmd->data.sensor_plugin_enable.sensor);
    if (cmd->data.sensor_plugin_enable.servername)
      g_free(cmd->data.sensor_plugin_enable.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE:
    if (cmd->data.sensor_plugin_disable.sensor)
      g_free(cmd->data.sensor_plugin_disable.sensor);
    if (cmd->data.sensor_plugin_disable.servername)
      g_free(cmd->data.sensor_plugin_disable.servername);
    break;

  case SIM_COMMAND_TYPE_WATCH_RULE:
    if (cmd->data.watch_rule.str)
      g_free(cmd->data.watch_rule.str);
    break;

  case SIM_COMMAND_TYPE_HOST_OS_EVENT:
    g_free(cmd->data.host_os_event.date_str);
    if (cmd->data.host_os_event.host)
      g_free(cmd->data.host_os_event.host);
    if (cmd->data.host_os_event.os)
      g_free(cmd->data.host_os_event.os);
    if (cmd->data.host_os_event.sensor)
      g_free(cmd->data.host_os_event.sensor);
    if (cmd->data.host_os_event.interface)
      g_free(cmd->data.host_os_event.interface);
    break;

  case SIM_COMMAND_TYPE_HOST_MAC_EVENT:
    g_free(cmd->data.host_mac_event.date_str);
    if (cmd->data.host_mac_event.host)
      g_free(cmd->data.host_mac_event.host);
    if (cmd->data.host_mac_event.mac)
      g_free(cmd->data.host_mac_event.mac);
    if (cmd->data.host_mac_event.vendor)
      g_free(cmd->data.host_mac_event.vendor);
    if (cmd->data.host_mac_event.sensor)
      g_free(cmd->data.host_mac_event.sensor);
    if (cmd->data.host_mac_event.interface)
      g_free(cmd->data.host_mac_event.interface);
    break;

  case SIM_COMMAND_TYPE_HOST_SERVICE_EVENT:
    g_free(cmd->data.host_service_event.date_str);
    if (cmd->data.host_service_event.host)
      g_free(cmd->data.host_service_event.host);
    if (cmd->data.host_service_event.service)
      g_free(cmd->data.host_service_event.service);
    if (cmd->data.host_service_event.application)
      g_free(cmd->data.host_service_event.application);
    if (cmd->data.host_service_event.log)
      g_free(cmd->data.host_service_event.log);
    if (cmd->data.host_service_event.sensor)
      g_free(cmd->data.host_service_event.sensor);
    if (cmd->data.host_service_event.interface)
      g_free(cmd->data.host_service_event.interface);
    break;

  case SIM_COMMAND_TYPE_HOST_IDS_EVENT:
    g_free(cmd->data.host_ids_event.date_str);
    if (cmd->data.host_ids_event.host)
      g_free(cmd->data.host_ids_event.host);
    if (cmd->data.host_ids_event.hostname)
      g_free(cmd->data.host_ids_event.hostname);
    if (cmd->data.host_ids_event.event_type)
      g_free(cmd->data.host_ids_event.event_type);
    if (cmd->data.host_ids_event.target)
      g_free(cmd->data.host_ids_event.target);
    if (cmd->data.host_ids_event.what)
      g_free(cmd->data.host_ids_event.what);
    if (cmd->data.host_ids_event.extra_data)
      g_free(cmd->data.host_ids_event.extra_data);
    if (cmd->data.host_ids_event.sensor)
      g_free(cmd->data.host_ids_event.sensor);
    if (cmd->data.host_ids_event.log)
      g_free(cmd->data.host_ids_event.log);

    if (cmd->data.host_ids_event.filename)
      g_free(cmd->data.host_ids_event.filename);
    if (cmd->data.host_ids_event.username)
      g_free(cmd->data.host_ids_event.username);
    if (cmd->data.host_ids_event.password)
      g_free(cmd->data.host_ids_event.password);
    if (cmd->data.host_ids_event.userdata1)
      g_free(cmd->data.host_ids_event.userdata1);
    if (cmd->data.host_ids_event.userdata2)
      g_free(cmd->data.host_ids_event.userdata2);
    if (cmd->data.host_ids_event.userdata3)
      g_free(cmd->data.host_ids_event.userdata3);
    if (cmd->data.host_ids_event.userdata4)
      g_free(cmd->data.host_ids_event.userdata4);
    if (cmd->data.host_ids_event.userdata5)
      g_free(cmd->data.host_ids_event.userdata5);
    if (cmd->data.host_ids_event.userdata6)
      g_free(cmd->data.host_ids_event.userdata6);
    if (cmd->data.host_ids_event.userdata7)
      g_free(cmd->data.host_ids_event.userdata7);
    if (cmd->data.host_ids_event.userdata8)
      g_free(cmd->data.host_ids_event.userdata8);
    if (cmd->data.host_ids_event.userdata9)
      g_free(cmd->data.host_ids_event.userdata9);

    break;

  default:
    break;
    }

  G_OBJECT_CLASS(parent_class)->finalize(gobject);
}

static void
sim_command_class_init(SimCommandClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS(class);

  parent_class = g_type_class_ref(G_TYPE_OBJECT);

  object_class->dispose = sim_command_impl_dispose;
  object_class->finalize = sim_command_impl_finalize;
}

static void
sim_command_instance_init(SimCommand *command)
{
  command->type = SIM_COMMAND_TYPE_NONE;
  command->id = 0;
  command->buffer = NULL;
}

/* Public Methods */

GType
sim_command_get_type(void)
{
  static GType object_type = 0;

  if (!object_type)
    {
      static const GTypeInfo type_info =
        { sizeof(SimCommandClass), NULL, NULL,
            (GClassInitFunc) sim_command_class_init, NULL, NULL, /* class data */
            sizeof(SimCommand), 0, /* number of pre-allocs */
            (GInstanceInitFunc) sim_command_instance_init, NULL /* value table */
        };

      g_type_init();

      object_type = g_type_register_static(G_TYPE_OBJECT, "SimCommand",
          &type_info, 0);
    }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new(void)
{
  SimCommand *command = NULL;

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  if (command)
    command->pf_event_scan = sim_command_event_scan;

  return command;
}

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new_from_buffer(const gchar *buffer, SimSession *session)
{
  SimCommand *command = NULL;
  SimSensor *sensor;
  g_return_val_if_fail(buffer, NULL);

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  g_return_val_if_fail(command, NULL);
  /* Check for current version */
  /* Default parsing functions, must be changed later*/
  command->pf_event_scan = sim_command_event_scan;
#if 0
  sensor = sim_container_get_sensor_by_ia (ossim.container, sim_session_get_ia(session));
  if (sensor != NULL && sim_sensor_get_agent_version (sensor)!=NULL )
    {
      /* Check if the sensor has a scan function*/
      gboolean (*pf_scan)(SimCommand *,GScanner *) = NULL;
      if ((pf_scan = sim_session_get_event_scan_fn (session)) == NULL)
        {
          int i = 0;
          while (agent_parsers_table[i].version!=NULL)
            {
              if (strcmp(sim_sensor_get_agent_version(sensor),agent_parsers_table[i].version) == 0)
                {
                  pf_scan = agent_parsers_table[i].pf;
                  sim_session_set_event_scan_fn (session, pf_scan);
                  g_log(G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"Changing parser to:%016x in sessin %016x inx:%d",pf_scan,session,i);
                  break;
                }
              i++;
            }
          assert(pf_scan!=NULL);
        }
      //g_log(G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"Changing parser to:%016x in sessin %016x",pf_scan,session);
      command->pf_event_scan = pf_scan;
    }
#endif

  if (!sim_command_scan(command, buffer, session))
    {
      if (SIM_IS_COMMAND (command))
        g_object_unref(command);
      return NULL;
    }

  command->buffer = g_strdup(buffer); //store the original buffer to be able to resend it later without any overcharge

  return command;
}

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new_from_type(SimCommandType type)
{
  SimCommand *command = NULL;

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  command->type = type;

  return command;
}

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new_from_rule(SimRule *rule)
{
  SimCommand *command;
  GString *str = NULL;
  GList *list = NULL;
  gint plugin_id;
  gint interval;
  gboolean absolute;
  SimConditionType condition;
  gchar *value;
  gchar *ip;

  g_return_val_if_fail(rule, NULL);
  g_return_val_if_fail(SIM_IS_RULE (rule), NULL);

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  command->type = SIM_COMMAND_TYPE_WATCH_RULE;

  str = g_string_new("watch-rule ");

  /* Plugin ID */
  plugin_id = sim_rule_get_plugin_id(rule);
  if (plugin_id > 0)
    {
      g_string_append_printf(str, "plugin_id=\"%d\" ", plugin_id);
    }

  /* Plugin SID */
  list = sim_rule_get_plugin_sids(rule);
  if (list)
    {
      gint plugin_sid = GPOINTER_TO_INT(list->data);
      g_string_append_printf(str, "plugin_sid=\"%d\" ", plugin_sid);
    }

  /* Condition */
  condition = sim_rule_get_condition(rule);
  if (condition != SIM_CONDITION_TYPE_NONE)
    {
      value = sim_condition_get_str_from_type(condition);
      g_string_append_printf(str, "condition=\"%s\" ", value);
      g_free(value);
    }

  /* Value */
  value = sim_rule_get_value(rule);
  if (value)
    {
      g_string_append_printf(str, "value=\"%s\" ", value);
    }

  /* Interval */
  interval = sim_rule_get_interval(rule);
  if (interval > 0)
    {
      g_string_append_printf(str, "interval=\"%d\" ", interval);
    }

  /* Absolute */
  absolute = sim_rule_get_absolute(rule);
  if (interval > 0)
    {
      if (absolute)
        str = g_string_append(str, "absolute=\"true\" ");
      else
        str = g_string_append(str, "absolute=\"false\" ");
    }
  else
    //if interval is 0, that implies that absolute is true, as we don't have any time to compare with it. We only are able to
    //know when the "value" as been reached (ie. when somebody has reached 100 network packets), but it can spend as much time as it wants.
    str = g_string_append(str, "absolute=\"true\" ");

  /* PORT FROM */
  list = sim_rule_get_src_ports(rule);
  if (list)
    g_string_append(str, "port_from=\"");
  while (list)
    {
      gint port = GPOINTER_TO_INT(list->data);

      g_string_append_printf(str, "%d", port);

      if (list->next)
        str = g_string_append(str, ",");
      else
        str = g_string_append(str, "\" ");

      list = list->next;
    }

  /* PORT TO  */
  list = sim_rule_get_dst_ports(rule);
  if (list)
    str = g_string_append(str, "port_to=\"");
  while (list)
    {
      gint port = GPOINTER_TO_INT(list->data);

      g_string_append_printf(str, "%d", port);

      if (list->next)
        str = g_string_append(str, ",");
      else
        str = g_string_append(str, "\" ");

      list = list->next;
    }

  /* SRC IAS */
  list = sim_rule_get_src_inets(rule);
  if (list)
    str = g_string_append(str, "from=\"");
  while (list)
    {
      SimInet *inet = (SimInet *) list->data;

      ip = sim_inet_ntop(inet);
      str = g_string_append(str, ip);
      g_free(ip);

      if (list->next)
        str = g_string_append(str, ",");
      else
        str = g_string_append(str, "\" ");

      list = list->next;
    }

  /* DST IAS */
  list = sim_rule_get_dst_inets(rule);
  if (list)
    str = g_string_append(str, "to=\"");
  while (list)
    {
      SimInet *inet = (SimInet *) list->data;

      ip = sim_inet_ntop(inet);
      str = g_string_append(str, ip);
      g_free(ip);

      if (list->next)
        str = g_string_append(str, ",");
      else
        str = g_string_append(str, "\" ");

      list = list->next;
    }

  str = g_string_append(str, "\n");

  command->data.watch_rule.str = g_string_free(str, FALSE); //free the GString object and returns the string

  return command;
}

GScanner *
sim_command_start_scanner()
{
  GScanner *scanner = NULL;
  gint i;

  /* Create scanner */
  scanner = g_scanner_new(NULL);

  /* Config scanner */
  scanner->config->cset_identifier_nth = (G_CSET_a_2_z ":._-0123456789" G_CSET_A_2_Z);
  scanner->config->case_sensitive = TRUE;
  scanner->config->symbol_2_token = TRUE;

  /* Added command symbols */
  for (i = 0; i < G_N_ELEMENTS(command_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_COMMAND,
        command_symbols[i].name, GINT_TO_POINTER(command_symbols[i].token));

  /* Added connect symbols */
  for (i = 0; i < G_N_ELEMENTS(connect_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_CONNECT,
        connect_symbols[i].name, GINT_TO_POINTER(connect_symbols[i].token));

  /* Added append plugin symbols */
  for (i = 0; i < G_N_ELEMENTS(session_append_plugin_symbols); i++)
    g_scanner_scope_add_symbol(scanner,
        SIM_COMMAND_SCOPE_SESSION_APPEND_PLUGIN,
        session_append_plugin_symbols[i].name, GINT_TO_POINTER(
            session_append_plugin_symbols[i].token));

  /* Added remove plugin symbols */
  for (i = 0; i < G_N_ELEMENTS(session_remove_plugin_symbols); i++)
    g_scanner_scope_add_symbol(scanner,
        SIM_COMMAND_SCOPE_SESSION_REMOVE_PLUGIN,
        session_remove_plugin_symbols[i].name, GINT_TO_POINTER(
            session_remove_plugin_symbols[i].token));

  /* Added server get sensors symbols */
  for (i = 0; i < G_N_ELEMENTS(server_get_sensors_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSORS,
        server_get_sensors_symbols[i].name, GINT_TO_POINTER(
            server_get_sensors_symbols[i].token));

  /* Added sensor symbols */
  for (i = 0; i < G_N_ELEMENTS(sensor_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SENSOR,
        sensor_symbols[i].name, GINT_TO_POINTER(sensor_symbols[i].token));

  /* Added server symbols */
  for (i = 0; i < G_N_ELEMENTS(server_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SERVER,
        server_symbols[i].name, GINT_TO_POINTER(server_symbols[i].token));

  /* Added server get servers symbols */
  for (i = 0; i < G_N_ELEMENTS(server_get_servers_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SERVER_GET_SERVERS,
        server_get_servers_symbols[i].name, GINT_TO_POINTER(
            server_get_servers_symbols[i].token));

  /* Added server get sensor plugins symbols */
  for (i = 0; i < G_N_ELEMENTS(server_get_sensor_plugins_symbols); i++)
    g_scanner_scope_add_symbol(scanner,
        SIM_COMMAND_SCOPE_SERVER_GET_SENSOR_PLUGINS,
        server_get_sensor_plugins_symbols[i].name, GINT_TO_POINTER(
            server_get_sensor_plugins_symbols[i].token));

  /* Added server set Data role symbols. Role is the role of each server ( */
  for (i = 0; i < G_N_ELEMENTS(server_set_data_role_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SERVER_SET_DATA_ROLE,
        server_set_data_role_symbols[i].name, GINT_TO_POINTER(
            server_set_data_role_symbols[i].token));

  /* Added sensor plugin symbols */
  for (i = 0; i < G_N_ELEMENTS(sensor_plugin_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN,
        sensor_plugin_symbols[i].name, GINT_TO_POINTER(
            sensor_plugin_symbols[i].token));

  /* Added sensor plugin start symbols */
  for (i = 0; i < G_N_ELEMENTS(sensor_plugin_start_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_START,
        sensor_plugin_start_symbols[i].name, GINT_TO_POINTER(
            sensor_plugin_start_symbols[i].token));

  /* Added sensor plugin stop symbols */
  for (i = 0; i < G_N_ELEMENTS(sensor_plugin_stop_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_STOP,
        sensor_plugin_stop_symbols[i].name, GINT_TO_POINTER(
            sensor_plugin_stop_symbols[i].token));

  /* Added sensor plugin enabled symbols */
  for (i = 0; i < G_N_ELEMENTS(sensor_plugin_enable_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_ENABLE,
        sensor_plugin_enable_symbols[i].name, GINT_TO_POINTER(
            sensor_plugin_enable_symbols[i].token));

  /* Added sensor plugin disabled symbols */
  for (i = 0; i < G_N_ELEMENTS(sensor_plugin_disable_symbols); i++)
    g_scanner_scope_add_symbol(scanner,
        SIM_COMMAND_SCOPE_SENSOR_PLUGIN_DISABLE,
        sensor_plugin_disable_symbols[i].name, GINT_TO_POINTER(
            sensor_plugin_disable_symbols[i].token));

  /* Added plugin start symbols */
  for (i = 0; i < G_N_ELEMENTS(plugin_state_started_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STARTED,
        plugin_state_started_symbols[i].name, GINT_TO_POINTER(
            plugin_state_started_symbols[i].token));

  /* Added plugin unknown symbols */
  for (i = 0; i < G_N_ELEMENTS(plugin_state_unknown_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_UNKNOWN,
        plugin_state_unknown_symbols[i].name, GINT_TO_POINTER(
            plugin_state_unknown_symbols[i].token));

  /* Added plugin stop symbols */
  for (i = 0; i < G_N_ELEMENTS(plugin_state_stopped_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STOPPED,
        plugin_state_stopped_symbols[i].name, GINT_TO_POINTER(
            plugin_state_stopped_symbols[i].token));

  /* Added plugin enabled symbols */
  for (i = 0; i < G_N_ELEMENTS(plugin_enabled_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_PLUGIN_ENABLED,
        plugin_enabled_symbols[i].name, GINT_TO_POINTER(
            plugin_enabled_symbols[i].token));

  /* Added plugin disabled symbols */
  for (i = 0; i < G_N_ELEMENTS(plugin_disabled_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_PLUGIN_DISABLED,
        plugin_disabled_symbols[i].name, GINT_TO_POINTER(
            plugin_disabled_symbols[i].token));

  /* Added event symbols */
  for (i = 0; i < G_N_ELEMENTS(event_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_EVENT,
        event_symbols[i].name, GINT_TO_POINTER(event_symbols[i].token));

  /* Added reload plugins symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_plugins_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_PLUGINS,
        reload_plugins_symbols[i].name, GINT_TO_POINTER(
            reload_plugins_symbols[i].token));

  /* Added reload sensors symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_sensors_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_SENSORS,
        reload_sensors_symbols[i].name, GINT_TO_POINTER(
            reload_sensors_symbols[i].token));

  /* Added reload hosts symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_hosts_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_HOSTS,
        reload_hosts_symbols[i].name, GINT_TO_POINTER(
            reload_hosts_symbols[i].token));

  /* Added reload nets symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_nets_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_NETS,
        reload_nets_symbols[i].name, GINT_TO_POINTER(
            reload_nets_symbols[i].token));

  /* Added reload policies symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_policies_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_POLICIES,
        reload_policies_symbols[i].name, GINT_TO_POINTER(
            reload_policies_symbols[i].token));

  /* Added reload directives symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_directives_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_DIRECTIVES,
        reload_directives_symbols[i].name, GINT_TO_POINTER(
            reload_directives_symbols[i].token));

  /* Added reload all symbols */
  for (i = 0; i < G_N_ELEMENTS(reload_all_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_RELOAD_ALL,
        reload_all_symbols[i].name,
        GINT_TO_POINTER(reload_all_symbols[i].token));

  /* Added host os event symbols */
  for (i = 0; i < G_N_ELEMENTS(host_os_event_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_HOST_OS_EVENT,
        host_os_event_symbols[i].name, GINT_TO_POINTER(
            host_os_event_symbols[i].token));

  /* Added host mac event symbols */
  for (i = 0; i < G_N_ELEMENTS(host_mac_event_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_HOST_MAC_EVENT,
        host_mac_event_symbols[i].name, GINT_TO_POINTER(
            host_mac_event_symbols[i].token));

  /* Add host service event symbols */
  for (i = 0; i < G_N_ELEMENTS(host_service_event_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_HOST_SERVICE_EVENT,
        host_service_event_symbols[i].name, GINT_TO_POINTER(
            host_service_event_symbols[i].token));

  /* Add HIDS symbols */
  for (i = 0; i < G_N_ELEMENTS(host_ids_event_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_HOST_IDS_EVENT,
        host_ids_event_symbols[i].name, GINT_TO_POINTER(
            host_ids_event_symbols[i].token));

  /* Add OK symbols */
  for (i = 0; i < G_N_ELEMENTS(ok_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_OK,
        ok_symbols[i].name, GINT_TO_POINTER(ok_symbols[i].token));

  /* Add Database Query symbols (remote DB) */
  for (i = 0; i < G_N_ELEMENTS(database_query_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_DATABASE_QUERY,
        database_query_symbols[i].name, GINT_TO_POINTER(
            database_query_symbols[i].token));

  /* Add Database Answer symbols (remote DB) */
  for (i = 0; i < G_N_ELEMENTS(database_answer_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_DATABASE_ANSWER,
        database_answer_symbols[i].name, GINT_TO_POINTER(
            database_answer_symbols[i].token));
  /* Add snort event symbols */
  for (i = 0; i < G_N_ELEMENTS(snort_event_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT,
        snort_event_symbols[i].name, GINT_TO_POINTER(
            snort_event_symbols[i].token));
  /* Add snort data symbols*/
  for (i = 0; i < G_N_ELEMENTS(snort_event_data_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_DATA,
        snort_event_data_symbols[i].name, GINT_TO_POINTER(
            snort_event_data_symbols[i].token));
  /* Add raw  symbools */
  for (i = 0; i < G_N_ELEMENTS(snort_event_packet_raw_symbols); i++)
    g_scanner_scope_add_symbol(scanner,
        SIM_COMMAND_SCOPE_SNORT_EVENT_PACKET_RAW,
        snort_event_packet_raw_symbols[i].name, GINT_TO_POINTER(
            snort_event_packet_raw_symbols[i].token));

  /* Add agent-date symbols*/
  for (i = 0; i < G_N_ELEMENTS(agent_date_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_AGENT_DATE,
        agent_date_symbols[i].name,
        GINT_TO_POINTER(agent_date_symbols[i].token));

  /* Add ip symbools */
  for (i = 0; i < G_N_ELEMENTS(snort_event_packet_ip_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_IP,
        snort_event_packet_ip_symbols[i].name, GINT_TO_POINTER(
            snort_event_packet_ip_symbols[i].token));
  /* Add icmp symbols */
  for (i = 0; i < G_N_ELEMENTS(snort_event_packet_icmp_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_ICMP,
        snort_event_packet_icmp_symbols[i].name, GINT_TO_POINTER(
            snort_event_packet_icmp_symbols[i].token));
  /* Add udp symbols */
  for (i = 0; i < G_N_ELEMENTS(snort_event_packet_udp_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_UDP,
        snort_event_packet_udp_symbols[i].name, GINT_TO_POINTER(
            snort_event_packet_udp_symbols[i].token));
  /* Add tcp symbols */
  for (i = 0; i < G_N_ELEMENTS(snort_event_packet_tcp_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_TCP,
        snort_event_packet_tcp_symbols[i].name, GINT_TO_POINTER(
            snort_event_packet_tcp_symbols[i].token));
  /* Add snort data symbols */
  for (i = 0; i < G_N_ELEMENTS(snort_event_data_symbols); i++)
    g_scanner_scope_add_symbol(scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_TCP,
        snort_event_data_symbols[i].name, GINT_TO_POINTER(
            snort_event_data_symbols[i].token));

  return scanner;

}

/*
 *
 * If the command analyzed has some field incorrect, the command will be rejected.
 * The 'command' parameter is filled inside this function and not returned, outside
 * this function you'll be able to access to it directly.
 */
static gboolean
sim_command_scan(SimCommand *command, const gchar *buffer, SimSession *session)
{
  GScanner *scanner;
  gboolean OK = TRUE; //if a problem appears in the command scanning, we'll return.

  gchar *aux;
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(buffer != NULL);
  SimSensor *sensor = NULL;
  gchar *ip_st;
  gboolean
  (*pf_scan)(SimCommand*, GScanner*) = NULL;
  if ((scanner = (GScanner*) g_private_get(privScanner)) == NULL)
    {

      scanner = sim_command_start_scanner();
      g_private_set(privScanner, scanner);
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Scanner: %p, thread: %p",
          scanner, g_thread_self());
    }

  /* Sets input text */
  g_scanner_input_text(scanner, buffer, strlen(buffer));
  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_COMMAND);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_CONNECT:
        if (!sim_command_connect_scan(command, scanner))
          OK = FALSE;
        break;

        /*Commands from frameworkd OR Master servers */

      case SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS:
        if (!sim_command_server_get_sensors_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS:
        if (!sim_command_server_get_servers_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SERVER_GET_SENSOR_PLUGINS:
        if (!sim_command_server_get_sensor_plugins_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SERVER_SET_DATA_ROLE:
        if (!sim_command_server_set_data_role_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN:
        if (!sim_command_sensor_plugin_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_START:
        if (!sim_command_sensor_plugin_start_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_STOP:
        if (!sim_command_sensor_plugin_stop_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_ENABLE:
        if (!sim_command_sensor_plugin_enable_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_DISABLE:
        if (!sim_command_sensor_plugin_disable_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_PLUGINS:
        if (!sim_command_reload_plugins_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_SENSORS:
        if (!sim_command_reload_sensors_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_HOSTS:
        if (!sim_command_reload_hosts_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_NETS:
        if (!sim_command_reload_nets_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_POLICIES:
        if (!sim_command_reload_policies_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_DIRECTIVES:
        if (!sim_command_reload_directives_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_RELOAD_ALL:
        if (!sim_command_reload_all_scan(command, scanner))
          OK = FALSE;
        break;

        /*Commands from Sensors*/

      case SIM_COMMAND_SYMBOL_SESSION_APPEND_PLUGIN:
        if (!sim_command_session_append_plugin_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SESSION_REMOVE_PLUGIN:
        if (!sim_command_session_remove_plugin_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_STATE_STARTED:
        if (!sim_command_plugin_state_started_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_STATE_UNKNOWN:
        if (!sim_command_plugin_state_unknown_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_STATE_STOPPED:
        if (!sim_command_plugin_state_stopped_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_ENABLED:
        if (!sim_command_plugin_enabled_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_DISABLED:
        if (!sim_command_plugin_disabled_scan(command, scanner))
          OK = FALSE;
        break;

        /*Commands from sensors or Children Servers*/

      case SIM_COMMAND_SYMBOL_EVENT:
#if 0
        ip_st = gnet_inetaddr_get_canonical_name (sim_session_get_ia (session));
        g_log (G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"Event from session IP:%s",ip_st);
        if (ip_st)
        g_free (ip_st);
#endif
#if 0
        sensor = sim_container_get_sensor_by_ia(ossim.container,
            sim_session_get_ia(session));
        if (sensor != NULL && sim_sensor_get_agent_version(sensor) != NULL)
          {
            /* Check if the sensor has a scan function*/
            gboolean
            (*pf_scan)(SimCommand *, GScanner *) = NULL;
            if ((pf_scan = sim_session_get_event_scan_fn(session)) == NULL)
              {
                int i = 0;
                while (agent_parsers_table[i].version != NULL)
                  {
                    if (strcmp(sim_sensor_get_agent_version(sensor),
                            agent_parsers_table[i].version) == 0)
                      {
                        pf_scan = agent_parsers_table[i].pf;
                        sim_session_set_event_scan_fn(session, pf_scan);
                        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                            "Changing parser to:%016x in sessin %016x inx:%d",
                            pf_scan, session, i);
                        break;
                      }
                    i++;
                  }
                assert(pf_scan!=NULL);
              }
            //g_log(G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"Changing parser to:%016x in sessin %016x",pf_scan,session);
            command->pf_event_scan = pf_scan;
          }

        assert(command->pf_event_scan!=NULL);
#endif 0
        pf_scan = sim_session_get_event_scan_fn(session);
        if (!pf_scan(command, scanner))
          OK = FALSE;
        break;
#if 0
        if (!sim_command_event_scan (command, scanner))
        OK=FALSE;
        break;
#endif
      case SIM_COMMAND_SYMBOL_HOST_OS_EVENT:
        if (!sim_command_host_os_event_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_HOST_MAC_EVENT:
        if (!sim_command_host_mac_event_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_HOST_SERVICE_EVENT:
        if (!sim_command_host_service_event_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_HOST_IDS_EVENT:
        if (!sim_command_host_ids_event_scan(command, scanner))
          OK = FALSE;
        break;

        /*Commands from Children Servers; answer to a previous query from this (or an upper) server */
      case SIM_COMMAND_SYMBOL_SENSOR: //answer to SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS query made in this server to a children server.
        if (!sim_command_sensor_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SERVER: //answer to SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS query made in this server to a children server.
        if (!sim_command_server_scan(command, scanner))
          OK = FALSE;
        break;

      case SIM_COMMAND_SYMBOL_OK:
        if (!sim_command_ok_scan(command, scanner))
          OK = FALSE;
        //					  command->type = SIM_COMMAND_TYPE_OK;
        break;
      case SIM_COMMAND_SYMBOL_ERROR:
        command->type = SIM_COMMAND_TYPE_ERROR;
        break;
      case SIM_COMMAND_SYMBOL_DATABASE_QUERY:
        if (!sim_command_database_query_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_DATABASE_ANSWER:
        if (!sim_command_database_answer_scan(command, scanner))
          OK = FALSE;
        break;
      case SIM_COMMAND_SYMBOL_SNORT_EVENT:
        if (!sim_command_snort_event_scan(command, scanner))
          OK = FALSE;
        return OK; /* all the process is in sim_command_snort_event_scan */
        break;
      case SIM_COMMAND_SYMBOL_AGENT_DATE:
        if (!sim_command_agent_date_scan(command, scanner))
          OK = FALSE;
        break;
      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(
            G_LOG_DOMAIN,
            G_LOG_LEVEL_DEBUG,
            "sim_command_scan: error command unknown; Buffer from command: [%s]",
            buffer);
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  //  g_scanner_destroy (scanner);
  return OK; //well... ok... or not!
}

/*
 *
 *
 *
 */
static gboolean
sim_command_connect_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_CONNECT;
  command->data.connect.username = NULL;
  command->data.connect.password = NULL;
  command->data.connect.hostname = NULL;
  command->data.connect.version = NULL;
  command->data.connect.type = SIM_SESSION_TYPE_NONE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_CONNECT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: connect event incorrect. Please check the symbol_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_USERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.connect.username = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PASSWORD:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.connect.password = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_HOSTNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.connect.hostname = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (!g_ascii_strcasecmp(scanner->value.v_string, "SERVER"))
          {
            command->data.connect.type = SIM_SESSION_TYPE_SERVER_DOWN;
          }
        else if (!g_ascii_strcasecmp(scanner->value.v_string, "SENSOR"))
          {
            command->data.connect.type = SIM_SESSION_TYPE_SENSOR;
          }
        else if (!g_ascii_strcasecmp(scanner->value.v_string, "WEB"))
          {
            command->data.connect.type = SIM_SESSION_TYPE_WEB;
          }
        else
          {
            command->data.connect.type = SIM_SESSION_TYPE_NONE;
          }
        break;

      case SIM_COMMAND_SYMBOL_AGENT_VERSION:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */
        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.connect.version = g_strdup(scanner->value.v_string);
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_connect_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_session_append_plugin_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN;
  command->data.session_append_plugin.id = 0;
  command->data.session_append_plugin.type = SIM_PLUGIN_TYPE_NONE;
  command->data.session_append_plugin.name = NULL;
  command->data.session_append_plugin.state = 0;
  command->data.session_append_plugin.enabled = FALSE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SESSION_APPEND_PLUGIN);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: append plugin event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.session_append_plugin.id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: append plugin event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;
      case SIM_COMMAND_SYMBOL_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.session_append_plugin.type = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: append plugin event incorrect. Please check the type issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_NAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.session_append_plugin.name = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_STATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (!g_ascii_strcasecmp(scanner->value.v_string, "start"))
          command->data.session_append_plugin.state = 1;
        else if (!g_ascii_strcasecmp(scanner->value.v_string, "stop"))
          command->data.session_remove_plugin.state = 2;
        break;

      case SIM_COMMAND_SYMBOL_ENABLED:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.session_append_plugin.enabled = TRUE;
        else if (!g_ascii_strcasecmp(scanner->value.v_string, "false"))
          command->data.session_remove_plugin.enabled = FALSE;
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_session_append_plugin_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_session_remove_plugin_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN;
  command->data.session_remove_plugin.id = 0;
  command->data.session_remove_plugin.type = SIM_PLUGIN_TYPE_NONE;
  command->data.session_remove_plugin.name = NULL;
  command->data.session_remove_plugin.state = 0;
  command->data.session_remove_plugin.enabled = FALSE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SESSION_REMOVE_PLUGIN);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Remove plugin event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.session_remove_plugin.id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Remove plugin event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.session_remove_plugin.type = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Remove plugin event incorrect. Please check the type issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_NAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        command->data.session_remove_plugin.name = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_STATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (!g_ascii_strcasecmp(scanner->value.v_string, "start"))
          command->data.session_remove_plugin.state = 1;
        else if (!g_ascii_strcasecmp(scanner->value.v_string, "stop"))
          command->data.session_remove_plugin.state = 2;
        break;

      case SIM_COMMAND_SYMBOL_ENABLED:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.session_remove_plugin.enabled = TRUE;
        else if (!g_ascii_strcasecmp(scanner->value.v_string, "false"))
          command->data.session_remove_plugin.enabled = FALSE;
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_session_remove_plugin_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_server_get_sensors_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SERVER_GET_SENSORS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSORS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: get sensors event incorrect. Please check the id issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.server_get_sensors.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: get sensors; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_server_get_sensors_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_command_server_get_sensors_scan: id: %d", command->id);
  return TRUE;
}

/*
 *
 */
static gboolean
sim_command_server_get_servers_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SERVER_GET_SERVERS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SERVER_GET_SERVERS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: get servers event incorrect. Please check the id issued from the frameworkd or the master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.server_get_servers.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: get servers; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_server_get_servers_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_command_server_get_servers_scan: id: %d", command->id);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_server_get_sensor_plugins_scan(SimCommand *command,
    GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSOR_PLUGINS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: get sensor plugin event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.server_get_sensor_plugins.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: get sensor plugins; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_server_get_sensor_plugins_scan: error symbol unknown");
        return FALSE;
        break;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_server_set_data_role_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE;

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_command_server_set_data_role_scan command->type: %d", command->type);
  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SERVER_SET_DATA_ROLE);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: set data role event incorrect. Please check the id issued from the server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.server_set_data_role.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: set data role event incorrect. Please check the host issued from the server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ROLE_CORRELATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.server_set_data_role.correlate = TRUE;
        else
          command->data.server_set_data_role.correlate = FALSE;
        break;

      case SIM_COMMAND_SYMBOL_ROLE_CROSS_CORRELATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.server_set_data_role.cross_correlate = TRUE;
        else
          command->data.server_set_data_role.cross_correlate = FALSE;
        break;

      case SIM_COMMAND_SYMBOL_ROLE_STORE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.server_set_data_role.store = TRUE;
        else
          command->data.server_set_data_role.store = FALSE;
        break;

      case SIM_COMMAND_SYMBOL_ROLE_QUALIFY:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.server_set_data_role.qualify = TRUE;
        else
          command->data.server_set_data_role.qualify = FALSE;
        break;

      case SIM_COMMAND_SYMBOL_ROLE_RESEND_ALARM:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.server_set_data_role.resend_alarm = TRUE;
        else
          command->data.server_set_data_role.resend_alarm = FALSE;
        break;

      case SIM_COMMAND_SYMBOL_ROLE_RESEND_EVENT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.server_set_data_role.resend_event = TRUE;
        else
          command->data.server_set_data_role.resend_event = FALSE;
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_server_set_data_role_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_command_server_set_data_role_scan: id: %d", command->id);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN;
  command->data.sensor_plugin.sensor = NULL;
  command->data.sensor_plugin.plugin_id = 0;
  command->data.sensor_plugin.state = 0;
  command->data.sensor_plugin.enabled = FALSE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.sensor_plugin.sensor
              = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: Sensor plugin event incorrect. Please check the sensor ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.sensor_plugin.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_STATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (g_ascii_strcasecmp(scanner->value.v_string, "start"))
          command->data.sensor_plugin.state = 1;
        else if (g_ascii_strcasecmp(scanner->value.v_string, "stop"))
          command->data.sensor_plugin.state = 2;
        else if (g_ascii_strcasecmp(scanner->value.v_string, "unknown"))
          command->data.sensor_plugin.state = 3;
        break;

      case SIM_COMMAND_SYMBOL_ENABLED:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.sensor_plugin.enabled = TRUE;
        else if (g_ascii_strcasecmp(scanner->value.v_string, "false"))
          command->data.sensor_plugin.enabled = FALSE;

        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_sensor_plugin_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_start_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_START;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_START);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin start event incorrect. Please check the id issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.sensor_plugin_start.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: sensor plugin start; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.sensor_plugin_start.sensor = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Sensor plugin start. Please check the sensor ip issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.sensor_plugin_start.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin start event incorrect. Please check the plugin_id issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_sensor_plugin_start_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_stop_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_STOP);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin stop event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.sensor_plugin_stop.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: sensor plugin stop; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.sensor_plugin_stop.sensor = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Sensor plugin stop. Please check the sensor ip issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.sensor_plugin_stop.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_sensor_plugin_stop_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_enable_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_ENABLE);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin enable event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.sensor_plugin_enable.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: sensor plugin enable; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.sensor_plugin_enable.sensor = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Sensor plugin enable. Please check the sensor ip issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }

        command->data.sensor_plugin_enable.sensor = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.sensor_plugin_enable.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin enable event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_sensor_plugin_enable_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_disable_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_DISABLE);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin disable event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.sensor_plugin_disable.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: sensor plugin disable; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.sensor_plugin_disable.sensor = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Sensor plugin disable. Please check the sensor ip issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.sensor_plugin_disable.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin disable event incorrect. Please check the plugin_id issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_sensor_plugin_disable_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_state_started_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STARTED);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin start event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.plugin_state_started.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin start event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_plugin_start_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_state_unknown_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_PLUGIN_STATE_UNKNOWN;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_UNKNOWN);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin unknown event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.plugin_state_unknown.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin unknown event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_plugin_unknown_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_state_stopped_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_PLUGIN_STATE_STOPPED;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STOPPED);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin stop event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.plugin_state_stopped.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin stop event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_plugin_stop_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_enabled_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_PLUGIN_ENABLED;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_PLUGIN_ENABLED);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin enabled event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.plugin_enabled.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin enabled event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_plugin_enabled_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_disabled_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_PLUGIN_DISABLED;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_PLUGIN_DISABLED);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin disabled event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.plugin_disabled.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor plugin disabled event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_plugin_disabled_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}
/*
 * BASE64 VERSION of sim_command_event_scan
 */
static gboolean
sim_command_event_scan_base64(SimCommand *command, GScanner *scanner)
{
  struct tm tm; //needed to check the time parameter.
  gsize base64len;
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_EVENT;
  command->data.event.type = NULL;
  command->data.event.id = 0;
  command->data.event.date = 0;
  command->data.event.date_str = NULL; //be carefull, if you insert some event without this parameter, you'll get unix date: 1970/01/01
  command->data.event.sensor = NULL;
  command->data.event.interface = NULL;

  command->data.event.plugin_id = 0;
  command->data.event.plugin_sid = 0;

  command->data.event.protocol = NULL;
  command->data.event.src_ip = NULL;
  command->data.event.src_port = 0;
  command->data.event.dst_ip = NULL;
  command->data.event.dst_port = 0;

  command->data.event.condition = NULL;
  command->data.event.value = NULL;
  command->data.event.interval = 0;

  command->data.event.data = NULL;
  command->data.event.snort_sid = 0;
  command->data.event.snort_cid = 0;

  command->data.event.priority = 0;
  command->data.event.reliability = 0;
  command->data.event.asset_src = 2;
  command->data.event.asset_dst = 2;
  command->data.event.risk_a = 0;
  command->data.event.risk_c = 0;
  command->data.event.alarm = FALSE;
  command->data.event.event = NULL;

  command->data.event.filename = NULL;
  command->data.event.username = NULL;
  command->data.event.password = NULL;
  command->data.event.userdata1 = NULL;
  command->data.event.userdata2 = NULL;
  command->data.event.userdata3 = NULL;
  command->data.event.userdata4 = NULL;
  command->data.event.userdata5 = NULL;
  command->data.event.userdata6 = NULL;
  command->data.event.userdata7 = NULL;
  command->data.event.userdata8 = NULL;
  command->data.event.userdata9 = NULL;
  command->data.event.is_prioritized = FALSE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_EVENT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.type = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the id issued from the remote server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.plugin_id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.plugin_sid = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the plugin_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.event.date = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the date issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.event.date_str = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.event.tzone = g_ascii_strtod(scanner->value.v_string,
              (gchar**) NULL);
        else
          {
            g_message(
                "Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.event.sensor = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: (SIM_COMMAND_SYMBOL_SENSOR) event incorrect. Please check the sensor issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_INTERFACE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.interface = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PRIORITY:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.priority = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the priority issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PROTOCOL:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.protocol = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SRC_IP:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.event.src_ip = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the src ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SRC_PORT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.src_port = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the src_port issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DST_IP:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.event.dst_ip = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the dst ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DST_PORT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.dst_port = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the dst_port issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_CONDITION:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.condition = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_VALUE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.value = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_INTERVAL:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.interval = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the interval issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DATA:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        //command->data.event.data = g_strdup (scanner->value.v_string);
        command->data.event.data = g_base64_decode(scanner->value.v_string,
            &base64len);
        break;

      case SIM_COMMAND_SYMBOL_LOG:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.log = g_base64_decode(scanner->value.v_string,
            &base64len);
        break;

      case SIM_COMMAND_SYMBOL_SNORT_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.snort_sid = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the snort_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SNORT_CID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.snort_cid = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the snort_cid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ASSET_SRC:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.asset_src = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the asset src issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ASSET_DST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.asset_dst = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the asset dst issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_RISK_A:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1)) //this can be float...
          command->data.event.risk_a = strtod(scanner->value.v_string,
              (char **) NULL);
        else
          {
            g_message(
                "Error: event incorrect. Please check the Risk_A issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_RISK_C:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1)) //this can be float
          command->data.event.risk_c = strtod(scanner->value.v_string,
              (char **) NULL);
        else
          {
            g_message(
                "Error: event incorrect. Please check the Risk_C issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_RELIABILITY:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.reliability = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the reliability issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_ALARM:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "TRUE"))
          command->data.event.alarm = TRUE;
        break;

      case SIM_COMMAND_SYMBOL_FILENAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.filename = g_base64_decode(scanner->value.v_string,
            &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.username = g_base64_decode(scanner->value.v_string,
            &base64len);
        break;

      case SIM_COMMAND_SYMBOL_PASSWORD:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.password = g_base64_decode(scanner->value.v_string,
            &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA1:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata1 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA2:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata2 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA3:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata3 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA4:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata4 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA5:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata5 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA6:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata6 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA7:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata7 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA8:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata8 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA9:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata9 = g_base64_decode(
            scanner->value.v_string, &base64len);
        break;

        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.event.is_prioritized = TRUE;
        else
          command->data.event.is_prioritized = FALSE;
        break;
      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(
            G_LOG_DOMAIN,
            G_LOG_LEVEL_DEBUG,
            "sim_command_event_scan: error symbol unknown; Symbol number:%d. Event Rejected.",
            scanner->token);
        return FALSE; //we will return with the first rare token
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_event_scan(SimCommand *command, GScanner *scanner)
{
  struct tm tm; //needed to check the time parameter.

  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_EVENT;
  command->data.event.type = NULL;
  command->data.event.id = 0;
  command->data.event.date = 0;
  command->data.event.date_str = NULL; //be carefull, if you insert some event without this parameter, you'll get unix date: 1970/01/01
  command->data.event.sensor = NULL;
  command->data.event.device = NULL;
  command->data.event.interface = NULL;

  command->data.event.plugin_id = 0;
  command->data.event.plugin_sid = 0;

  command->data.event.protocol = NULL;
  command->data.event.src_ip = NULL;
  command->data.event.src_port = 0;
  command->data.event.dst_ip = NULL;
  command->data.event.dst_port = 0;

  command->data.event.condition = NULL;
  command->data.event.value = NULL;
  command->data.event.interval = 0;

  command->data.event.data = NULL;
  command->data.event.snort_sid = 0;
  command->data.event.snort_cid = 0;

  command->data.event.priority = 0;
  command->data.event.reliability = 0;
  command->data.event.asset_src = 2;
  command->data.event.asset_dst = 2;
  command->data.event.risk_a = 0;
  command->data.event.risk_c = 0;
  command->data.event.alarm = FALSE;
  command->data.event.event = NULL;

  command->data.event.filename = NULL;
  command->data.event.username = NULL;
  command->data.event.password = NULL;
  command->data.event.userdata1 = NULL;
  command->data.event.userdata2 = NULL;
  command->data.event.userdata3 = NULL;
  command->data.event.userdata4 = NULL;
  command->data.event.userdata5 = NULL;
  command->data.event.userdata6 = NULL;
  command->data.event.userdata7 = NULL;
  command->data.event.userdata8 = NULL;
  command->data.event.userdata9 = NULL;
  command->data.event.is_prioritized = FALSE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_EVENT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.type = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the id issued from the remote server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.plugin_id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.plugin_sid = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the plugin_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.event.date = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the date issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.event.date_str = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.event.tzone = g_ascii_strtod(scanner->value.v_string,
              (gchar**) NULL);
        else
          {
            g_message(
                "Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          {
            command->data.event.sensor = g_strdup(scanner->value.v_string);
          }
        else
          {
            g_message(
                "Error:() canonical addr event incorrect. Please check the sensor issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DEVICE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.event.device = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the device issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_INTERFACE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.interface = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PRIORITY:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.priority = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the priority issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PROTOCOL:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.protocol = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SRC_IP:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.event.src_ip = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the src ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SRC_PORT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.src_port = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the src_port issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DST_IP:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.event.dst_ip = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the dst ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DST_PORT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.dst_port = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the dst_port issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_CONDITION:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.condition = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_VALUE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.value = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_INTERVAL:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.interval = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the interval issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DATA:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.data = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_LOG:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.log = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SNORT_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.snort_sid = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the snort_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SNORT_CID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.snort_cid = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the snort_cid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ASSET_SRC:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.asset_src = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the asset src issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ASSET_DST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.asset_dst = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the asset dst issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_RISK_A:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1)) //this can be float...
          command->data.event.risk_a = strtod(scanner->value.v_string,
              (char **) NULL);
        else
          {
            g_message(
                "Error: event incorrect. Please check the Risk_A issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_RISK_C:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1)) //this can be float
          command->data.event.risk_c = strtod(scanner->value.v_string,
              (char **) NULL);
        else
          {
            g_message(
                "Error: event incorrect. Please check the Risk_C issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_RELIABILITY:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.event.reliability = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the reliability issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_ALARM:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "TRUE"))
          command->data.event.alarm = TRUE;
        break;

      case SIM_COMMAND_SYMBOL_FILENAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.filename = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.username = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PASSWORD:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.password = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA1:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata1 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA2:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata2 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA3:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata3 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA4:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata4 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA5:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata5 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA6:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata6 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA7:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata7 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA8:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata8 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA9:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.event.userdata9 = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_IS_PRIORITIZED:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (!g_ascii_strcasecmp(scanner->value.v_string, "true"))
          command->data.event.is_prioritized = TRUE;
        else
          command->data.event.is_prioritized = FALSE;
        break;
      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(
            G_LOG_DOMAIN,
            G_LOG_LEVEL_DEBUG,
            "sim_command_event_scan: error symbol unknown; Symbol number:%d. Event Rejected.",
            scanner->token);
        return FALSE; //we will return with the first rare token
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_plugins_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_PLUGINS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_PLUGINS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload plugins event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_plugins.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload plugins; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;

        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_plugins_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_sensors_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_SENSORS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_SENSORS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload sensors event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_sensors.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload sensors; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_sensors_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_hosts_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_HOSTS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_HOSTS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload hosts event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_hosts.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload_hosts; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_host_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_nets_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_NETS;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_NETS);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload inets event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_nets.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload nets; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_nets_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_policies_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_POLICIES;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_POLICIES);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload policies event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_policies.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload policies; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_policies_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_directives_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_DIRECTIVES;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_DIRECTIVES);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload directives event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_directives.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload directives; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_directives_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_all_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_RELOAD_ALL;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_RELOAD_ALL);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Reload all event incorrect. Please check the id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.reload_all.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: reload all; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_reload_all_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_host_os_event_scan(SimCommand *command, GScanner *scanner)
{
  struct tm tm; //needed to check the time parameter.

  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_HOST_OS_EVENT;
  command->data.host_os_event.date = 0;
  command->data.host_os_event.date_str = NULL;
  command->data.host_os_event.id = 0;
  command->data.host_os_event.host = NULL;
  command->data.host_os_event.os = NULL;
  command->data.host_os_event.sensor = NULL;
  command->data.host_os_event.interface = NULL;
  command->data.host_os_event.plugin_id = 0;
  command->data.host_os_event.plugin_sid = 0;
  command->data.host_os_event.log = NULL;
  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_HOST_OS_EVENT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_os_event.date = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host OS event incorrect. Please check the date issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_os_event.date_str
            = g_strdup(scanner->value.v_string);

        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_os_event.tzone = g_ascii_strtod(
              scanner->value.v_string, (gchar**) NULL);
        else
          {
            g_message(
                "Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_os_event.id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host_OS event incorrect. Please check the id issued from the remote server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_HOST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.host_os_event.host = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: Host OS event incorrect. Please check the host ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_OS:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_os_event.os = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_os_event.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host_OS event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.host_os_event.sensor
              = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: Host OS event incorrect. Please check the sensor issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_INTERFACE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_os_event.interface = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_os_event.plugin_sid = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host_OS event incorrect. Please check the plugin_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_LOG:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_os_event.log = g_strdup(scanner->value.v_string);
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_host_os_event_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_host_mac_event_scan(SimCommand *command, GScanner *scanner)
{
  struct tm tm; //needed to check the date parameter.

  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_HOST_MAC_EVENT;
  command->data.host_mac_event.date = 0;
  command->data.host_mac_event.date_str = NULL;
  command->data.host_mac_event.tzone = 0;
  command->data.host_mac_event.id = 0;
  command->data.host_mac_event.host = NULL;
  command->data.host_mac_event.mac = NULL;
  command->data.host_mac_event.vendor = NULL;
  command->data.host_mac_event.sensor = NULL;
  command->data.host_mac_event.interface = NULL;
  command->data.host_mac_event.plugin_id = 0;
  command->data.host_mac_event.plugin_sid = 0;
  command->data.host_mac_event.log = NULL;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_HOST_MAC_EVENT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_mac_event.date = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host mac event incorrect. Please check the date issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_mac_event.date_str = g_strdup(
            scanner->value.v_string);

        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_mac_event.tzone = g_ascii_strtod(
              scanner->value.v_string, (gchar**) NULL);
        else
          {
            g_message(
                "Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_mac_event.id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host MAC event incorrect. Please check the id issued from the remote server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_HOST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.host_mac_event.host = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: Host MAC event incorrect. Please check the host ip issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_MAC:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_mac_event.mac = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_VENDOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_mac_event.vendor = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.host_mac_event.sensor = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Host MAC event incorrect. Please check the sensor issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_mac_event.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host_MAC event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_mac_event.plugin_sid = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host_MAC event incorrect. Please check the plugin_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_INTERFACE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_mac_event.interface = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_LOG:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_mac_event.log = g_strdup(scanner->value.v_string);
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;

        g_message("sim_command_host_mac_event_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 * Host service new
 *
 */
static gboolean
sim_command_host_service_event_scan(SimCommand *command, GScanner *scanner)
{
  struct tm tm; //needed to check the date parameter.

  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_HOST_SERVICE_EVENT;
  command->data.host_service_event.date = 0;
  command->data.host_service_event.date_str = NULL;
  command->data.host_service_event.tzone = 0;
  command->data.host_service_event.id = 0;
  command->data.host_service_event.host = NULL;
  command->data.host_service_event.port = 0;
  command->data.host_service_event.protocol = 0;
  command->data.host_service_event.service = NULL;
  command->data.host_service_event.application = NULL;
  command->data.host_service_event.sensor = NULL;
  command->data.host_service_event.interface = NULL;
  command->data.host_service_event.plugin_id = 0;
  command->data.host_service_event.plugin_sid = 0;
  command->data.host_service_event.log = NULL;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_HOST_SERVICE_EVENT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_service_event.date = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host Service event incorrect. Please check the date issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;
      case SIM_COMMAND_SYMBOL_DATE_STRING:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_service_event.date_str = g_strdup(
            scanner->value.v_string);

        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_service_event.tzone = g_ascii_strtod(
              scanner->value.v_string, (gchar**) NULL);
        else
          {
            g_message(
                "Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_service_event.id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Service event incorrect. Please check the id issued from the remote server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_HOST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.host_service_event.host = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the host issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PORT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_service_event.port = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host service event incorrect. Please check the port issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PROTOCOL:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_service_event.protocol = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: host service event incorrect. Please check the protocol issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_SERVICE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_service_event.service = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_APPLICATION:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_service_event.application = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (gnet_inetaddr_is_canonical(scanner->value.v_string))
          command->data.host_service_event.sensor = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: event incorrect. Please check the sensor issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_INTERFACE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_service_event.interface = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_LOG:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_service_event.log
            = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_service_event.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host_service event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_service_event.plugin_sid = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: host service event incorrect. Please check the plugin_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_host_service_event_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *
 * HIDS
 *
 */
static gboolean
sim_command_host_ids_event_scan(SimCommand *command, GScanner *scanner)
{
  char *temporal;
  struct tm tm; //needed to check the date parameter.

  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_HOST_IDS_EVENT;
  command->data.host_ids_event.date = 0;
  command->data.host_ids_event.date_str = NULL;
  command->data.host_ids_event.tzone = 0;
  command->data.host_ids_event.id = 0;
  command->data.host_ids_event.host = NULL;
  command->data.host_ids_event.hostname = NULL;
  command->data.host_ids_event.event_type = NULL;
  command->data.host_ids_event.target = NULL;
  command->data.host_ids_event.what = NULL;
  command->data.host_ids_event.extra_data = NULL;
  command->data.host_ids_event.sensor = NULL;
  command->data.host_ids_event.plugin_id = 0;
  command->data.host_ids_event.plugin_sid = 0;
  command->data.host_ids_event.log = NULL;

  command->data.host_ids_event.filename = NULL;
  command->data.host_ids_event.username = NULL;
  command->data.host_ids_event.password = NULL;
  command->data.host_ids_event.userdata1 = NULL;
  command->data.host_ids_event.userdata2 = NULL;
  command->data.host_ids_event.userdata3 = NULL;
  command->data.host_ids_event.userdata4 = NULL;
  command->data.host_ids_event.userdata5 = NULL;
  command->data.host_ids_event.userdata6 = NULL;
  command->data.host_ids_event.userdata7 = NULL;
  command->data.host_ids_event.userdata8 = NULL;
  command->data.host_ids_event.userdata9 = NULL;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_HOST_IDS_EVENT);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_ids_event.date = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Host IDS event incorrect. Please check the date issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_ids_event.date_str = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 1))
          command->data.host_ids_event.tzone = g_ascii_strtod(
              scanner->value.v_string, (gchar**) NULL);
        else
          {
            g_message(
                "Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_ids_event.id = strtol(scanner->value.v_string,
              (char **) NULL, 10);
        else
          {
            g_message(
                "Error: event incorrect. Please check the id issued from the remote server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_HOST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.host = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_HOSTNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        command->data.host_ids_event.hostname = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_EVENT_TYPE:
        //FIXME: the HIDS_EVENT_TYPE field is duplicated. When the new agent enters the game, this must be removed.
        //Both keywords, event_type, and hids_event_type stores data in the same place at this moment.
      case SIM_COMMAND_SYMBOL_HIDS_EVENT_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.event_type = g_strdup(
            scanner->value.v_string);
        break;

        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.event_type = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_TARGET:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.target = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_WHAT:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.what = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_EXTRA_DATA:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.extra_data = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SENSOR:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.sensor = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_LOG:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.log = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_ids_event.plugin_id = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: HIDS event incorrect. Please check the plugin_id issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.host_ids_event.plugin_sid = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: HIDS event incorrect. Please check the plugin_sid issued from the agent: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_FILENAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.filename = g_strdup(
            scanner->value.v_string);
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_host_ids_event_scan filename: %s",
            command->data.host_ids_event.filename);

        break;

      case SIM_COMMAND_SYMBOL_USERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.username = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_PASSWORD:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.password = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA1:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata1 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA2:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata2 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA3:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata3 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA4:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata4 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA5:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata5 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA6:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata6 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA7:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata7 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA8:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata8 = g_strdup(
            scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_USERDATA9:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }
        command->data.host_ids_event.userdata9 = g_strdup(
            scanner->value.v_string);
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_host_ids_event_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 * This is an answer from a children server to a SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS query made in this server (or in a master server and
 * resended here) and sended to children. This is only needed to resend it to a master server or the
 * frameworkd
 */
static gboolean
sim_command_sensor_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SENSOR;
  command->data.sensor.host = NULL;
  command->data.sensor.state = 0;
  command->data.sensor.servername = NULL;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SENSOR);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: sensor event incorrect. Please check the id issued from the children server: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_HOST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.sensor.host = g_strdup(scanner->value.v_string);
        break;

        //FIXME: not used
      case SIM_COMMAND_SYMBOL_STATE:
        g_scanner_get_next_token(scanner);
        g_scanner_get_next_token(scanner);

        if (scanner->token != G_TOKEN_STRING)
          break;
        /*
         if (g_ascii_strcasecmp (scanner->value.v_string, "start"))
         command->data.sensorgin.state = 1;
         else if (g_ascii_strcasecmp (scanner->value.v_string, "stop"))
         command->data.sensor_plugin.state = 2;
         else if (g_ascii_strcasecmp (scanner->value.v_string, "unknown"))
         command->data.sensor_plugin.state = 3;
         */
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.sensor.servername = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: sensor; Server Name incorrect. Please check the server name issued from the children server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_sensor_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 * This is an answer from a children server to a SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS query made in this server (or in a master server and
 * resended here) and sended to children. This is only needed to resend it to a master server or the
 * frameworkd
 */
static gboolean
sim_command_server_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_SERVER;
  command->data.server.host = NULL;
  command->data.server.servername = NULL;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_SERVER);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: server answer incorrect. Please check the id issued from the children server: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_HOST:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        command->data.server.host = g_strdup(scanner->value.v_string);
        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.server.servername = g_strdup(scanner->value.v_string);
        else
          {
            g_message(
                "Error: server; Server Name incorrect. Please check the server name issued from the children server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_server_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 * OK response
 *
 */
static gboolean
sim_command_ok_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner);

  command->type = SIM_COMMAND_TYPE_OK;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_OK);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: OK event incorrect. Please check the id issued from the remote machine: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_ok_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 *	Scan and store the query wich has arrived to this server. May be executed here or in an upper server (depending on servername)
 */
static gboolean
sim_command_database_query_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_DATABASE_QUERY;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_DATABASE_QUERY);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: database query event incorrect. Please check the symbol_id issued from the other server: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.database_query.database_element_type = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Database query event incorrect. Please check the id issued from the remote machine: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
      case SIM_COMMAND_SYMBOL_SENSORNAME: //we will use the servername variable to store the name of the connected machine, regardless
        //its a server or a sensor. The sensor must be able to ask only for its Policy
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.database_query.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Database query; Server Name incorrect. Please check the server name issued from the children server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;
        /*
         case SIM_COMMAND_SYMBOL_QUERY:
         g_scanner_get_next_token (scanner);
         g_scanner_get_next_token (scanner);

         if (scanner->token != G_TOKEN_STRING)
         break;
         command->data.database_query.query = g_strdup (scanner->value.v_string);
         break;
         */

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_database_query_scan: error symbol unknown: %s",
            scanner->value.v_string);
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *	Scan and store the query answer wich has arrived here from a master server.
 */
static gboolean
sim_command_database_answer_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_DATABASE_ANSWER;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_DATABASE_ANSWER);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number(scanner->value.v_string, 0))
          command->id = strtol(scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: database answer event incorrect. Please check the symbol_id issued from the other server: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number(scanner->value.v_string, 0))
          command->data.database_answer.database_element_type = strtol(
              scanner->value.v_string, (char **) NULL, 10);
        else
          {
            g_message(
                "Error: Database answer event incorrect. Please check the id issued from the remote machine: %s",
                scanner->value.v_string);
            return FALSE;
          }

        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.database_answer.servername = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Database answer; Server Name incorrect. Please check the server name issued from the master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      case SIM_COMMAND_SYMBOL_ANSWER:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
          {
            command->type = SIM_COMMAND_TYPE_NONE;
            break;
          }

        if (scanner->value.v_string)
          command->data.database_answer.answer = g_strdup(
              scanner->value.v_string);
        else
          {
            g_message(
                "Error: Database answer; No answer. Please check the answer issued from the master server: %s",
                scanner->value.v_string);
            return FALSE;
          }
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_database_query_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *	
 */
static gboolean
sim_command_agent_date_scan(SimCommand *command, GScanner *scanner)
{
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(scanner != NULL);

  command->type = SIM_COMMAND_TYPE_AGENT_DATE;

  g_scanner_set_scope(scanner, SIM_COMMAND_SCOPE_AGENT_DATE);
  do
    {
      g_scanner_get_next_token(scanner);

      switch (scanner->token)
        {
      case SIM_COMMAND_SYMBOL_AGENT__DATE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */

        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_command_agent_date_scan: error symbol unknown");
        return FALSE;
        }
    }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *
 *
 *
 */
gchar*
sim_command_get_string(SimCommand *command)
{
  SimRule *rule;
  gchar *str = NULL;
  gchar *value = NULL;
  gchar *state;

  g_return_val_if_fail(command != NULL, NULL);
  g_return_val_if_fail(SIM_IS_COMMAND (command), NULL);

  switch (command->type)
    {
  case SIM_COMMAND_TYPE_OK:
    str = g_strdup_printf("ok id=\"%d\"\n", command->id);
    break;

  case SIM_COMMAND_TYPE_ERROR:
    str = g_strdup_printf("error id=\"%d\"\n", command->id);
    break;

  case SIM_COMMAND_TYPE_CONNECT:
    switch (command->data.connect.type)
      {
    case SIM_SESSION_TYPE_SERVER_UP:
    case SIM_SESSION_TYPE_SERVER_DOWN:
      value = g_strdup("server");
      break;
    case SIM_SESSION_TYPE_SENSOR:
      value = g_strdup("sensor");
      break;
    case SIM_SESSION_TYPE_WEB:
      value = g_strdup("web");
      break;
    default:
      value = g_strdup("none");
      }

    str = g_strdup_printf("connect id=\"%d\" type=\"%s\" hostname=\"%s\"\n",
        command->id, value, command->data.connect.hostname);
    g_free(value);
    break;

  case SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE:
    str
        = g_strdup_printf(
            "server-set-data-role id=\"%d\" servername=\"%s\" role_correlate=\"%d\" role_cross_correlate=\"%d\" role_store=\"%d\" role_qualify=\"%d\" role_resend_alarm=\"%d\" role_resend_event=\"%d\"\n",
            command->id, command->data.server_set_data_role.servername,
            command->data.server_set_data_role.correlate,
            command->data.server_set_data_role.cross_correlate,
            command->data.server_set_data_role.store,
            command->data.server_set_data_role.qualify,
            command->data.server_set_data_role.resend_alarm,
            command->data.server_set_data_role.resend_event);
    break;

  case SIM_COMMAND_TYPE_EVENT:
    str = sim_event_to_string(command->data.event.event);
    break;

  case SIM_COMMAND_TYPE_WATCH_RULE:
    if (!command->data.watch_rule.str)
      break;

    str = g_strdup(command->data.watch_rule.str);
    break;

  case SIM_COMMAND_TYPE_SENSOR:
    str = g_strdup_printf(
        "sensor host=\"%s\" state=\"%s\" servername=\"%s\" id=\"%d\"\n",
        command->data.sensor.host, (command->data.sensor.state) ? "on" : "off",
        command->data.sensor.servername, command->id);
    break;

  case SIM_COMMAND_TYPE_SERVER:
    str
        = g_strdup_printf("server host=\"%s\" servername=\"%s\" id=\"%d\"\n",
            command->data.server.host, command->data.server.servername,
            command->id);
    break;

  case SIM_COMMAND_TYPE_SENSOR_PLUGIN:
    switch (command->data.sensor_plugin.state)
      {
    case 1:
      state = g_strdup("start");
      break;
    case 2:
      state = g_strdup("stop");
      break;
    case 3:
      state = g_strdup("unknown");
      break;
    default:
      state = g_strdup("unknown");
      }

    str
        = g_strdup_printf(
            "sensor-plugin sensor=\"%s\" plugin_id=\"%d\" state=\"%s\" enabled=\"%s\"\n",
            command->data.sensor_plugin.sensor,
            command->data.sensor_plugin.plugin_id, state,
            (command->data.sensor_plugin.enabled) ? "true" : "false");

    g_free(state);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_START:
    str = g_strdup_printf("sensor-plugin-start plugin_id=\"%d\"\n",
        command->data.sensor_plugin_start.plugin_id);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP:
    str = g_strdup_printf("sensor-plugin-stop plugin_id=\"%d\"\n",
        command->data.sensor_plugin_stop.plugin_id);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE:
    str = g_strdup_printf("sensor-plugin-enable plugin_id=\"%d\"\n",
        command->data.sensor_plugin_enable.plugin_id);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE:
    str = g_strdup_printf("sensor-plugin-disable plugin_id=\"%d\"\n",
        command->data.sensor_plugin_disable.plugin_id);
    break;
  case SIM_COMMAND_TYPE_DATABASE_QUERY:
    str = g_strdup_printf(
        "database-query database-element-type=\"%d\" servername=\"%s\"\n",
        command->data.database_query.database_element_type,
        command->data.database_query.servername);
    break;

  case SIM_COMMAND_TYPE_DATABASE_ANSWER:
    str
        = g_strdup_printf(
            "database-answer database-element-type=\"%d\" servername=\"%s\" answer=\"%s\"\n",
            command->data.database_answer.database_element_type,
            command->data.database_answer.servername,
            command->data.database_answer.answer);
    break;

  default:
    g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
        "sim_command_get_string: error command unknown");
    break;
    }

  return str;
}

/*
 * Transforms the data received in a new event object. Returns it.
 *
 */
SimEvent*
sim_command_get_event(SimCommand *command)
{
  SimEventType type;
  SimEvent *event;
  struct tm tm;
  GInetAddr *ia_temp;
  g_return_val_if_fail(command, NULL);
  g_return_val_if_fail(SIM_IS_COMMAND (command), NULL);

  if (command->type != SIM_COMMAND_TYPE_EVENT && command->type
      != SIM_COMMAND_TYPE_SNORT_EVENT)
    return NULL;
  //g_return_val_if_fail (command->type == SIM_COMMAND_TYPE_EVENT , NULL);
  g_return_val_if_fail(command->data.event.type, NULL);

  type = sim_event_get_type_from_str(command->data.event.type); //monitor or detector?

  if (type == SIM_EVENT_TYPE_NONE)
    return NULL;

  event = sim_event_new_from_type(type); //creates a new event just filled with type.

  if (command->data.event.date)
    {
      event->time = command->data.event.date;
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_command_get_event event->time= %u", event->time);
      event->diff_time
          = (time(NULL) > event->time) ? (time(NULL) - event->time) : 0;
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_command_get_event event->diff_time= %u", event->diff_time);
    }
  else
    return NULL;

  if (command->data.event.date_str)
    event->time_str = g_strdup(command->data.event.date_str);

  if (command->data.event.tzone)
    event->tzone = command->data.event.tzone;

  if (command->data.event.sensor)
    event->sensor = g_strdup(command->data.event.sensor);

  if (command->data.event.device)
    event->device = g_strdup(command->data.event.device);

  if (!(ia_temp = gnet_inetaddr_new_nonblock(event->sensor, 0)))
    { //sanitize
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_command_get_event: Error: please specify sensor IP");
      g_object_unref(event);
      return NULL;
    }
  else
    gnet_inetaddr_unref(ia_temp);

  if (command->data.event.interface)
    {
      event->interface = g_strdup(command->data.event.interface);
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_command_get_event: Interface: %s", command->data.event.interface);

    }
  else
    //FIXME: this is a piece of shit. event->interface must be removed from all the code. In the meantime, this silly "fix" is used.
    event->interface = g_strdup_printf("none");

  if (command->data.event.plugin_id)
    event->plugin_id = command->data.event.plugin_id;
  else
    {
      g_object_unref(event);
      return NULL;
    }

  if (command->data.event.plugin_sid)
    event->plugin_sid = command->data.event.plugin_sid;

  if (command->data.event.protocol)
    {
      event->protocol = sim_protocol_get_type_from_str(
          command->data.event.protocol);

      if (event->protocol == SIM_PROTOCOL_TYPE_NONE)
        {
          if (sim_string_is_number(command->data.event.protocol, 0))
            event->protocol = (SimProtocolType) atoi(
                command->data.event.protocol);
          else
            //if we receive some strange protocol, its converted into a generic one.
            event->protocol = SIM_PROTOCOL_TYPE_OTHER;
        }
    }
  else
    //If no protocol is defined use TCP, this allow using port filters in base
    //forensics console
    event->protocol = SIM_PROTOCOL_TYPE_TCP;

  //sanitize the event. An event ALWAYS must have a src_ip. And should have a dst_ip (not mandatory).
  //If it's not defined, it will be 0.0.0.0 to avoid problems inside DB and other places.
  if (command->data.event.src_ip)
    event->src_ia = gnet_inetaddr_new_nonblock(command->data.event.src_ip, 0);
  if (!event->src_ia)
    {
      g_object_unref(event);
      return NULL;
    }

  if (command->data.event.dst_ip)
    event->dst_ia = gnet_inetaddr_new_nonblock(command->data.event.dst_ip, 0);
  else
    event->dst_ia = gnet_inetaddr_new_nonblock("0.0.0.0", 0);

  if (command->data.event.src_port)
    event->src_port = command->data.event.src_port;
  if (command->data.event.dst_port)
    event->dst_port = command->data.event.dst_port;

  if (command->data.event.condition)
    event->condition = sim_condition_get_type_from_str(
        command->data.event.condition);
  if (command->data.event.value)
    event->value = g_strdup(command->data.event.value);
  if (command->data.event.interval)
    event->interval = command->data.event.interval;

  if (command->data.event.data)
    event->data = g_strdup(command->data.event.data);
  if (command->data.event.log)
    event->log = g_strdup(command->data.event.log);

  if (command->data.event.snort_sid)
    event->snort_sid = command->data.event.snort_sid;

  if (command->data.event.snort_cid)
    event->snort_cid = command->data.event.snort_cid;

  event->reliability = command->data.event.reliability;
  event->asset_src = command->data.event.asset_src;
  event->asset_dst = command->data.event.asset_dst;
  event->risk_a = command->data.event.risk_a;
  event->risk_c = command->data.event.risk_c;
  event->alarm = command->data.event.alarm;

  if (command->data.event.priority)
    {
      if (command->data.event.priority < 0)
        event->priority = 0;
      else if (command->data.event.priority > 5)
        event->priority = 5;
      else
        event->priority = command->data.event.priority;
    }

  if (command->data.event.filename)
    event->textfields[SimTextFieldFilename] = g_strdup(
        command->data.event.filename);
  if (command->data.event.username)
    event->textfields[SimTextFieldUsername] = g_strdup(
        command->data.event.username);
  if (command->data.event.password)
    event->textfields[SimTextFieldPassword] = g_strdup(
        command->data.event.password);
  if (command->data.event.userdata1)
    event->textfields[SimTextFieldUserdata1] = g_strdup(
        command->data.event.userdata1);
  if (command->data.event.userdata2)
    event->textfields[SimTextFieldUserdata2] = g_strdup(
        command->data.event.userdata2);
  if (command->data.event.userdata3)
    event->textfields[SimTextFieldUserdata3] = g_strdup(
        command->data.event.userdata3);
  if (command->data.event.userdata4)
    event->textfields[SimTextFieldUserdata4] = g_strdup(
        command->data.event.userdata4);
  if (command->data.event.userdata5)
    event->textfields[SimTextFieldUserdata5] = g_strdup(
        command->data.event.userdata5);
  if (command->data.event.userdata6)
    event->textfields[SimTextFieldUserdata6] = g_strdup(
        command->data.event.userdata6);
  if (command->data.event.userdata7)
    event->textfields[SimTextFieldUserdata7] = g_strdup(
        command->data.event.userdata7);
  if (command->data.event.userdata8)
    event->textfields[SimTextFieldUserdata8] = g_strdup(
        command->data.event.userdata8);
  if (command->data.event.userdata9)
    event->textfields[SimTextFieldUserdata9] = g_strdup(
        command->data.event.userdata9);

  event->buffer = g_strdup(command->buffer); //we need this to resend data to other servers, or to send
  //events that matched with policy to frameworkd (future implementation)
  if (command->data.event.is_prioritized)
    event->is_prioritized = TRUE;
  else
    event->is_prioritized = FALSE;
  /* if snort_event, copy snort data*/
  if (command->type == SIM_COMMAND_TYPE_SNORT_EVENT)
    {
      event->packet = (SimPacket*) g_object_ref(command->packet);
      SimPluginSid *simpluginsid;
      simpluginsid = sim_container_get_plugin_sid_by_pky(ossim.container,
          event->plugin_id, event->plugin_sid);
      if (simpluginsid)
        event->log = g_strdup_printf("%s, src:%u dst:%u",
            sim_plugin_sid_get_name(simpluginsid), command->data.event.src_ip,
            command->data.event.dst_ip);
      else
        {
          event->log
              = g_strdup_printf(
                  "Event unknown, please insert plugin_id: %d and plugin_sid: %d into DB. src:%u dst:%u",
                  event->plugin_id, event->plugin_sid,
                  command->data.event.src_ip, command->data.event.dst_ip);
          g_message(
              "Event unknown, please insert plugin_id: %d and plugin_sid: %d into DB",
              event->plugin_id, event->plugin_sid);
        }
    }
  return event;
}

/*
 *
 * FIXME: This function is not called from anywhere
 *
 */
gboolean
sim_command_is_valid(SimCommand *cmd)
{
  g_return_val_if_fail(cmd, FALSE);
  g_return_val_if_fail(SIM_IS_COMMAND (cmd), FALSE);

  switch (cmd->type)
    {
  case SIM_COMMAND_TYPE_CONNECT:
    break;
  case SIM_COMMAND_TYPE_EVENT:
    break;
  case SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN:
    break;
  case SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN:
    break;
  case SIM_COMMAND_TYPE_WATCH_RULE:
    break;
  default:
    return FALSE;
    break;
    }
  return TRUE;
}
/*
 *  This function decides wich one is the correct parser to scan the agent log; BASE64 or standard human readable.
 */
gboolean (*
    sim_command_get_agent_scan(SimCommand *command))(SimCommand*,GScanner*)
    {

      g_return_val_if_fail(command != NULL, NULL);
      g_return_val_if_fail(SIM_IS_COMMAND (command), NULL);
      gboolean
      (*pf)(SimCommand *, GScanner*);
      int i = 0;
      pf = sim_command_event_scan;
      if (command->data.connect.version != NULL)
        {
          while (agent_parsers_table[i].pf != NULL)
            {
              if (strcmp(command->data.connect.version,
                  agent_parsers_table[i].version) == 0)
                {
                  pf = agent_parsers_table[i].pf;
                  break;
                }
              i++;
            }
        }
      return pf;
    }
    // vim: set tabstop=2:

