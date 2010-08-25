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
#include <stdlib.h>

#include "sim-config.h"
#include "os-sim.h"
#include <config.h>
#include "sim-event.h"
#include "sim-container.h"

extern SimMain  ossim; //needed to be able to access to ossim.dbossim directly in sim_config_set_data_role()

enum
{
  DESTROY,
  LAST_SIGNAL
};

static gpointer parent_class = NULL;
static gint sim_config_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_config_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_config_impl_finalize (GObject  *gobject)
{
  SimConfig  *config = (SimConfig *) gobject;
  GList      *list;

  list = config->datasources;
  while (list)
    {
      SimConfigDS *ds = (SimConfigDS *) list->data;
      sim_config_ds_free (ds);
      list = list->next;
    }

  list = config->notifies;
  while (list)
    {
      SimConfigNotify *notify = (SimConfigNotify *) list->data;
      sim_config_notify_free (notify);
      list = list->next;
    }

  if (config->log.filename)
    g_free (config->log.filename);

  if (config->directive.filename)
    g_free (config->directive.filename);

	if (config->server.name)
		g_free (config->server.name);

	if (config->server.ip)
		g_free (config->server.ip);

	if (config->server.role)
		g_free (config->server.role);
	
	if (config->notify_prog)
		g_free (config->notify_prog);
	
	if (config->server.HA_ip)
		g_free (config->server.HA_ip);

	if (config->smtp.host)
		g_free (config->smtp.host);

	if (config->framework.name)
		g_free (config->framework.name);

	if (config->framework.host)
		g_free (config->framework.host);
	
  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_config_class_init (SimConfigClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_config_impl_dispose;
  object_class->finalize = sim_config_impl_finalize;
}

static void
sim_config_instance_init (SimConfig *config)
{
  config->log.filename = NULL;

  config->datasources = NULL;
  config->notifies = NULL;
  config->rservers = NULL;

  config->notify_prog = NULL;
  
	config->max_event_tmp = 0;

  config->directive.filename = NULL;

  config->scheduler.interval = 0;

  config->server.port = 0;
  config->server.name = NULL;
  config->server.ip = NULL;
	config->server.role = g_new0 (SimRole, 1);

  config->smtp.host = NULL;
  config->smtp.port = 0;

  config->framework.name = NULL;
  config->framework.host = NULL;
  config->framework.port = 0;

}

/* Public Methods */

GType
sim_config_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimConfigClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_config_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimConfig),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_config_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimConfig", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 */
SimConfig*
sim_config_new (void)
{
  SimConfig *config = NULL;

  config = SIM_CONFIG (g_object_new (SIM_TYPE_CONFIG, NULL));

  return config;
}

/*
 *
 *
 *
 */
SimConfigDS*
sim_config_ds_new (void)
{
  SimConfigDS *ds;

  ds = g_new0 (SimConfigDS, 1);
  ds->name = NULL;
  ds->provider = NULL;
  ds->dsn = NULL;
  ds->local_DB = TRUE;
  ds->rserver_name = NULL;

  return ds;
}

/*
 *
 *
 *
 */
void
sim_config_ds_free (SimConfigDS *ds)
{
  g_return_if_fail (ds);

  if (ds->name)
    g_free (ds->name);
  if (ds->provider)
    g_free (ds->provider);
  if (ds->dsn)
    g_free (ds->dsn);
	if (ds->rserver_name)
		g_free (ds->rserver_name);

  g_free (ds);
}

/*
 * This function doesn't returns anything, it stores directly the data into config parameter.
 */
void
sim_config_set_data_role (SimConfig		*config,
													SimCommand	*cmd)
{
	g_return_if_fail (config);
	g_return_if_fail (SIM_IS_CONFIG (config));
	g_return_if_fail (cmd);
	g_return_if_fail (SIM_IS_COMMAND (cmd));
	
	config->server.role->store = cmd->data.server_set_data_role.store;
	config->server.role->cross_correlate = cmd->data.server_set_data_role.cross_correlate;
	config->server.role->correlate = cmd->data.server_set_data_role.correlate;
	config->server.role->qualify = cmd->data.server_set_data_role.qualify;
	config->server.role->resend_event = cmd->data.server_set_data_role.resend_event;	
	config->server.role->resend_alarm = cmd->data.server_set_data_role.resend_alarm;	

	
	//Also store in DB the configuration
	
	gchar *query;
	query = g_strdup_printf ("REPLACE INTO server_role (name, correlate, cross_correlate, store, qualify, resend_alarm, resend_event)"
														"	VALUES ('%s', %d, %d, %d, %d, %d, %d)", cmd->data.server_set_data_role.servername, 
														 cmd->data.server_set_data_role.correlate,
														 cmd->data.server_set_data_role.cross_correlate,
														 cmd->data.server_set_data_role.store,
													   cmd->data.server_set_data_role.qualify,
														 cmd->data.server_set_data_role.resend_event,
														 cmd->data.server_set_data_role.resend_alarm);

  sim_database_execute_no_query (ossim.dbossim, query);
	g_free (query);

}

/*
 *
 *
 *
 */
SimConfigDS*
sim_config_get_ds_by_name (SimConfig    *config,
			   const gchar  *name)
{
  GList  *list;

  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);
  g_return_val_if_fail (name, NULL);

  list = config->datasources;
  while (list)
    {
      SimConfigDS *ds = (SimConfigDS *) list->data;

      if (!g_ascii_strcasecmp (ds->name, name))
	return ds;

      list = list->next;
    }

  return NULL;
}

/*
 *
 *
 *
 */
SimConfigNotify*
sim_config_notify_new (void)
{
  SimConfigNotify *notify;

  notify = g_new0 (SimConfigNotify, 1);
  notify->emails = NULL;
  notify->alarm_risks = NULL;

  return notify;
}

/*
 *
 *
 *
 */
void
sim_config_notify_free (SimConfigNotify *notify)
{
  GList *list;

  g_return_if_fail (notify);

  if (notify->emails)
    g_free (notify->emails);

  list = notify->alarm_risks;
  while (list)
    {
      gint *level = (gint *) list->data;
      g_free (level);
      list = list->next;
    }

  g_free (notify);
}

/*
 *
 *
 *
 */
SimConfigRServer*
sim_config_rserver_new (void)
{
  SimConfigRServer *rserver;

  rserver = g_new0 (SimConfigRServer, 1);
  rserver->name = NULL;
  rserver->ip = NULL;
  rserver->ia = NULL;
  rserver->port = 0;
	rserver->socket = NULL;
  rserver->iochannel = NULL;
  rserver->HA_role = HA_ROLE_NONE;
  rserver->is_HA_server = FALSE;
  rserver->primary = TRUE; //usually there will be just one rserver
  return rserver;
}

void
sim_config_rserver_debug_print (SimConfigRServer *rserver)
{
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_config_rserver_debug_print:");

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->name: %s", rserver->name);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->ip: %s", rserver->ip);
  gchar *ip_temp = gnet_inetaddr_get_canonical_name(rserver->ia);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->ia: %s", ip_temp);
  g_free (ip_temp);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->port: %d", rserver->port);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->socket: %x", rserver->socket);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->iochannel: %x", rserver->iochannel);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->HA_role: %d", rserver->HA_role);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "            rserver->primary: %d", rserver->primary);
}


/*
 *
 *
 *
 */
void
sim_config_rserver_free (SimConfigRServer *rserver)
{
  g_return_if_fail (rserver);

  if (rserver->name)
    g_free (rserver->name);
  if (rserver->ip)
    g_free (rserver->ip);
  if (rserver->ia)
    gnet_inetaddr_unref (rserver->ia);
	
  g_free (rserver);
}

/*
 * Loads all the data needed from configuration in DB.
 * If this server is a children server without local DB, this data will be loaded from sim_container_new() to simplify things.
 */
void
sim_config_load_database_config	(SimConfig     *config,
																SimDatabase			*database)
{
  g_return_if_fail (config);
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (database);
  g_return_if_fail (SIM_IS_DATABASE (database));

	if (sim_database_is_local (database))
	{
		//set the maximum number of events that can appear in event_tmp ossim DB table.
		sim_config_set_config_db_max_event_tmp (config, database);	//NOTE: if this is a children server, this won't be loaded because
																																//as we can't store data in event_tmp it has no sense to know how much
																																//data could we store

		//load the server's role specified in DB (this can be changed with an event from a
		//master server). 
		sim_server_load_role (ossim.server);
		
		//Load the children server's role
		GList *list;
		list = sim_container_get_servers (ossim.container);
		while (list)
		{
			SimServer *server = (SimServer *) list->data;
			sim_server_load_role (server);
			list = list->next;
		}
    g_list_free (list);

	}

}

/*
 * This function extracts how much events should be stored in event_tmp table in ossim db.
 *
 */
void
sim_config_set_config_db_max_event_tmp (SimConfig     *config,
			                    		          SimDatabase   *database)
{
  GdaDataModel  *dm;
  GdaValue      *value;
  gchar         *query;
	gchar					*max_event_tmp;

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_config_set_config_db_max_event_tmp: Entering");
  g_return_if_fail (config);
  g_return_if_fail (SIM_IS_CONFIG (config));

  query = g_strdup_printf ("SELECT value FROM config WHERE conf='max_event_tmp'");
  dm = sim_database_execute_single_command (database, query);

  if (dm)
  {
    if (gda_data_model_get_n_rows(dm) !=0) 
    {
			value = (GdaValue *) gda_data_model_get_value_at (dm, 0, 0);
			sim_gda_value_extract_type (value);
      if (!gda_value_is_null (value))
      {
				max_event_tmp = gda_value_stringify (value); //we extract a string and convert it to a number to use it in sim_organizer_store_event_tmp()
				
				if (sim_string_is_number (max_event_tmp, 0))
					config->max_event_tmp = atoi (max_event_tmp);
				else
				{
					g_message ("Error: max_event_tmp value from config table wrong. Please check it.");
					config->max_event_tmp = 0;
				}
      }
      else
        g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_xml_config_set_config_max_event_tmp value null");
    }
    else
      config->max_event_tmp = 0;
  }
  else
  {
    g_message ("Error: Config DATA MODEL ERROR");
    config->max_event_tmp = 0;
  }

  g_free (query);
}

// vim: set tabstop=2:

