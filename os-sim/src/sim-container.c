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

#include <netdb.h>

#include "os-sim.h"
#include "sim-container.h"
//#include "sim-policy.h"
#include "sim-xml-directive.h"
#include <config.h>
#include "sim-sensor.h"

static time_t last_time = 0;

/*****debug*****/
struct _SimInetPrivate
{
  guchar bits;
  struct sockaddr_storage sa;
};

/*****debug*****/

extern SimMain ossim;

enum
{
  DESTROY, LAST_SIGNAL
};

struct _SimContainerPrivate
{
  GList *plugins;
  GList *plugin_sids;
  GList *sensors;
  GList *hosts;
  GList *nets; //SimNet Objects
  GList *policies; //SimPolicy objects
  GList *directives; //SimDirective

  GList *servers; //SimServer objects. NOTE: This objects are used ONLY to store name, ip & port of the children servers.
  //Those children servers may be connected or not, but anyway we load their info.

  GList *host_levels;
  GList *net_levels;
  GList *backlogs; //SimDirective

  GList *plugin_references; //cross correlation. Relations between one and another plugins.(snort/nessus ie.)
	GHashTable	* host_plugin_sids; //cross correlation. Host, and sids with vulnerabilities associated.
	GMutex			*mutex_host_plugin_sids;

  //FIXME
  //AQUI: create plugin_references and host_plugin_sids objects to load it in memory without DB.

  GQueue *events;
  GAsyncQueue *ar_events; // This queue is for action/response events

  GCond *cond_events;
  GMutex *mutex_events;

  GCond *cond_ar_events; //  For action responses queue
  GMutex *mutex_ar_events; // For action responses queue

  GQueue *monitor_rules;
  GCond *cond_monitor_rules;
  GMutex *mutex_monitor_rules;

  gboolean rload_complete; //Used when this server hasn't got any DB. True when all the data from a remote server has been loaded.
  GCond *rload_cond;
  GMutex *rload_mutex;
};

static gpointer parent_class = NULL;
static gint sim_container_signals[LAST_SIGNAL] =
  { 0 };

/* GType Functions */

static void
sim_container_impl_dispose(GObject *gobject)
{
  G_OBJECT_CLASS(parent_class)->dispose(gobject);
}

static void
sim_container_impl_finalize(GObject *gobject)
{
  SimContainer *container = SIM_CONTAINER (gobject);

  sim_container_free_plugins_ul(container);
  sim_container_free_plugin_sids_ul(container);
  sim_container_free_sensors_ul(container);
  sim_container_free_hosts_ul(container);
  sim_container_free_nets_ul(container);
  sim_container_free_policies_ul(container);
  sim_container_free_directives_ul(container);
  sim_container_free_host_levels_ul(container);
  sim_container_free_net_levels_ul(container);
  sim_container_free_backlogs_ul(container);
  sim_container_free_events(container);
  sim_container_free_servers_ul(container);

  g_cond_free(container->_priv->cond_events);
  g_mutex_free(container->_priv->mutex_events);

  g_cond_free(container->_priv->cond_ar_events);
  g_mutex_free(container->_priv->mutex_ar_events);

  g_cond_free(container->_priv->cond_monitor_rules);
  g_mutex_free(container->_priv->mutex_monitor_rules);

  g_cond_free(container->_priv->rload_cond);
  g_mutex_free(container->_priv->rload_mutex);

  g_mutex_free(container->_priv->mutex_host_plugin_sids);

  g_free(container->_priv);

  G_OBJECT_CLASS(parent_class)->finalize(gobject);
}

static void
sim_container_class_init(SimContainerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS(class);

  parent_class = g_type_class_ref(G_TYPE_OBJECT);

  object_class->dispose = sim_container_impl_dispose;
  object_class->finalize = sim_container_impl_finalize;
}

static void
sim_container_instance_init(SimContainer *container)
{
  container->_priv = g_new0(SimContainerPrivate, 1);

  container->_priv->plugins = NULL;
  container->_priv->plugin_sids = NULL;
  container->_priv->sensors = NULL;
  container->_priv->hosts = NULL;
  container->_priv->nets = NULL;

  container->_priv->policies = NULL;
  container->_priv->directives = NULL;

  container->_priv->host_levels = NULL;
  container->_priv->net_levels = NULL;
  container->_priv->backlogs = NULL;

  container->_priv->events = g_queue_new();
  container->_priv->ar_events = g_async_queue_new();

  container->_priv->servers = NULL;

  container->_priv->host_plugin_sids = NULL; // Needed for cross-correlation.
  container->_priv->mutex_host_plugin_sids = g_mutex_new();

  /* Mutex Events Init */
  container->_priv->cond_events = g_cond_new();
  container->_priv->mutex_events = g_mutex_new();

  /* For action responses mutex and cond */
  container->_priv->cond_ar_events = g_cond_new();
  container->_priv->mutex_ar_events = g_mutex_new();

  // Mutex Monitor rules Init */
  container->_priv->monitor_rules = g_queue_new();
  container->_priv->cond_monitor_rules = g_cond_new();
  container->_priv->mutex_monitor_rules = g_mutex_new();

  /* Mutex loading data from remote server complete? Init. */
  container->_priv->rload_complete = FALSE;
  container->_priv->rload_cond = g_cond_new();
  container->_priv->rload_mutex = g_mutex_new();
}

/* Public Methods */

GType
sim_container_get_type(void)
{
  static GType object_type = 0;

  if (!object_type)
    {
      static const GTypeInfo type_info =
        { sizeof(SimContainerClass), NULL, NULL,
            (GClassInitFunc) sim_container_class_init, NULL, NULL, /* class data */
            sizeof(SimContainer), 0, /* number of pre-allocs */
            (GInstanceInitFunc) sim_container_instance_init, NULL /* value table */
        };

      g_type_init();

      object_type = g_type_register_static(G_TYPE_OBJECT, "SimContainer",
          &type_info, 0);
    }

  return object_type;
}

/*
 * Start the connection to the rservers and set the session as "initial".
 */
void
sim_container_start_temp_listen(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  GList *list;

  if (!sim_scheduler_task_rservers(SIM_SCHEDULER_STATE_INITIAL)) //we have to start the connection to the rservers here as there aren't any active conn at this moment
    {
      g_print(
          "Error: I can't find any remote server to load data. May be you forget to specify it in server.xml?. If this server hasn't got DB (as it seems you specified), it needs to load data from a parent server to work.\n");
      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "Error: I can't find any remote server to load data. May be you forget to specify it in server.xml?. If this server hasn't got DB (as it seems you specified), it needs to load data from a parent server to work.");
      exit(EXIT_FAILURE);
    }

  list = sim_server_get_sessions(ossim.server); //here we will get just one session, the session with primary master server,
  //as no other sessions are stablished yet
  while (list)
    {
      SimSession *session = (SimSession *) list->data;
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_start_temp_listen");

      sim_session_wait_fully_stablished(session); //this will wait until the session with primary server is fully stablished
      //this happens when we sended a "connect" and he answer with an "OK" in sim_session_read().
      list = list->next;
    }
  g_list_free(list);
}

/*
 * Basicly, unset the "is_initial" tag from rserver sessions This don't disconnect the rserver sessions.
 */
void
sim_container_stop_temp_listen(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  GList *list;
  list = sim_server_get_sessions(ossim.server);

  while (list)
    {
      SimSession *session = (SimSession *) list->data;
      sim_session_set_is_initial(session, FALSE); //From here, if some server tell this to do something, it will be done
      //in the "normal" event listener.
      list = list->next;
    }
  g_list_free(list);

}
/*
 *
 *
 *
 *
 */
SimContainer*
sim_container_new(SimConfig *config)
{
  SimContainer *container = NULL;

  g_return_val_if_fail(config, NULL);
  g_return_val_if_fail(SIM_IS_CONFIG (config), NULL);

  container = SIM_CONTAINER (g_object_new (SIM_TYPE_CONTAINER, NULL));

  if (sim_database_is_local(ossim.dbossim))
    {
      //Update server version
      gchar
          *query =
              g_strdup_printf(
                  "REPLACE INTO config(conf,value) VALUES (\"ossim_server_version\",\"%s\");",
                  ossim.version);
      sim_database_execute_no_query(ossim.dbossim, query);

      // We dont nedd to remove them in database. Just replace if needed
      // sim_container_db_delete_plugin_sid_directive_ul (container, ossim.dbossim);
      sim_container_db_delete_backlogs_ul(container, ossim.dbossim);
      if ((config->directive.filename) && (g_file_test(
          config->directive.filename, G_FILE_TEST_EXISTS)))
        sim_container_load_directives_from_file(container, ossim.dbossim,
            config->directive.filename);

      sim_container_db_load_plugins(container, ossim.dbossim);
      sim_container_db_load_plugin_sids(container, ossim.dbossim);
      //FIXME: add cross correlation to pre-load without DB.
      //sim_container_db_load_plugin_references (container, ossim.dbossim); //used for cross correlation
      //sim_container_db_load_host_plugin_sids (container, ossim.dbossim); //used for cross correlation
      sim_container_db_load_sensors(container, ossim.dbossim);
      sim_container_db_load_hosts(container, ossim.dbossim);
      sim_container_db_load_nets(container, ossim.dbossim);
      sim_container_db_load_policies(container, ossim.dbossim);
      sim_container_db_load_host_levels(container, ossim.dbossim);
      sim_container_db_load_net_levels(container, ossim.dbossim);
      sim_container_db_load_servers(container, ossim.dbossim); //NOTE: These are the children servers.
      //FIXME: this is done at this time only in the main master server. That's why
      //it doesn't appears in sim_container_remote_load_element(), it can be only local.

    }
  else //get data from a remote primary master server.
    {
      ossim.container = container; //this is needed here (although it "breaks" the common usage) when this server hasn't got a local DB.
      //We need to have the container assigned to the ossim variable to be able to access to it in the
      //answers from master server to be able to append things (hosts, nets, directives...)

      //As the scheduler thread can't be executed yet, we need to execute a special thread wich just will be used
      //to connect to rservers and get the data specified.
      sim_container_start_temp_listen(container);

      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_PLUGINS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_PLUGIN_SIDS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_SENSORS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_HOSTS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_NETS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_POLICIES);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_HOST_LEVELS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_NET_LEVELS);
      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_SERVER_ROLE); //this, strictly, is not container data because it will be stored
      //in server's config. Anyway to simplify remote queries I think
      //is better to do the query here. If DB is local, the role load
      //is done inside sim_config_load_database_config().

      sim_container_remote_load_element(SIM_DB_ELEMENT_TYPE_LOAD_COMPLETE);//not a "send me data" message. This will tell to the master
      //server that it must send a message to children server when
      //he ends all the msgs issued to children server.

      sim_container_wait_rload_complete(container); //wait until all data is loaded from remote server.
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_container_new: End loading data.");

      sim_container_stop_temp_listen(container);
    }

  //here we load the directives. If there are not DB defined, we don't try to insert anything in db, just get the directive and
  //directive plugin from master server.
  if ((config->directive.filename) && (g_file_test(config->directive.filename,
      G_FILE_TEST_EXISTS)))
    sim_container_load_directives_from_file(container, ossim.dbossim,
        config->directive.filename);

  //	sim_container_debug_print_all (container);
  //sim_container_debug_print_plugins (container);
  //sim_container_debug_print_plugin_sids (container);
  sim_container_debug_print_servers(container);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_new: End loading data.2");

  return container;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_container_db_get_host_os_ul(SimContainer *container, SimDatabase *database,
    GInetAddr *ia, GInetAddr *sensor)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gchar *os = NULL;
  gint row;

  g_return_val_if_fail(container != NULL, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(database != NULL, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);

  query
      = g_strdup_printf(
          "SELECT os FROM host_os WHERE ip = %u AND sensor = %u ORDER BY date DESC LIMIT 1",
          sim_inetaddr_ntohl(ia), sim_inetaddr_ntohl(sensor));
  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      if (gda_data_model_get_n_rows(dm) != 0) //to avoid (null)-Critical first time
        {
          if (!gda_value_is_null(value))
            os = gda_value_stringify(value);
        }
      else
        os = NULL;

      g_object_unref(dm);
    }
  else
    g_message("HOST OS DATA MODEL ERROR");

  g_free(query);

  return os;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_host_os_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *date, gchar *sensor,
    gchar *interface, gchar *os)
{
  gchar *query;
  gchar *os_esc;
  gint plugin_sid;
  gint plugin_id;
  SimPluginSid *Splugin_sid;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);
  g_return_if_fail(date);
  g_return_if_fail(os);

  os_esc = g_strescape(os, NULL);

  //we want to insert only the hosts defined in Policy->hosts or inside a network from policy->networks
  //	if((sim_container_get_host_by_ia(container,ia) == NULL) && (sim_container_get_nets_has_ia(container,ia) == NULL))
  //		return;

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO host_os (ip, date, os, sensor, interface) VALUES (%u, '%s', '%s', '%u', '%s')",
          sim_inetaddr_ntohl(ia), date, os_esc, sim_ipchar_2_ulong(sensor),
          interface);

  sim_database_execute_no_query(database, query);

  plugin_id = sim_container_get_plugin_id_by_name(container, "os"); //one query is more than enough

  Splugin_sid = sim_container_get_plugin_sid_by_name_ul(container, plugin_id,
      os_esc);

  plugin_sid = sim_plugin_sid_get_sid(Splugin_sid);

  /*
   if (g_strstr_len (os_esc, strlen (os_esc), "Windows"))
   plugin_sid = 1;//FIXME (insert correct codes.)
   */
  if (plugin_sid)
    sim_container_db_insert_host_plugin_sid(container, database, ia, plugin_id,
        plugin_sid);
  else
    g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
        "sim_container_db_insert_host_os_ul, Database problem");

  g_free(os_esc);
  g_free(query);
}

/*
 *
 *
 * This function is not called from anywhere at this time. Its not needed.
 *
 */
void
sim_container_db_update_host_os_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *date, gchar *curr_os,
    gchar *prev_os, GInetAddr *sensor)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);
  g_return_if_fail(date);
  g_return_if_fail(curr_os);
  g_return_if_fail(prev_os);

  if ((sim_container_get_host_by_ia(container, ia) == NULL)
      && (sim_container_get_nets_has_ia(container, ia) == NULL))
    return;

  query
      = g_strdup_printf(
          "UPDATE host_os SET date='%s', os='%s', previous='%s', anom = 1 WHERE ip = %u and sensor = %u",
          date, curr_os, prev_os, sim_inetaddr_ntohl(ia), sim_inetaddr_ntohl(
              sensor));

  sim_database_execute_no_query(database, query);

  g_free(query);
}

/*
 *
 * Returns from the db the host mac and the vendor (NULL on error), provided an ip and the sensor to wich belongs that ip.
 * It returns the mac and the vendor associated with the last time it was modified (if any)
 *
 */
gchar**
sim_container_db_get_host_mac_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, GInetAddr *sensor)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gchar *version = NULL;
  gchar **m_and_v = NULL; //mac and vendor
  gint row;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(database, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail(ia, NULL);

  query
      = g_strdup_printf(
          "SELECT mac, vendor FROM host_mac WHERE ip = %u and sensor = %u ORDER BY date DESC LIMIT 1",
          sim_inetaddr_ntohl(ia), sim_inetaddr_ntohl(sensor)); //we want the last

  dm = sim_database_execute_single_command(database, query);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_host_mac_ul: %s", query);

  if (dm)
    {
      m_and_v = g_new0 (gchar*, 2);

      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      if (gda_data_model_get_n_rows(dm) != 0)
        {
          if (!gda_value_is_null(value))
            m_and_v[0] = gda_value_stringify(value); //MAC
        }
      else
        m_and_v[0] = NULL;

      value = (GdaValue *) gda_data_model_get_value_at(dm, 1, 0);
      if (gda_data_model_get_n_rows(dm) != 0)
        {
          if (!gda_value_is_null(value))
            m_and_v[1] = gda_value_stringify(value); //vendor
        }
      else
        m_and_v[1] = NULL;

      g_object_unref(dm);
    }
  else
    {
      g_message("HOST MAC DATA MODEL ERROR");
    }
  g_free(query);

  return m_and_v;

}

/*
 *
 * Provided the ia, port, and protocol, this function
 * returns the service name (ssh, telnet..) as well as the service (apache, IIS...)
 * The variable that it returns is called "application"  but in the DB its called "version"
 */
gchar**
sim_container_db_get_host_service_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gint port, gint protocol,
    GInetAddr *sensor)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gchar *version = NULL;
  gchar **v_and_s;
  gint row;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(database, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail(ia, NULL);

  /* origin = 0 (pads). origin = 1 would be nmap. */
  query
      = g_strdup_printf(
          "SELECT version, service FROM host_services WHERE ip = %u and port = %u and protocol = %u and origin = 0 and sensor = %u ORDER BY date DESC LIMIT 1",
          sim_inetaddr_ntohl(ia), port, protocol, sim_inetaddr_ntohl(sensor));

  dm = sim_database_execute_single_command(database, query);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_host_service_ul: %s", query);

  if (dm)
    {
      v_and_s = g_new0 (gchar*, 2);

      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      if (gda_data_model_get_n_rows(dm) != 0)
        {
          if (!gda_value_is_null(value))
            v_and_s[0] = gda_value_stringify(value); //application (apache, iis...)
        }
      else
        v_and_s[0] = NULL;

      value = (GdaValue *) gda_data_model_get_value_at(dm, 1, 0);
      if (gda_data_model_get_n_rows(dm) != 0)
        {
          if (!gda_value_is_null(value))
            v_and_s[1] = gda_value_stringify(value); //service (http, ssh...)
        }
      else
        v_and_s[1] = NULL;

      g_object_unref(dm);
    }
  else
    {
      g_message("HOST SERVICE DATA MODEL ERROR");
    }
  g_free(query);

  //return version;
  return v_and_s;
}

/*
 * Provided the ia and sensor, this function returns a SimHostServices struct
 */
GList *
//SimHostServices structs
sim_container_db_get_host_services(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *sensor, gint port)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  SimHostServices *HostService;
  GList *list = NULL;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(database, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail(ia, NULL);

  query
      = g_strdup_printf(
          "SELECT protocol, service, version FROM host_services WHERE ip = %u AND sensor = %u AND port = %u",
          sim_inetaddr_ntohl(ia), sim_ipchar_2_ulong(sensor), port);

  dm = sim_database_execute_single_command(database, query);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_host_services: %s", query);

  HostService = g_new(SimHostServices, 1);

  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
          if (!gda_value_is_null(value))
            HostService->protocol = gda_value_get_integer(value);

          value = (GdaValue *) gda_data_model_get_value_at(dm, 1, 0);
          if (!gda_value_is_null(value))
            HostService->service = gda_value_stringify(value);

          value = (GdaValue *) gda_data_model_get_value_at(dm, 2, 0);
          if (!gda_value_is_null(value))
            HostService->version = gda_value_stringify(value);

          HostService->port = port;

          list = g_list_append(list, HostService);
        }
      g_object_unref(dm);
    }
  else
    {
      g_message("HOST SERVICE DATA MODEL ERROR");
    }
  g_free(query);

  return list;
}

/*
 * FIXME: this function is not called anymore.
 */
gchar*
sim_container_db_get_host_mac_vendor_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, GInetAddr *sensor)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gchar *vendor = NULL;
  gint row;

  g_return_val_if_fail(container != NULL, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(database != NULL, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail(ia, NULL);
  g_return_val_if_fail(sensor, NULL);

  query
      = g_strdup_printf(
          "SELECT vendor FROM host_mac WHERE ip = %u and sensor = %u ORDER BY date DESC LIMIT 1",
          sim_inetaddr_ntohl(ia), sim_inetaddr_ntohl(sensor)); //we want the last
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_host_mac_vendor_ul query: %s", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      if (gda_data_model_get_n_rows(dm) != 0) //to avoid (null)-Critical: gda_value_is_null: assertion `value != NULL' failed
        { //this happens the first time that an event is inserted
          if (!gda_value_is_null(value))
            {
              vendor = gda_value_stringify(value);
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_container_db_get_host_mac_vendor_ul vendor: %s", vendor);
            }
          else
            g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                "sim_container_db_get_host_mac_vendor_ul vendor value null");
        }
      else
        {
          vendor = NULL;
          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_container_db_get_host_mac_vendor_ul gda_data_model_get_n_rows(dm): %d",
              gda_data_model_get_n_rows(dm));
        }

      g_object_unref(dm);
    }
  else
    g_message("HOST MAC VENDOR DATA MODEL ERROR");

  g_free(query);

  return vendor;
}

/*
 *
 * 
 *
 *
 */
void
sim_container_db_insert_host_mac_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *date, gchar *mac,
    gchar *vendor, gchar *interface, gchar *sensor)
{
  gchar *query;
  gchar *vendor_esc;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);
  g_return_if_fail(date);
  g_return_if_fail(mac);
  g_return_if_fail(interface);
  g_return_if_fail(sensor);

  //we want to insert only the hosts defined in Policy->hosts or inside a network from policy->networks
  //	if((sim_container_get_host_by_ia(container,ia) == NULL) && (sim_container_get_nets_has_ia(container,ia) == NULL))
  //		return;

  vendor_esc = g_strescape(vendor, NULL);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO host_mac (ip, date, mac, vendor, sensor, interface) VALUES (%u, '%s', '%s', '%s', %u, '%s')",
          sim_inetaddr_ntohl(ia), date, mac, (vendor_esc) ? vendor_esc : "",
          sim_ipchar_2_ulong(sensor), interface);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "query: %s", query);

  sim_database_execute_no_query(database, query);

  g_free(query);
  g_free(vendor_esc);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_host_service_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *date, gint port,
    gint protocol, gchar *sensor, gchar *interface, gchar *service,
    gchar *application)
{
  gchar *query;
  struct servent *temp_serv = NULL;
  struct protoent *temp_proto = NULL;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);
  g_return_if_fail(date);
  g_return_if_fail(port); /* Needed for ints */
  g_return_if_fail(protocol);
  g_return_if_fail(sensor);
  g_return_if_fail(service);
  g_return_if_fail(application);

  //  if ((sim_container_get_host_by_ia (container, ia) == NULL) &&
  //      (sim_container_get_nets_has_ia (container, ia) == NULL))
  //    return; /* Update only those hosts that are defined as hosts or networks. */

  temp_proto = getprotobynumber(protocol);
  if (temp_proto->p_name == NULL)
    return; /* Since we don't know the proto we wont insert a service without a protocol */

  temp_serv = getservbyport(port, temp_proto->p_name);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO host_services (ip, date, port, protocol, service, service_type, version, origin, sensor, interface) VALUES (%u, '%s', %u, %u, '%s', '%s', '%s', 0, '%u', '%s')",
          sim_inetaddr_ntohl(ia), date, port, protocol,
          (temp_serv != NULL) ? temp_serv->s_name : "unknown", service,
          application, sim_ipchar_2_ulong(sensor), interface);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_insert_host_service_ul: query: %s", query);

  sim_database_execute_no_query(database, query);
  g_free(query);

  sim_container_db_insert_host_plugin_sid(container, database, ia,
      sim_container_get_plugin_id_by_name(container, "services"), port);

}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_host_ids_event_ul(SimContainer *container,
    SimDatabase *dbossim, SimDatabase *dbsnort, SimEvent *event,
    gchar *timestamp, gint sid, //sid & cid is needed here to reference the extra_data.
    gulong cid)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(dbossim);
  g_return_if_fail(SIM_IS_DATABASE (dbossim));
  g_return_if_fail(dbsnort);
  g_return_if_fail(SIM_IS_DATABASE (dbsnort));
  g_return_if_fail(timestamp);
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO host_ids (ip, date, hostname, sensor, plugin_sid, event_type, what, target, extra_data, sid, cid) VALUES (%u, '%s', '%s', '%s', %u, '%s', '%s', '%s', '%s', '%d', '%d')",
          sim_inetaddr_ntohl(event->src_ia), timestamp, event->data_storage[0],//hostname
          event->sensor, event->plugin_sid, event->data_storage[1], //event_type
          event->data_storage[3], //what
          event->data_storage[2], //target
          event->data_storage[4], //extra_data (nothing to do with extra_data table)
          sid, cid);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_insert_host_ids_event_ul query: %s", query);

  sim_database_execute_no_query(dbossim, query);
  g_free(query);

  sim_organizer_snort_extra_data_insert(dbsnort, event, sid, cid);

}

/*
 *
 * As now we don't modify entries, this function is not used anymore.
 *
 *
 */
void
sim_container_db_update_host_mac_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *date, gchar *curr_mac,
    gchar *prev_mac, gchar *vendor, GInetAddr *sensor)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);
  g_return_if_fail(date);
  g_return_if_fail(curr_mac);
  g_return_if_fail(prev_mac);
  g_return_if_fail(sensor);

  query
      = g_strdup_printf(
          "UPDATE host_mac SET date='%s', mac='%s', previous='%s', vendor='%s', anom = 1 WHERE ip = %u and sensor = %u",
          date, curr_mac, prev_mac, (vendor) ? vendor : "", sim_inetaddr_ntohl(
              ia), sim_inetaddr_ntohl(sensor));

  sim_database_execute_no_query(database, query);

  g_free(query);
}

/*
 *
 * As now we don't modify entries, this function is not used anymore.
 *
 */
void
sim_container_db_update_host_service_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gchar *date, gint port,
    gint protocol, gchar *service, gchar *application, GInetAddr *sensor)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);
  g_return_if_fail(date);
  g_return_if_fail(port);
  g_return_if_fail(protocol);
  g_return_if_fail(service);
  g_return_if_fail(application);

  query
      = g_strdup_printf(
          "UPDATE host_services SET date='%s', port=%u, protocol=%u, service_type='%s', version='%s' WHERE ip = %u AND origin = 0 AND sensor = %u",
          date, port, protocol, service, application, sim_inetaddr_ntohl(ia),
          sim_inetaddr_ntohl(sensor));

  sim_database_execute_no_query(database, query);

  g_free(query);
}

/*
 *
 * This function inserts event into DB, but it doesn't calculate the event->id, 
 * so the event-> id should be calculated outside.
 *
 */
void
sim_container_db_insert_event_ul(SimContainer *container, //FIXME: container is not used here.
    SimDatabase *database, SimEvent *event)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query = NULL;

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database != NULL);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(event != NULL);
  g_return_if_fail(SIM_IS_EVENT (event));

  query = sim_event_get_insert_clause(event);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 * This function gets an event-> id and insert the event into DB.
 */
void
sim_container_db_insert_event(SimContainer *container, //FIXME: container is not used here.
    SimDatabase *database, SimEvent *event)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query = NULL;

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database != NULL);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(event != NULL);
  g_return_if_fail(SIM_IS_EVENT (event));

  event->id = sim_database_get_id(ossim.dbossim, EVENT_SEQ_TABLE);

  query = sim_event_get_insert_clause(event);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_insert_event: query= %s", query);
  sim_database_execute_no_query(database, query);
  g_free(query);

  /*
   query = g_strdup_printf ("SELECT LAST_INSERT_ID()");
   dm = sim_database_execute_single_command (database, query);
   if (dm)
   {
   value = (GdaValue *) gda_data_model_get_value_at (dm, 0, 0);
   if (!gda_value_is_null (value))
   event->id = gda_value_get_bigint (value);	//get the id of the event so the event into the Container knows it

   g_object_unref(dm);
   }
   else
   g_message ("BACKLOG INSERT DATA MODEL ERROR");

   g_free (query);
   */
}

/*
 *
 * Update the DB with the event. May be something has changed, like C & A or...
 *
 *
 */
void
sim_container_db_update_event_ul(SimContainer *container,
    SimDatabase *database, SimEvent *event)
{
  gchar *query;
  gint n;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  query = sim_event_get_update_clause(event);
  n = sim_database_execute_no_query(database, query);
  g_free(query);

  if (!n)
    {
      query = sim_event_get_insert_clause(event);
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_container_db_update_event_ul: query 2 = %s", query);
      sim_database_execute_no_query(database, query);
      g_free(query);
    }
}

/*
 *
 * Replace the DB with the new event. May be something has changed, like priority or..
 *
 *
 */
void
sim_container_db_replace_event_ul(SimContainer *container,
    SimDatabase *database, SimEvent *event)
{
  gchar *query;
  gint n;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  query = sim_event_get_replace_clause(event);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 *
 *
 *
 */
void
sim_container_db_delete_backlog_by_id_ul(guint32 backlog_id)
{
  GdaDataModel *dm;
  GdaDataModel *dm1 = NULL;
  GdaValue *value;
  gchar *query0;
  gchar *query1;
  gchar *query2;
  guint32 event_id;
  gint row, count;

  query0 = g_strdup_printf(
      "SELECT event_id FROM backlog_event WHERE backlog_id = %u", backlog_id);
  dm = sim_database_execute_single_command(ossim.dbossim, query0);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          event_id = gda_value_get_bigint(value);

          query1 = g_strdup_printf(
              "SELECT COUNT(event_id) FROM backlog_event WHERE event_id = %u",
              event_id);
          //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_db_delete_backlog_by_id_ul: event_id= %u",event_id);
          dm1 = sim_database_execute_single_command(ossim.dbossim, query1);
          if (dm1)
            {
              //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "query1: %s",query1);

              value = (GdaValue *) gda_data_model_get_value_at(dm1, 0, 0);
              count = gda_value_get_bigint(value);

              //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "count: %d",count);

              if (count == 1)
                {
                  query2 = g_strdup_printf("DELETE FROM event WHERE id = %u",
                      event_id);
                  sim_database_execute_no_query(ossim.dbossim, query2);
                  g_free(query2);
                }

              g_object_unref(dm1);
            }
          else
            g_message(
                "Error: problem executing the following command in the DB: %s",
                query1);
          g_free(query1);
        }
      g_object_unref(dm);
    }
  g_free(query0);

  query0 = g_strdup_printf("DELETE FROM backlog_event WHERE backlog_id = %u",
      backlog_id);
  sim_database_execute_no_query(ossim.dbossim, query0);
  g_free(query0);

  query0 = g_strdup_printf("DELETE FROM backlog WHERE id = %u", backlog_id);
  sim_database_execute_no_query(ossim.dbossim, query0);
  g_free(query0);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_backlogs_ul(SimContainer *container,
    SimDatabase *database)
{
  GdaDataModel *dm;
  GdaDataModel *dm1;
  GdaValue *value;
  gchar *query0;
  gchar *query1;
  gchar *query2;
  guint32 backlog_id;
  guint32 event_id;
  gint row;

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database != NULL);
  g_return_if_fail(SIM_IS_DATABASE (database));

  /* Search Backlogs lost in backlog table */
  /*
   query0 =  g_strdup_printf ("SELECT id FROM backlog");
   dm = sim_database_execute_single_command (ossim.dbossim, query0);
   if (dm)
   {
   for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
   {
   value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
   backlog_id = gda_value_get_bigint (value);

   query1 = g_strdup_printf ("SELECT backlog_id FROM alarm WHERE backlog_id = %u", backlog_id);
   dm1 = sim_database_execute_single_command (ossim.dbossim, query1);
   if (dm1)
   {
   if (!gda_data_model_get_n_rows (dm1))
   sim_container_db_delete_backlog_by_id_ul (backlog_id);

   g_object_unref(dm1);
   }
   else
   {
   g_message ("BACKLOG SELECT DATA MODEL ERROR");
   }
   g_free (query1);
   }
   g_object_unref(dm);
   }
   else
   {
   g_message ("BACKLOG DELETE DATA MODEL ERROR");
   }
   g_free (query0);
   */
  /* Search Backlogs lost in backlog_event table */
  /*
   query0 =  g_strdup_printf ("SELECT DISTINCT backlog_id FROM backlog_event");
   dm = sim_database_execute_single_command (ossim.dbossim, query0);
   if (dm)
   {
   for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
   {
   value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
   backlog_id = gda_value_get_bigint (value);

   query1 = g_strdup_printf ("SELECT backlog_id FROM alarm WHERE backlog_id = %u", backlog_id);
   dm1 = sim_database_execute_single_command (ossim.dbossim, query1);
   if (dm1)
   {
   if (!gda_data_model_get_n_rows (dm1))
   sim_container_db_delete_backlog_by_id_ul (backlog_id);

   g_object_unref(dm1);
   }
   else
   {
   g_message ("BACKLOG EVENT SELECT DATA MODEL ERROR");
   }
   g_free (query1);
   }
   g_object_unref(dm);
   }
   else
   {
   g_message ("BACKLOG EVENT DELETE DATA MODEL ERROR");
   }
   g_free (query0);
   */
  /* Search Events lost in event table */
  /*
   query0 =  g_strdup_printf ("SELECT id FROM event");
   dm = sim_database_execute_single_command (ossim.dbossim, query0);
   if (dm)
   {
   for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
   {
   value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
   event_id = gda_value_get_bigint (value);

   query1 = g_strdup_printf ("SELECT event_id FROM backlog_event WHERE event_id = %u", event_id);
   dm1 = sim_database_execute_single_command (ossim.dbossim, query1);
   if (dm1)
   {
   if (!gda_data_model_get_n_rows (dm1))
   {
   query2 = g_strdup_printf ("DELETE FROM event WHERE id = %u", event_id);
   sim_database_execute_no_query (ossim.dbossim, query2);
   g_free (query2);
   }

   g_object_unref(dm1);
   }
   else
   {
   g_message ("EVENT SELECT DATA MODEL ERROR");
   }
   g_free (query1);
   }
   g_object_unref(dm);
   }
   else
   {
   g_message ("EVENT DATA MODEL ERROR");
   }
   g_free (query0);
   */
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_plugin_sid_directive_ul(SimContainer *container,
    SimDatabase *database)
{
  /*
   * The 1505 is a handcoded value os SIM_PLUGIN_ID_DIRECTIVE
   * it could be interesring make a #define with the delete clause
   */

  gchar *query = "DELETE FROM plugin_sid WHERE plugin_id = 1505";

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database != NULL);
  g_return_if_fail(SIM_IS_DATABASE (database));

  sim_database_execute_no_query(database, query);
}

/*
 * Table "host_plugin_sid" stores if some host has some event associated. For example, 
 * the host 192.168.1.1 has a vulnerability identified with the Nessus plugin 12123. then
 * a row will be: "192.168.1.1, 3001, 12123". This table is filled with the DoNessus.py plugin or
 * in sim_organizer_snort(), when storing OS & service data.
 * Table "plugin_reference" has the relationships between some plugins and another, between plugins and the ports they uses,
 * and between plugins and the OS they have.
 *
 * This function returns a SimPluginSid list with all of the plugin_sids associated with the destination
 * GInetAddr specified in the event.
 *
 */
GList*
sim_container_db_host_get_plugin_sids_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gint plugin_id, gint plugin_sid)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  GList *list = NULL;
  gint reference_id;
  gint reference_sid;

  g_return_val_if_fail(container, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);
  g_return_val_if_fail(ia, 0);
  g_return_val_if_fail(plugin_id > 0, 0);
  g_return_val_if_fail(plugin_sid > 0, 0);

  query = g_strdup_printf("SELECT reference_id, reference_sid "
    "FROM host_plugin_sid INNER JOIN plugin_reference "
    "ON (host_plugin_sid.plugin_id = plugin_reference.reference_id "
    "AND host_plugin_sid.plugin_sid = plugin_reference.reference_sid) "
    "WHERE host_ip = %u "
    "AND plugin_reference.plugin_id = %d "
    "AND plugin_reference.plugin_sid = %d", sim_inetaddr_ntohl(ia), plugin_id,
      plugin_sid);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_host_get_plugin_sids_ul. Query: -%s-", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          SimPluginSid *sid;

          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          reference_id = gda_value_get_integer(value);
          value = (GdaValue *) gda_data_model_get_value_at(dm, 1, row);
          reference_sid = gda_value_get_integer(value);

          sid = sim_container_get_plugin_sid_by_pky(container, reference_id,
              reference_sid);

          if (sid)
            list = g_list_append(list, sid); //append to a glist all of SimPluginSid objects wich a specific host has.
        }

      g_object_unref(dm);
    }
  else
    g_message("HOST PLUGIN SID DATA MODEL ERROR");

  g_free(query);

  return list;
}

/*
 * Table "host_plugin_sid" stores if some host has some event associated. For example, 
 * the host 192.168.1.1 has a vulnerability identified with the Nessus plugin 12123. then
 * a row will be: "192.168.1.1, 3001, 12123". This table is filled with the DoNessus.py plugin or
 * in sim_organizer_snort(), when storing OS & service data.
 * Table "plugin_reference" has the relationships between some plugins and another, between plugins and the ports they uses,
 * and between plugins and the OS they have.
 *
 * This function returns a SimPluginSid list with all of the plugin_sids associated with the destination
 * GInetAddr specified in the event.
 *
 */
GList*
sim_container_db_host_get_plugin_sids_ul_new(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gint plugin_id, gint plugin_sid)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  GList *list = NULL;
  gint reference_id;
  gint reference_sid;

  g_return_val_if_fail(container, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);
  g_return_val_if_fail(ia, 0);
  g_return_val_if_fail(plugin_id > 0, 0);
  g_return_val_if_fail(plugin_sid > 0, 0);

  query = g_strdup_printf("SELECT reference_id, reference_sid "
    "FROM host_plugin_sid INNER JOIN plugin_reference "
    "ON (host_plugin_sid.plugin_id = plugin_reference.reference_id "
    "AND host_plugin_sid.plugin_sid = plugin_reference.reference_sid) "
    "WHERE host_ip = %u "
    "AND plugin_reference.plugin_id = %d "
    "AND plugin_reference.plugin_sid = %d", sim_inetaddr_ntohl(ia), plugin_id,
      plugin_sid);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_host_get_plugin_sids_ul. Query: -%s-", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          SimPluginSid *sid;

          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          reference_id = gda_value_get_integer(value);
          value = (GdaValue *) gda_data_model_get_value_at(dm, 1, row);
          reference_sid = gda_value_get_integer(value);

          sid = sim_container_get_plugin_sid_by_pky(container, reference_id,
              reference_sid);

          if (sid)
            list = g_list_append(list, sid); //append to a glist all of SimPluginSid objects wich a specific host has.
        }

      g_object_unref(dm);
    }
  else
    g_message("HOST PLUGIN SID DATA MODEL ERROR");

  g_free(query);

  return list;
}

/*
 * returns all the plugin_sids (may be ports, OS or nessus vulnerabilities) that a specific host has
 */
GList*
sim_container_db_host_get_single_plugin_sid(SimContainer *container,
    SimDatabase *database, GInetAddr *ia)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  GList *list = NULL;
  gint plugin_id;
  gint plugin_sid;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(database, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail(ia, NULL);

  query = g_strdup_printf(
      "SELECT plugin_id, plugin_sid FROM host_plugin_sid WHERE host_ip= %u",
      sim_inetaddr_ntohl(ia));
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_host_get_single_plugin_sid Query: %s", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          SimPluginSid *sid;

          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          plugin_id = gda_value_get_integer(value);

          value = (GdaValue *) gda_data_model_get_value_at(dm, 1, row);
          plugin_sid = gda_value_get_integer(value);

          sid = sim_container_get_plugin_sid_by_pky(container, plugin_id,
              plugin_sid);

          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_container_db_host_get_single_plugin_sid plugin id/sid &sid: %d/%d %x",
              plugin_id, plugin_sid, sid);

          if (sid)
            list = g_list_append(list, sid);
        }

      g_object_unref(dm);
    }
  else
    g_message("HOST PLUGIN SID DATA MODEL ERROR");

  g_free(query);

  return list;
}

/*
 *
 */
GList*
sim_container_db_get_reference_sid(SimContainer *container,
    SimDatabase *database, gint reference_id, gint plugin_id, gint plugin_sid)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  GList *list = NULL;
  gint reference_sid = 0;

  g_return_val_if_fail(container, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);

  query
      = g_strdup_printf(
          "SELECT reference_sid FROM plugin_reference WHERE plugin_id = %d AND plugin_sid = %d AND reference_id = %d",
          plugin_id, plugin_sid, reference_id);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_reference_sid query: %s", query);
  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          reference_sid = gda_value_get_integer(value);

          if (reference_sid)
            list = g_list_append(list, GINT_TO_POINTER(reference_sid));
          //			gda_value_free (value); //FIXME: Fix all the leaks about this (there are lots). Check if this works. (its not working in sim-plugin-sid.c)
        }

      g_object_unref(dm);
    }
  else
    g_message("PLUGIN_REFERENCE DATA MODEL ERROR");

  g_free(query);

  return list;
}

/*
 * Here we check if in the plugin_reference table, once provided a reference_id and a reference_sid,
 * it's the same than the values inside plugin_reference.
 */
gboolean
sim_container_db_plugin_reference_match(SimContainer *container,
    SimDatabase *database, gint plugin_id, gint plugin_sid, gint reference_id,
    gint reference_sid)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  gint cmp_plugin_id;
  gint cmp_plugin_sid;

  g_return_val_if_fail(container, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);

  query
      = g_strdup_printf(
          "SELECT plugin_id, plugin_sid FROM plugin_reference WHERE reference_id = %d AND reference_sid = %d",
          reference_id, reference_sid);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          cmp_plugin_id = gda_value_get_integer(value);

          value = (GdaValue *) gda_data_model_get_value_at(dm, 1, row);
          cmp_plugin_sid = gda_value_get_integer(value);

          if ((cmp_plugin_id == plugin_id) && (cmp_plugin_sid == plugin_sid))
            {
              g_free(query);
              g_object_unref(dm);
              return TRUE;
            }
        }
      g_object_unref(dm);
    }
  else
    g_message("OSVDB DATA MODEL ERROR");

  g_free(query);
  return FALSE;

}

/*
 * Given a osvdb_id, returns a list with all the OSVDB version_name ("7.3.4 Rc3" i.e.)
 */
GList*
sim_container_db_get_osvdb_version_name(SimDatabase *database, gint osvdb_id)
{
  GdaDataModel *dm;
  gint row;
  GList *list = NULL;
  GdaValue *value;
  gchar *query;
  gchar *version_name = NULL;

  g_return_val_if_fail(database, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);

  query
      = g_strdup_printf(
          "SELECT version_name FROM object_version LEFT JOIN (object_correlation, object) ON (object_version.version_id = object_correlation.version_id AND object_correlation.corr_id = object.corr_id) WHERE object.osvdb_id= %d",
          osvdb_id);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_osvdb_version_name query: %s", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          version_name = gda_value_stringify(value);
          if (version_name)
            list = g_list_append(list, version_name);
        }
      g_object_unref(dm);
    }
  else
    g_message("OSVDB DATA MODEL ERROR");

  g_free(query);

  return list;
}

/*
 * Given a osvdb_id, returns a list with all the possible OSVDB base_name ("wu-ftpd" i.e.)
 */
GList*
sim_container_db_get_osvdb_base_name(SimDatabase *database, gint osvdb_id)
{
  GdaDataModel *dm;
  gint row;
  GList *list = NULL;
  GdaValue *value;
  gchar *query;
  gchar *base_name = NULL;

  g_return_val_if_fail(database, NULL);
  g_return_val_if_fail(SIM_IS_DATABASE (database), NULL);

  query
      = g_strdup_printf(
          "SELECT base_name FROM object_base LEFT JOIN (object_correlation, object) ON (object_base.base_id = object_correlation.base_id AND object_correlation.corr_id = object.corr_id) WHERE object.osvdb_id= %d",
          osvdb_id);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_get_osvdb_base_name query: %s", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          base_name = gda_value_stringify(value);

          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_container_db_get_osvdb_base_name base_name: -%s-", base_name);

          if (base_name)
            list = g_list_append(list, base_name);
        }
      g_object_unref(dm);
    }
  else
    g_message("OSVDB DATA MODEL ERROR");

  g_free(query);

  return list;
}

/*
 * 
 */
void
sim_container_db_insert_host_plugin_sid(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gint plugin_id, gint plugin_sid)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);

  g_mutex_lock (container->_priv->mutex_host_plugin_sids);
  sim_container_db_insert_host_plugin_sid_ul (container, database, ia, plugin_id, plugin_sid);
	g_mutex_unlock (container->_priv->mutex_host_plugin_sids);
}

/*
 * In this function we will insert the data from an OS event, to the host_plugin_sid table, so we
 * will be able to do some cross-correlation.
 */
void
sim_container_db_insert_host_plugin_sid_ul(SimContainer *container,
    SimDatabase *database, GInetAddr *ia, gint plugin_id, gint plugin_sid)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(ia);

  //this is a plugin_sid wich comes from an OS event, (the plugin_id)
  query
      = g_strdup_printf(
          "REPLACE INTO host_plugin_sid (host_ip, plugin_id, plugin_sid) VALUES (%u, %d, %d) ",
          sim_inetaddr_ntohl(ia), plugin_id, plugin_sid);

  sim_database_execute_no_query(database, query);

  g_free(query);
}

/*
 *
 *
 *
 *
 */
gint
sim_container_db_get_recovery_ul(SimContainer *container, SimDatabase *database)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query = "SELECT value as recovery FROM config WHERE conf = 'recovery'";
  gint row;
  gint recovery = 1;

  g_return_val_if_fail(container != NULL, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database != NULL, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);

  dm = sim_database_execute_single_command(database, query); //well... just execute the upper query and returns DataModel
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          /* Recovery */
          gchar *recovery_string;
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          if (NULL != (recovery_string = gda_value_stringify(value)))
            {
              recovery = atoi(recovery_string);
              g_free(recovery_string);
            }
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("RECOVERY DATA MODEL ERROR");
    }

  return recovery;
}

/*
 *
 * gets the recovery level of the SIM.
 *
 *
 */
gint
sim_container_db_get_recovery(SimContainer *container, SimDatabase *database)
{
  gint recovery;

  g_return_val_if_fail(container != NULL, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database != NULL, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);

  G_LOCK(s_mutex_config);
  recovery = sim_container_db_get_recovery_ul(container, database);
  G_UNLOCK(s_mutex_config);

  return recovery;
}

/*
 * FIXME: Not used anymore. check and remove.
 */
gint
sim_container_db_get_threshold_ul(SimContainer *container,
    SimDatabase *database)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query =
      "SELECT value as threshold FROM config WHERE conf = 'threshold'";
  gint row;
  gint threshold = 1;

  g_return_val_if_fail(container != NULL, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database != NULL, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          /* Threshold */
          gchar *threshold_string;
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          if (NULL != (threshold_string = gda_value_stringify(value)))
            {
              threshold = atoi(threshold_string);
              g_free(threshold_string);
            }
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("THRESHOLD DATA MODEL ERROR");
    }

  return threshold;
}

/*
 * FIXME: Not used anymore. check with dk if needed and remove.
 */
gint
sim_container_db_get_threshold(SimContainer *container, SimDatabase *database)
{
  gint threshold;

  g_return_val_if_fail(container != NULL, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database != NULL, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);

  G_LOCK(s_mutex_config);
  threshold = sim_container_db_get_threshold_ul(container, database);
  G_UNLOCK(s_mutex_config);

  return threshold;
}

/*
 *
 *
 *
 *
 */
gint
sim_container_db_get_max_plugin_sid_ul(SimContainer *container,
    SimDatabase *database, gint plugin_id)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gint row;
  gint max_sid = 0;

  g_return_val_if_fail(container != NULL, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database != NULL, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);
  g_return_val_if_fail(plugin_id > 0, 0);

  query = g_strdup_printf(
      "SELECT max(sid) FROM plugin_sid WHERE plugin_id = %d", plugin_id);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          /* Max Sid */
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          if (!gda_value_is_null(value))
            max_sid = gda_value_get_integer(value);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("MAX PLUGIN SID DATA MODEL ERROR");
    }

  g_free(query);

  return max_sid;
}

/*
 *
 *
 *
 *
 */
gint
sim_container_db_get_max_plugin_sid(SimContainer *container,
    SimDatabase *database, gint plugin_id)
{
  gint max_sid;

  g_return_val_if_fail(container != NULL, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail(database != NULL, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (database), 0);
  g_return_val_if_fail(plugin_id > 0, 0);

  G_LOCK(s_mutex_plugin_sids);
  max_sid = sim_container_db_get_max_plugin_sid_ul(container, database,
      plugin_id);
  G_UNLOCK(s_mutex_plugin_sids);

  return max_sid;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_plugins_ul(SimContainer *container, SimDatabase *database)
{
  SimPlugin *plugin;
  GdaDataModel *dm;
  gint row;
  gchar *query = "SELECT id, type, name, description FROM plugin";
  SimCommand *cmd;
  GList *list;
  GList *list2;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_load_plugins_ul OOOO");
  if (sim_database_is_local(database))
    {
      dm = sim_database_execute_single_command(database, query);
      if (dm)
        {
          for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
            {
              plugin = sim_plugin_new_from_dm(dm, row);
              container->_priv->plugins = g_list_append(
                  container->_priv->plugins, plugin);
            }

          g_object_unref(dm);
        }
      else
        g_message("PLUGINS DATA MODEL ERROR");

    }
  else
    {
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_db_load_plugins_ul");

      list = sim_server_get_sessions(ossim.server);

      //As there are not local DB, we are going to connect to rservers to get the data. In fact we doesn't load the data here, we send a msg
      //to primary rserver and wait. The rserver will send us a msg with all the data that will be processed in sim_session_cmd_database_answer().
      while (list) //list of the sessions connected to the server. We have to check some things before getting data:
        {
          SimSession *session = (SimSession *) list->data;
          if (sim_session_is_master_server(session)) //check if the session connected has rights
            {
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_container_db_load_plugins_ul2");
              list2 = ossim.config->rservers;
              while (list2)
                {
                  SimConfigRServer *rserver = (SimConfigRServer*) list2->data;
                  sim_config_rserver_debug_print(rserver);

                  g_log(
                      G_LOG_DOMAIN,
                      G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_plugins_ul: sesionarriba: %s, rservername: %s, ip: %d, primary: %d",
                      sim_session_get_hostname(session), rserver->name,
                      rserver->ip, rserver->primary);
                  if (!strcmp(sim_session_get_hostname(session), rserver->name)) //check if the session connected is the primary server
                    {
                      if (rserver->primary)
                        {
                          cmd = sim_command_new_from_type(
                              SIM_COMMAND_TYPE_DATABASE_QUERY);
                          cmd->id = 0; //not used at this moment.
                          cmd->data.database_query.database_element_type
                              = SIM_DB_ELEMENT_TYPE_PLUGINS;
                          cmd->data.database_query.servername = g_strdup(
                              sim_server_get_name(ossim.server));
                          sim_session_write(session, cmd);
                          g_object_unref(cmd);
                          return; //OK, we need to send it only to one server.
                        }
                      else
                        g_message(
                            "Error. Not primary server defined & connected. May be you need to check server's config.xml statment");
                    }

                  list2 = list2->next;
                }

            }
          list = list->next;
        }
    }
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_plugin_ul(SimContainer *container, SimPlugin *plugin)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin);
  g_return_if_fail(SIM_IS_PLUGIN (plugin));

  container->_priv->plugins = g_list_append(container->_priv->plugins, plugin);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_plugin_ul(SimContainer *container, SimPlugin *plugin)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin);
  g_return_if_fail(SIM_IS_PLUGIN (plugin));

  container->_priv->plugins = g_list_remove(container->_priv->plugins, plugin);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_plugins_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->plugins);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_plugins_ul(SimContainer *container, GList *plugins)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugins);

  container->_priv->plugins = g_list_concat(container->_priv->plugins, plugins);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_plugins_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->plugins;
  while (list)
    {
      SimPlugin *plugin = (SimPlugin *) list->data;
      g_object_unref(plugin);

      list = list->next;
    }
  g_list_free(container->_priv->plugins);
  container->_priv->plugins = NULL;
}

/*
 *
 *
 *
 *
 */
SimPlugin*
sim_container_get_plugin_by_id_ul(SimContainer *container, gint id)
{
  SimPlugin *plugin;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(id > 0, NULL);

  list = container->_priv->plugins;
  while (list)
    {
      plugin = (SimPlugin *) list->data;

      if (sim_plugin_get_id(plugin) == id)
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return plugin;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_plugins(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_plugins);
  sim_container_db_load_plugins_ul(container, database);
  G_UNLOCK(s_mutex_plugins);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_plugin(SimContainer *container, SimPlugin *plugin)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin);
  g_return_if_fail(SIM_IS_PLUGIN (plugin));

  G_LOCK(s_mutex_plugins);
  sim_container_append_plugin_ul(container, plugin);
  G_UNLOCK(s_mutex_plugins);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_plugin(SimContainer *container, SimPlugin *plugin)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin);
  g_return_if_fail(SIM_IS_PLUGIN (plugin));

  G_LOCK(s_mutex_plugins);
  sim_container_remove_plugin_ul(container, plugin);
  G_UNLOCK(s_mutex_plugins);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_plugins(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_plugins);
  list = sim_container_get_plugins_ul(container);
  G_UNLOCK(s_mutex_plugins);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_plugins(SimContainer *container, GList *plugins)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugins);

  G_LOCK(s_mutex_plugins);
  sim_container_set_plugins_ul(container, plugins);
  G_UNLOCK(s_mutex_plugins);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_plugins(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_plugins);
  sim_container_free_plugins_ul(container);
  G_UNLOCK(s_mutex_plugins);
}

/*
 *
 *
 *
 *
 */
SimPlugin*
sim_container_get_plugin_by_id(SimContainer *container, gint id)
{
  SimPlugin *plugin;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(id > 0, NULL);

  G_LOCK(s_mutex_plugins);
  plugin = sim_container_get_plugin_by_id_ul(container, id);
  G_UNLOCK(s_mutex_plugins);

  return plugin;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_plugin_sids_ul(SimContainer *container,
    SimDatabase *database)
{
  SimPluginSid *plugin_sid;
  GdaDataModel *dm;
  gint row;
  gchar *query =
      "SELECT plugin_id, sid, reliability, priority, name FROM plugin_sid";

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      g_message(
          "Please be patient; This will take a while. Depending on your plugin_sid list and your system, may be some minutes...");
      //this is done outside im_plugin_sid_new_from_dm() rying to accelerate the loop
      g_return_if_fail(GDA_IS_DATA_MODEL(dm));

      gint count = gda_data_model_get_n_rows(dm);

      for (row = 0; row < count; row++)
        {
          plugin_sid = sim_plugin_sid_new_from_dm(dm, row);
          container->_priv->plugin_sids = g_list_prepend(
              container->_priv->plugin_sids, plugin_sid);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("PLUGINS DATA MODEL ERROR");
    }
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_load_plugin_sids_ul: ended loading");
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_plugin_sid_ul(SimContainer *container,
    SimPluginSid *plugin_sid)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin_sid);
  g_return_if_fail(SIM_IS_PLUGIN_SID (plugin_sid));

  container->_priv->plugin_sids = g_list_append(container->_priv->plugin_sids,
      plugin_sid);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_plugin_sid_ul(SimContainer *container,
    SimPluginSid *plugin_sid)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin_sid);
  g_return_if_fail(SIM_IS_PLUGIN_SID (plugin_sid));

  container->_priv->plugin_sids = g_list_remove(container->_priv->plugin_sids,
      plugin_sid);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_plugin_sids_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->plugin_sids);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_plugin_sids_ul(SimContainer *container, GList *plugin_sids)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin_sids);

  container->_priv->plugin_sids = g_list_concat(container->_priv->plugin_sids,
      plugin_sids);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_plugin_sids_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->plugin_sids;
  while (list)
    {
      SimPluginSid *plugin_sid = (SimPluginSid *) list->data;
      g_object_unref(plugin_sid);

      list = list->next;
    }
  g_list_free(container->_priv->plugin_sids);
  container->_priv->plugin_sids = NULL;
}

/*
 *  Given the name of a plugin, this function returns the plugin_id. 
 *  Returns -1 on error.
 *
 */

inline gint
sim_container_get_plugin_id_by_name(SimContainer *container, gchar *name)
{
  gint plugin_id;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(name);

  G_LOCK(s_mutex_plugins);
  plugin_id = sim_container_get_plugin_id_by_name_ul(container, name);
  G_UNLOCK(s_mutex_plugins);

  return plugin_id;
}

/*
 * 
 *
 */

inline gint
sim_container_get_plugin_id_by_name_ul(SimContainer *container, gchar *name)
{
  GList *list;
  gboolean found = FALSE;
  gchar *query;
  GdaDataModel *dm;
  GdaValue *value;
  gint plugin_id = -1;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(name);

  query = g_strdup_printf("SELECT id FROM plugin WHERE name='%s'", name);

  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      if (gda_data_model_get_n_rows(dm))
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
          plugin_id = gda_value_get_integer(value);
        }
      g_object_unref(dm);
    }
  else
    g_message("PLUGIN ID DATA MODEL ERROR");

  g_free(query);
  return plugin_id;

}

/*
 *
 * Returns the SimPluginSid object (plugin_sid) associated with the plugin_id and plugin_sid 
 * from the event issued.
 *
 */
SimPluginSid*
sim_container_get_plugin_sid_by_pky_ul(SimContainer *container, gint plugin_id,
    gint sid)
{
  SimPluginSid *plugin_sid;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(plugin_id > 0, NULL);
  g_return_val_if_fail(sid > 0, NULL);

  list = container->_priv->plugin_sids;
  while (list)
    {
      plugin_sid = (SimPluginSid *) list->data;

      if (!plugin_sid)
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_container_get_plugin_sid_by_pky_ul. plugin error. %d / %d",
              plugin_id, sid);
          return NULL;
        }

      if ((sim_plugin_sid_get_plugin_id(plugin_sid) == plugin_id) && //if the plugin id of the plugin sid provided match,
          (sim_plugin_sid_get_sid(plugin_sid) == sid)) //and if the plugin sid matches, we return it.
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return plugin_sid;
}

/*
 *
 *
 *
 *
 */
SimPluginSid*
sim_container_get_plugin_sid_by_name_ul(SimContainer *container,
    gint plugin_id, const gchar *name)
{
  SimPluginSid *plugin_sid;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(plugin_id > 0, NULL);
  g_return_val_if_fail(name, NULL);

  list = container->_priv->plugin_sids;
  while (list)
    {
      plugin_sid = (SimPluginSid *) list->data;

      if ((sim_plugin_sid_get_plugin_id(plugin_sid) == plugin_id) && (!strcmp(
          name, sim_plugin_sid_get_name(plugin_sid))))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return plugin_sid;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_plugin_sids(SimContainer *container,
    SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_plugin_sids);
  sim_container_db_load_plugin_sids_ul(container, database);
  G_UNLOCK(s_mutex_plugin_sids);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_plugin_sid(SimContainer *container,
    SimPluginSid *plugin_sid)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin_sid);
  g_return_if_fail(SIM_IS_PLUGIN_SID (plugin_sid));

  G_LOCK(s_mutex_plugin_sids);
  sim_container_append_plugin_sid_ul(container, plugin_sid);
  G_UNLOCK(s_mutex_plugin_sids);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_plugin_sid(SimContainer *container,
    SimPluginSid *plugin_sid)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin_sid);
  g_return_if_fail(SIM_IS_PLUGIN_SID (plugin_sid));

  G_LOCK(s_mutex_plugin_sids);
  sim_container_remove_plugin_sid_ul(container, plugin_sid);
  G_UNLOCK(s_mutex_plugin_sids);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_plugin_sids(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_plugin_sids);
  list = sim_container_get_plugin_sids_ul(container);
  G_UNLOCK(s_mutex_plugin_sids);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_plugin_sids(SimContainer *container, GList *plugin_sids)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(plugin_sids);

  G_LOCK(s_mutex_plugin_sids);
  sim_container_set_plugin_sids_ul(container, plugin_sids);
  G_UNLOCK(s_mutex_plugin_sids);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_plugin_sids(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_plugin_sids);
  sim_container_free_plugin_sids_ul(container);
  G_UNLOCK(s_mutex_plugin_sids);
}

/*
 *
 * Returns the SimPluginSid object (plugin_sid) associated with the plugin_id and plugin_sid
 * from the event issued.
 *
 */
SimPluginSid*
sim_container_get_plugin_sid_by_pky(SimContainer *container, gint plugin_id,
    gint sid)
{
  SimPluginSid *plugin_sid;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(plugin_id > 0, NULL);
  g_return_val_if_fail(sid > 0, NULL);

  G_LOCK(s_mutex_plugin_sids);
  plugin_sid
      = sim_container_get_plugin_sid_by_pky_ul(container, plugin_id, sid);
  G_UNLOCK(s_mutex_plugin_sids);

  return plugin_sid;
}

/*
 *
 *
 *
 *
 */
SimPluginSid*
sim_container_get_plugin_sid_by_name(SimContainer *container, gint plugin_id,
    const gchar *name)
{
  SimPluginSid *plugin_sid;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(plugin_id > 0, NULL);
  g_return_val_if_fail(name, NULL);

  G_LOCK(s_mutex_plugin_sids);
  plugin_sid = sim_container_get_plugin_sid_by_name_ul(container, plugin_id,
      name);
  G_UNLOCK(s_mutex_plugin_sids);

  return plugin_sid;
}

/*
 * Get the hash table of host plugin sids mutex.
 *
 */
GMutex *
sim_container_get_host_plugin_sids_mutex (SimContainer * container)
{
	g_return_val_if_fail (container, NULL);
	g_return_val_if_fail (SIM_IS_CONTAINER(container), NULL);

	return (container->_priv->mutex_host_plugin_sids);
}


/*
 * Get the hash table of host plugin sids.
 *
 */
GHashTable *
sim_container_get_host_plugin_sids (SimContainer * container)
{
	g_return_val_if_fail (container, NULL);
	g_return_val_if_fail (SIM_IS_CONTAINER(container), NULL);

	return (container->_priv->host_plugin_sids);
}

/*
 * Set the hash table of host plugin sids.
 *
 */
void
sim_container_set_host_plugin_sids (SimContainer * container, GHashTable * host_plugin_sids)
{
	g_return_if_fail (container);
	g_return_if_fail (SIM_IS_CONTAINER(container));

	container->_priv->host_plugin_sids = host_plugin_sids;
}

/*
 * Load all the plugins related to well known conditions in a specific host and
 * return a hash table.
 *
 */
void
sim_container_db_load_host_plugin_sids_ul (SimContainer * container, SimDatabase * database)
{
  GdaDataModel  * dm;
  GdaValue      * value;
  gchar         * query;
  gint            row;
  GList         * list = NULL;
	gchar         * key = NULL;
	GHashTable    * host_plugin_sids;
	SimPluginSid  * sid;
	gulong          host_ip;
	gint            plugin_id;
	gint            plugin_sid;
  gint            reference_id;
  gint            reference_sid;

	if ((host_plugin_sids = container->_priv->host_plugin_sids) != NULL)
		g_hash_table_destroy(host_plugin_sids);
	/* FIXME: This may be a g_int_hash/g_int_equal instead to be more efficient. */
	host_plugin_sids = g_hash_table_new_full(g_str_hash, g_str_equal, g_free, g_list_free);

	query = g_strdup_printf ("SELECT host_ip, plugin_reference.plugin_id, plugin_reference.plugin_sid, reference_id, reference_sid "
													 "FROM host_plugin_sid INNER JOIN plugin_reference "
													 "ON (host_plugin_sid.plugin_id = plugin_reference.reference_id "
													 "AND host_plugin_sid.plugin_sid = plugin_reference.reference_sid) "
													 "GROUP BY host_ip, plugin_reference.plugin_id, plugin_reference.plugin_sid");

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "%s: Query: %s", __func__, query);

  dm = sim_database_execute_single_command (database, query);

  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
		{
			/* Build a hash with the first three values. */
			value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
			host_ip = gda_value_get_uinteger (value);
			value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
			plugin_id = gda_value_get_integer (value);		
			value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
			plugin_sid = gda_value_get_integer (value);		
			key = g_strdup_printf("%lu:%d:%d", host_ip, plugin_id, plugin_sid);

			/* Get reference plugin id and sid and build a new SimPluginSid. */
		  value = (GdaValue *) gda_data_model_get_value_at (dm, 3, row);
	  	reference_id = gda_value_get_integer (value);
		  value = (GdaValue *) gda_data_model_get_value_at (dm, 4, row);
		  reference_sid = gda_value_get_integer (value);

	  	sid = sim_container_get_plugin_sid_by_pky (container,
																						     reference_id,
																						     reference_sid);

			/* Search for this hash. */
			if (sid)
			{
				if (list = (GList *) g_hash_table_lookup (host_plugin_sids, key))
				{
					g_free (key);
					list = g_list_append (list, sid);
				}
				else
				{
					list = g_list_append (list, sid);
					g_hash_table_insert (host_plugin_sids, key, list);
				}
			}
		}

    g_object_unref(dm);
  }
  else
    g_message ("%s: DATA MODEL ERROR", __func__);
  
	g_free (query);
	container->_priv->host_plugin_sids = host_plugin_sids;
}


/*
 * Lock db and load all the plugins related to well known conditions in a specific host and
 * return a hash table.
 */
void
sim_container_db_load_host_plugin_sids (SimContainer * container, SimDatabase * database)
{
  g_return_if_fail (container);
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_if_fail (database);
  g_return_if_fail (SIM_IS_DATABASE (database));

  g_mutex_lock (container->_priv->mutex_host_plugin_sids);
  sim_container_db_load_host_plugin_sids_ul (container, database);
	g_mutex_unlock (container->_priv->mutex_host_plugin_sids);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_sensors_ul (SimContainer  *container,
				  SimDatabase   *database)
{
  SimSensor *sensor;
  GdaDataModel *dm;
  gint row;
  gchar *query = "SELECT name, ip, port, connect FROM sensor";

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          sensor = sim_sensor_new_from_dm(dm, row);
          container->_priv->sensors = g_list_append(container->_priv->sensors,
              sensor);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("SENSORS DATA MODEL ERROR");
    }
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_sensor_ul(SimContainer *container, SimSensor *sensor)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(sensor);
  g_return_if_fail(SIM_IS_SENSOR (sensor));

  container->_priv->sensors = g_list_append(container->_priv->sensors, sensor);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_sensor_ul(SimContainer *container, SimSensor *sensor)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(sensor);
  g_return_if_fail(SIM_IS_SENSOR (sensor));

  container->_priv->sensors = g_list_remove(container->_priv->sensors, sensor);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_sensors_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->sensors);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_sensors_ul(SimContainer *container, GList *sensors)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(sensors);

  container->_priv->sensors = g_list_concat(container->_priv->sensors, sensors);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_sensors_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->sensors;
  while (list)
    {
      SimSensor *sensor = (SimSensor *) list->data;
      g_object_unref(sensor);

      list = list->next;
    }
  g_list_free(container->_priv->sensors);
  container->_priv->sensors = NULL;
}

/*
 *
 *
 *
 *
 */
SimSensor*
sim_container_get_sensor_by_name_ul(SimContainer *container, gchar *name)
{
  SimSensor *sensor;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  list = container->_priv->sensors;
  while (list)
    {
      sensor = (SimSensor *) list->data;

      if (!g_ascii_strcasecmp(sim_sensor_get_name(sensor), name))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return sensor;
}

/*
 *
 * Check every sensor defined previously (they're inside the container) 
 * to see if the ia (internet address) matches with it. Then returns the sensor
 * as an object.
 *
 */
SimSensor*
sim_container_get_sensor_by_ia_ul(SimContainer *container, GInetAddr *ia)
{
  SimSensor *sensor;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);
  list = container->_priv->sensors;
  while (list)
    {
      sensor = (SimSensor *) list->data;

      if (gnet_inetaddr_noport_equal(sim_sensor_get_ia(sensor), ia))
        {
          found = TRUE;
          return sensor;
        }
      list = list->next;
    }
  if (!found)
    return NULL;

}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_sensors(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_sensors);
  sim_container_db_load_sensors_ul(container, database);
  G_UNLOCK(s_mutex_sensors);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_sensor(SimContainer *container, SimSensor *sensor)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(sensor);
  g_return_if_fail(SIM_IS_SENSOR (sensor));

  G_LOCK(s_mutex_sensors);
  sim_container_append_sensor_ul(container, sensor);
  G_UNLOCK(s_mutex_sensors);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_sensor(SimContainer *container, SimSensor *sensor)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(sensor);
  g_return_if_fail(SIM_IS_SENSOR (sensor));

  G_LOCK(s_mutex_sensors);
  sim_container_remove_sensor_ul(container, sensor);
  G_UNLOCK(s_mutex_sensors);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_sensors(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_sensors);
  list = sim_container_get_sensors_ul(container);
  G_UNLOCK(s_mutex_sensors);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_sensors(SimContainer *container, GList *sensors)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(sensors);

  G_LOCK(s_mutex_sensors);
  sim_container_set_sensors_ul(container, sensors);
  G_UNLOCK(s_mutex_sensors);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_sensors(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_sensors);
  sim_container_free_sensors_ul(container);
  G_UNLOCK(s_mutex_sensors);
}

/*
 *
 *
 *
 *
 */
SimSensor*
sim_container_get_sensor_by_name(SimContainer *container, gchar *name)
{
  SimSensor *sensor;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  G_LOCK(s_mutex_sensors);
  sensor = sim_container_get_sensor_by_name_ul(container, name);
  G_UNLOCK(s_mutex_sensors);

  return sensor;
}

/*
 *
 *
 *
 *
 */
SimSensor*
sim_container_get_sensor_by_ia(SimContainer *container, GInetAddr *ia)
{
  SimSensor *sensor;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  G_LOCK(s_mutex_sensors);
  sensor = sim_container_get_sensor_by_ia_ul(container, ia);
  G_UNLOCK(s_mutex_sensors);

  return sensor;
}

/*
 * Stores in memory the number of events of each sensor. The number of events are stored each 5 minutes 
 * thanks to sim_scheduler_task_store_event_number_at_5min()
 *
 */
void
sim_container_set_sensor_event_number(SimContainer *container, gint event_kind,
    GInetAddr *sensor_ia)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  GList *list;

  G_LOCK(s_mutex_sensors); //FIXME: time consuming :/ I'm thinking in doing something to avoid this

  list = container->_priv->sensors;
  SimSensor *sensor;
  while (list)
    {
      sensor = (SimSensor *) list->data;
      if (gnet_inetaddr_noport_equal(sim_sensor_get_ia(sensor), sensor_ia) == 0) //if match
        {
          switch (event_kind)
            {
          case SIM_EVENT_EVENT:
            sim_sensor_add_number_events(sensor);
            break;
          case SIM_EVENT_HOST_OS_EVENT:
            sim_sensor_add_number_host_os_events(sensor);
            break;
          case SIM_EVENT_HOST_MAC_EVENT:
            sim_sensor_add_number_host_mac_events(sensor);
            break;
          case SIM_EVENT_HOST_SERVICE_EVENT:
            sim_sensor_add_number_host_service_events(sensor);
            break;
          case SIM_EVENT_HOST_IDS_EVENT:
            sim_sensor_add_number_host_ids_events(sensor);
            break;
            }

          break;
        }
      list = list->next;
    }
  G_UNLOCK(s_mutex_sensors);

}

/*
 *
 * Store in DB the number of events of each sensor
 */
void
sim_container_db_update_sensor_events_number(SimContainer *container,
    SimDatabase *database, SimSensor *sensor)
{
  event_kind event_number;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(sensor);
  g_return_if_fail(SIM_IS_SENSOR (sensor));

  event_number = sim_sensor_get_events_number(sensor);

  gchar *query;
  query
      = g_strdup_printf(
          "UPDATE sensor_stats SET events='%d', os_events='%d', mac_events='%d', service_events='%d', ids_events='%d' WHERE name='%s'",
          event_number.events, event_number.host_os_events,
          event_number.host_mac_events, event_number.host_service_events,
          event_number.host_ids_events, sim_sensor_get_name(sensor));

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_update_sensor_events_number QUERY: %s", query);
  sim_database_execute_no_query(database, query);

  g_free(query);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_update_sensor_events_number: %s, %d %d %d %d %d",
      sim_sensor_get_name(sensor), event_number.events,
      event_number.host_os_events, event_number.host_mac_events,
      event_number.host_service_events, event_number.host_ids_events);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_hosts_ul(SimContainer *container, SimDatabase *database)
{
  SimHost *host;
  GdaDataModel *dm;
  gint row;
  gchar *query = "SELECT ip, hostname, asset FROM host";

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          host = sim_host_new_from_dm(dm, row);
          container->_priv->hosts
              = g_list_append(container->_priv->hosts, host);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("HOSTS DATA MODEL ERROR");
    }
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_host_ul(SimContainer *container, SimHost *host)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host);
  g_return_if_fail(SIM_IS_HOST (host));

  container->_priv->hosts = g_list_append(container->_priv->hosts, host);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_host_ul(SimContainer *container, SimHost *host)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host);
  g_return_if_fail(SIM_IS_HOST (host));

  container->_priv->hosts = g_list_remove(container->_priv->hosts, host);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_hosts_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->hosts);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_hosts_ul(SimContainer *container, GList *hosts)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(hosts);

  container->_priv->hosts = g_list_concat(container->_priv->hosts, hosts);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_hosts_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->hosts;
  while (list)
    {
      SimHost *host = (SimHost *) list->data;
      g_object_unref(host);

      list = list->next;
    }
  g_list_free(container->_priv->hosts);
  container->_priv->hosts = NULL;
}

/*
 *
 * Check if the IA is in Policy->Hosts, and returns the object.
 *
 *
 */
SimHost*
sim_container_get_host_by_ia_ul(SimContainer *container, GInetAddr *ia)
{
  SimHost *host;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  list = container->_priv->hosts;
  while (list)
    {
      host = (SimHost *) list->data;

      gchar *ip_temp = gnet_inetaddr_get_canonical_name(sim_host_get_ia(host));
      if (ip_temp)
        {
          g_free(ip_temp);
          if (gnet_inetaddr_noport_equal(sim_host_get_ia(host), ia))
            {
              found = TRUE;
              break;
            }
        }
      else
        g_message(
            "Error: Some host is bad-defined in Policy->Hosts. Please check it!!");

      list = list->next;
    }

  if (!found)
    return NULL;

  return host;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_hosts(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_hosts);
  sim_container_db_load_hosts_ul(container, database);
  G_UNLOCK(s_mutex_hosts);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_host(SimContainer *container, SimHost *host)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host);
  g_return_if_fail(SIM_IS_HOST (host));

  G_LOCK(s_mutex_hosts);
  sim_container_append_host_ul(container, host);
  G_UNLOCK(s_mutex_hosts);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_host(SimContainer *container, SimHost *host)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host);
  g_return_if_fail(SIM_IS_HOST (host));

  G_LOCK(s_mutex_hosts);
  sim_container_remove_host_ul(container, host);
  G_UNLOCK(s_mutex_hosts);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_hosts(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_hosts);
  list = sim_container_get_hosts_ul(container);
  G_UNLOCK(s_mutex_hosts);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_hosts(SimContainer *container, GList *hosts)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(hosts);

  G_LOCK(s_mutex_hosts);
  sim_container_set_hosts_ul(container, hosts);
  G_UNLOCK(s_mutex_hosts);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_hosts(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_hosts);
  sim_container_free_hosts_ul(container);
  G_UNLOCK(s_mutex_hosts);
}

/*
 *
 *
 *
 *
 */
SimHost*
sim_container_get_host_by_ia(SimContainer *container, GInetAddr *ia)
{
  SimHost *host;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  G_LOCK(s_mutex_hosts);
  host = sim_container_get_host_by_ia_ul(container, ia);
  G_UNLOCK(s_mutex_hosts);

  return host;
}

/*
 *
 * Load the networks from the DB into the Container. 
 *
 *
 */
void
sim_container_db_load_nets_ul(SimContainer *container, SimDatabase *database)
{
  SimNet *net;
  GdaDataModel *dm;
  GdaDataModel *dm2;
  GdaValue *value;
  GInetAddr *ia;
  gint row;
  gint row2;
  gchar *query = "SELECT name, ips, asset FROM net";
  gchar *query2;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          net = sim_net_new_from_dm(dm, row); //creates a new SimNet object to store network(s) under the same name in policy

          /*
           query2 = g_strdup_printf ("SELECT host_ip FROM net_host_reference WHERE net_name = '%s'",
           sim_net_get_name (net));

           dm2 = sim_database_execute_single_command (database, query2);
           if (dm2)
           {
           for (row2 = 0; row2 < gda_data_model_get_n_rows (dm2); row2++)
           {
           gchar *ip;

           value = (GdaValue *) gda_data_model_get_value_at (dm2, 0, row2);
           ip = gda_value_stringify (value);

           ia = gnet_inetaddr_new_nonblock (ip, 0);
           sim_net_append_ia (net, ia);

           g_free (ip);
           }
           g_object_unref(dm2);
           }
           else
           {
           g_message ("NET HOST REFERENCES DATA MODEL ERROR");
           }

           g_free (query2);
           */

          container->_priv->nets = g_list_append(container->_priv->nets, net);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("NETS DATA MODEL ERROR");
    }
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_net_ul(SimContainer *container, SimNet *net)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net);
  g_return_if_fail(SIM_IS_NET (net));

  container->_priv->nets = g_list_append(container->_priv->nets, net);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_net_ul(SimContainer *container, SimNet *net)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net);
  g_return_if_fail(SIM_IS_NET (net));

  container->_priv->nets = g_list_remove(container->_priv->nets, net);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_nets_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->nets);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_nets_ul(SimContainer *container, GList *nets)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(nets);

  container->_priv->nets = g_list_concat(container->_priv->nets, nets);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_nets_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->nets;
  while (list)
    {
      SimNet *net = (SimNet *) list->data;
      g_object_unref(net);

      list = list->next;
    }
  g_list_free(container->_priv->nets);
  container->_priv->nets = NULL;
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_nets_has_ia_ul(SimContainer *container, GInetAddr *ia)
{
  SimInet *inet;
  GList *list;
  GList *nets = NULL;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  inet = sim_inet_new_from_ginetaddr(ia);

  list = container->_priv->nets;
  while (list)
    {
      SimNet *net = (SimNet *) list->data;

      // check if some of the SimInet objects inside "net" (net is Policy->networks), match with the SimInet
      // "inet" object
      if (sim_net_has_inet(net, inet))
        {
          nets = g_list_append(nets, net);
          //     g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_get_nets_has_ia_ul: COINCIDE");

        }
      //  else
      //    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_get_nets_has_ia_ul: NO COINCIDE");

      list = list->next;
    }

  g_object_unref(inet);

  return nets;
}

/*
 *
 *
 *
 *
 */
SimNet*
sim_container_get_net_by_name_ul(SimContainer *container, const gchar *name)
{
  SimNet *net;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  list = container->_priv->nets;
  while (list)
    {
      net = (SimNet *) list->data;

      if (!strcmp(sim_net_get_name(net), name))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return net;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_nets(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_nets);
  sim_container_db_load_nets_ul(container, database);
  G_UNLOCK(s_mutex_nets);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_net(SimContainer *container, SimNet *net)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net);
  g_return_if_fail(SIM_IS_NET (net));

  G_LOCK(s_mutex_nets);
  sim_container_append_net_ul(container, net);
  G_UNLOCK(s_mutex_nets);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_net(SimContainer *container, SimNet *net)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net);
  g_return_if_fail(SIM_IS_NET (net));

  G_LOCK(s_mutex_nets);
  sim_container_remove_net_ul(container, net);
  G_UNLOCK(s_mutex_nets);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_nets(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_nets);
  list = sim_container_get_nets_ul(container);
  G_UNLOCK(s_mutex_nets);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_nets(SimContainer *container, GList *nets)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(nets);

  G_LOCK(s_mutex_nets);
  sim_container_set_nets_ul(container, nets);
  G_UNLOCK(s_mutex_nets);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_nets(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_nets);
  sim_container_free_nets_ul(container);
  G_UNLOCK(s_mutex_nets);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_nets_has_ia(SimContainer *container, GInetAddr *ia)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  G_LOCK(s_mutex_nets);
  list = sim_container_get_nets_has_ia_ul(container, ia);
  G_UNLOCK(s_mutex_nets);

  return list;
}

/*
 *
 *
 *
 *
 */
SimNet*
sim_container_get_net_by_name(SimContainer *container, const gchar *name)
{
  SimNet *net;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  G_LOCK(s_mutex_nets);
  net = sim_container_get_net_by_name_ul(container, name);
  G_UNLOCK(s_mutex_nets);

  return net;
}

/*
 *
 * Helper function called from sim_container_db_load_policies_ul. This function executes the query 
 * provided, loads all the host or nets (depending the SrcOrDst), and stores it in the policy. 
 *
 */
gboolean
sim_container_db_load_src_or_dst(SimDatabase *database, gchar *query,
    SimPolicy *policy, int src_or_dst)
{
  GdaDataModel *dm;
  gint row;
  GdaValue *value;
  GList *list;

  //	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_db_load_src_or_dst. Query: %s", query);

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          gchar *SrcOrDst; //this can contain multiple hosts or networks.

          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          SrcOrDst = gda_value_stringify(value);
          //  		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_db_load_src_or_dst. String: %s", SrcOrDst);

          list = sim_get_SimInet_from_string(SrcOrDst);

          while (list)
            {
              SimInet *HostOrNet = (SimInet *) list->data;

              /*****debug****
               struct sockaddr_in* sa_in1 = (struct sockaddr_in*) &HostOrNet->_priv->sa;

               guint32 val1 = ntohl (sa_in1->sin_addr.s_addr);

               gchar *temp = g_strdup_printf ("%d.%d.%d.%d",
               (val1 >> 24) & 0xFF,
               (val1 >> 16) & 0xFF,
               (val1 >> 8) & 0xFF,
               (val1) & 0xFF);

               g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Adding src or dst: %d bits: %s",HostOrNet->_priv->bits, temp);
               g_free(temp);
               /*********/

              if (src_or_dst == SIM_SRC)
                sim_policy_append_src(policy, HostOrNet);
              else if (src_or_dst == SIM_DST)
                sim_policy_append_dst(policy, HostOrNet);

              list = list->next;
            }
          g_free(SrcOrDst);

        }
      g_object_unref(dm);
    }
  else
    return 0; //meeecs, error!.

  return 1;
}

/*
 *
 * Loads the policy in the database to memory
 *
 */
void
sim_container_db_load_policies_ul(SimContainer *container,
    SimDatabase *database)
{
  SimPolicy *policy;
  GdaDataModel *dm;
  GdaDataModel *dm2;
  GdaValue *value;
  GdaValue *value2;
  GInetAddr *ia;
  gint row;
  gint row2;
  gint row3;
  gchar
      *query =
          "SELECT policy.id, policy.priority, policy.descr, policy_time.begin_hour, policy_time.end_hour, policy_time.begin_day, policy_time.end_day FROM policy, policy_time WHERE policy.id = policy_time.policy_id AND policy.active != 0 order by policy.order;";
  gchar *query2;
  gchar *query3;
  gchar *temp;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          policy = sim_policy_new_from_dm(dm, row);
          //First the source addresses (hosts, nets, and host_groups. All of them are transformed into IP's
          // Host SRC Inet Address
          sim_policy_set_has_actions(policy,
              sim_container_policy_has_actions_in_db(database,
                  sim_policy_get_id(policy)));
          query2
              = g_strdup_printf(
                  "SELECT host_ip FROM  policy_host_reference WHERE policy_id = %d AND direction = 'source'",
                  sim_policy_get_id(policy));

          //The following query is useful if we change and use names instead ips
          //query2 = g_strdup_printf ("SELECT ip FROM policy_host_reference,host WHERE policy_host_reference.host_ip = host.hostname AND policy_host_reference.direction = 'source' AND policy_id = %d ", sim_policy_get_id (policy));
          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_SRC))
            g_message("POLICY HOST SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);

          //Host group SRC
          query2
              = g_strdup_printf(
                  "SELECT host_ip FROM policy_host_group_reference,host_group_reference WHERE policy_host_group_reference.host_group_name =host_group_reference.host_group_name AND policy_host_group_reference.direction = 'source' AND policy_id = %d",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_SRC))
            g_message("POLICY HOST SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);

          // Net SRC Inet Address
          query2
              = g_strdup_printf(
                  "SELECT ips FROM policy_net_reference,net WHERE policy_net_reference.net_name = net.name AND policy_net_reference.direction = 'source' AND policy_id = %d",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_SRC))
            g_message("POLICY NET SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);
          // Net group SRC Inet Address
          query2
              = g_strdup_printf(
                  "SELECT ips FROM net, policy_net_group_reference, net_group_reference WHERE net.name = net_group_reference.net_name AND policy_net_group_reference.net_group_name = net_group_reference.net_group_name AND policy_net_group_reference.direction = 'source' AND policy_id = %d",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_SRC))
            g_message("POLICY NET GRP SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);

          //Second, we load the destination addresses...
          // Host DST Inet Address
          query2
              = g_strdup_printf(
                  "SELECT host_ip FROM  policy_host_reference WHERE policy_id = %d AND direction = 'dest'",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_DST))
            g_message("POLICY HOST DEST REFERENCES DATA MODEL ERROR");
          g_free(query2);

          //Host group DST
          query2
              = g_strdup_printf(
                  "SELECT host_ip FROM policy_host_group_reference,host_group_reference WHERE policy_host_group_reference.host_group_name =host_group_reference.host_group_name AND policy_host_group_reference.direction = 'dest' AND policy_id = %d",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_DST))
            g_message("POLICY HOST SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);

          // Net DST Inet Address
          query2
              = g_strdup_printf(
                  "SELECT ips FROM policy_net_reference,net WHERE policy_net_reference.net_name = net.name AND policy_net_reference.direction = 'dest' AND policy_id = %d",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_DST))
            g_message("POLICY NET SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);

          // Net group DST Inet Address
          query2
              = g_strdup_printf(
                  "SELECT ips FROM net, policy_net_group_reference, net_group_reference WHERE net.name = net_group_reference.net_name AND policy_net_group_reference.net_group_name = net_group_reference.net_group_name AND policy_net_group_reference.direction = 'dest' AND policy_id = %d",
                  sim_policy_get_id(policy));

          if (!sim_container_db_load_src_or_dst(database, query2, policy,
              SIM_SRC))
            g_message("POLICY NET SOURCE REFERENCES DATA MODEL ERROR");
          g_free(query2);

          /* Ports */
          query2
              = g_strdup_printf(
                  "SELECT port_number, protocol_name  FROM policy_port_reference, port_group_reference WHERE policy_port_reference.port_group_name = port_group_reference.port_group_name AND policy_port_reference.policy_id = %d",
                  sim_policy_get_id(policy));
          dm2 = sim_database_execute_single_command(database, query2);
          if (dm2)
            {
              for (row2 = 0; row2 < gda_data_model_get_n_rows(dm2); row2++)
                {
                  SimPortProtocol *pp;
                  SimProtocolType proto_type;
                  gint port_num;
                  gchar *proto_name;

                  value
                      = (GdaValue *) gda_data_model_get_value_at(dm2, 0, row2);

                  gchar *str_port;
                  str_port = gda_value_stringify(value); //ok, probably it'll be a number, but we have to check if its "any"
                  //if str_port is "ANY", we will store a "0", so later we will use it at sim_policy_match to match ANY ports
                  if (g_strstr_len(str_port, strlen(str_port),
                      SIM_IN_ADDR_ANY_CONST) || g_strstr_len(str_port, strlen(
                      str_port), "any"))
                    {
                      port_num = 0;
                    }
                  else
                    port_num = gda_value_get_integer(value);

                  value
                      = (GdaValue *) gda_data_model_get_value_at(dm2, 1, row2);
                  proto_name = gda_value_stringify(value);
                  proto_type = sim_protocol_get_type_from_str(proto_name);
                  pp = sim_port_protocol_new(port_num, proto_type);

                  sim_policy_append_port(policy, pp);
                  g_free(proto_name);
                }
              g_object_unref(dm2);
            }
          else
            g_message("POLICY CATEGORY REFERENCES DATA MODEL ERROR");

          g_free(query2);

          /* Sensors */
          //Remember!!!! if someone inserts into DB "ANY" sensor and other sensor, this will fail. If you want to put ANY,
          //shouldn't exist more sensors
          query2
              = g_strdup_printf(
                  "SELECT ip FROM sensor,policy_sensor_reference WHERE policy_id = %d and policy_sensor_reference.sensor_name=sensor.name;",
                  sim_policy_get_id(policy));
          dm2 = sim_database_execute_single_command(database, query2);
          if (dm2)
            {
              for (row2 = 0; row2 < gda_data_model_get_n_rows(dm2); row2++)
                {
                  gchar *sensor;

                  value
                      = (GdaValue *) gda_data_model_get_value_at(dm2, 0, row2);
                  sensor = gda_value_stringify(value);

                  if (g_strstr_len(sensor, strlen(sensor),
                      SIM_IN_ADDR_ANY_CONST) || g_strstr_len(sensor, strlen(
                      sensor), "any"))
                    {
                      g_free(sensor);
                      sensor = g_strdup_printf("0"); //okay, this should be something like "0.0.0.0", but I prefer speed in matches
                    }

                  sim_policy_append_sensor(policy, sensor); //append the string with the sensor's ip  (i.e. "1.1.1.1" or "0" if ANY)
                }
              //May be that the sensor is "ANY", so the last query won't return nothing, as the sensor ANY hasn't got an IP.
              //We've to check if this is the case
              if (gda_data_model_get_n_rows(dm2) == 0)
                {
                  gchar
                      *query3 =
                          g_strdup_printf(
                              "SELECT sensor_name FROM policy_sensor_reference WHERE policy_id = %d",
                              sim_policy_get_id(policy));
                  GdaDataModel *dm3;
                  dm3 = sim_database_execute_single_command(database, query3);
                  if (dm3)
                    {
                      for (row2 = 0; row2 < gda_data_model_get_n_rows(dm3); row2++)
                        {
                          gchar *sensor;
                          value = (GdaValue *) gda_data_model_get_value_at(dm3,
                              0, row2);
                          sensor = gda_value_stringify(value);

                          if (g_strstr_len(sensor, strlen(sensor),
                              SIM_IN_ADDR_ANY_CONST) || //ANY
                              g_strstr_len(sensor, strlen(sensor), "any"))
                            {
                              g_free(sensor);
                              sensor = g_strdup_printf("0"); //okay, this should be something like "0.0.0.0", but I prefer speed in matches
                            }
                          sim_policy_append_sensor(policy, sensor); //append the string with the sensor's ip  (i.e. "1.1.1.1" or "0" if ANY)
                        }
                      g_object_unref(dm3);
                    }
                  else
                    g_message("POLICY SENSOR REFERENCE DATA MODEL ERROR");
                  g_free(query3);
                }

              g_object_unref(dm2);
            }
          else
            g_message("POLICY SENSOR REFERENCES DATA MODEL ERROR");
          g_free(query2);

          /*Plugin_id/sid groups*/
          query2
              = g_strdup_printf(
                  "SELECT group_id FROM policy_plugin_group_reference WHERE policy_id = %d",
                  sim_policy_get_id(policy));
          dm2 = sim_database_execute_single_command(database, query2);
          if (dm2)
            {
              for (row2 = 0; row2 < gda_data_model_get_n_rows(dm2); row2++)
                {
                  gint plugin_group_id;

                  value
                      = (GdaValue *) gda_data_model_get_value_at(dm2, 0, row2);
                  plugin_group_id = gda_value_get_integer(value);

                  //	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_db_load_policies_ul plugin_id: %d", plugin_group->plugin);
                  if (plugin_group_id == 0)
                    {
                      Plugin_PluginSid *plugin_group = g_new0(Plugin_PluginSid,
                          1);
                      plugin_group->plugin_id = 0;
                      sim_policy_append_plugin_group(policy, plugin_group); //appends the plugin_group.
                    }
                  else
                    {
                      query3
                          = g_strdup_printf(
                              "SELECT plugin_id, plugin_sid FROM plugin_group WHERE group_id = %d",
                              plugin_group_id);
                      GdaDataModel *dm3;
                      dm3 = sim_database_execute_single_command(database,
                          query3);
                      if (dm3)
                        {
                          for (row3 = 0; row3 < gda_data_model_get_n_rows(dm3); row3++)
                            {
                              Plugin_PluginSid *plugin_group = g_new0(
                                  Plugin_PluginSid, 1);
                              gchar *str_plugin_sids;

                              value2
                                  = (GdaValue *) gda_data_model_get_value_at(
                                      dm3, 0, row3); //plugin_id
                              plugin_group->plugin_id = gda_value_get_integer(
                                  value2);
                              value2
                                  = (GdaValue *) gda_data_model_get_value_at(
                                      dm3, 1, row3); //plugin_sid
                              str_plugin_sids = gda_value_stringify(value2);

                              g_log(
                                  G_LOG_DOMAIN,
                                  G_LOG_LEVEL_DEBUG,
                                  "sim_container_db_load_policies_ul str_plugin_sids: %s",
                                  str_plugin_sids);

                              //at this moment we have all the plugin_sid's from a specific plugin_id. they can have the following format:
                              // "101,102,103-107" We've to separate it into individual *gint so we can store it inside
                              //the plugin_group struct.
                              gchar **uniq_plugin_ids = g_strsplit(
                                  str_plugin_sids, ",", 0);
                              gint i, ii;

                              for (i = 0; i
                                  < sim_g_strv_length(uniq_plugin_ids); i++)
                                {
                                  gchar *multiple = NULL;
                                  multiple = strchr(uniq_plugin_ids[i], '-');
                                  if (multiple) //"103-107"
                                    {
                                      gint from, to;
                                      gchar *end;
                                      gchar **individual_plugin_ids;

                                      individual_plugin_ids = g_strsplit(
                                          uniq_plugin_ids[i], "-", 0);

                                      from = strtol(individual_plugin_ids[0],
                                          (char **) NULL, 10);
                                      to = strtol(individual_plugin_ids[1],
                                          (char **) NULL, 10);

                                      for (ii = 0; ii <= (to - from); ii++) //transform every plugin_sid into a number to store it.
                                        {
                                          gint *uniq = g_new0(gint, 1);
                                          *uniq = from + ii;
                                          plugin_group->plugin_sid
                                              = g_list_append(
                                                  plugin_group->plugin_sid,
                                                  uniq);
                                        }
                                      g_strfreev(individual_plugin_ids);
                                    }
                                  else //"101"
                                    {
                                      gint *uniq = g_new0(gint, 1);
                                      *uniq = strtol(uniq_plugin_ids[i], NULL,
                                          10);
                                      plugin_group->plugin_sid = g_list_append(
                                          plugin_group->plugin_sid, uniq);
                                    }
                                }
                              sim_policy_append_plugin_group(policy,
                                  plugin_group); //appends the plugin_group.
                              g_strfreev(uniq_plugin_ids);
                            }
                          g_object_unref(dm3);
                        }
                      else
                        g_message("POLICY PLUGIN_GROUP DATA MODEL ERROR");

                      g_free(query3);
                    }
                }
              g_object_unref(dm2);
            }
          else
            g_message("POLICY PLUGIN_ID REFERENCES DATA MODEL ERROR");

          g_free(query2);

          /* Load the role of this policy.*/
          query2
              = g_strdup_printf(
                  "SELECT correlate, cross_correlate, store, qualify, resend_alarm, resend_event FROM policy_role_reference WHERE policy_id = %d",
                  sim_policy_get_id(policy));
          dm2 = sim_database_execute_single_command(database, query2);
          if (dm2)
            {
              if (gda_data_model_get_n_rows(dm2) != 0) //to avoid (null)-Critical first time
                {
                  SimRole *role = g_new0(SimRole, 1);
                  role->cross_correlate;
                  gboolean store;
                  gboolean qualify;
                  gboolean resend_alarm;
                  gboolean resend_avent;

                  value = (GdaValue *) gda_data_model_get_value_at(dm2, 0, 0);
                  role->correlate = gda_value_get_tinyint(value); //this should be boolean, but GDA is... is.... gggggg
                  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_policies_ul role->correlate: %d",
                      role->correlate);

                  sim_gda_value_extract_type(value);

                  value = (GdaValue *) gda_data_model_get_value_at(dm2, 1, 0);
                  role->cross_correlate = gda_value_get_tinyint(value);
                  g_log(
                      G_LOG_DOMAIN,
                      G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_policies_ul role->cross_correlate: %d",
                      role->cross_correlate);

                  sim_gda_value_extract_type(value);
                  // Store events in this policy in DB or not?
                  value = (GdaValue *) gda_data_model_get_value_at(dm2, 2, 0);
                  sim_gda_value_extract_type(value);
                  role->store = gda_value_get_tinyint(value);
                  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_policies_ul role->Store: %d",
                      role->store);

                  value = (GdaValue *) gda_data_model_get_value_at(dm2, 3, 0);
                  sim_gda_value_extract_type(value);
                  role->qualify = gda_value_get_tinyint(value);
                  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_policies_ul role->qualify: %d",
                      role->qualify);

                  value = (GdaValue *) gda_data_model_get_value_at(dm2, 4, 0);
                  role->resend_alarm = gda_value_get_tinyint(value);
                  g_log(
                      G_LOG_DOMAIN,
                      G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_policies_ul role->resend_alarm: %d",
                      role->resend_alarm);

                  sim_gda_value_extract_type(value);

                  value = (GdaValue *) gda_data_model_get_value_at(dm2, 5, 0);
                  role->resend_event = gda_value_get_tinyint(value);
                  g_log(
                      G_LOG_DOMAIN,
                      G_LOG_LEVEL_DEBUG,
                      "sim_container_db_load_policies_ul role->resend_event: %d",
                      role->resend_event);

                  sim_policy_set_role(policy, role);

                }
              else
                {
                  //Until web has this, this is no an error.
                  //        g_message("Error: May be that there are a problem in role table; role load failed!");
                  sim_policy_set_role(policy, NULL);

                }

              g_object_unref(dm2);
            }
          else
            g_message("POLICY STORE DATA MODEL ERROR");

          g_free(query2);

          /* Target: Servers OR/AND sensors */
          //Targets doesn't needs to match with anything. This field is needed to know where this policy should be installed.
          //And the policy can be installed either in servers, or in agents.
          query2
              = g_strdup_printf(
                  "SELECT target_name FROM policy_target_reference WHERE policy_target_reference.policy_id = %d",
                  sim_policy_get_id(policy));
          dm2 = sim_database_execute_single_command(database, query2);
          if (dm2)
            {
              for (row2 = 0; row2 < gda_data_model_get_n_rows(dm2); row2++)
                {
                  gchar *target_name;

                  value
                      = (GdaValue *) gda_data_model_get_value_at(dm2, 0, row2);

                  target_name = gda_value_stringify(value); // we have to check if its "any" so we will use it in every server/sensor, or just in a few ones
                  if (!g_ascii_strcasecmp(target_name, SIM_IN_ADDR_ANY_CONST))
                    {
                      sim_policy_append_target(policy, target_name);
                      break; //if it's any, it has no sense to continue loading targets (servers or agents)
                    }

                  sim_policy_append_target(policy, target_name);
                }
              g_object_unref(dm2);
            }
          else
            g_message("POLICY CATEGORY REFERENCES DATA MODEL ERROR");

          g_free(query2);

          //Add the policy wich we have filled to the policies list.
          container->_priv->policies = g_list_append(
              container->_priv->policies, policy);
          sim_policy_debug_print(policy);

        }
      g_object_unref(dm);
    }
  else
    g_message("POLICY DATA MODEL ERROR");

}

/*
 *
 *
 *
 *
 */
void
sim_container_append_policy_ul(SimContainer *container, SimPolicy *policy)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(policy);
  g_return_if_fail(SIM_IS_POLICY (policy));

  container->_priv->policies
      = g_list_append(container->_priv->policies, policy);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_policy_ul(SimContainer *container, SimPolicy *policy)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(policy);
  g_return_if_fail(SIM_IS_POLICY (policy));

  container->_priv->policies
      = g_list_remove(container->_priv->policies, policy);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_policies_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->policies);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_policies_ul(SimContainer *container, GList *policies)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(policies);

  container->_priv->policies = g_list_concat(container->_priv->policies,
      policies);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_policies_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->policies;
  while (list)
    {
      SimPolicy *policy = (SimPolicy *) list->data;
      g_object_unref(policy);

      list = list->next;
    }
  g_list_free(container->_priv->policies);
  container->_priv->policies = NULL;
}

/*
 *
 *
 *
 */
SimPolicy*
sim_container_get_policy_match_ul(SimContainer *container, gint date,
    GInetAddr *src_ip, GInetAddr *dst_ip, SimPortProtocol *pp, gchar *sensor,
    guint plugin_id, guint plugin_sid)
{
  SimPolicy *policy;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container != NULL, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(src_ip != NULL, NULL);
  g_return_val_if_fail(dst_ip != NULL, NULL);
  g_return_val_if_fail(pp != NULL, NULL);
  g_return_val_if_fail(sensor != NULL, NULL);

  list = container->_priv->policies;
  while (list)
    {
      policy = (SimPolicy *) list->data;
      //sim_policy_debug_print (policy);
      if (sim_policy_match(policy, date, src_ip, dst_ip, pp, sensor, plugin_id,
          plugin_sid))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return policy;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_load_policies(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_policies);
  sim_container_db_load_policies_ul(container, database);
  G_UNLOCK(s_mutex_policies);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_policy(SimContainer *container, SimPolicy *policy)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(policy);
  g_return_if_fail(SIM_IS_POLICY (policy));

  G_LOCK(s_mutex_policies);
  sim_container_append_policy_ul(container, policy);
  G_UNLOCK(s_mutex_policies);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_policy(SimContainer *container, SimPolicy *policy)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(policy);
  g_return_if_fail(SIM_IS_POLICY (policy));

  G_LOCK(s_mutex_policies);
  sim_container_remove_policy_ul(container, policy);
  G_UNLOCK(s_mutex_policies);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_policies(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_policies);
  list = sim_container_get_policies_ul(container);
  G_UNLOCK(s_mutex_policies);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_policies(SimContainer *container, GList *policies)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(policies);

  G_LOCK(s_mutex_policies);
  sim_container_set_policies_ul(container, policies);
  G_UNLOCK(s_mutex_policies);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_policies(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_policies);
  sim_container_free_policies_ul(container);
  G_UNLOCK(s_mutex_policies);
}

/*
 *
 *
 *
 */
SimPolicy*
sim_container_get_policy_match(SimContainer *container, gint date,
    GInetAddr *src_ip, GInetAddr *dst_ip, SimPortProtocol *pp, gchar *sensor,
    guint plugin_id, guint plugin_sid)
{
  SimPolicy *policy;

  g_return_val_if_fail(container != NULL, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(src_ip != NULL, NULL);
  g_return_val_if_fail(dst_ip != NULL, NULL);
  g_return_val_if_fail(pp != NULL, NULL);
  g_return_val_if_fail(sensor != NULL, NULL);

  G_LOCK(s_mutex_policies);
  policy = sim_container_get_policy_match_ul(container, date, src_ip, dst_ip,
      pp, sensor, plugin_id, plugin_sid);
  G_UNLOCK(s_mutex_policies);

  return policy;
}

/*
 * Directives are loaded always from file, not from master servers
 * The directive's file in the master server MUST contain all the directives in children server's.
 * It doesn't matters if a children server doesn't have all the directives from its master. 
 * The directives wich are not loaded in children server will be just another plugin_sid memory entry in container, without any effect
 * (because directive are not loaded) other than a little memory waste (probably a few Kbs, no worries). 
 *
 */
void
sim_container_load_directives_from_file_ul(SimContainer *container,
    SimDatabase *db_ossim, const gchar *filename)
{
  SimXmlDirective *xml_directive;
  GList *list = NULL;
  gint max_sid = 0;
  gint previous = 0;
  SimPluginSid *plugin_sid;
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(filename);

  previous = xmlSubstituteEntitiesDefault(1); //....

  xml_directive = sim_xml_directive_new_from_file(container, filename);
  container->_priv->directives
      = sim_xml_directive_get_directives(xml_directive);

  //This will REPLACE INTO all the directives. If the user changes something different from plugin_id&pin generic.xml for example
  //if we are a children server without local DB, we can't insert into DB. Its supposed that we load ALL the plugins from master server.
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_load_directives_from_file_ul: 1 %d",
      sim_database_is_local(db_ossim));
  if (sim_database_is_local(db_ossim))
    {
      list = container->_priv->directives;
      while (list)
        {
          SimDirective *directive = (SimDirective *) list->data;
          // FIXME: is better to check all the information from DB, to avoid re-insert things (loading time increases).
          //			plugin_sid = sim_container_get_plugin_sid_by_pky (container,
          //																												 SIM_PLUGIN_ID_DIRECTIVE,
          //																												 sim_directive_get_id (directive));

          //	    if (!plugin_sid)
          //			{
          plugin_sid = sim_plugin_sid_new_from_data(SIM_PLUGIN_ID_DIRECTIVE,
              sim_directive_get_id(directive), 1, sim_directive_get_priority(
                  directive), sim_directive_get_name(directive));
          //			  sim_container_append_plugin_sid (container, plugin_sid);

          query = sim_plugin_sid_get_insert_clause(plugin_sid);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_container_load_directives_from_file_ul: %s", query);
          sim_database_execute_no_query(db_ossim, query);
          g_free(query);
          //			}

          list = list->next;
        }
    }
  xmlSubstituteEntitiesDefault(previous);

  g_object_unref(xml_directive);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_directive_ul(SimContainer *container,
    SimDirective *directive)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(directive);
  g_return_if_fail(SIM_IS_DIRECTIVE (directive));

  container->_priv->directives = g_list_append(container->_priv->directives,
      directive);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_directive_ul(SimContainer *container,
    SimDirective *directive)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(directive);
  g_return_if_fail(SIM_IS_DIRECTIVE (directive));

  container->_priv->directives = g_list_remove(container->_priv->directives,
      directive);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_directives_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  return container->_priv->directives;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_directives_ul(SimContainer *container, GList *directives)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(directives);

  container->_priv->directives = g_list_concat(container->_priv->directives,
      directives);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_directives_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->directives;
  while (list)
    {
      SimDirective *directive = (SimDirective *) list->data;
      g_object_unref(directive);

      list = list->next;
    }
  g_list_free(container->_priv->directives);
  container->_priv->directives = NULL;
}

/*
 *
 *
 *
 *
 */
void
sim_container_load_directives_from_file(SimContainer *container,
    SimDatabase *db_ossim, const gchar *filename)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(filename);

  g_mutex_lock(ossim.mutex_directives);
  sim_container_load_directives_from_file_ul(container, db_ossim, filename);
  g_mutex_unlock(ossim.mutex_directives);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_directive(SimContainer *container, SimDirective *directive)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(directive);
  g_return_if_fail(SIM_IS_DIRECTIVE (directive));

  g_mutex_lock(ossim.mutex_directives);
  sim_container_append_directive_ul(container, directive);
  g_mutex_unlock(ossim.mutex_directives);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_directive(SimContainer *container, SimDirective *directive)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(directive);
  g_return_if_fail(SIM_IS_DIRECTIVE (directive));

  g_mutex_lock(ossim.mutex_directives);
  sim_container_remove_directive_ul(container, directive);
  g_mutex_unlock(ossim.mutex_directives);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_directives(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  g_mutex_lock(ossim.mutex_directives);
  list = sim_container_get_directives_ul(container);
  g_mutex_unlock(ossim.mutex_directives);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_directives(SimContainer *container, GList *directives)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(directives);

  g_mutex_lock(ossim.mutex_directives);
  sim_container_set_directives_ul(container, directives);
  g_mutex_unlock(ossim.mutex_directives);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_directives(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  g_mutex_lock(ossim.mutex_directives);
  sim_container_free_directives_ul(container);
  g_mutex_unlock(ossim.mutex_directives);
}

/*
 *
 *
 *
 */
void
sim_container_db_load_host_levels_ul(SimContainer *container,
    SimDatabase *database)
{
  SimHostLevel *host_level;
  GdaDataModel *dm;
  gint row;
  gchar *query = "SELECT host_ip, compromise, attack FROM host_qualification";

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          host_level = sim_host_level_new_from_dm(dm, row);
          container->_priv->host_levels = g_list_append(
              container->_priv->host_levels, host_level);
        }

      g_object_unref(dm);
    }
  else
    g_message("HOST LEVELS DATA MODEL ERROR");
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_host_level_ul(SimContainer *container,
    SimDatabase *database, SimHostLevel *host_level)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  if (query = sim_host_level_get_insert_clause(host_level))
    {
      sim_database_execute_no_query(database, query);
      g_free(query);
    }
  else
    g_message(
        "There's a problem trying to insert a hosts level. Please check Policy->Hosts to see if there are any mistake");
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_update_host_level_ul(SimContainer *container,
    SimDatabase *database, SimHostLevel *host_level)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  if (query = sim_host_level_get_update_clause(host_level))
    {
      sim_database_execute_no_query(database, query);
      g_free(query);
    }
  else
    g_message(
        "There's a problem trying to update a hosts level. Please check Policy->Hosts to see if there are any mistake");

}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_host_level_ul(SimContainer *container,
    SimDatabase *database, SimHostLevel *host_level)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  if (query = sim_host_level_get_delete_clause(host_level))
    {
      sim_database_execute_no_query(database, query);
      g_free(query);
    }
  else
    g_message(
        "There's a problem trying to delete a hosts level. Please check Policy->Hosts to see if there are any mistake");

}

/*
 *
 *
 *
 *
 */
void
sim_container_append_host_level_ul(SimContainer *container,
    SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  container->_priv->host_levels = g_list_append(container->_priv->host_levels,
      host_level);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_host_level_ul(SimContainer *container,
    SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  container->_priv->host_levels = g_list_remove(container->_priv->host_levels,
      host_level);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_host_levels_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->host_levels);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_host_levels_ul(SimContainer *container, GList *host_levels)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host_levels);

  container->_priv->host_levels = g_list_concat(container->_priv->host_levels,
      host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_host_levels_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->host_levels;
  while (list)
    {
      SimHostLevel *host_level = (SimHostLevel *) list->data;
      g_object_unref(host_level);

      list = list->next;
    }
  g_list_free(container->_priv->host_levels);
  container->_priv->host_levels = NULL;
}

/*
 *
 *
 *
 *
 */
SimHostLevel*
sim_container_get_host_level_by_ia_ul(SimContainer *container, GInetAddr *ia)
{
  SimHostLevel *host_level = NULL;
  GList *list;
  gboolean found = FALSE;
  GInetAddr *cmp = NULL;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  list = container->_priv->host_levels;
  while (list)
    {
      host_level = (SimHostLevel *) list->data;
      cmp = sim_host_level_get_ia(host_level);

      if (!cmp)
        {
          list = list->next;
          continue;
        }

      if (gnet_inetaddr_noport_equal(cmp, ia))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return host_level;
}

/*
 *
 *
 *
 */
void
sim_container_set_host_levels_recovery_ul(SimContainer *container,
    SimDatabase *database, gint recovery)
{
  GList *list;
  GList *removes = NULL;
  gint c;
  gint a;

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(recovery >= 0);

  list = container->_priv->host_levels;
  while (list)
    {
      SimHostLevel *host_level = (SimHostLevel *) list->data;

      sim_host_level_set_recovery(host_level, recovery); /* Update Memory */

      c = sim_host_level_get_c(host_level);
      a = sim_host_level_get_a(host_level);

      if (c == 0 && a == 0)
        {
          gchar *query = sim_host_level_get_delete_clause(host_level);
          sim_database_execute_no_query(database, query);
          g_free(query);

          removes = g_list_append(removes, host_level);
        }
      else
        {
          gchar *query = sim_host_level_get_update_clause(host_level);
          if (query)
            {
              sim_database_execute_no_query(database, query);
              g_free(query);
            }
        }

      list = list->next;
    }

  while (removes)
    {
      SimHostLevel *host_level = (SimHostLevel *) removes->data;

      container->_priv->host_levels = g_list_remove_all(
          container->_priv->host_levels, host_level);
      g_object_unref(host_level);

      removes = removes->next;
    }
  g_list_free(removes);
}

/*
 *
 *
 *
 */
void
sim_container_db_load_host_levels(SimContainer *container,
    SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_host_levels);
  sim_container_db_load_host_levels_ul(container, database);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_host_level(SimContainer *container,
    SimDatabase *database, SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  G_LOCK(s_mutex_host_levels);
  sim_container_db_insert_host_level_ul(container, database, host_level);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_update_host_level(SimContainer *container,
    SimDatabase *database, SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  G_LOCK(s_mutex_host_levels);
  sim_container_db_update_host_level_ul(container, database, host_level);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_host_level(SimContainer *container,
    SimDatabase *database, SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  G_LOCK(s_mutex_host_levels);
  sim_container_db_delete_host_level_ul(container, database, host_level);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_host_level(SimContainer *container,
    SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  G_LOCK(s_mutex_host_levels);
  sim_container_append_host_level_ul(container, host_level);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_host_level(SimContainer *container,
    SimHostLevel *host_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host_level);
  g_return_if_fail(SIM_IS_HOST_LEVEL (host_level));

  G_LOCK(s_mutex_host_levels);
  sim_container_remove_host_level_ul(container, host_level);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_host_levels(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_host_levels);
  list = sim_container_get_host_levels_ul(container);
  G_UNLOCK(s_mutex_host_levels);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_host_levels(SimContainer *container, GList *host_levels)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(host_levels);

  G_LOCK(s_mutex_host_levels);
  sim_container_set_host_levels_ul(container, host_levels);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_host_levels(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_host_levels);
  sim_container_free_host_levels_ul(container);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 *
 */
SimHostLevel*
sim_container_get_host_level_by_ia(SimContainer *container, GInetAddr *ia)
{
  SimHostLevel *host_level;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(ia, NULL);

  G_LOCK(s_mutex_host_levels);
  host_level = sim_container_get_host_level_by_ia_ul(container, ia);
  G_UNLOCK(s_mutex_host_levels);

  return host_level;
}

/*
 *
 *
 *
 */
void
sim_container_set_host_levels_recovery(SimContainer *container,
    SimDatabase *database, gint recovery)
{
  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(recovery >= 0);

  G_LOCK(s_mutex_host_levels);
  sim_container_set_host_levels_recovery_ul(container, database, recovery);
  G_UNLOCK(s_mutex_host_levels);
}

/*
 *
 *
 *
 */
void
sim_container_db_load_net_levels_ul(SimContainer *container,
    SimDatabase *database)
{
  SimNetLevel *net_level;
  GdaDataModel *dm;
  gint row;
  gchar *query = "SELECT net_name, compromise, attack FROM net_qualification";

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          net_level = sim_net_level_new_from_dm(dm, row);

          container->_priv->net_levels = g_list_append(
              container->_priv->net_levels, net_level);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("NET LEVELS DATA MODEL ERROR");
    }
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_net_level_ul(SimContainer *container,
    SimDatabase *database, SimNetLevel *net_level)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  query = sim_net_level_get_insert_clause(net_level);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_update_net_level_ul(SimContainer *container,
    SimDatabase *database, SimNetLevel *net_level)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  query = sim_net_level_get_update_clause(net_level);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_net_level_ul(SimContainer *container,
    SimDatabase *database, SimNetLevel *net_level)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  query = sim_net_level_get_delete_clause(net_level);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_net_level_ul(SimContainer *container,
    SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  container->_priv->net_levels = g_list_append(container->_priv->net_levels,
      net_level);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_net_level_ul(SimContainer *container,
    SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  container->_priv->net_levels = g_list_remove(container->_priv->net_levels,
      net_level);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_net_levels_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->net_levels);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_net_levels_ul(SimContainer *container, GList *net_levels)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net_levels);

  container->_priv->net_levels = g_list_concat(container->_priv->net_levels,
      net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_net_levels_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->net_levels;
  while (list)
    {
      SimNetLevel *net_level = (SimNetLevel *) list->data;
      g_object_unref(net_level);

      list = list->next;
    }
  g_list_free(container->_priv->net_levels);
  container->_priv->net_levels = NULL;
}

/*
 *
 *
 *
 *
 */
SimNetLevel*
sim_container_get_net_level_by_name_ul(SimContainer *container,
    const gchar *name)
{
  SimNetLevel *net_level;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  list = container->_priv->net_levels;
  while (list)
    {
      net_level = (SimNetLevel *) list->data;

      if (!strcmp(sim_net_level_get_name(net_level), name))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return net_level;
}

/*
 *
 *
 *
 */
void
sim_container_set_net_levels_recovery_ul(SimContainer *container,
    SimDatabase *database, gint recovery)
{
  GList *list;
  GList *removes = NULL;
  gint c;
  gint a;

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(recovery >= 0);

  list = container->_priv->net_levels;
  while (list)
    {
      SimNetLevel *net_level = (SimNetLevel *) list->data;

      sim_net_level_set_recovery(net_level, recovery); /* Update Memory */

      c = sim_net_level_get_c(net_level);
      a = sim_net_level_get_a(net_level);

      if (c == 0 && a == 0)
        {
          gchar *query = sim_net_level_get_update_clause(net_level);
          sim_database_execute_no_query(database, query);
          g_free(query);

          /* Fix this in the PostgreSQL version */
          //container->_priv->net_levels = g_list_remove (container->_priv->net_levels, net_level); /* Delete Container List */
          //sim_container_db_delete_net_level (container, database, net_level); /* Delete DB */
        }
      else
        {
          gchar *query = sim_net_level_get_update_clause(net_level);
          sim_database_execute_no_query(database, query);
          g_free(query);
          //sim_container_db_update_net_level (container, database, net_level); /* Update DB */
        }

      list = list->next;
    }

  while (removes)
    {
      SimNetLevel *net_level = (SimNetLevel *) removes->data;

      container->_priv->net_levels = g_list_remove_all(
          container->_priv->net_levels, net_level);
      g_object_unref(net_level);

      removes = removes->next;
    }
  g_list_free(removes);
}

/*
 *
 *
 *
 */
void
sim_container_db_load_net_levels(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_net_levels);
  sim_container_db_load_net_levels_ul(container, database);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_net_level(SimContainer *container,
    SimDatabase *database, SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  G_LOCK(s_mutex_net_levels);
  sim_container_db_insert_net_level_ul(container, database, net_level);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_update_net_level(SimContainer *container,
    SimDatabase *database, SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  G_LOCK(s_mutex_net_levels);
  sim_container_db_update_net_level_ul(container, database, net_level);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_net_level(SimContainer *container,
    SimDatabase *database, SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  G_LOCK(s_mutex_net_levels);
  sim_container_db_delete_net_level_ul(container, database, net_level);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_net_level(SimContainer *container, SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  G_LOCK(s_mutex_net_levels);
  sim_container_append_net_level_ul(container, net_level);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_net_level(SimContainer *container, SimNetLevel *net_level)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net_level);
  g_return_if_fail(SIM_IS_NET_LEVEL (net_level));

  G_LOCK(s_mutex_net_levels);
  sim_container_remove_net_level_ul(container, net_level);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_net_levels(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_net_levels);
  list = sim_container_get_net_levels_ul(container);
  G_UNLOCK(s_mutex_net_levels);

  return list;
}

/*
 *
 *
 *
 *
 */
void
sim_container_set_net_levels(SimContainer *container, GList *net_levels)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(net_levels);

  G_LOCK(s_mutex_net_levels);
  sim_container_set_net_levels_ul(container, net_levels);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_net_levels(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_net_levels);
  sim_container_free_net_levels_ul(container);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 *
 *
 *
 *
 */
SimNetLevel*
sim_container_get_net_level_by_name(SimContainer *container, const gchar *name)
{
  SimNetLevel *net_level;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  G_LOCK(s_mutex_net_levels);
  net_level = sim_container_get_net_level_by_name_ul(container, name);
  G_UNLOCK(s_mutex_net_levels);

  return net_level;
}

/*
 *
 *
 *
 */
void
sim_container_set_net_levels_recovery(SimContainer *container,
    SimDatabase *database, gint recovery)
{
  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(recovery >= 0);

  G_LOCK(s_mutex_net_levels);
  sim_container_set_net_levels_recovery_ul(container, database, recovery);
  G_UNLOCK(s_mutex_net_levels);
}

/*
 * Load the children servers from database. This doesn't loads "this" server data (its loaded from /etc/ossim/server/config.xml).
 * This is used to configure children servers and to store its data in memory without waiting them to connect here.
 */
void
sim_container_db_load_servers_ul(SimContainer *container, SimDatabase *database)
{
  SimServer *server;
  GdaDataModel *dm;
  gint row;
  gchar *query = "SELECT name, ip, port FROM server";
  SimConfig *config;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          server = sim_server_new_from_dm(dm, row);
          container->_priv->servers = g_list_append(container->_priv->servers,
              server);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("SERVERS DATA MODEL ERROR");
    }
}

/*
 *
 */
void
sim_container_append_server_ul(SimContainer *container, SimServer *server)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  container->_priv->servers = g_list_append(container->_priv->servers, server);
}

/*
 *
 */
void
sim_container_remove_server_ul(SimContainer *container, SimServer *server)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  container->_priv->servers = g_list_remove(container->_priv->servers, server);
}

/*
 *
 */
GList*
sim_container_get_servers_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  list = g_list_copy(container->_priv->servers);

  return list;
}

/*
 *
 */
void
sim_container_set_servers_ul(SimContainer *container, GList *servers)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(servers);

  container->_priv->servers = g_list_concat(container->_priv->servers, servers);
}

/*
 *
 */
void
sim_container_free_servers_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->servers;
  while (list)
    {
      SimServer *server = (SimServer *) list->data;
      g_object_unref(server);

      list = list->next;
    }
  g_list_free(container->_priv->servers);
  container->_priv->servers = NULL;
}

/*
 *
 */
SimServer*
sim_container_get_server_by_name_ul(SimContainer *container, gchar *name)
{
  SimServer *server;
  GList *list;
  gboolean found = FALSE;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  list = container->_priv->servers;
  while (list)
    {
      server = (SimServer *) list->data;

      if (!g_ascii_strcasecmp(sim_server_get_name(server), name))
        {
          found = TRUE;
          break;
        }

      list = list->next;
    }

  if (!found)
    return NULL;

  return server;
}

/*
 *
 */
void
sim_container_db_load_servers(SimContainer *container, SimDatabase *database)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));

  G_LOCK(s_mutex_servers);
  sim_container_db_load_servers_ul(container, database);
  G_UNLOCK(s_mutex_servers);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_server(SimContainer *container, SimServer *server)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  G_LOCK(s_mutex_servers);
  sim_container_append_server_ul(container, server);
  G_UNLOCK(s_mutex_servers);
}

/*
 *
 */
void
sim_container_remove_server(SimContainer *container, SimServer *server)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  G_LOCK(s_mutex_servers);
  sim_container_remove_server_ul(container, server);
  G_UNLOCK(s_mutex_servers);
}

/*
 *
 */
GList*
sim_container_get_servers(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  G_LOCK(s_mutex_servers);
  list = sim_container_get_servers_ul(container);
  G_UNLOCK(s_mutex_servers);

  return list;
}

/*
 *
 */
void
sim_container_set_servers(SimContainer *container, GList *servers)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(servers);

  G_LOCK(s_mutex_servers);
  sim_container_set_servers_ul(container, servers);
  G_UNLOCK(s_mutex_servers);
}

/*
 *
 */
void
sim_container_free_servers(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  G_LOCK(s_mutex_servers);
  sim_container_free_servers_ul(container);
  G_UNLOCK(s_mutex_servers);
}

/*
 *
 */
SimServer*
sim_container_get_server_by_name(SimContainer *container, gchar *name)
{
  SimServer *server;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail(name, NULL);

  G_LOCK(s_mutex_servers);
  server = sim_container_get_server_by_name_ul(container, name);
  G_UNLOCK(s_mutex_servers);

  return server;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_backlog_ul(SimContainer *container,
    SimDatabase *database, SimDirective *backlog)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar *query = NULL;
  guint backlog_id = 0;

  g_return_if_fail(container != NULL);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database != NULL);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog != NULL);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  backlog_id = sim_database_get_id(ossim.dbossim, BACKLOG_SEQ_TABLE);
  sim_directive_set_backlog_id(backlog, backlog_id);

  query = sim_directive_backlog_get_insert_clause(backlog);
  sim_database_execute_no_query(database, query);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_db_insert_backlog_ul: %s", query);
  g_free(query);
}

/*
 *
 * Update if a specific backlog entry (a SimDirective) has matched or not 
 *
 *
 */
void
sim_container_db_update_backlog_ul(SimContainer *container,
    SimDatabase *database, SimDirective *backlog)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  query = sim_directive_backlog_get_update_clause(backlog);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_backlog_ul(SimContainer *container,
    SimDatabase *database, SimDirective *backlog)
{
  GdaDataModel *dm;
  gchar *query;
  guint32 backlog_id;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  backlog_id = sim_directive_get_backlog_id(backlog);
  query = g_strdup_printf("SELECT backlog_id FROM alarm WHERE backlog_id = %u",
      backlog_id);
  dm = sim_database_execute_single_command(database, query);
  if (dm)
    {
      if (!gda_data_model_get_n_rows(dm))
        sim_container_db_delete_backlog_by_id_ul(backlog_id);

      g_object_unref(dm);
    }
  else
    g_message("BACKLOG DELETE DATA MODEL ERROR");

  g_free(query);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_backlog_event_ul(SimContainer *container,
    SimDatabase *database, SimDirective *backlog, SimEvent *event)
{
  gchar *query;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  query = sim_directive_backlog_event_get_insert_clause(backlog, event);
  sim_database_execute_no_query(database, query);
  g_free(query);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_backlog_ul(SimContainer *container, SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  container->_priv->backlogs = g_list_append(container->_priv->backlogs,
      backlog);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_backlog_ul(SimContainer *container, SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  container->_priv->backlogs = g_list_remove(container->_priv->backlogs,
      backlog);
}

/*
 *
 * This returns a SimDirective list.
 *
 *
 */
GList*
sim_container_get_backlogs_ul(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  return container->_priv->backlogs;
}

/*
 * FIXME: The function wich call to this function is not called anymore.
 * So this function is never executed :)
 *
 *
 */
void
sim_container_set_backlogs_ul(SimContainer *container, GList *backlogs)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(backlogs);

  container->_priv->backlogs = g_list_concat(container->_priv->backlogs,
      backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_backlogs_ul(SimContainer *container)
{
  GList *list;

  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  list = container->_priv->backlogs;
  while (list)
    {
      SimDirective *backlog = (SimDirective *) list->data;
      g_object_unref(backlog);

      list = list->next;
    }
  g_list_free(container->_priv->backlogs);
  container->_priv->backlogs = NULL;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_backlog(SimContainer *container, SimDatabase *database,
    SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_db_insert_backlog_ul(container, database, backlog);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_update_backlog(SimContainer *container, SimDatabase *database,
    SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_db_update_backlog_ul(container, database, backlog);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_backlog(SimContainer *container, SimDatabase *database,
    SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_db_delete_backlog_ul(container, database, backlog);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_insert_backlog_event(SimContainer *container,
    SimDatabase *database, SimDirective *backlog, SimEvent *event)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(database);
  g_return_if_fail(SIM_IS_DATABASE (database));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_db_insert_backlog_event_ul(container, database, backlog, event);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_append_backlog(SimContainer *container, SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_append_backlog_ul(container, backlog);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_remove_backlog(SimContainer *container, SimDirective *backlog)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(backlog);
  g_return_if_fail(SIM_IS_DIRECTIVE (backlog));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_remove_backlog_ul(container, backlog);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
GList*
sim_container_get_backlogs(SimContainer *container)
{
  GList *list;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  g_mutex_lock(ossim.mutex_backlogs);
  list = sim_container_get_backlogs_ul(container);
  g_mutex_unlock(ossim.mutex_backlogs);

  return list;
}

/*
 *
 * FIXME: This function is not called anymore
 *
 *
 */
void
sim_container_set_backlogs(SimContainer *container, GList *backlogs)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(backlogs);

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_set_backlogs_ul(container, backlogs);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_backlogs(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  g_mutex_lock(ossim.mutex_backlogs);
  sim_container_free_backlogs_ul(container);
  g_mutex_unlock(ossim.mutex_backlogs);
}

/*
 *
 *
 *
 *
 */
void
sim_container_push_event(SimContainer *container, SimEvent *event)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  g_mutex_lock(container->_priv->mutex_events);
  g_queue_push_head(container->_priv->events, event);
  g_cond_signal(container->_priv->cond_events);
  g_mutex_unlock(container->_priv->mutex_events);
}

void
sim_container_push_ar_event(SimContainer *container, SimEvent *event)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  //	g_mutex_lock (container->_priv->mutex_ar_events);
  g_async_queue_push(container->_priv->ar_events, event);
  //	g_mutex_unlock (container->_priv->mutex_ar_events);
}

SimEvent*
sim_container_pop_ar_event(SimContainer *container)
{
  SimEvent *event;
  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  //g_mutex_lock (container->_priv->mutex_ar_events);

  event = (SimEvent *) g_async_queue_pop(container->_priv->ar_events);

  //g_mutex_unlock (container->_priv->mutex_ar_events);
  return event;
}

/*
 *
 *
 */
SimEvent*
sim_container_pop_event(SimContainer *container)
{
  SimEvent *event;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  g_mutex_lock(container->_priv->mutex_events);

  while (!g_queue_peek_tail(container->_priv->events)) //We stops until some element appears in the event queue.
    g_cond_wait(container->_priv->cond_events, container->_priv->mutex_events);

  event = (SimEvent *) g_queue_pop_tail(container->_priv->events);

  //FIXXME: Is really needed this 'if' clause?
  if (!g_queue_peek_tail(container->_priv->events)) //if there are more events in the queue, don't do nothing
    {
      g_cond_free(container->_priv->cond_events);
      container->_priv->cond_events = g_cond_new();
    }
  g_mutex_unlock(container->_priv->mutex_events);

  return event;
}

/*
 *
 *
 *
 *
 */
void
sim_container_free_events(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  g_mutex_lock(container->_priv->mutex_events);
  while (!g_queue_is_empty(container->_priv->events))
    {
      SimEvent *event = (SimEvent *) g_queue_pop_head(container->_priv->events);
      g_object_unref(event);
    }
  g_queue_free(container->_priv->events);
  container->_priv->events = g_queue_new();
  g_mutex_unlock(container->_priv->mutex_events);
}

/*
 *
 *
 *
 *
 */
gboolean
sim_container_is_empty_events(SimContainer *container)
{
  gboolean empty;

  g_return_val_if_fail(container, TRUE);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), TRUE);

  g_mutex_lock(container->_priv->mutex_events);
  empty = g_queue_is_empty(container->_priv->events);
  g_mutex_unlock(container->_priv->mutex_events);

  return empty;
}

/*
 *
 *
 *
 *
 */
gint
sim_container_length_events(SimContainer *container)
{
  gint length;

  g_return_val_if_fail(container, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);

  g_mutex_lock(container->_priv->mutex_events);
  length = container->_priv->events->length;
  g_mutex_unlock(container->_priv->mutex_events);

  return length;
}

/*
 * //FIXME: working here. This will insert a monitor rule in a queue
 *
 */
void
sim_container_push_monitor_rule(SimContainer *container, SimRule *rule)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  g_return_if_fail(rule);
  g_return_if_fail(SIM_IS_RULE (rule));

  g_mutex_lock(container->_priv->mutex_monitor_rules);
  g_queue_push_head(container->_priv->monitor_rules, rule);
  g_cond_signal(container->_priv->cond_monitor_rules);
  g_mutex_unlock(container->_priv->mutex_monitor_rules);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_push_monitor_rule: pushed");
}

/*
 * //FIXME: Working here. This will extract the monitor rules from the queue
 *
 */
SimRule*
sim_container_pop_monitor_rule(SimContainer *container)
{
  SimRule *rule;

  g_return_val_if_fail(container, NULL);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), NULL);

  g_mutex_lock(container->_priv->mutex_monitor_rules);

  while (!g_queue_peek_tail(container->_priv->monitor_rules)) //We stop until some element appears in the event queue.
    g_cond_wait(container->_priv->cond_monitor_rules,
        container->_priv->mutex_monitor_rules);

  rule = (SimRule *) g_queue_pop_tail(container->_priv->monitor_rules);

  if (!g_queue_peek_tail(container->_priv->monitor_rules)) //if there are more events in the queue, don't do nothing
    {
      g_cond_free(container->_priv->cond_monitor_rules);
      container->_priv->cond_monitor_rules = g_cond_new();
    }
  g_mutex_unlock(container->_priv->mutex_monitor_rules);

  return rule;
}

/*
 *
 *
 */
void
sim_container_free_monitor_rules(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  g_mutex_lock(container->_priv->mutex_monitor_rules);
  while (!g_queue_is_empty(container->_priv->monitor_rules))
    {
      SimRule *rule = (SimRule *) g_queue_pop_head(
          container->_priv->monitor_rules);
      g_object_unref(rule);
    }
  g_queue_free(container->_priv->monitor_rules);
  container->_priv->monitor_rules = g_queue_new();
  g_mutex_unlock(container->_priv->mutex_monitor_rules);
}

/*
 *
 *
 *
 *
 */
gboolean
sim_container_is_empty_monitor_rules(SimContainer *container)
{
  gboolean empty;

  g_return_val_if_fail(container, TRUE);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), TRUE);

  g_mutex_lock(container->_priv->mutex_monitor_rules);
  empty = g_queue_is_empty(container->_priv->monitor_rules);
  g_mutex_unlock(container->_priv->mutex_monitor_rules);

  return empty;
}

/*
 *
 *
 *
 *
 */
gint
sim_container_length_monitor_rules(SimContainer *container)
{
  gint length;

  g_return_val_if_fail(container, 0);
  g_return_val_if_fail(SIM_IS_CONTAINER (container), 0);

  g_mutex_lock(container->_priv->mutex_monitor_rules);
  length = container->_priv->monitor_rules->length;
  g_mutex_unlock(container->_priv->mutex_monitor_rules);

  return length;
}

/*
 *
 */
void
sim_container_remote_load_element(SimDBElementType element_type)
{
  SimCommand *cmd;
  GList *list;
  GList *list2;

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_remote_load_element OOOO");

  list = sim_server_get_sessions(ossim.server);

  //As there are not local DB, we are going to connect to rservers to get the data. In fact we doesn't load the data here, we send a msg
  //to primary rserver and wait. The rserver will send us a msg with all the data that will be processed in sim_session_cmd_database_answer().
  while (list) //list of the sessions connected to the server. We have to check some things before getting data:
    {
      SimSession *session = (SimSession *) list->data;
      if (sim_session_is_master_server(session)) //check if the session connected has rights
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_container_db_load_plugins_ul2");
          list2 = ossim.config->rservers;
          while (list2)
            {
              SimConfigRServer *rserver = (SimConfigRServer*) list2->data;
              //		sim_config_rserver_debug_print (rserver);

              if (!strcmp(sim_session_get_hostname(session), rserver->name)) //check if the session connected is the primary server
                {
                  if (rserver->primary)
                    { //now we know where to connect and ask for data. We have to specify what kind of data we need to load.
                      cmd = sim_command_new_from_type(
                          SIM_COMMAND_TYPE_DATABASE_QUERY);
                      cmd->id = 0; //not used at this moment.
                      cmd->data.database_query.database_element_type
                          = element_type;
                      cmd->data.database_query.servername = g_strdup(
                          sim_server_get_name(ossim.server));
                      sim_session_write(session, cmd);
                      g_object_unref(cmd);

                      return; //OK, we need to send it only to one server.
                    }
                  else
                    g_message(
                        "Error. Not primary server defined & connected. May be you need to check server's config.xml statment");
                }

              list2 = list2->next;
            }

        }
      list = list->next;
    }
  g_list_free(list);

}

void
sim_container_debug_print_all(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));
  sim_container_debug_print_plugins(container);
  sim_container_debug_print_plugin_sids(container);
  sim_container_debug_print_hosts(container);
  sim_container_debug_print_nets(container);
  sim_container_debug_print_sensors(container);
  sim_container_debug_print_policy(container);
  sim_container_debug_print_host_levels(container);
  sim_container_debug_print_net_levels(container);
  sim_container_debug_print_servers(container);
}

void
sim_container_debug_print_plugins(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_plugins");
  GList *list = container->_priv->plugins;
  while (list)
    {
      SimPlugin *p = (SimPlugin *) list->data;
      sim_plugin_debug_print(p);
      list = list->next;
    }
}

void
sim_container_debug_print_plugin_sids(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_debug_print_plugin_sids");
  GList *list = container->_priv->plugin_sids;
  while (list)
    {
      SimPluginSid *p = (SimPluginSid *) list->data;
      sim_plugin_sid_debug_print(p);
      list = list->next;
    }
}

void
sim_container_debug_print_hosts(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_hosts");
  GList *list = container->_priv->hosts;
  while (list)
    {
      SimHost *host = (SimHost *) list->data;
      sim_host_debug_print(host);
      list = list->next;
    }
}

void
sim_container_debug_print_nets(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_hosts");
  GList *list = container->_priv->nets;
  while (list)
    {
      SimNet *net = (SimNet *) list->data;
      sim_net_debug_print(net);
      list = list->next;
    }
}

void
sim_container_debug_print_sensors(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_sensors");
  GList *list = container->_priv->sensors;
  while (list)
    {
      SimSensor *s = (SimSensor *) list->data;
      sim_sensor_debug_print(s);
      list = list->next;
    }
}

void
sim_container_debug_print_policy(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_policy");
  GList *list = container->_priv->policies;
  while (list)
    {
      SimPolicy *p = (SimPolicy *) list->data;
      sim_policy_debug_print(p);
      list = list->next;
    }
}

void
sim_container_debug_print_host_levels(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_container_debug_print_host_levels");
  GList *list = container->_priv->host_levels;
  while (list)
    {
      SimHostLevel *host_level = (SimHostLevel *) list->data;
      sim_host_level_debug_print(host_level);
      list = list->next;
    }
}

void
sim_container_debug_print_net_levels(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_net_levels");
  GList *list = container->_priv->net_levels;
  while (list)
    {
      SimNetLevel *net_level = (SimNetLevel *) list->data;
      sim_net_level_debug_print(net_level);
      list = list->next;
    }
}

void
sim_container_debug_print_servers(SimContainer *container)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_debug_print_sensors");
  GList *list = container->_priv->servers;
  while (list)
    {
      SimServer *s = (SimServer *) list->data;
      sim_server_debug_print(s);
      list = list->next;
    }
}

void
sim_container_wait_rload_complete(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  g_mutex_lock(container->_priv->rload_mutex);

  while (!container->_priv->rload_complete) //this is set in 
    g_cond_wait(container->_priv->rload_cond, container->_priv->rload_mutex);

  g_mutex_unlock(container->_priv->rload_mutex);

}

/*
 *
 */
void
sim_container_set_rload_complete(SimContainer *container)
{
  g_return_if_fail(container);
  g_return_if_fail(SIM_IS_CONTAINER (container));

  g_mutex_lock(container->_priv->rload_mutex);
  container->_priv->rload_complete = TRUE;
  g_cond_signal(container->_priv->rload_cond);
  g_mutex_unlock(container->_priv->rload_mutex);

}

gint
sim_container_policy_has_actions_in_db(SimDatabase *database, gint policy_id)
{
  if (policy_id <= 0)
    return 0;

  GdaDataModel *dm;
  gchar *query;

  query = g_strdup_printf(
      "SELECT policy_id, action_id FROM policy_actions WHERE policy_id = %d",
      policy_id);
  dm = sim_database_execute_single_command(database, query);

  if (dm)
    return gda_data_model_get_n_rows(dm);
  return 0;

}

// vim: set tabstop=2:

