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

#ifndef __SIM_SESSION_H__
#define __SIM_SESSION_H__ 1 

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-enums.h"
#include "sim-command.h"
#include "sim-config.h"
#include "sim-database.h"
#include "sim-sensor.h"


#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */



#define SIM_TYPE_SESSION                  (sim_session_get_type ())
#define SIM_SESSION(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SESSION, SimSession))
#define SIM_SESSION_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SESSION, SimSessionClass))
#define SIM_IS_SESSION(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SESSION))
#define SIM_IS_SESSION_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SESSION))
#define SIM_SESSION_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SESSION, SimSessionClass))

//this define's are usefull for sim_container_set_sensor_event_number(), wich is called when
//an event is issued from the agent. We use here the plugin_id to identify the "special" events. Anyway this
//may change in the future, so is better to keep the numbers controlled with a define
#define SIM_EVENT_EVENT								1
#define SIM_EVENT_HOST_OS_EVENT				1511
#define SIM_EVENT_HOST_MAC_EVENT			1512
#define SIM_EVENT_HOST_SERVICE_EVENT	1516
#define SIM_EVENT_HOST_IDS_EVENT			4001

//plugin sids to store MAC changes, OS fingerprinting, etc. This plugin_sids are only used from some plugins, and
//they're needed because when the message arrives, we don't know yet the plugin_sid and we have to deduce it.
#define EVENT_NEW     1
#define EVENT_CHANGE  2
#define EVENT_DELETED 3
#define EVENT_SAME    4
#define EVENT_UNKNOWN 5
	
G_BEGIN_DECLS

typedef struct _SimSession        SimSession;
typedef struct _SimSessionClass   SimSessionClass;
typedef struct _SimSessionPrivate SimSessionPrivate;

struct _SimSession {
  GObject parent;

  SimSessionType      type;

  SimSessionPrivate  *_priv;
};

struct _SimSessionClass {
  GObjectClass parent_class;
};

GType             sim_session_get_type                        (void);
SimSession*       sim_session_new                             (GObject       *server,
																													     SimConfig     *config,
																												       GTcpSocket    *socket);

GInetAddr*        sim_session_get_ia                          (SimSession *session);
gboolean          sim_session_read                            (SimSession  *session);
gint              sim_session_write                           (SimSession  *session,
																												       SimCommand  *command);
guint							sim_session_write_from_buffer								(SimSession  *session,
																																gchar			 *buffer);
gboolean          sim_session_has_plugin_type                 (SimSession     *session,
																												       SimPluginType   type);
gboolean          sim_session_has_plugin_id                   (SimSession     *session, 
																																gint            plugin_id);
//ag, so ugly to do this!
#ifndef __SIM_SERVER_H__
#include "sim-server.h"
SimServer*			  sim_session_get_server                      (SimSession *session);
#endif	

SimSensor*        sim_session_get_sensor                      (SimSession *session);
gboolean          sim_session_is_sensor                       (SimSession *session);
gboolean          sim_session_is_server                       (SimSession *session);
void              sim_session_close                           (SimSession *session);
void							sim_session_resend_command									(SimSession *session,
																		                            SimCommand  *command);
void							sim_session_resend_buffer										(gchar *buffer);
gchar*						sim_session_get_hostname										(SimSession *session);	
void							sim_session_set_hostname										(SimSession *session,
																																gchar			*hostname);
void							sim_session_set_is_initial									(SimSession *session,
																											          gboolean tf);
gboolean					sim_session_get_is_initial									(SimSession *session);
void							sim_session_set_fully_stablished						(SimSession *session);
void							sim_session_wait_fully_stablished						(SimSession *session);
void							sim_session_set_id													(SimSession *session,
																															gint id);
gint							sim_session_get_id													(SimSession *session);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */


#endif /* __SIM_SESSION_H__ */
// vim: set tabstop=2:
