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

#include "os-sim.h"
#include "sim-session.h"
#include "sim-server.h"
#include "sim-sensor.h"
#include <signal.h>
#include <config.h>

extern SimMain ossim;

enum
{
  DESTROY, LAST_SIGNAL
};

struct _SimServerPrivate
{
  SimConfig *config;

  GTcpSocket *socket;

  gint port;

  GList *sessions;

  gchar *ip;
  gchar *name;

  GCond *sessions_cond; //condition & mutex to control fully_stablished var.
  GMutex *sessions_mutex;

};

typedef struct
{
  SimConfig *config;
  SimServer *server;
  GTcpSocket *socket;
} SimSessionData;

static gpointer
sim_server_session(gpointer data);

static gpointer parent_class = NULL;
static gint sim_server_signals[LAST_SIGNAL] =
  { 0 };

/* GType Functions */

static void
sim_server_impl_dispose(GObject *gobject)
{
  G_OBJECT_CLASS(parent_class)->dispose(gobject);
}

static void
sim_server_impl_finalize(GObject *gobject)
{
  SimServer *server = SIM_SERVER (gobject);
  g_cond_free(server->_priv->sessions_cond);
  g_mutex_free(server->_priv->sessions_mutex);

  g_free(server->_priv);

  G_OBJECT_CLASS(parent_class)->finalize(gobject);

}

static void
sim_server_class_init(SimServerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS(class);

  parent_class = g_type_class_peek_parent(class);

  object_class->dispose = sim_server_impl_dispose;
  object_class->finalize = sim_server_impl_finalize;
}

static void
sim_server_instance_init(SimServer * server)
{
  server->_priv = g_new0(SimServerPrivate, 1);

  server->_priv->config = NULL;
  server->_priv->socket = NULL;

  server->_priv->port = 40001;

  server->_priv->sessions = NULL;

  server->_priv->ip = NULL;
  server->_priv->name = NULL;

  server->_priv->sessions_cond = g_cond_new();
  server->_priv->sessions_mutex = g_mutex_new();

}

/* Public Methods */

GType
sim_server_get_type(void)
{
  static GType object_type = 0;

  if (!object_type)
    {
      static const GTypeInfo type_info =
        { sizeof(SimServerClass), NULL, NULL,
            (GClassInitFunc) sim_server_class_init, NULL, NULL, /* class data */
            sizeof(SimServer), 0, /* number of pre-allocs */
            (GInstanceInitFunc) sim_server_instance_init, NULL /* value table */
        };

      g_type_init();

      object_type = g_type_register_static(G_TYPE_OBJECT, "SimServer",
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
SimServer*
sim_server_new(SimConfig *config)
{
  SimServer *server;

  g_return_val_if_fail(config, NULL);
  g_return_val_if_fail(SIM_IS_CONFIG (config), NULL);

  server = SIM_SERVER (g_object_new (SIM_TYPE_SERVER, NULL));
  server->_priv->config = config;

  if (config->server.name)
    server->_priv->name = g_strdup(config->server.name);

  if (simCmdArgs.port > 0)
    server->_priv->port = simCmdArgs.port;
  else if (config->server.port > 0) //anti-moron sanity check
    server->_priv->port = config->server.port;

  if (simCmdArgs.ip)
    server->_priv->ip = g_strdup(simCmdArgs.ip);
  else if (config->server.ip)
    server->_priv->ip = g_strdup(config->server.ip);

  return server;
}

/*
 * As we want to use the same functions that with the "normal" server, we fill the
 * internal data of the server with the HA config data. ie, the server->_priv->ip
 * with the HA_ip from the config.
 *
 * Each server ("normal" and HA) will have it's own sessions.
 */
SimServer*
sim_server_HA_new(SimConfig *config)
{
  SimServer *server;

  g_return_val_if_fail(config, NULL);
  g_return_val_if_fail(SIM_IS_CONFIG (config), NULL);

  server = SIM_SERVER (g_object_new (SIM_TYPE_SERVER, NULL));
  server->_priv->config = config;

  if (config->server.name)
    server->_priv->name = g_strdup(config->server.name);

  if (config->server.HA_port > 0)
    server->_priv->port = config->server.HA_port;

  if (config->server.HA_ip)
    server->_priv->ip = g_strdup(config->server.HA_ip);

  return server;
}

/*
 * NOTE: This is NOT the "normal" server. This is used to store & maintain configuration of children servers.
 */
SimServer*
sim_server_new_from_dm(GdaDataModel *dm, gint row)
{
  SimServer *server;
  GdaValue *value;
  SimConfig *config;

  g_return_val_if_fail(dm, NULL);
  g_return_val_if_fail(GDA_IS_DATA_MODEL(dm), NULL);

  config = sim_config_new(); //used almost only to store the server's role

  server = SIM_SERVER (g_object_new (SIM_TYPE_SERVER, NULL));
  server->_priv->config = config;

  value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
  server->_priv->name = gda_value_stringify(value);

  value = (GdaValue *) gda_data_model_get_value_at(dm, 1, row);
  server->_priv->ip = gda_value_stringify(value);

  value = (GdaValue *) gda_data_model_get_value_at(dm, 2, row);
  server->_priv->port = gda_value_get_integer(value);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_server_new_from_dm: %s",
      server->_priv->name);
  sim_server_debug_print(server);

  return server;

}

/*
 * OSSIM has internally in fact two servers; the ossim.server (the "main" server), wich
 * stores all the sessions from children and master servers, as well as the sensors and 
 * frameworkd sessions. And the ossim.HA_server, wich only contains sessions from an
 * HA server. 
 *
 * This function can be called with ossim.server or ossim.HA_server as parameters. Here
 * is the main loop wich accept connections from "main" server or HA server.
 * 
 */
void
sim_server_listen_run(SimServer *server)
{
  SimSession *session;
  SimSensor *sensor;
  SimSessionData *session_data;
  GTcpSocket * socket;
  GThread *thread;
  GError *error;
  GInetAddr *serverip;

  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  g_message("Waiting for connections...");

  if (!server->_priv->ip)
    server->_priv->ip = g_strdup("0.0.0.0");

  serverip = gnet_inetaddr_new_nonblock(server->_priv->ip, 0);
  if (!serverip)
    {
      g_message(
          "Error creating server address. Please check that the ip %s has the right format",
          server->_priv->ip);
      exit(EXIT_FAILURE);
    }

  server->_priv->socket = gnet_tcp_socket_server_new_full(serverip,
      server->_priv->port); //bind in the interface defined

  if (!server->_priv->socket)
    {
      printf(
          "Error in bind; may be another app is running in port %d? You should also check the <server ... ip=\"\"> entry and see if any of your local interfaces has got that ip address.",
          server->_priv->port); //the log file may be in use.
      g_message(
          "Error in bind; may be another app is running in port %d? You should also check the <server ... ip=\"\"> entry and see if any of your local interfaces has got that ip address.",
          server->_priv->port);
      exit(EXIT_FAILURE);
    }

  //Main loop wich accept connections
  while ((socket = gnet_tcp_socket_server_accept(server->_priv->socket))
      != NULL)
    {
      /*FIXME: we don't know yet the type of the session. we can't close it
       * just because the ip is the same. Check if do something with this is really interesting
       * (I don't think so, very probably I'll remove this check in a near future)
       //If we have some session established with that machine, it will be removed before the new session gets connected.
       GInetAddr *ia = gnet_tcp_socket_get_remote_inetaddr (socket);
       sensor = sim_container_get_sensor_by_ia (ossim.container, ia);
       if (sensor)
       {
       session = sim_server_get_session_by_sensor (server, sensor);
       if (session)
       {
       sim_session_close (session);
       }
       }
       gnet_inetaddr_unref (ia);
       */

      session_data = g_new0(SimSessionData, 1);
      session_data->config = server->_priv->config;
      session_data->server = server;
      session_data->socket = socket;

      /* Session Thread */
      thread = g_thread_create(sim_server_session, session_data, FALSE, &error);

      if (thread == NULL)
        g_message("thread error %d: %s", error->code, error->message);
      else
        continue;

    }

}

/*
 *
 *
 */
void
sim_server_HA_run(SimServer *server)
{
  SimSession *session;
  SimSensor *sensor;
  SimSessionData *session_data;
  GTcpSocket * socket;
  GThread *thread;
  GError *error;
  GInetAddr *serverip;

  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  g_message("Waiting for connections...");

  if (!server->_priv->ip)
    server->_priv->ip = g_strdup("0.0.0.0");

  serverip = gnet_inetaddr_new_nonblock(server->_priv->ip, 0);
  if (!serverip)
    {
      g_message(
          "Error creating server address. Please check that the ip %s has the right format",
          server->_priv->ip);
      exit(EXIT_FAILURE);
    }

  server->_priv->socket = gnet_tcp_socket_server_new_full(serverip,
      server->_priv->port); //bind in the interface defined

  if (!server->_priv->socket)
    {
      printf(
          "Error in bind; as you didn't specify different ip and/or port for the HA process, it will listen in the same ip/port than the server"); //the log file may be in use.
      g_message(
          "Error in bind; as you didn't specify different ip and/or port for the HA process, it will listen in the same ip/port than the server");
      return;
    }

  while ((socket = gnet_tcp_socket_server_accept(server->_priv->socket))
      != NULL)
    {
      GInetAddr *ia = gnet_tcp_socket_get_remote_inetaddr(socket);
      sensor = sim_container_get_sensor_by_ia(ossim.container, ia);
      if (sensor)
        {
          session = sim_server_get_session_by_sensor(server, sensor);
          //FIXME: little memory leak to avoid some crashes.. :( fix ASAP!
          if (session)
            sim_session_close(session);
        }
      gnet_inetaddr_unref(ia);

      session_data = g_new0(SimSessionData, 1);
      session_data->config = server->_priv->config;
      session_data->server = server;
      session_data->socket = socket;

      /* Session Thread */
      thread = g_thread_create(sim_server_session, session_data, FALSE, &error);

      if (thread == NULL)
        g_message("thread error %d: %s", error->code, error->message);
      else
        continue;

    }

}

/*
 *
 *
 *
 *
 *
 */
static gpointer
sim_server_session(gpointer data)
{
  SimSessionData *session_data = (SimSessionData *) data;
  SimConfig *config = session_data->config;
  SimServer *server = session_data->server;
  GTcpSocket *socket = session_data->socket;
  SimSession *session;

  g_return_val_if_fail(config, NULL);
  g_return_val_if_fail(SIM_IS_CONFIG (config), NULL);
  g_return_val_if_fail(server, NULL);
  g_return_val_if_fail(SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail(socket, NULL);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_server_session: Trying to do a sim_session_new: pid %d", getpid());

  session = sim_session_new(G_OBJECT(server), config, socket);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_server_session: New Session: pid %d; session address: %x", getpid(),
      session);
  g_message("New session");

  if (!sim_session_must_close(session))
    {
      sim_server_append_session(server, session);

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_server_session: Session Append: pid %d; session address: %x",
          getpid(), session);
      g_message("Session Append");

      sim_session_read(session);

      if (sim_server_remove_session(server, session))
        { /*
         if (sim_session_is_sensor (session))
         {
         GInetAddr *ia = sim_sensor_get_ia (sim_session_get_sensor (session));
         gchar *ip = gnet_inetaddr_get_canonical_name (ia);
         if (sim_session_get_hostname (session))
         g_message ("- Session Sensor %s %s: REMOVED", sim_session_get_hostname (session), ip);
         else
         g_message ("- Session Sensor: REMOVED");
         g_free (ip);
         }*/

          g_message("Session Removed");
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_server_session: After remove session: pid %d. session: %x",
              getpid(), session);
        }
      else
        g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
            "sim_server_session: Error removing session: %x", session);
    }
  else
    {
      g_object_unref(session);
      g_message("Session Removed: error");
      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "sim_server_session: Error: after remove session: pid %d. session: %x",
          getpid(), session);
    }

  g_free(session_data);

  return NULL;
}

/*
 *
 *
 *
 *
 *
 */
void
sim_server_append_session(SimServer *server, SimSession *session)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));
  g_return_if_fail(session);
  g_return_if_fail(SIM_IS_SESSION (session));

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  server->_priv->sessions = g_list_append(server->_priv->sessions, session);

  g_mutex_unlock(server->_priv->sessions_mutex);
}

/*
 *
 *
 *
 *
 *
 */
gint
sim_server_remove_session(SimServer *server, SimSession *session)
{
  g_return_val_if_fail(server, 0);
  g_return_val_if_fail(SIM_IS_SERVER (server), 0);
  g_return_val_if_fail(session, 0);
  g_return_val_if_fail(SIM_IS_SESSION (session), 0);

  void * tmp = session;

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  server->_priv->sessions = g_list_remove(server->_priv->sessions, tmp); //and then, the list node itself
  g_object_unref(session);//first, remove the data inside the session

  g_mutex_unlock(server->_priv->sessions_mutex);

  return 1;
}

/*
 *
 *
 *
 *
 *
 */
GList*
sim_server_get_sessions(SimServer *server)
{
  GList *list;
  g_return_val_if_fail(server, NULL);
  g_return_val_if_fail(SIM_IS_SERVER (server), NULL);

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);
  list = g_list_copy(server->_priv->sessions);
  g_mutex_unlock(server->_priv->sessions_mutex);

  return list;

}

/*
 * This is called just from sim_organizer_run
 */
void
sim_server_push_session_command(SimServer *server, SimSessionType session_type,
    SimCommand *command)
{
  GList *list;

  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
    {
      SimSession *session = (SimSession *) list->data;

      if ((session != NULL) && SIM_IS_SESSION(session))
        if (session_type == SIM_SESSION_TYPE_ALL || session_type
            == session->type)
          sim_session_write(session, command);

      list = list->next;
    }

  g_mutex_unlock(server->_priv->sessions_mutex);
}

/*
 *
 *	Now, depending on the rule, we'll generate a specific command that will be sent
 *	with the data from the rule to the agent who issued the event that
 *	made match with the alarm.
 *
 *
 */
void
sim_server_push_session_plugin_command(SimServer *server,
    SimSessionType session_type, gint plugin_id, SimRule *rule)
{
  GList *list;

  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));
  g_return_if_fail(rule);
  g_return_if_fail(SIM_IS_RULE (rule));

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;

  list = server->_priv->sessions;
  while (list)
    {
      SimSession *session = (SimSession *) list->data;

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_server_push_session_plugin_command");
      if ((session != NULL) && SIM_IS_SESSION (session))
        {
          if (session_type == SIM_SESSION_TYPE_ALL || session_type
              == session->type)
            {
              if (sim_session_has_plugin_id(session, plugin_id))
                {
                  /*				monitor_requests	*data = g_new0 (monitor_requests, 1);
                   GError						*error;
                   GThread *thread;
                   */
                  g_log(
                      G_LOG_DOMAIN,
                      G_LOG_LEVEL_DEBUG,
                      "sim_server_push_session_plugin_command. Monitor request for plugin_id: %d",
                      plugin_id);
                  SimCommand *cmd = sim_command_new_from_rule(rule); //this will be freed in sim_server_thread_monitor_requests()
                  //					data->session = session;
                  //				data->command = cmd;
                  sim_session_write(session, cmd);
                  g_object_unref(cmd);
                  //				  thread = g_thread_create (sim_server_thread_monitor_requests, data, FALSE, &error);
                  //	    if (thread == NULL)
                  //      g_message ("thread error %d: %s", error->code, error->message);

                }
            }
        }
      else
        {
          //avoiding race condition; this happens when the agent disconnect from the server and there aren't any established session. FIXME: this will broke the correlation procedure in this event, I've to check this ASAP.
          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_server_push_session_plugin_command: Error, session %x is invalid!!",
              session);
          break;
        }

      list = list->next;
    }
  g_mutex_unlock(server->_priv->sessions_mutex);
}

#if 0
gpointer
sim_server_thread_monitor_requests (gpointer data)
  {
    monitor_requests *request = (monitor_requests *) data;

    g_return_val_if_fail (request->command != NULL, 0);
    g_return_val_if_fail (SIM_IS_COMMAND (request->command), 0);

    sim_session_write (request->session, request->command);

    //I don't like to reserve/free memory in different levels of execution, but it's the only way
    //without change a bit more the code
    g_object_unref (request->command);

    return NULL;
  }
#endif

/*
 *
 *
 *
 *
 *
 */
void
sim_server_reload(SimServer *server)
{
  GList *list;

  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
    {
      SimSession *session = (SimSession *) list->data;

      if ((session != NULL) && SIM_IS_SESSION(session))
        sim_session_reload(session);

      list = list->next;
    }
  g_mutex_unlock(server->_priv->sessions_mutex);
}

/*
 *
 * We want to know wich is the session wich belongs to a specific sensor
 *
 *
 */
SimSession*
sim_server_get_session_by_sensor(SimServer *server, SimSensor *sensor)
{
  GList *list;

  g_return_val_if_fail(server, NULL);
  g_return_val_if_fail(SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail(sensor, NULL);
  g_return_val_if_fail(SIM_IS_SENSOR (sensor), NULL);

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
    {
      SimSession *session = (SimSession *) list->data;
      if ((session != NULL) && SIM_IS_SESSION(session))
        if (sim_session_get_sensor(session) == sensor)
          {
            g_mutex_unlock(server->_priv->sessions_mutex);
            return session;
          }

      list = list->next;
    }
  g_mutex_unlock(server->_priv->sessions_mutex);

  return NULL; //no sessions stablished
}

/*
 *
 * returns this server's bind IP.
 *
 *
 */
gchar*
sim_server_get_ip(SimServer *server)
{
  GList *list;

  g_return_val_if_fail(server, NULL);
  g_return_val_if_fail(SIM_IS_SERVER (server), NULL);

  return server->_priv->ip;
}

/*
 * returns this server's unique OSSIM name.
 */
gchar*
sim_server_get_name(SimServer *server)
{
  GList *list;

  g_return_val_if_fail(server, NULL);
  g_return_val_if_fail(SIM_IS_SERVER (server), NULL);

  return server->_priv->name;
}

/*
 *
 * This will return the session associated with a specific ia (ip & port).
 * If the parameter "server" is ossim.server, here you'll find the sessions from 
 * other agents, as well as the sessions from this server to its master servers.
 * If the parameter "server" is ossim.HA_server, you'll find the HA server sessions.
 *
 * Although it's a bad idea (and I'm not sure if really interesting),
 * you can do the following: Say you have 2 machines each one with an ossim-server, A and B. 
 * You can connect server A to server B, and configure the agent B to send data 
 * to server A instead to server B.
 *
 */
SimSession*
sim_server_get_session_by_ia(SimServer *server, SimSessionType session_type,
    GInetAddr *ia)
{
  GList *list;

  g_return_val_if_fail(server, NULL);
  g_return_val_if_fail(SIM_IS_SERVER (server), NULL);

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
    {
      SimSession *session = (SimSession *) list->data;
      if ((session != NULL) && SIM_IS_SESSION(session))
        if (session_type == SIM_SESSION_TYPE_ALL || session_type
            == session->type)
          {
            GInetAddr *tmp = sim_session_get_ia(session);
            if (gnet_inetaddr_equal(tmp, ia))
              {
                g_mutex_unlock(server->_priv->sessions_mutex);
                return session;
              }
          }

      list = list->next;
    }
  g_mutex_unlock(server->_priv->sessions_mutex);
  return NULL;
}

/*
 * Sets this server's different roles
 *
 */
void
sim_server_set_data_role(SimServer *server, SimCommand *command)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));
  g_return_if_fail(command);
  g_return_if_fail(SIM_IS_COMMAND (command));

  SimConfig *conf = server->_priv->config;
  sim_config_set_data_role(conf, command);
}

/*
 * Same than sim_server_set_data_role, but this only stores data in memory and directly from role.
 */
void
sim_server_set_role(SimServer *server, SimRole *role)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));
  g_return_if_fail(role);

  SimConfig *config = server->_priv->config;
  config->server.role = role;
}

SimRole *
sim_server_get_role(SimServer *server)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  SimConfig *config = server->_priv->config;
  return config->server.role;
}

SimConfig*
sim_server_get_config(SimServer *server)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  return server->_priv->config;
}

gint
sim_server_get_port(SimServer *server)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  return server->_priv->port;
}

void
sim_server_set_port(SimServer *server, gint port)
{
  g_return_if_fail(server);
  g_return_if_fail(SIM_IS_SERVER (server));

  server->_priv->port = port;
}
/*
 *
 * Debug function: print the server sessions 
 *
 *
 */
void
sim_server_debug_print_sessions(SimServer *server)
{
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_server_debug_print_sessions:");
  GList *list;
  int a = 0;

  g_mutex_lock(server->_priv->sessions_mutex);
  while (!server->_priv->sessions_cond) //if we dont have the condition, g_cond_wait().
    g_cond_wait(server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
    {
      SimSession *session = (SimSession *) list->data;
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "session %d: %x", a, session);
      a++;
      list = list->next;
    }
  g_mutex_unlock(server->_priv->sessions_mutex);

}

void
sim_server_debug_print(SimServer *server)
{
  gchar *aux = g_strdup_printf("%s|%s|%d", sim_server_get_name(server),
      sim_server_get_ip(server), sim_server_get_port(server));

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_sensor_debug_print: %s", aux);

  g_free(aux);

}

/*
 * Load "this" server info into memory
 */
void
sim_server_load_role(SimServer *server)
{
  GdaDataModel *dm;
  gchar *query;
  gchar *c;
  GdaValue *value;
  gchar *yesno; //aux var

  SimConfig *config = server->_priv->config;

  //load correlate role
  query = g_strdup_printf(
      "SELECT value FROM config WHERE conf = 'server_correlate'");
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      yesno = gda_value_stringify(value);
      if (!strcmp(yesno, "yes"))
        config->server.role->correlate = TRUE;
      else
        config->server.role->correlate = FALSE;
      g_free(yesno);
      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");
  g_free(query);

  query = g_strdup_printf(
      "SELECT value FROM config WHERE conf = 'server_cross_correlate'");
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      yesno = gda_value_stringify(value);
      if (!strcmp(yesno, "yes"))
        config->server.role->cross_correlate = TRUE;
      else
        config->server.role->cross_correlate = FALSE;
      g_free(yesno);
      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");
  g_free(query);

  query = g_strdup_printf(
      "SELECT value FROM config WHERE conf = 'server_store'");
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      yesno = gda_value_stringify(value);
      if (!strcmp(yesno, "yes"))
        config->server.role->store = TRUE;
      else
        config->server.role->store = FALSE;
      g_free(yesno);
      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");
  g_free(query);

  query = g_strdup_printf(
      "SELECT value FROM config WHERE conf = 'server_qualify'");
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      yesno = gda_value_stringify(value);
      if (!strcmp(yesno, "yes"))
        config->server.role->qualify = TRUE;
      else
        config->server.role->qualify = FALSE;
      g_free(yesno);
      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");
  g_free(query);

  query = g_strdup_printf(
      "SELECT value FROM config WHERE conf = 'server_forward_alarm'");
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      yesno = gda_value_stringify(value);
      if (!strcmp(yesno, "yes"))
        config->server.role->resend_alarm = TRUE;
      else
        config->server.role->resend_alarm = FALSE;
      g_free(yesno);
      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");
  g_free(query);

  query = g_strdup_printf(
      "SELECT value FROM config WHERE conf = 'server_forward_event'");
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      yesno = gda_value_stringify(value);
      if (!strcmp(yesno, "yes"))
        config->server.role->resend_event = TRUE;
      else
        config->server.role->resend_event = FALSE;
      g_free(yesno);
      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");
  g_free(query);
}

/*
 * Loads the role of all the children servers. The server parameter must be a children server.
 */
void
sim_server_load_role_children(SimServer *server)
{
  GdaDataModel *dm;
  gchar *query;
  gchar *c;
  GdaValue *value;

  SimConfig *config = server->_priv->config;

  //load correlate role
  query
      = g_strdup_printf(
          "SELECT correlate, cross_correlate, store, qualify, resend_alarm, resend_event FROM server_role WHERE name = '%s'",
          sim_server_get_name(server));
  dm = sim_database_execute_single_command(ossim.dbossim, query);
  if (dm)
    {
      value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
      sim_gda_value_extract_type(value);
      config->server.role->correlate = gda_value_get_tinyint(value);
      value = (GdaValue *) gda_data_model_get_value_at(dm, 1, 0);
      sim_gda_value_extract_type(value);
      config->server.role->cross_correlate = gda_value_get_tinyint(value);
      value = (GdaValue *) gda_data_model_get_value_at(dm, 2, 0);
      sim_gda_value_extract_type(value);
      config->server.role->store = gda_value_get_tinyint(value);
      value = (GdaValue *) gda_data_model_get_value_at(dm, 3, 0);
      sim_gda_value_extract_type(value);
      config->server.role->qualify = gda_value_get_tinyint(value);
      value = (GdaValue *) gda_data_model_get_value_at(dm, 4, 0);
      sim_gda_value_extract_type(value);
      config->server.role->resend_alarm = gda_value_get_tinyint(value);
      value = (GdaValue *) gda_data_model_get_value_at(dm, 5, 0);
      sim_gda_value_extract_type(value);
      config->server.role->resend_event = gda_value_get_tinyint(value);

      g_object_unref(dm);
    }
  else
    g_message("LOAD ROLE DATA MODEL ERROR");

  g_free(query);

}

// vim: set tabstop=2:
