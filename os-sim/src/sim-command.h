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

#ifndef __SIM_COMMAND_H__
#define __SIM_COMMAND_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-enums.h"
#include "sim-event.h"
#include "sim-rule.h"
#include "sim-packet.h"
#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_COMMAND                  (sim_command_get_type ())
#define SIM_COMMAND(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_COMMAND, SimCommand))
#define SIM_COMMAND_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_COMMAND, SimCommandClass))
#define SIM_IS_COMMAND(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_COMMAND))
#define SIM_IS_COMMAND_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_COMMAND))
#define SIM_COMMAND_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_COMMAND, SimCommandClass))

G_BEGIN_DECLS

typedef struct _SimCommand        SimCommand;
typedef struct _SimCommandClass   SimCommandClass;
struct _SimCommand {
  GObject parent;

  SimCommandType      type;
  gint                id;
	gchar								*buffer;	//here will be stored the original buffer received so we can resend it later

  union {
    struct {
      gchar          *username;
      gchar          *password;
			gchar          *hostname; //Used only in server conns. Not needed for sensors.
			gchar					 *version;
      SimSessionType  type;
    } connect;

    struct {												//command sent from server to frameworkd or to other servers
      gchar           *host;        //ip, not name. This is the children server connected to server "servername"
      gchar           *servername;    // OSSIM name.
    } server;


		struct {
      gint            id;
			gchar						*servername; //OSSIM name, no FQDN. Tells the name of the server from where we want to know the sensors connected.
    } server_get_sensors;

    struct {
      gint            id;
			gchar						*servername; //OSSIM server name, no FQDN
    } server_get_servers;


    struct {
      gint            id;
			gchar						*servername; //OSSIM server name, no FQDN
    } server_get_sensor_plugins;

    struct {
      gint            id;
			gchar						*servername;	//sever name to wich send data to. 
			gboolean				store;
			gboolean				correlate;
			gboolean				cross_correlate;
			gboolean				qualify;
			gboolean				resend_alarm;			
			gboolean				resend_event;			
    } server_set_data_role;

    struct {												//command sent from server to frameworkd
      gchar           *host;        //ip, not name
      gboolean        state;
      gchar           *servername;  //this info is inserted by the server. This is the server to wich is attached the sensor
    } sensor;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
      gboolean        enabled;
      gint            state;
    } sensor_plugin;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_start;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_stop;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_enable;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_disable;

		//we could use just one struct to store all the "reload *" servername's,
		//but I prefer to use multiple structs to not break the common usage (sig...).
		//Oh, and may be in the future more fields needs to be added.
		struct {
			gchar						*servername;
		} reload_plugins;
		
		struct {
			gchar						*servername;
		} reload_sensors;
		
		struct {
			gchar						*servername;
		} reload_hosts;
		
		struct {
			gchar						*servername;
		} reload_nets;
		
		struct {
			gchar						*servername;
		} reload_policies;
		
		struct {
			gchar						*servername;
		} reload_directives;
		
		struct {
			gchar						*servername;
		} reload_all;
		
		struct {
      gint            id;
      SimPluginType   type;
      gchar          *name;
      gboolean        enabled;
      gint            state;
    } session_append_plugin;

    struct {
      gint            id;
      SimPluginType   type;
      gchar          *name;
      gboolean        enabled;
      gint            state;
    } session_remove_plugin;


    struct {
      gint            id;
      gint            plugin_id;
    } plugin_state_started;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_state_unknown;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_state_stopped;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_enabled;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_disabled;

    struct {
      /* Event Info */
      gchar             *type;
      gint							id;
      time_t            date;
      gchar             *date_str;
      gint              tzone;
      gchar             *sensor;
      gchar             *interface;

      /* Plugin Info */
      gint               plugin_id;
      gint               plugin_sid;

      /* Plugin Type Detector */
      gchar             *protocol;
      gchar             *src_ip;
      gchar             *dst_ip;
      gint               src_port;
      gint               dst_port;

      /* Plugin Type Monitor */
      gchar             *condition;
      gchar             *value;
      gint               interval;

      gchar             *data;
      gchar             *log;

      guint32            snort_sid;
      guint32            snort_cid;

      gint               reliability;
      gint               priority;
      gint               asset_src;
      gint               asset_dst;
      gdouble            risk_c;
      gdouble            risk_a;
      gboolean					 alarm;

      SimEvent          *event;

			gchar							*filename;	//this variables are duplicated, here and inside the above "event" object
			gchar							*username;
			gchar							*password;
			gchar							*userdata1;
			gchar							*userdata2;
			gchar							*userdata3;
			gchar							*userdata4;
			gchar							*userdata5;
			gchar							*userdata6;
			gchar							*userdata7;
			gchar							*userdata8;
			gchar							*userdata9;

			gboolean					is_prioritized;	//needed to know if the children server has changed the event's priority, or it should be done in master server.

    } event;

    struct {
      gchar             *str;
    } watch_rule;


    struct {
      time_t             date;
      gint							id;
      gchar             *host;
      gchar             *os;
      gchar             *sensor;
      gchar             *interface;
      gint              tzone;
      gchar             *date_str;

      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;
    } host_os_event;

    struct {
      time_t            date;
      gint							id;
      gchar             *host;
      gchar             *mac;
      gchar             *vendor;
      gchar             *sensor;
      gchar             *interface;
      gint              tzone;
      gchar             *date_str;

      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;
    } host_mac_event;

    struct {
      time_t             date;
      gint							id;
      gchar             *host;
      gint               port;
      gint               protocol;
      gchar             *service;
      gchar             *sensor;
      gchar             *interface;
      gchar             *application;
      gint              tzone;
      gchar             *date_str;

      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;
    } host_service_event;

    struct {
      gint							id;
      gchar             *host;
      gchar             *hostname;
      gchar             *event_type;
      gchar             *target;
      gchar             *what;
      gchar             *extra_data;
      gchar             *sensor;
      gchar             *interface;
      time_t            date;
      gint              tzone;
      gchar             *date_str;

      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;

			gchar							*filename;	//this variables are duplicated, here and inside the above "event" object
			gchar							*username;
			gchar							*password;
			gchar							*userdata1;
			gchar							*userdata2;
			gchar							*userdata3;
			gchar							*userdata4;
			gchar							*userdata5;
			gchar							*userdata6;
			gchar							*userdata7;
			gchar							*userdata8;
			gchar							*userdata9;
    } host_ids_event;

		struct {
      gint							id;	//Not used at this moment.
			SimDBElementType	database_element_type; //is this a Host query, or a network query, or a directive query....
			gchar							*servername;	//the master server to wich is sended this query, has to know where does the msg come from.
																			//This is the server who originated the query.
		} database_query;
	
		struct {
      gint							id;
			gchar							*answer;
			SimDBElementType	database_element_type; //is this a Host answer, or a network answer, or a directive answer....
			gchar							*servername;	//children server to wich is sended the answer
		} database_answer;
		
		  } data;
	struct {
		  guint unziplen;
			guint8 *gzipdata;
			guint32 snort_gid; /* snort generator */
			guint32 snort_sid; /* snort signature */
			guint32 snort_rev; /* snort revision */
			guint32 snort_classification; /* snort classification */
			guint32 snort_priority; /* snort priority */

		} snort_event;
	SimPacket *packet;
};

struct _SimCommandClass {
  GObjectClass parent_class;
};

GType             sim_command_get_type                        (void);
SimCommand*       sim_command_new                             (void);
SimCommand*       sim_command_new_from_buffer                 (const gchar     *buffer);
SimCommand*       sim_command_new_from_type                   (SimCommandType   type);
SimCommand*       sim_command_new_from_rule                   (SimRule         *rule);

GScanner *              sim_command_start_scanner                   (void);

gchar*            sim_command_get_string                      (SimCommand      *command);

SimEvent*         sim_command_get_event                       (SimCommand      *command);

gboolean          sim_command_is_valid                        (SimCommand      *command);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_COMMAND_H__ */

// vim: set tabstop=2:

