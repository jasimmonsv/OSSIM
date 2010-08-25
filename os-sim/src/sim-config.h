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

#ifndef __SIM_CONFIG_H__
#define __SIM_CONFIG_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-enums.h"
#include "sim-command.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_CONFIG                  (sim_config_get_type ())
#define SIM_CONFIG(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_CONFIG, SimConfig))
#define SIM_CONFIG_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_CONFIG, SimConfigClass))
#define SIM_IS_CONFIG(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_CONFIG))
#define SIM_IS_CONFIG_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_CONFIG))
#define SIM_CONFIG_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CONFIG, SimConfigClass))

#define HA_ROLE_NONE		0
#define HA_ROLE_PASSIVE	1
#define HA_ROLE_ACTIVE	2
	

G_BEGIN_DECLS

typedef struct _SimConfig        SimConfig;
typedef struct _SimConfigClass   SimConfigClass;
typedef struct _SimConfigDS      SimConfigDS;
typedef struct _SimConfigNotify  SimConfigNotify;
typedef struct _SimConfigRServer SimConfigRServer;

struct _SimConfig {
  GObject parent;

  GList   *datasources;
  GList   *notifies;
  GList   *rservers;		//SimConfigRServer

  gchar   *notify_prog;

	gint		max_event_tmp;	//this is taken from 'config' table in DB. It means the maximum number 
													//of events that should be inside event_tmp table.
  gboolean copy_siem_events;  //copy events from acid_event_input to acid_event

  struct {
    gchar    *filename;
  } log;

/*  struct {
    gchar    *name;
    gchar    *ip;
    gchar    *interface;
  } sensor;
*/
  struct {
    gchar    *filename;
  } directive;

  struct {
    gulong    interval;
  } scheduler;

  struct {
		gchar			*name;
		gchar			*ip;
//		gchar			*interface;
    gint      port;
		SimRole		*role;
		gchar			*HA_ip;
		gint			HA_port;
  } server;

  struct {
    gchar    *host;
    gint      port;
  } smtp;

  struct {
    gchar    *name;
    gchar    *host;
    gint      port;
  } framework;

};

struct _SimConfigClass {
  GObjectClass parent_class;
};

struct _SimConfigDS {
  gchar    *name;
  gchar    *provider;
  gchar    *dsn;
  gboolean local_DB;     //if False: database queries are executed against other ossim server in other machine.
  gchar		 *rserver_name;     //if local_DB=False, this is the server where we have to connect to.
};

struct _SimConfigNotify {
  gchar    *emails;
  GList    *alarm_risks;
};

struct _SimConfigRServer {	//servers "up" in the architecture directly connected. Also, the HA remote server is a rserver too.
  gchar				*name;
  gchar				*ip;	//ip & ia has the same address. //FIXME: redundant storage
  GInetAddr		*ia;
  gint				port;
	GTcpSocket	*socket;
	GIOChannel	*iochannel;
	gint				HA_role;			//HA_ROLE_PASSIVE, HA_ROLE_ACTIVE, HA_ROLE_NONE
	gboolean		is_HA_server;	//true if the remote server is an HA server.
	gboolean		primary; //true if the rserver is the main master server. At last, this rserver thinks that this is the 
												//main master server, I mean: ie. in an architecture like this:
												//server1->server2->server3, where server3 is the children server lower in the architecture, the "real" main master
												//server is the server1. But for server3, his main & primary master server will be server2.
												//NOTE: Mandatory in server's config.xml. If not specified, this server won't be able to extract data
												//(hosts, nets..) from it.
};

GType             sim_config_get_type                        (void);
SimConfig*        sim_config_new                             (void);
SimConfigDS*      sim_config_ds_new                          (void);
void              sim_config_ds_free                         (SimConfigDS  *ds);
SimConfigDS*      sim_config_get_ds_by_name                  (SimConfig    *config,
																												      const gchar  *name);

SimConfigNotify*  sim_config_notify_new                      (void);
void              sim_config_notify_free                     (SimConfigNotify *notify);

SimConfigRServer* sim_config_rserver_new                     (void);
void              sim_config_rserver_free                    (SimConfigRServer *rserver);
void							sim_config_set_data_role										(SimConfig   *config,
																															SimCommand  *cmd);
void							sim_config_rserver_debug_print							(SimConfigRServer *rserver);

//aggg do this here...
#ifndef __SIM_DATABASE_H__
#include "sim-database.h"
void							sim_config_set_config_db_max_event_tmp			(SimConfig     *config,
													                                     SimDatabase   *database);

void							sim_config_load_database_config							(SimConfig     *config,
																	                             SimDatabase     *database);

void							sim_config_set_config_copy_siem_events				(SimConfig     *config,
												                                        SimDatabase   *database);

#endif

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_CONFIG_H__ */

// vim: set tabstop=2:

