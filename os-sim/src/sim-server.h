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

#ifndef __SIM_SERVER_H__
#define __SIM_SERVER_H__ 1

#include <glib.h>
#include <glib-object.h>

#include "sim-config.h"
#include "sim-database.h"

#ifndef __SIM_SESSION_H__
#include "sim-session.h"
#endif

#include "sim-command.h"

//FIXME: remove when tested
#if 0
typedef struct _monitor_requests monitor_requests;
struct _monitor_requests //this struct will be used to permit the threaded use
												//of monitor requests from sim_server_push_session_plugin_command
{
	SimSession	*session;
	SimCommand	*command;
};
#endif


#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_SERVER                  (sim_server_get_type ())
#define SIM_SERVER(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SERVER, SimServer))
#define SIM_SERVER_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SERVER, SimServerClass))
#define SIM_IS_SERVER(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SERVER))
#define SIM_IS_SERVER_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SERVER))
#define SIM_SERVER_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SERVER, SimServerClass))

G_BEGIN_DECLS

typedef struct _SimServer        SimServer;
typedef struct _SimServerClass   SimServerClass;
typedef struct _SimServerPrivate SimServerPrivate;

struct _SimServer {
  GObject parent;

  SimServerPrivate *_priv;
};

struct _SimServerClass {
  GObjectClass parent_class;
};

GType             sim_server_get_type                      (void);
SimServer*        sim_server_new                           (SimConfig       *config);
SimServer*        sim_server_HA_new                        (SimConfig       *config);

SimServer*				sim_server_new_from_dm									(GdaDataModel  *dm,
																		                        gint row);
void              sim_server_listen_run                    (SimServer       *server);
void              sim_server_master_run                    (SimServer       *server);

void              sim_server_append_session                (SimServer       *server,
																												    SimSession      *session);
gint              sim_server_remove_session                (SimServer       *server,
																												    SimSession      *session);
GList*            sim_server_get_sessions                  (SimServer       *server);

void              sim_server_push_session_command          (SimServer       *server,
																												    SimSessionType   type,
																												    SimCommand      *command);
void              sim_server_push_session_plugin_command   (SimServer       *server,
																												    SimSessionType   session_type,
																												    gint             plugin_id,
																												    SimRule					*rule);
//gpointer					sim_server_thread_monitor_requests				(gpointer data);
	
SimSession*       sim_server_get_session_by_sensor         (SimServer   *server,
																												    SimSensor   *sensor);

void							sim_server_debug_print										(SimServer		*server);
void              sim_server_debug_print_sessions           (SimServer    *server); //debug function
gchar*						sim_server_get_ip													(SimServer   *server);
gchar*						sim_server_get_name												(SimServer   *server);
gint							sim_server_get_port												(SimServer   *server);
void							sim_server_set_port												(SimServer   *server,
																															gint				port);

SimConfig*				sim_server_get_config											(SimServer   *server);
void							sim_server_load_role											(SimServer *server);
void							sim_server_set_role												(SimServer *server,
																														SimRole *role);

SimRole*					sim_server_get_role												(SimServer *server);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_SERVER_H__ */
// vim: set tabstop=2:
