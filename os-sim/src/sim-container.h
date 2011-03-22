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

#ifndef __SIM_CONTAINER_H__
#define __SIM_CONTAINER_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-enums.h"
#include "sim-database.h"
#include "sim-plugin.h"
#include "sim-plugin-sid.h"
#include "sim-sensor.h"
#include "sim-server.h"
#include "sim-host.h"
#include "sim-net.h"
#include "sim-event.h"
#include "sim-policy.h"
#include "sim-directive.h"
#include "sim-host-level.h"
#include "sim-net-level.h"
#include "sim-config.h"
#include "sim-command.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_CONTAINER                  (sim_container_get_type ())
#define SIM_CONTAINER(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_CONTAINER, SimContainer))
#define SIM_CONTAINER_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_CONTAINER, SimContainerClass))
#define SIM_IS_CONTAINER(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_CONTAINER))
#define SIM_IS_CONTAINER_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_CONTAINER))
#define SIM_CONTAINER_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CONTAINER, SimContainerClass))

#define EVENT_SEQ_TABLE         "event_seq"
#define BACKLOG_SEQ_TABLE       "backlog_seq"
#define BACKLOG_EVENT_SEQ_TABLE "backlog_event_seq" //FIXME: needed?
#define EVENT_TMP_SEQ_TABLE			"event_tmp_seq"
		
G_BEGIN_DECLS
	
typedef struct _SimContainer         SimContainer;
typedef struct _SimContainerClass    SimContainerClass;
typedef struct _SimContainerPrivate  SimContainerPrivate;

struct _SimContainer {
  GObject parent;

  SimContainerPrivate  *_priv;
};

struct _SimContainerClass {
  GObjectClass parent_class;
};

G_LOCK_DEFINE_STATIC (s_mutex_config);
G_LOCK_DEFINE_STATIC (s_mutex_plugins);
G_LOCK_DEFINE_STATIC (s_mutex_plugin_sids);
G_LOCK_DEFINE_STATIC (s_mutex_sensors);
G_LOCK_DEFINE_STATIC (s_mutex_hosts);
G_LOCK_DEFINE_STATIC (s_mutex_nets);
G_LOCK_DEFINE_STATIC (s_mutex_policies);
G_LOCK_DEFINE_STATIC (s_mutex_host_levels);
G_LOCK_DEFINE_STATIC (s_mutex_net_levels);
G_LOCK_DEFINE_STATIC (s_mutex_events);
G_LOCK_DEFINE_STATIC (s_mutex_servers);

GType             sim_container_get_type                        (void);
SimContainer*     sim_container_new                             (SimConfig     *config);


GList*            sim_container_db_host_get_plugin_sids_ul      (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gint           plugin_id,
								 gint           plugin_sid);



gchar*            sim_container_db_get_host_os_ul               (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 GInetAddr     *sensor);
void              sim_container_db_insert_host_os_ul            (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gchar         *date,
								 gchar		     *sensor,
								 gchar     		 *interface,
								 gchar         *os);
void              sim_container_db_update_host_os_ul            (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gchar         *date,
								 gchar         *curr_os,
								 gchar         *prev_os,
								 GInetAddr     *sensor);

gchar**          sim_container_db_get_host_mac_ul              (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 GInetAddr     *sensor);
gchar*            sim_container_db_get_host_mac_vendor_ul       (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 GInetAddr     *sensor);
void              sim_container_db_insert_host_mac_ul           (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gchar         *date,
								 gchar         *mac,
								 gchar         *vendor,
								 gchar         *interface,
								 gchar		     *sensor);
void              sim_container_db_update_host_mac_ul           (SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gchar         *date,
								 gchar         *curr_mac,
								 gchar         *prev_mac,
								 gchar         *vendor,
								 GInetAddr     *sensor);

gchar**		sim_container_db_get_host_service_ul		(SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gint           port,
								 gint           protocol,
								 GInetAddr     *sensor);
void		sim_container_db_insert_host_service_ul		(SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gchar         *date,
								 gint           port,
								 gint           protocol,
								 gchar		     *sensor,
								 gchar				 *interface,
								 gchar         *service,
								 gchar         *application);

void		sim_container_db_update_host_service_ul		(SimContainer  *container,
								 SimDatabase   *database,
								 GInetAddr     *ia,
								 gchar         *date,
								 gint           port,
								 gint           protocol,
								 gchar         *service,
								 gchar         *application,
								 GInetAddr     *sensor);

void		sim_container_db_insert_host_ids_event_ul 	(SimContainer  *container,
																											SimDatabase   *dbossim,
																											SimDatabase   *dbsnort,
																											SimEvent			*event,
																											gchar         *timestamp,
																											gint					sid,
																											gulong				cid);
void		sim_container_db_insert_host_plugin_sid 		(SimContainer   *container,
						                                          SimDatabase   *database,
            						                              GInetAddr    *ia,
                        						                  gint          plugin_id,
                                    						      gint          plugin_sid);
void		sim_container_db_insert_host_plugin_sid_ul	(SimContainer   *container,
						                                          SimDatabase   *database,
            						                              GInetAddr    *ia,
                        						                  gint          plugin_id,
                                    						      gint          plugin_sid);

/* Cross correlation functions*/
GList* sim_container_db_get_reference_sid (SimContainer  *container,
       				                             SimDatabase   *database,
              			                       gint           reference_id,
                    		      	           gint           plugin_id,
                        			             gint           plugin_sid);

GList* sim_container_db_get_host_services (SimContainer  *container,
			                                    SimDatabase   *database,
						                              GInetAddr     *ia,
									                        gchar         *sensor,
												                  gint          port);

GList* sim_container_db_host_get_single_plugin_sid (SimContainer *container,
			                                             SimDatabase *database,
						                                       GInetAddr     *ia);
//									                                 gint          plugin_id);

GList*	sim_container_db_get_osvdb_base_name (SimDatabase   *database,
									                             gint           osvdb_id);
GList*	sim_container_db_get_osvdb_version_name (SimDatabase   *database,
							                                 gint           osvdb_id);

GHashTable *
sim_container_get_host_plugin_sids (SimContainer * container);

GMutex *
sim_container_get_host_plugin_sids_mutex (SimContainer * container);

void
sim_container_db_load_host_plugin_sids (SimContainer * container, SimDatabase * database);

//loading data...
void		sim_container_remote_load_element						(SimDBElementType element_type);
void		sim_container_wait_rload_complete						(SimContainer *container);
void		sim_container_set_rload_complete						(SimContainer *container);


/* Recovery Function */

void              sim_container_db_delete_plugin_sid_directive_ul (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_db_delete_backlogs_ul           (SimContainer  *container,
								 SimDatabase   *database);
gint              sim_container_db_get_recovery_ul              (SimContainer  *container,
								 SimDatabase   *database);
gint              sim_container_db_get_recovery                 (SimContainer  *container,
								 SimDatabase   *database);
/* Threshold Function */

gint              sim_container_db_get_threshold_ul             (SimContainer  *container,
								 SimDatabase   *database);
gint              sim_container_db_get_threshold                (SimContainer  *container,
								 SimDatabase   *database);

/* Plugins Functions */

void              sim_container_db_load_plugins_ul              (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_plugin_ul                (SimContainer  *container,
								 SimPlugin     *plugin);
void              sim_container_remove_plugin_ul                (SimContainer  *container,
								 SimPlugin     *plugin);
GList*            sim_container_get_plugins_ul                  (SimContainer  *container);
void              sim_container_set_plugins_ul                  (SimContainer  *container,
								 GList         *plugins);
void              sim_container_free_plugins_ul                 (SimContainer  *container);

SimPlugin*        sim_container_get_plugin_by_id_ul             (SimContainer  *container,
								 gint           id);

void              sim_container_db_load_plugins                 (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_plugin                   (SimContainer  *container,
								 SimPlugin     *plugin);
void              sim_container_remove_plugin                   (SimContainer  *container,
								 SimPlugin     *plugin);
GList*            sim_container_get_plugins                     (SimContainer  *container);
void              sim_container_set_plugins                     (SimContainer  *container,
								 GList         *plugins);
void              sim_container_free_plugins                    (SimContainer  *container);

SimPlugin*        sim_container_get_plugin_by_id                (SimContainer  *container,
								 gint           id);

/* Plugin Sids Functions */

void              sim_container_db_load_plugin_sids_ul          (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_plugin_sid_ul            (SimContainer  *container,
								 SimPluginSid  *plugin_sid);
void              sim_container_remove_plugin_sid_ul            (SimContainer  *container,
								 SimPluginSid  *plugin_sid);
GList*            sim_container_get_plugin_sids_ul              (SimContainer  *container);
void              sim_container_set_plugin_sids_ul              (SimContainer  *container,
								 GList         *plugin_sids);
void              sim_container_free_plugin_sids_ul             (SimContainer  *container);

SimPluginSid*     sim_container_get_plugin_sid_by_pky_ul        (SimContainer  *container,
								 gint           plugin_id,
								 gint           sid);
SimPluginSid*     sim_container_get_plugin_sid_by_name_ul       (SimContainer  *container,
								 gint           plugin_id,
								 const gchar   *name);

void              sim_container_db_load_plugin_sids             (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_plugin_sid               (SimContainer  *container,
								 SimPluginSid  *plugin_sid);
void              sim_container_remove_plugin_sid               (SimContainer  *container,
								 SimPluginSid  *plugin_sid);
GList*            sim_container_get_plugin_sids                 (SimContainer  *container);
void              sim_container_set_plugin_sids                 (SimContainer  *container,
								 GList         *plugin_sids);
void              sim_container_free_plugin_sids                (SimContainer  *container);

SimPluginSid*     sim_container_get_plugin_sid_by_pky           (SimContainer  *container,
								 gint           plugin_id,
								 gint           sid);
SimPluginSid*     sim_container_get_plugin_sid_by_name          (SimContainer  *container,
								 gint           plugin_id,
								 const gchar   *name);

inline 
gint              sim_container_get_plugin_id_by_name 		(SimContainer  *container,
																													gchar           *name);
inline												     
gint              sim_container_get_plugin_id_by_name_ul 	(SimContainer  *container,
										                                       gchar           *name);
					
/* Sensors Functions */

void              sim_container_db_load_sensors_ul              (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_sensor_ul                (SimContainer  *container,
								 SimSensor     *sensor);
void              sim_container_remove_sensor_ul                (SimContainer  *container,
								 SimSensor     *sensor);
GList*            sim_container_get_sensors_ul                  (SimContainer  *container);
void              sim_container_set_sensors_ul                  (SimContainer  *container,
								 GList         *sensors);
void              sim_container_free_sensors_ul                 (SimContainer  *container);

SimSensor*        sim_container_get_sensor_by_name_ul           (SimContainer  *container,
								 gchar         *name);
SimSensor*        sim_container_get_sensor_by_ia_ul             (SimContainer  *container,
								 GInetAddr     *ia);

void              sim_container_db_load_sensors                 (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_sensor                   (SimContainer  *container,
								 SimSensor     *sensor);
void              sim_container_remove_sensor                   (SimContainer  *container,
								 SimSensor     *sensor);
GList*            sim_container_get_sensors                     (SimContainer  *container);
void              sim_container_set_sensors                     (SimContainer  *container,
								 GList         *sensors);
void              sim_container_free_sensors                    (SimContainer  *container);

SimSensor*        sim_container_get_sensor_by_name              (SimContainer  *container,
								 gchar         *name);
SimSensor*        sim_container_get_sensor_by_ia                (SimContainer  *container,
								 GInetAddr     *ia);

void							sim_container_set_sensor_event_number					(SimContainer *container,
																																	gint event_kind, 
																																	GInetAddr *sensor_ia);
void							sim_container_db_update_sensor_events_number (SimContainer	*container, 
																																SimDatabase   *database,
																																SimSensor			*sensor);
	
/* Hosts Functions */

void              sim_container_db_load_hosts_ul                (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_host_ul                  (SimContainer  *container,
								 SimHost       *host);
void              sim_container_remove_host_ul                  (SimContainer  *container,
								 SimHost       *host);
GList*            sim_container_get_hosts_ul                    (SimContainer  *container);
void              sim_container_set_hosts_ul                    (SimContainer  *container,
								 GList         *hosts);
void              sim_container_free_hosts_ul                   (SimContainer  *container);

SimHost*          sim_container_get_host_by_ia_ul               (SimContainer  *container,
								 GInetAddr     *ia);

void              sim_container_db_load_hosts                   (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_host                     (SimContainer  *container,
								 SimHost       *host);
void              sim_container_remove_host                     (SimContainer  *container,
								 SimHost       *host);
GList*            sim_container_get_hosts                       (SimContainer  *container);
void              sim_container_set_hosts                       (SimContainer  *container,
								 GList         *hosts);
void              sim_container_free_hosts                      (SimContainer  *container);

SimHost*          sim_container_get_host_by_ia                  (SimContainer  *container,
								 GInetAddr     *ia);

/* Nets Functions */
void              sim_container_db_load_nets_ul                 (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_net_ul                   (SimContainer  *container,
								 SimNet        *net);
void              sim_container_remove_net_ul                   (SimContainer  *container,
								 SimNet        *net);
GList*            sim_container_get_nets_ul                     (SimContainer  *container);
void              sim_container_set_nets_ul                     (SimContainer  *container,
								 GList         *nets);
void              sim_container_free_nets_ul                    (SimContainer  *container);

GList*            sim_container_get_nets_has_ia_ul              (SimContainer  *container,
								 GInetAddr     *ia);
SimNet*           sim_container_get_net_by_name_ul              (SimContainer  *container,
								 const gchar   *name);

void              sim_container_db_load_nets                    (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_net                      (SimContainer  *container,
								 SimNet        *net);
void              sim_container_remove_net                      (SimContainer  *container,
								 SimNet        *net);
GList*            sim_container_get_nets                        (SimContainer  *container);
void              sim_container_set_nets                        (SimContainer  *container,
								 GList         *nets);
void              sim_container_free_nets                       (SimContainer  *container);

GList*            sim_container_get_nets_has_ia                 (SimContainer  *container,
								 GInetAddr     *ia);
SimNet*           sim_container_get_net_by_name                 (SimContainer  *container,
								 const gchar   *name);


/* Policies Functions */
void              sim_container_db_load_policies_ul             (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_policy_ul                (SimContainer  *container,
																																 SimPolicy     *policy);
void              sim_container_remove_policy_ul                (SimContainer  *container,
																																 SimPolicy     *policy);
GList*            sim_container_get_policies_ul                 (SimContainer  *container);
void              sim_container_set_policies_ul                 (SimContainer  *container,
																																 GList         *policies);
void              sim_container_free_policies_ul                (SimContainer  *container);

SimPolicy*        sim_container_get_policy_match_ul             (SimContainer     *container,
																																 gint              date,
																																 GInetAddr        *src_ip,
																																 GInetAddr        *dst_ip,
																																 SimPortProtocol  *port,
																																 gchar						*sensor,
																																 guint						plugin_id,
																																 guint						plugin_sid);

void              sim_container_db_load_policies                (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_append_policy                   (SimContainer  *container,
								 SimPolicy     *policy);
void              sim_container_remove_policy                   (SimContainer  *container,
								 SimPolicy     *policy);
GList*            sim_container_get_policies                    (SimContainer  *container);
void              sim_container_set_policies                    (SimContainer  *container,
								 GList         *policies);
void              sim_container_free_policies                   (SimContainer  *container);

SimPolicy*        sim_container_get_policy_match                (SimContainer     *container,
																																 gint              date,
																																 GInetAddr        *src_ip,
																																 GInetAddr        *dst_ip,
																																 SimPortProtocol  *port,
																																 gchar						*sensor,
																																 guint						plugin_id,
																																 guint						plugin_sid);

gboolean				 sim_container_db_load_src_or_dst								 (SimDatabase *database,
																																	gchar 			*query,
																																	SimPolicy 	*policy,
																																	int 				src_or_dst);

gint             sim_container_policy_has_actions_in_db					(SimDatabase *database, gint policy_id);

/* Directives Functions */

void              sim_container_load_directives_from_file_ul    (SimContainer  *container,
								 SimDatabase   *db_ossim,
								 const gchar   *filename);
void              sim_container_append_directive_ul             (SimContainer  *container,
								 SimDirective  *directive);
void              sim_container_remove_directive_ul             (SimContainer  *container,
								 SimDirective  *directive);
GList*            sim_container_get_directives_ul               (SimContainer  *container);
void              sim_container_set_directives_ul               (SimContainer  *container,
								 GList         *directives);
void              sim_container_free_directives_ul              (SimContainer  *container);


void              sim_container_load_directives_from_file       (SimContainer  *container,
								 SimDatabase   *db_ossim,
								 const gchar   *filename);
void              sim_container_append_directive                (SimContainer  *container,
								 SimDirective  *directive);
void              sim_container_remove_directive                (SimContainer  *container,
								 SimDirective  *directive);
GList*            sim_container_get_directives                  (SimContainer  *container);
void              sim_container_set_directives                  (SimContainer  *container,
								 GList         *directives);
void              sim_container_free_directives                 (SimContainer  *container);


/* Host Levelss Functions */
void              sim_container_db_load_host_levels_ul          (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_db_insert_host_level_ul         (SimContainer  *container,
								 SimDatabase   *database,
								 SimHostLevel  *host_level);
void              sim_container_db_update_host_level_ul         (SimContainer  *container,
								 SimDatabase   *database,
								 SimHostLevel  *host_level);
void              sim_container_db_delete_host_level_ul         (SimContainer  *container,
								 SimDatabase   *database,
								 SimHostLevel  *host_level);
void              sim_container_append_host_level_ul            (SimContainer  *container,
								 SimHostLevel  *host_level);
void              sim_container_remove_host_level_ul            (SimContainer  *container,
								 SimHostLevel  *host_level);
GList*            sim_container_get_host_levels_ul              (SimContainer  *container);
void              sim_container_set_host_levels_ul              (SimContainer  *container,
								 GList         *host_levels);
void              sim_container_free_host_levels_ul             (SimContainer  *container);

SimHostLevel*     sim_container_get_host_level_by_ia_ul         (SimContainer  *container,
								 GInetAddr     *ia);
void              sim_container_set_host_levels_recovery_ul     (SimContainer  *container,
								 SimDatabase   *database,
								 gint           recovery);

void              sim_container_db_load_host_levels             (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_db_insert_host_level            (SimContainer  *container,
								 SimDatabase   *database,
								 SimHostLevel  *host_level);
void              sim_container_db_update_host_level            (SimContainer  *container,
								 SimDatabase   *database,
								 SimHostLevel  *host_level);
void              sim_container_db_delete_host_level            (SimContainer  *container,
								 SimDatabase   *database,
								 SimHostLevel  *host_level);
void              sim_container_append_host_level               (SimContainer  *container,
								 SimHostLevel  *host_level);
void              sim_container_remove_host_level               (SimContainer  *container,
								 SimHostLevel  *host_level);
GList*            sim_container_get_host_levels                 (SimContainer  *container);
void              sim_container_set_host_levels                 (SimContainer  *container,
								 GList         *host_levels);
void              sim_container_free_host_levels                (SimContainer  *container);

SimHostLevel*     sim_container_get_host_level_by_ia            (SimContainer  *container,
								 GInetAddr     *ia);
void              sim_container_set_host_levels_recovery        (SimContainer  *container,
								 SimDatabase   *database,
								 gint           recovery);

/* Net Levels s Functions */
void              sim_container_db_load_net_levels_ul           (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_db_insert_net_level_ul          (SimContainer  *container,
								 SimDatabase   *database,
								 SimNetLevel   *net_level);
void              sim_container_db_update_net_level_ul          (SimContainer  *container,
								 SimDatabase   *database,
								 SimNetLevel   *net_level);
void              sim_container_db_delete_net_level_ul          (SimContainer  *container,
								 SimDatabase   *database,
								 SimNetLevel   *net_level);
void              sim_container_append_net_level_ul             (SimContainer  *container,
								 SimNetLevel   *net_level);
void              sim_container_remove_net_level_ul             (SimContainer  *container,
								 SimNetLevel   *net_level);
GList*            sim_container_get_net_levels_ul               (SimContainer  *container);
void              sim_container_set_net_levels_ul               (SimContainer  *container,
								 GList         *net_levels);
void              sim_container_free_net_levels_ul              (SimContainer  *container);

SimNetLevel*      sim_container_get_net_level_by_name_ul        (SimContainer  *container,
								 const gchar   *name);
void              sim_container_set_net_levels_recovery_ul      (SimContainer  *container,
								 SimDatabase   *database,
								 gint           recovery);

void              sim_container_db_load_net_levels              (SimContainer  *container,
								 SimDatabase   *database);
void              sim_container_db_insert_net_level             (SimContainer  *container,
								 SimDatabase   *database,
								 SimNetLevel   *net_level);
void              sim_container_db_update_net_level             (SimContainer  *container,
								 SimDatabase   *database,
								 SimNetLevel   *net_level);
void              sim_container_db_delete_net_level             (SimContainer  *container,
								 SimDatabase   *database,
								 SimNetLevel   *net_level);
void              sim_container_append_net_level                (SimContainer  *container,
								 SimNetLevel   *net_level);
void              sim_container_remove_net_level                (SimContainer  *container,
								 SimNetLevel   *net_level);
GList*            sim_container_get_net_levels                  (SimContainer  *container);
void              sim_container_set_net_levels                  (SimContainer  *container,
								 GList         *net_levels);
void              sim_container_free_net_levels                 (SimContainer  *container);

SimNetLevel*      sim_container_get_net_level_by_name           (SimContainer  *container,
								 const gchar   *name);
void              sim_container_set_net_levels_recovery         (SimContainer  *container,
								 SimDatabase   *database,
								 gint           recovery);

/* Server functions. This servers are regarding the children servers. The main server configuration
 * is done directly from config.xml (may be stored in ddbb after that)*/
void              sim_container_db_load_servers_ul              (SimContainer  *container,
																																 SimDatabase   *database);
void              sim_container_append_server_ul                (SimContainer  *container,
																																 SimServer     *server);
void              sim_container_remove_server_ul                (SimContainer  *container,
																																 SimServer     *server);
GList*            sim_container_get_servers_ul                  (SimContainer  *container);
void              sim_container_set_servers_ul                  (SimContainer  *container,
																																 GList         *servers);
void              sim_container_free_servers_ul                 (SimContainer  *container);

SimServer*        sim_container_get_server_by_name_ul           (SimContainer  *container,
																																 gchar         *name);

void              sim_container_db_load_servers                 (SimContainer  *container,
																																 SimDatabase   *database);
void              sim_container_append_server                   (SimContainer  *container,
																																 SimServer     *server);
void              sim_container_remove_server                   (SimContainer  *container,
																																 SimServer     *server);
GList*            sim_container_get_servers                     (SimContainer  *container);
void              sim_container_set_servers                     (SimContainer  *container,
																																 GList         *servers);
void              sim_container_free_servers                    (SimContainer  *container);

SimServer*        sim_container_get_server_by_name              (SimContainer  *container,
								 gchar         *name);

	
/* Backlogs Functions */
void              sim_container_db_insert_backlog_ul            (SimContainer  *container,
								 SimDatabase   *database,
								 SimDirective  *backlog);
void              sim_container_db_update_backlog_ul            (SimContainer  *container,
								 SimDatabase   *database,
								 SimDirective  *backlog);
void              sim_container_db_delete_backlog_ul            (SimContainer  *container,
								 SimDatabase   *database,
								 SimDirective  *backlog);
void              sim_container_append_backlog_ul               (SimContainer  *container,
								 SimDirective  *backlog);
void              sim_container_remove_backlog_ul               (SimContainer  *container,
								 SimDirective  *backlog);
GList*            sim_container_get_backlogs_ul                 (SimContainer  *container);
void              sim_container_set_backlogs_ul                 (SimContainer  *container,
								 GList         *backlogs);
void              sim_container_free_backlogs_ul                (SimContainer  *container);

void              sim_container_db_insert_backlog               (SimContainer  *container,
								 SimDatabase   *database,
								 SimDirective  *backlog);
void              sim_container_db_update_backlog               (SimContainer  *container,
								 SimDatabase   *database,
								 SimDirective  *backlog);
void              sim_container_db_delete_backlog               (SimContainer  *container,
								 SimDatabase   *database,
								 SimDirective  *backlog);
void              sim_container_append_backlog                  (SimContainer  *container,
								 SimDirective  *backlog);
void              sim_container_remove_backlog                  (SimContainer  *container,
								 SimDirective  *backlog);
GList*            sim_container_get_backlogs                    (SimContainer  *container);
void              sim_container_set_backlogs                    (SimContainer  *container,
								 GList         *backlogs);
void              sim_container_free_backlogs                   (SimContainer  *container);

/* Events Functions */
void              sim_container_push_event                      (SimContainer  *container,
																																 SimEvent      *event);
SimEvent*         sim_container_pop_event                       (SimContainer  *container);
void              sim_container_free_events                     (SimContainer  *container);

gboolean          sim_container_is_empty_events                 (SimContainer  *container);
gint              sim_container_length_events                   (SimContainer  *container);

//Monitor rule threads management
void              sim_container_push_monitor_rule              (SimContainer  *container,
																																 SimRule      *rule);
SimRule*          sim_container_pop_monitor_rule               (SimContainer  *container);
void              sim_container_free_monitor_rules              (SimContainer  *container);

gboolean          sim_container_is_empty_monitor_rules          (SimContainer  *container);
gint              sim_container_length_monitor_rules            (SimContainer  *container);


//Debug functions
void							sim_container_debug_print_all									(SimContainer *container); //all the debug below in one function
void							sim_container_debug_print_plugins							(SimContainer *container);
void							sim_container_debug_print_plugin_sids					(SimContainer *container);
void							sim_container_debug_print_sensors							(SimContainer *container);
void							sim_container_debug_print_hosts								(SimContainer *container);
void							sim_container_debug_print_nets								(SimContainer *container);
void							sim_container_debug_print_host_levels					(SimContainer *container);
void							sim_container_debug_print_net_levels					(SimContainer *container);
void							sim_container_debug_print_policy							(SimContainer *container);
void							sim_container_debug_print_servers							(SimContainer *container);


//For action responses event queue (ar_queue)
 
void	sim_container_push_ar_event (SimContainer  *container, SimEvent    *event);
SimEvent*	sim_container_pop_ar_event (SimContainer  *container);



G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_CONTAINER_H__ */

// vim: set tabstop=2:

