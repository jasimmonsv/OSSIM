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
#include <string.h>
#include <signal.h>
#include "sim-config.h"	//server role.
#include "os-sim.h"
#include "sim-session.h"
#include "sim-rule.h"
#include "sim-directive.h"
#include "sim-plugin-sid.h"
#include "sim-plugin.h"
#include "sim-container.h"
#include "sim-sensor.h"
#include "sim-command.h"
#include "sim-plugin-state.h"
#include <config.h>

extern SimMain    ossim;

enum 
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimSessionPrivate {
  GTcpSocket	*socket;

  SimServer		*server;
  SimConfig		*config;

  SimSensor		*sensor;
  GList				*plugins;
  GList				*plugin_states;

  GIOChannel	*io;

  GInetAddr		*ia;
  gint				seq;
  gboolean		close;
  gboolean		connect;
	gchar				*hostname;	//name of the machine connected. This can be a server name (it can be up or down in the architecture)
													//, a sensor name or even a frameworkd name
  guint       watch; 

  gboolean    is_initial;	//When the server doesn't uses a local DB, it has to take the data from a master server.
													//But at the moment that Container tries to load the data, still there aren't
													//any active session, and we must accept ONLY data from master server and ONLY data with
													//information from DB. We can't accept other events because they obviously will crash the server. 
													//is_initial=TRUE if this is the initial session where data are loaded. (this happens in sim_container_new())
													
	gboolean		fully_stablished; //If this server hasn't got local DB, the container needs to know when can
																//ask for data to master servers. The connection will be fully_stablished when the children server (this
																//server) had been sent a message to master server, and the master server answers with an OK.
	GCond				*initial_cond;		//condition & mutex to control fully_stablished var.
	GMutex			*initial_mutex;		

	gint				id;			//this id is not used always. It's used to know what is the identification of the master server or
											//frameworkd that sent a msg to this server, asking for data in a children server. I.e. server1->server2->server3. 
											//server1 asks to server2 for the sensors connected to server3. We store in the session id the same id that server1
											//sent us, and it will be kept during all the messages.

};

static gpointer parent_class = NULL;
static gint sim_server_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_session_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_session_impl_finalize (GObject  *gobject)
{
  SimSession *session = SIM_SESSION (gobject);

  if (session->_priv->socket)
    gnet_tcp_socket_delete (session->_priv->socket);

	if (sim_session_is_sensor (session))
			g_message ("Session Sensor : REMOVED");
	else
	if (sim_session_is_web (session))
		g_message ("Session Web: REMOVED");
	else
	if (sim_session_is_master_server (session))
		g_message ("Session Master server: REMOVED");
	else
	if (sim_session_is_children_server (session))
		g_message ("Session Children Server: REMOVED");
	
	gchar *ip = gnet_inetaddr_get_canonical_name (session->_priv->ia);
	g_message ("              Removed IP: %s", ip);
	g_free (ip);

  if (session->_priv->ia)
    gnet_inetaddr_unref (session->_priv->ia);
  if (session->_priv->hostname)
		    g_free (session->_priv->hostname);

	g_cond_free (session->_priv->initial_cond);
	g_mutex_free (session->_priv->initial_mutex);

  g_free (session->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_session_class_init (SimSessionClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_session_impl_dispose;
  object_class->finalize = sim_session_impl_finalize;
}

static void
sim_session_instance_init (SimSession *session)
{
  session->_priv = g_new0 (SimSessionPrivate, 1);

  session->type = SIM_SESSION_TYPE_NONE;

  session->_priv->socket = NULL;

  session->_priv->config = NULL;
  session->_priv->server = NULL;

  session->_priv->sensor = NULL;
  session->_priv->plugins = NULL;

  session->_priv->plugin_states = NULL;

  session->_priv->io = NULL;
  session->_priv->ia = NULL;

  session->_priv->seq = 0;

  session->_priv->connect = FALSE;
  
	session->_priv->hostname = NULL;

	session->_priv->is_initial = FALSE;

	//mutex initial session init. In fact we only need the condition.
	session->_priv->fully_stablished = FALSE;
	session->_priv->initial_cond = g_cond_new();
	session->_priv->initial_mutex = g_mutex_new();

	session->_priv->id = 0;
}

/* Public Methods */

GType
sim_session_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimSessionClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_session_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimSession),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_session_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimSession", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimSession*
sim_session_new (GObject       *object,
								 SimConfig     *config,
								 GTcpSocket    *socket)
{
  SimServer    *server = (SimServer *) object;
  SimSession   *session = NULL;

  g_return_val_if_fail (server, NULL);
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);
  g_return_val_if_fail (socket, NULL);

  session = SIM_SESSION (g_object_new (SIM_TYPE_SESSION, NULL));
  session->_priv->config = config;
  session->_priv->server = server;
  session->_priv->socket = socket;
  session->_priv->close = FALSE;
		
  session->_priv->ia = gnet_tcp_socket_get_remote_inetaddr (socket);

	gchar *ip_temp = gnet_inetaddr_get_canonical_name (session->_priv->ia);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_new: remote IP/port: %s/%d", ip_temp, gnet_inetaddr_get_port (session->_priv->ia));
  g_message ("New Session remote IP: %s", ip_temp);
	g_free (ip_temp);
		
  if (gnet_inetaddr_is_loopback (session->_priv->ia)) //if the agent is in the same host than the server, we should get the real ip.
  {
		GInetAddr *aux = gnet_inetaddr_get_host_addr ();
		if (aux)
		{
			gnet_inetaddr_unref (session->_priv->ia);
	    session->_priv->ia = aux;
		  gchar *ip_temp = gnet_inetaddr_get_canonical_name (session->_priv->ia);
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_new Remote address is loopback, applying new address: %s ", ip_temp);
			g_free (ip_temp);
		}
		else
		{
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_new: Warning: we will maintain the 127.0.0.1 address. Please check your /etc/hosts file to include the real IP");
		}
  }

  session->_priv->io = gnet_tcp_socket_get_io_channel (session->_priv->socket);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_new session->_priv->io: %x",session->_priv->io);

	if (!session->_priv->io) //FIXME: Why does this happens?
  {
	  gchar *ip_temp = gnet_inetaddr_get_canonical_name (session->_priv->ia);
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_new Error: channel with IP %s has been closed (NULL value)", ip_temp);
		g_free (ip_temp);
    
    session->_priv->close=TRUE;
    return session;
  }
  return session;
}

/*
 *
 *
 *
 */
GInetAddr*
sim_session_get_ia (SimSession *session)
{
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  return session->_priv->ia;
}

/*
 * The hostname in a session means the name of the connected machine. This can be i.e. the hostname of a server or a sensor one.
 * This has nothing to do with the FQDN of the machine, this is the OSSIM name.
 */
void
sim_session_set_hostname (SimSession *session,
													gchar *hostname)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	session->_priv->hostname = g_strdup (hostname);
}

/*
 */
gchar*
sim_session_get_hostname (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	return session->_priv->hostname;
}


/*
 *
 *
 *
 */
static void
sim_session_cmd_connect (SimSession  *session,
												 SimCommand  *command)
{
  SimCommand  *cmd;
  SimSensor   *sensor = NULL;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));
  
  sensor = sim_container_get_sensor_by_ia (ossim.container, session->_priv->ia);
  session->_priv->sensor = sensor;	//if the connection is from a server or frameworkd, this will be NULL.
	
  switch (command->data.connect.type)
  {
    case SIM_SESSION_TYPE_SERVER_DOWN:
		      session->type = SIM_SESSION_TYPE_SERVER_DOWN;
				  break;
    case SIM_SESSION_TYPE_SENSOR:
		      session->type = SIM_SESSION_TYPE_SENSOR;
				  break;
    case SIM_SESSION_TYPE_WEB:
		      session->type = SIM_SESSION_TYPE_WEB;
				  break;
    default:
		      session->type = SIM_SESSION_TYPE_NONE;
		      break;
  }
	
	if (command->data.connect.hostname)
		sim_session_set_hostname (session, command->data.connect.hostname);
	else
		sim_session_set_hostname (session, "");

	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_connect: hostname: %s", sim_session_get_hostname(session));

	
	if (session->type != SIM_SESSION_TYPE_NONE)
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;

		sim_session_write (session, cmd);
	  g_object_unref (cmd);
		session->_priv->connect = TRUE;

		if(command->data.connect.version!=NULL)
		{
			//Update server version

			gchar *ip = gnet_inetaddr_get_canonical_name (session->_priv->ia);
      gchar *query= g_strdup_printf("INSERT INTO sensor_properties (ip,version) VALUES (\"%s\",\"%s\") ON DUPLICATE KEY UPDATE version = \"%s\"", ip, command->data.connect.version, command->data.connect.version);

			g_free (ip);
			sim_database_execute_no_query(ossim.dbossim, query);
			g_free(query);
		}
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;

		sim_session_write (session, cmd);
	  g_object_unref (cmd);

		g_message("Received a strange session type. Clossing connection....");
		sim_session_close (session);
	}

}

/*
 * This command add one to the session plugin count in the server.
 *
 * If the plugin is a Monitor plugin, and it matches with a root node directive,
 * a msg is sent to the agent to test if it matches.
 *
 */
static void
sim_session_cmd_session_append_plugin (SimSession  *session,
				       SimCommand  *command)
{
  SimCommand      *cmd;
  SimPlugin       *plugin = NULL;
  SimPluginState  *plugin_state;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  session->type = SIM_SESSION_TYPE_SENSOR;	//FIXME: This will be desappear. A session always must be initiated
																						//with a "connect" command

  plugin = sim_container_get_plugin_by_id (ossim.container, command->data.session_append_plugin.id);
  if (plugin)
  {
    plugin_state = sim_plugin_state_new_from_data (plugin,
						     command->data.session_append_plugin.id,
						     command->data.session_append_plugin.state,
						     command->data.session_append_plugin.enabled);

    session->_priv->plugin_states = g_list_append (session->_priv->plugin_states, plugin_state);
/*
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  */
      /* Directives with root rule type MONITOR */
    if (plugin->type == SIM_PLUGIN_TYPE_MONITOR)
	  {      
	    GList *directives = NULL;
  	  g_mutex_lock (ossim.mutex_directives);
	    directives = sim_container_get_directives_ul (ossim.container);
  	  while (directives)
	    {
	      SimDirective *directive = (SimDirective *) directives->data;
	      SimRule *rule = sim_directive_get_root_rule (directive);

	      if (sim_rule_get_plugin_id (rule) == command->data.session_append_plugin.id)
	    	{
					cmd = sim_command_new_from_rule (rule);
				  sim_session_write (session, cmd);
				  g_object_unref (cmd);
				}

	      directives = directives->next;
		  }
		  g_mutex_unlock (ossim.mutex_directives);
		}
  }
  else
  {/*
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
		*/
  }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_session_remove_plugin (SimSession  *session,
				       SimCommand  *command)
{
  SimCommand  *cmd;
  SimPlugin   *plugin = NULL;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  plugin = sim_container_get_plugin_by_id (ossim.container, command->data.session_remove_plugin.id);
  if (plugin)
    {
      session->_priv->plugins = g_list_remove (session->_priv->plugins, plugin);

      cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
      cmd->id = command->id;

      sim_session_write (session, cmd);
      g_object_unref (cmd);
    }
  else
    {
      cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
      cmd->id = command->id;

      sim_session_write (session, cmd);
      g_object_unref (cmd);
    }
}

/*
 * Send to the session connected (master server or frameworkd) a list with all the sensors connected.
 */
static void
sim_session_cmd_server_get_sensors (SimSession  *session,
																    SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
	gboolean		 for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||	// a little identity check
			sim_session_is_web (session))
	{
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensors Inside");
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.server_get_sensors.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_get_sensors.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensors: %s, %s", sim_server_get_name (server), command->data.server_get_sensors.servername);

		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensors Inside 2");
				if (sim_session_is_sensor (sess))	
				{
				  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SENSOR);
					cmd->id = command->id;	//the original query sim_session_cmd_server_get_sensors has originated an id. This id is needed to know
																	//where to send the answer. I.e. server1/server0->server2->server3. If we're server3, we need to say to
																	//server2 which is the server1 where we want to send data, server0 or server1.
					cmd->data.sensor.host = gnet_inetaddr_get_canonical_name (sess->_priv->ia);
					cmd->data.sensor.state = TRUE;	//FIXME: check this and why is it used. Not filled in sim_command_sensor_scan() by now.
					cmd->data.sensor.servername = g_strdup (sim_server_get_name (server));
						
					sim_session_write (session, cmd);	//write the sensor info in the server master or web session
					g_object_unref (cmd);
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.server_get_sensors.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_set_id (session, command->id);	//when the answer has arrived again here, we need to know to what session must
																											//write data. The id is unique for each session, and the session machine connected
																											//must wait to the answer before send another query. 
																											//Ie. frameworkd->server1->server2. 
																											//       server0/     <----server0 connected also to server1
																											// if frameworkd sends a server-get-sensors command issued to server2, when the
																											// answer from server2 arrives to server1, server1 must know if the answer goes
																											// to server0 or to frameworkd. The session id tells who issued the query, but
																											// if it has issued another message, the id will be changed and this will fail.
																											// FIXME?: if we don't want to wait to send another query, the frameworkd
																											// can send with each command his name (not implemented in server), so its uniq.
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}

/*
 * Receives from a children server the sensors connected to it, or from other
 * children server (this->children->children i.e.)  down in the architecture.
 *
 * NOTE: this is a bit different from other msgs. This message is originated
 * thanks to a query from this server (originated in a master server or a
 * frmaeworkd). And the query usually will be redirected up.
 */
static void
sim_session_cmd_sensor (SimSession  *session,
											  SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
	gboolean		 for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_children_server (session))
	{
    SimServer *server = session->_priv->server;
		
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_sensor: %s, %s", sim_server_get_name (server), command->data.sensor.servername);

		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (sim_session_is_master_server (sess) ||
					sim_session_is_web (sess))	
			{
				if (sim_session_get_id (sess) == command->id ) //send data only to the machine that asked for it.
					sim_session_write (sess, command);	//write the sensor info in the server master or web session
			}
			list = list->next;
		}
		
	  g_list_free (list);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}


/*
 * Send to the session connected (master server or frameworkd) a list with the name of all the children servers connected.
 */
static void
sim_session_cmd_server_get_servers (SimSession  *session,
																    SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
	gboolean		 for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_servers Inside");
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.server_get_servers.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_get_servers.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_servers: %s, %s", sim_server_get_name (server), command->data.server_get_servers.servername);

		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_servers Inside 2");
				if (sim_session_is_children_server (sess))	
				{
				  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SERVER);
					cmd->id = command->id;	//see sim_session_cmd_server_get_sensors() to understand this.
					cmd->data.server.host = gnet_inetaddr_get_canonical_name (sess->_priv->ia);
					cmd->data.server.servername = g_strdup (sim_session_get_hostname (sess));
						
					sim_session_write (session, cmd);	//write the server info in the server master or web session
					g_object_unref (cmd);
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.server_get_servers.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_set_id (session, command->id);	//see sim_session_cmd_server_get_sensors() to understand this.
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}

/*
 * Receives from a children server the servers connected to it, or from other
 * children server (this->children->children i.e.)  down in the architecture.
 *
 * NOTE: this is a bit different from other msgs. This message is originated
 * thanks to a query from this server (originated in a master server or a
 * frmaeworkd). And the query usually will be redirected up.
 */
static void
sim_session_cmd_server (SimSession  *session,
											  SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
	gboolean		 for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_children_server (session))
	{
    SimServer *server = session->_priv->server;
		
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server: %s, %s", sim_server_get_name (server), command->data.server.servername);

		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (sim_session_is_master_server (sess) ||
					sim_session_is_web (sess))	
			{
				if (sim_session_get_id (sess) == command->id ) //send data only to the machine that asked for it.
					sim_session_write (sess, command);	//write the server info in the server master or web session
			}
			list = list->next;
		}
		
	  g_list_free (list);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}


/*
 * Send to frameworkd or to a master server the plugins from a specific sensor
 *
 * The state of the plugins, and if they are enabled or not, are "injected" to
 * the server each watchdog.interval seconds with the command SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED
 * or SIM_COMMAND_TYPE_PLUGIN_ENABLED.
 * 
 */
static void
sim_session_cmd_server_get_sensor_plugins (SimSession  *session,
																				   SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
  GList       *plugin_states;
	gboolean 		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.server_get_sensor_plugins.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_get_sensor_plugins.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensor_plugins: %s, %s", sim_server_get_name (server), command->data.server_get_sensor_plugins.servername);

		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
		
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensor_plugins: Session : %x", sess);
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
		      plugin_states = sess->_priv->plugin_states;
		      while (plugin_states)
		      {
    	      SimPluginState  *plugin_state = (SimPluginState *) plugin_states->data;
      		  SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);

		        cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SENSOR_PLUGIN);
    		    cmd->data.sensor_plugin.plugin_id = sim_plugin_get_id (plugin);
		        cmd->data.sensor_plugin.sensor = gnet_inetaddr_get_canonical_name (sess->_priv->ia); //if this is not defined
    		    cmd->data.sensor_plugin.state = sim_plugin_state_get_state (plugin_state);
        		cmd->data.sensor_plugin.enabled = sim_plugin_state_get_enabled (plugin_state);

						sim_session_write (session, cmd);	//write the sensor info in the server master or web session
        		g_object_unref (cmd);

		        plugin_states = plugin_states->next;
      		}
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.server_get_sensor_plugins.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		    
		}
		
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}

}

/*
 * tell to a specific server what should be done with the events that it receives
 *
 */
static void
sim_session_cmd_server_set_data_role (SimSession  *session,
                                    	SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
  GList       *sessions;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session)) //check if the remote server has rights to send data to this server
	{	
		SimServer *server = session->_priv->server;
	  
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_set_data_role: servername: %s; set to server: %s", sim_server_get_name (server), command->data.server_set_data_role.servername);
		
		//Check if the command is regarding this server to get the data and store it in memory & database
		if (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_set_data_role.servername))
		{
			sim_server_set_data_role (server, command);	

		}
		else
		{
			//send the data to other servers down in the architecture
		  list = sim_server_get_sessions (session->_priv->server);
			while (list)
			{
      	SimSession *sess = (SimSession *) list->data;

	      gboolean is_server = sim_session_is_children_server (sess);
	
  	    if (is_server)
    	  {
					gchar *hostname = sim_session_get_hostname (sess);
					if (!g_ascii_strcasecmp (hostname, command->data.server_set_data_role.servername))
					{

	  	    	cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE);

						cmd->data.server_set_data_role.servername = g_strdup (command->data.server_set_data_role.servername);
						cmd->data.server_set_data_role.store = command->data.server_set_data_role.store;
						cmd->data.server_set_data_role.correlate = command->data.server_set_data_role.correlate;
						cmd->data.server_set_data_role.cross_correlate = command->data.server_set_data_role.cross_correlate;
						cmd->data.server_set_data_role.qualify = command->data.server_set_data_role.qualify;
						cmd->data.server_set_data_role.resend_alarm = command->data.server_set_data_role.resend_alarm;
						cmd->data.server_set_data_role.resend_event = command->data.server_set_data_role.resend_event;

					  sim_session_write (sess, cmd);
						g_object_unref (cmd);
						break; //just one server per message plz...
					}
				}
				
				list = list->next;

			}
	    g_list_free (list); //FIXME: check this and all other functions so session list are returned, not copied. Add mutexes to sessions.
		
		}

	}
	else
	{
		GInetAddr *ia;
		ia = sim_session_get_ia (session);
	  gchar *ip_temp = gnet_inetaddr_get_canonical_name (ia);
		g_message ("Error: Warning, %s is trying to send server role without rights!", ip_temp);
		g_free (ip_temp);
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	

	}
}

/*
 * Tell to a sensor that it must start a specific plugin
 */
static void
sim_session_cmd_sensor_plugin_start (SimSession  *session,
																     SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
  GInetAddr   *ia;
	gboolean 		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_start.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_start.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_sensor_plugin_start: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_start.servername);

  	ia = gnet_inetaddr_new_nonblock (command->data.sensor_plugin_start.sensor, 0); //FIXME: Remember to check this as soon as event arrive!!

		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
	  	  	if (gnet_inetaddr_noport_equal (sess->_priv->ia, ia))	//FIXME:when agent support send names, this should be changed with sensor name
					{
		//				cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_START); //this is the command isssued TO sensor
						//Now we take the data from the command issued from web (in
						//cmd->data.sensor_plugin_start struct) and we copy it to resend it to the sensor in
						//cmd->data.plugin_start struct)
			//		  cmd->data.plugin_start.plugin_id = command->data.sensor_plugin_start.plugin_id;						
						sim_session_write (sess, command); //	we pass the same command we received so we can extract the query directly.
				//		g_object_unref (cmd);
						gnet_inetaddr_unref (ia);
					}
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_start.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

static void
sim_session_cmd_sensor_plugin_stop (SimSession  *session,
																    SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
  GInetAddr   *ia;
	gboolean 		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensors: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_stop.servername);
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_stop.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_stop.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					

  	ia = gnet_inetaddr_new_nonblock (command->data.sensor_plugin_stop.sensor, 0);
		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_server_get_sensors Inside 2");
				if (sim_session_is_sensor (sess))	
				{
		      if (gnet_inetaddr_noport_equal (sess->_priv->ia, ia))
    		  {
//		        cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_STOP);
//    		    cmd->data.plugin_stop.plugin_id = command->data.sensor_plugin_stop.plugin_id;
        		sim_session_write (sess, command);
//		        g_object_unref (cmd);
						gnet_inetaddr_unref (ia);
    		  }
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_stop.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 * This command can arrive from the web or a master server. It says that a
 * specific plugin must be enabled in a specific sensor.
 */
static void
sim_session_cmd_sensor_plugin_enable (SimSession  *session,
				     SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
  GInetAddr   *ia;
	gboolean 		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_sensor_plugin_enable: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_enable.servername);
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_enable.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_enable.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;					

  	ia = gnet_inetaddr_new_nonblock (command->data.sensor_plugin_enable.sensor, 0);
		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
 			  	if (gnet_inetaddr_noport_equal (sess->_priv->ia, ia))
					{
//					  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_ENABLED);
//						cmd->data.plugin_enabled.plugin_id = command->data.sensor_plugin_enabled.plugin_id;
					  sim_session_write (sess, command);
//					  g_object_unref (cmd);
						gnet_inetaddr_unref (ia);
    		  }
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_enable.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

static void
sim_session_cmd_sensor_plugin_disable (SimSession  *session,
					SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list;
  GInetAddr   *ia;
	gboolean 		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_sensor_plugin_disable: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_disable.servername);
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_disable.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_disable.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;					

  	ia = gnet_inetaddr_new_nonblock (command->data.sensor_plugin_disable.sensor, 0);
		list = sim_server_get_sessions (server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
 			  	if (gnet_inetaddr_noport_equal (sess->_priv->ia, ia))
					{
//		        cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_DISABLED);
 //   		    cmd->data.plugin_disabled.plugin_id = command->data.sensor_plugin_disabled.plugin_id;
					  sim_session_write (sess, command);
//					  g_object_unref (cmd);
						gnet_inetaddr_unref (ia);
    		  }
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_disable.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (list);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 * This info has been sended already to the server in the first message, the
 * "session-append-plugin". But now we need to remember it each certain time.
 * The sensor sends this information each (agent) watchdog.interval seconds, 
 * so the server learn it perodically and never is able to ask for it in a
 * specific message.
 *
 */
static void
sim_session_cmd_plugin_state_started (SimSession  *session,
			      SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *sessions;
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
    gint id = sim_plugin_get_id (plugin);

    if (id == command->data.plugin_state_started.plugin_id)
			sim_plugin_state_set_state (plugin_state, 1);

    list = list->next;
  }
}

static void
sim_session_cmd_plugin_state_unknown (SimSession  *session,
			      SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *sessions;
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
      gint id = sim_plugin_get_id (plugin);

      if (id == command->data.plugin_state_unknown.plugin_id)
	sim_plugin_state_set_state (plugin_state, 3);

      list = list->next;
    }
}



/*
 *
 *
 *
 */
static void
sim_session_cmd_plugin_state_stopped (SimSession  *session,
			      SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *sessions;
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
      gint id = sim_plugin_get_id (plugin);

      if (id == command->data.plugin_state_stopped.plugin_id)
	sim_plugin_state_set_state (plugin_state, 2);

      list = list->next;
    }
}

/*
 *
 * Enabled means that the process is actively sending msgs to the server
 *
 */
static void
sim_session_cmd_plugin_enabled (SimSession  *session,
				SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *sessions;
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
      gint id = sim_plugin_get_id (plugin);

      if (id == command->data.plugin_enabled.plugin_id)
	sim_plugin_state_set_enabled (plugin_state, TRUE);

      list = list->next;
    }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_plugin_disabled (SimSession  *session,
				 SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *sessions;
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
      gint id = sim_plugin_get_id (plugin);

      if (id == command->data.plugin_disabled.plugin_id)
	sim_plugin_state_set_enabled (plugin_state, FALSE);

      list = list->next;
    }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_event (SimSession	*session,
								       SimCommand	*command)
{
  SimPluginSid  *plugin_sid;
  SimEvent      *event;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Inside1");
  event = sim_command_get_event (command); //generates an event from the command received

  if (!event)
    return;

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Inside2");
  if (event->type == SIM_EVENT_TYPE_NONE)
  {
    g_object_unref (event);
    return;
  }

/*
	if (!sim_session_is_children_server (session)) //if this isn't from a children server we should change the priority & reliability with the DB values.
	{
		event->from_sensor = FALSE;
	}
	*/

  sim_container_push_event (ossim.container, event); //push the event in the queue

	GInetAddr *sensor = gnet_inetaddr_new_nonblock (command->data.event.sensor, 0);
	sim_container_set_sensor_event_number (ossim.container, SIM_EVENT_EVENT, sensor);
	gnet_inetaddr_unref (sensor);
		
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_plugins (SimSession  *session,
																SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_plugins.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_plugins.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_plugins: %s, %s", sim_server_get_name (server), command->data.reload_plugins.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
		  sim_container_free_plugins (ossim.container);
		  sim_container_db_load_plugins (ossim.container, ossim.dbossim);
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_plugins.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_sensors (SimSession  *session,
																SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_sensors.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_sensors.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_sensors: %s, %s", sim_server_get_name (server), command->data.reload_sensors.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
		  sim_container_free_sensors (ossim.container);
		  sim_container_db_load_sensors (ossim.container, ossim.dbossim);
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_sensors.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_hosts (SimSession  *session,
												      SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_hosts.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_hosts.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_hosts: %s, %s", sim_server_get_name (server), command->data.reload_hosts.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
  		sim_container_free_hosts (ossim.container);
		  sim_container_db_load_hosts (ossim.container, ossim.dbossim);
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_hosts.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_nets (SimSession  *session,
			     SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_nets.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_nets.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_nets: %s, %s", sim_server_get_name (server), command->data.reload_nets.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
  		sim_container_free_nets (ossim.container);
		  sim_container_db_load_nets (ossim.container, ossim.dbossim);
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_nets.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_policies (SimSession  *session,
																 SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_policies.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_policies.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_policies: %s, %s", sim_server_get_name (server), command->data.reload_policies.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
  		sim_container_free_policies (ossim.container);
			if (sim_database_is_local (ossim.dbossim))
			  sim_container_db_load_policies (ossim.container, ossim.dbossim);
			else 
			{
				//FIXME: this will produce unespected results, as the server is still receiving data and being processed.
				//mutex & blocking will be needed. This happens also with all the sim_session_cmd_reload_*() functions.
				//You should try to avoid to do this at this time; unless you're very lucky (and you aren't) this will crash something.
				//Instead, please re-start the ossim-server
				sim_container_remote_load_element (SIM_DB_ELEMENT_TYPE_POLICIES);
			}
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_policies.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_directives (SimSession  *session,
																   SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_directives.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_directives.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_directives: %s, %s", sim_server_get_name (server), command->data.reload_directives.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
		// We dont nedd to remove them in database. Just replace if needed
//		  sim_container_db_delete_plugin_sid_directive_ul (ossim.container, ossim.dbossim);
		  sim_container_db_delete_backlogs_ul (ossim.container, ossim.dbossim);

		  sim_container_free_backlogs (ossim.container);
		  sim_container_free_directives (ossim.container);
		  sim_container_load_directives_from_file (ossim.container,
																						   ossim.dbossim,
																						   SIM_XML_DIRECTIVE_FILE);
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_directives.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_all (SimSession  *session,
			    SimCommand  *command)
{
  SimCommand  *cmd;
  SimConfig   *config;
	GList				*list;
	gboolean		for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_all.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																									//we will assume that this is the dst server. This should be removed in 
																									//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_all.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_reload_all: %s, %s", sim_server_get_name (server), command->data.reload_all.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
 			config = session->_priv->config;

			sim_container_free_directives (ossim.container);
		  sim_container_free_backlogs (ossim.container);
		  sim_container_free_net_levels (ossim.container);
		  sim_container_free_host_levels (ossim.container);
		  sim_container_free_policies (ossim.container);
		  sim_container_free_nets (ossim.container);
		  sim_container_free_hosts (ossim.container);
		  sim_container_free_sensors (ossim.container);
		  sim_container_free_plugin_sids (ossim.container);
		  sim_container_free_plugins (ossim.container);

    // We dont nedd to remove them in database. Just replace if needed
		// sim_container_db_delete_plugin_sid_directive_ul (ossim.container, ossim.dbossim);
		  sim_container_db_delete_backlogs_ul (ossim.container, ossim.dbossim);

		  sim_container_db_load_plugins (ossim.container, ossim.dbossim);
		  sim_container_db_load_plugin_sids (ossim.container, ossim.dbossim);
		  sim_container_db_load_sensors (ossim.container, ossim.dbossim);
		  sim_container_db_load_hosts (ossim.container, ossim.dbossim);
		  sim_container_db_load_nets (ossim.container, ossim.dbossim);
		  sim_container_db_load_policies (ossim.container, ossim.dbossim);
		  sim_container_db_load_host_levels (ossim.container, ossim.dbossim);
		  sim_container_db_load_net_levels (ossim.container, ossim.dbossim);

		  if ((config->directive.filename) && (g_file_test (config->directive.filename, G_FILE_TEST_EXISTS)))
    		sim_container_load_directives_from_file (ossim.container, ossim.dbossim, config->directive.filename);

		  sim_server_reload (session->_priv->server);

		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_all.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (list);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *	This function stores the following:
 *	Userdata1: OS
 *
 */
void
sim_session_cmd_host_os_event (SimSession  *session,
															SimCommand  *command)
{
  SimConfig   *config;
  SimEvent    *event;
  GInetAddr   *ia=NULL;
  GInetAddr   *sensor=NULL;
  gchar       *os = NULL;
  struct tm    tm;

		
	g_return_if_fail (session);
	g_return_if_fail (SIM_IS_SESSION (session));
	g_return_if_fail (command);
	g_return_if_fail (SIM_IS_COMMAND (command));
	g_return_if_fail (command->data.host_os_event.date);
	g_return_if_fail (command->data.host_os_event.host);
	g_return_if_fail (command->data.host_os_event.os);
	g_return_if_fail (command->data.host_os_event.sensor);
	g_return_if_fail (command->data.host_os_event.interface);
	g_return_if_fail (command->data.host_os_event.plugin_id > 0);
	g_return_if_fail (command->data.host_os_event.plugin_sid > 0);
	
	config = session->_priv->config;

  if (command->data.host_os_event.sensor)
		sensor = gnet_inetaddr_new_nonblock (command->data.host_os_event.sensor, 0);
	if (!sensor)
		return;				
	
	if (ia = gnet_inetaddr_new_nonblock (command->data.host_os_event.host, 0))
	{
	
		os = sim_container_db_get_host_os_ul (ossim.container,
																				 ossim.dbossim,
																				 ia,
																				 sensor);
		event = sim_event_new ();
		
    // We only want first word (OS name)
    if (command->data.host_os_event.os)
    {
      gchar **os_event_split = NULL;
      os_event_split = g_strsplit (command->data.host_os_event.os, " ", 2);
      g_free (command->data.host_os_event.os);
      command->data.host_os_event.os = g_strdup (os_event_split?os_event_split[0]:NULL);
      g_strfreev(os_event_split);
    }

    if (!os) //the new event is inserted into db.
      event->plugin_sid = EVENT_NEW;
    else
    {
      gchar **os_split = NULL;

      // We only want first word (OS name)
      os_split = g_strsplit (os, " ", 2);
      g_free (os);
      os = g_strdup (os_split?os_split[0]:NULL);
      g_strfreev(os_split);

      if (!g_ascii_strcasecmp (os, command->data.host_os_event.os))
        event->plugin_sid = EVENT_SAME;
      else // we insert the event, but it's in database at this moment.
        event->plugin_sid = EVENT_CHANGE;

    }

		event->type = SIM_EVENT_TYPE_DETECTOR;
		event->alarm = FALSE;
		event->protocol=SIM_PROTOCOL_TYPE_HOST_OS_EVENT;
		event->plugin_id = command->data.host_os_event.plugin_id;
	
		event->sensor = g_strdup (command->data.host_os_event.sensor);

		event->interface = g_strdup (command->data.host_os_event.interface);

    if(command->data.host_os_event.date)
      event->time=command->data.host_os_event.date;
    else
      if(command->data.host_os_event.date_str)
        if (strptime (command->data.host_os_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
          event->time =  mktime (&tm);

    if(!event->time)
      event->time = time (NULL);

	  gchar *ip_temp = gnet_inetaddr_get_canonical_name (ia);
		if (ip_temp)
		{
			event->src_ia = ia;
		}
		else
		{
			event->src_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);
		}
		g_free (ip_temp);

    //we want to process only the hosts defined in Policy->hosts or inside a network from policy->networks
	  if ((sim_container_get_host_by_ia(ossim.container, event->src_ia) == NULL) &&
				(sim_container_get_nets_has_ia(ossim.container, event->src_ia) == NULL))
  	  return;
		
		event->dst_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);							

	  event->data = g_strdup_printf ("%s --> %s", (os) ? os : command->data.host_os_event.os,
																							 command->data.host_os_event.os);

  	//this is used to pass the event data to sim-organizer, so it can insert it into database
    event->data_storage = g_new(gchar*, 2);
    event->data_storage[0] = g_strdup((command->data.host_os_event.os) ? command->data.host_os_event.os : "");
  	event->data_storage[1] = NULL;  

		event->buffer = g_strdup (command->buffer); //we need this to resend data to other servers, or to send
                                                //events that matched with policy to frameworkd (future implementation)
																								//
		event->userdata1 = g_strdup (command->data.host_os_event.os); //needed for correlation
																										
		sim_container_push_event (ossim.container, event);
		sim_container_set_sensor_event_number (ossim.container, SIM_EVENT_HOST_OS_EVENT, sensor);
	
		if (os)
		  g_free (os);
		gnet_inetaddr_unref (sensor);
  }
  else
    g_message("Error: Data sent from agent; host OS event wrong src IP %s",command->data.host_os_event.host);

}

/*
 *	This function also stores the following:
 *	Userdata1: MAC
 *	Userdata2: Vendor
 *
 */
static void
sim_session_cmd_host_mac_event (SimSession  *session,
												        SimCommand  *command)
{
  SimConfig   *config;
  SimEvent    *event;
  GInetAddr   *ia=NULL;
  gchar       *mac = NULL;
  gchar       *vendor = NULL;
	gchar				**mac_and_vendor;
  GInetAddr   *sensor;   
  struct tm    tm;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.host_mac_event.date);
  g_return_if_fail (command->data.host_mac_event.host);
  g_return_if_fail (command->data.host_mac_event.mac);
  g_return_if_fail (command->data.host_mac_event.sensor);
  g_return_if_fail (command->data.host_mac_event.vendor); 
  g_return_if_fail (command->data.host_mac_event.interface); 
  g_return_if_fail (command->data.host_mac_event.plugin_id > 0);
  g_return_if_fail (command->data.host_mac_event.plugin_sid > 0);
 
  config = session->_priv->config;

  // Normalize MAC address (usefull for comparaisons)
  gchar *aux = sim_normalize_host_mac (command->data.host_mac_event.mac);
  if (aux == NULL)
    return;
  g_free(command->data.host_mac_event.mac);
  command->data.host_mac_event.mac = aux;	

	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: command->data.host_mac_event.mac: %s",command->data.host_mac_event.mac);
  
	if (command->data.host_mac_event.sensor)
  	sensor = gnet_inetaddr_new_nonblock (command->data.host_mac_event.sensor, 0);
	if (!sensor)
		return;

  if (ia = gnet_inetaddr_new_nonblock (command->data.host_mac_event.host, 0))
  {
    mac_and_vendor = sim_container_db_get_host_mac_ul (ossim.container, //get the mac wich should be the ia mac.
																											 ossim.dbossim,
																											 ia,
																											 sensor);
		mac = mac_and_vendor[0];
		vendor = mac_and_vendor[1];
		
    event = sim_event_new ();
    if (!mac) //if the ia-sensor pair doesn't obtains a mac in the database, inserts the new one.
    {
      event->plugin_sid = EVENT_NEW; 
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: EVENT_NEW");
    }
    else  
    if (!g_ascii_strcasecmp (mac, command->data.host_mac_event.mac)) //the mac IS the same (0 = exact match)
    {
      event->plugin_sid = EVENT_SAME;
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: EVENT_SAME");
    }
    else //the mac is different
    {
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: EVENT_CHANGE");

			event->plugin_sid = EVENT_CHANGE;       
    }

    event->type = SIM_EVENT_TYPE_DETECTOR;
    event->alarm = FALSE;
    event->plugin_id = command->data.host_mac_event.plugin_id;
		event->protocol=SIM_PROTOCOL_TYPE_HOST_ARP_EVENT;

    event->sensor = g_strdup (command->data.host_mac_event.sensor);

    event->interface = g_strdup (command->data.host_mac_event.interface);

    if(command->data.host_mac_event.date)
      event->time=command->data.host_mac_event.date;
    else
      if(command->data.host_mac_event.date_str)
        if (strptime (command->data.host_mac_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
          event->time =  mktime (&tm);

    if(!event->time)
      event->time = time (NULL);

		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: event->time: %d",event->time);

	  gchar *ip_temp = gnet_inetaddr_get_canonical_name (ia);
		if (ip_temp)
	    event->src_ia = ia;
		else
		{
			event->src_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);
    	g_message("Error: Data sent from agent; host MAC event wrong IP %s",command->data.host_mac_event.host);
		}
		g_free(ip_temp);

	  event->dst_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);							
		event->data = g_strdup_printf ("%s|%s --> %s|%s", (mac) ? mac : command->data.host_mac_event.mac,
																											(vendor) ? vendor : "",
																											command->data.host_mac_event.mac,
																											(command->data.host_mac_event.vendor) ? command->data.host_mac_event.vendor : "");
	
  	//this is used to pass the event data to sim-organizer, so it can insert it into database
    event->data_storage = g_new(gchar*, 3);
   	event->data_storage[0] = g_strdup((command->data.host_mac_event.mac) ? command->data.host_mac_event.mac : "");
	  event->data_storage[1] = g_strdup((command->data.host_mac_event.vendor) ? command->data.host_mac_event.vendor : "");
  	event->data_storage[2] = NULL; //this is needed for g_strfreev(). Don't remove. 

	  event->buffer = g_strdup (command->buffer); //we need this to resend data to other servers, or to send
		                                            //events that matched with policy to frameworkd (future implementation)
		
		event->userdata1 = g_strdup (command->data.host_mac_event.mac);	//needed for correlation
		if (command->data.host_mac_event.vendor)
			event->userdata2 = g_strdup (command->data.host_mac_event.vendor);

    sim_container_push_event (ossim.container, event);
		sim_container_set_sensor_event_number (ossim.container, SIM_EVENT_HOST_MAC_EVENT, sensor);
		
		if (mac)
			g_free (mac);
		if (vendor)
			g_free (vendor);	
		if (mac_and_vendor)
			g_free (mac_and_vendor);
    gnet_inetaddr_unref (sensor);
  }
  else
    g_message("Error: Data sent from agent; host MAC event wrong IP %s",command->data.host_mac_event.host);

	if(command->data.host_mac_event.date_str)
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "command->data.host_mac_event.date: %s",command->data.host_mac_event.date_str);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: TYPE: %d",event->plugin_sid);
				
}

/*
 * PADS plugin (or redirect to MAC plugin)
 * This function also stores the following:
 * userdata1: application
 * userdata2: service
 *
 */
static void
sim_session_cmd_host_service_event (SimSession  *session,
																	  SimCommand  *command)
{
  SimConfig		*config;
  SimEvent		*event;
  GInetAddr		*ia=NULL;
  gint				port = 0;
  gint				protocol = 0;
  gchar				*mac = NULL;
  gchar				*vendor = NULL;
  GInetAddr 	*sensor;   
  gchar				*application = NULL;
  gchar				*service = NULL;
  gchar				**application_and_service = NULL;
  struct tm		tm;
  SimCommand  *cmd;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.host_service_event.date);
  g_return_if_fail (command->data.host_service_event.host);
  g_return_if_fail (command->data.host_service_event.service);
  g_return_if_fail (command->data.host_service_event.sensor);
  g_return_if_fail (command->data.host_service_event.interface);
  g_return_if_fail (command->data.host_service_event.application); //application is "version" field in DDBB
  g_return_if_fail (command->data.host_service_event.plugin_id > 0);
  g_return_if_fail (command->data.host_service_event.plugin_sid > 0);

  // We don't use icmp. Maybe useful for a list of active hosts....
  if (command->data.host_service_event.protocol == 1)
    return;
  
  if (ia = gnet_inetaddr_new_nonblock (command->data.host_service_event.host, 0))
  {
    config = session->_priv->config;
			 
    // Check if we've got a mac to call host_mac_event and insert it.
    if (!g_ascii_strcasecmp (command->data.host_service_event.service, "ARP"))
    {			
      //as the pads plugin uses the same variables to store mac changes and services changes, we must normalize it.
      cmd = sim_command_new_from_type(SIM_COMMAND_TYPE_HOST_MAC_EVENT);
      cmd->data.host_mac_event.date = command->data.host_service_event.date;
      cmd->data.host_mac_event.date_str = g_strdup(command->data.host_service_event.date_str);
      cmd->data.host_mac_event.host = g_strdup(command->data.host_service_event.host);
      cmd->data.host_mac_event.mac = g_strdup(command->data.host_service_event.application);
      cmd->data.host_mac_event.sensor = g_strdup(command->data.host_service_event.sensor);
      cmd->data.host_mac_event.interface = g_strdup(command->data.host_service_event.interface);
      cmd->data.host_mac_event.vendor = g_strdup_printf(" "); //FIXME: this will be usefull when pads get patched to know the vendor
      cmd->data.host_mac_event.plugin_id = SIM_EVENT_HOST_MAC_EVENT;
      cmd->data.host_mac_event.plugin_sid = EVENT_UNKNOWN;
  
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event date: %d", cmd->data.host_mac_event.date);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event date_str: %s", cmd->data.host_mac_event.date_str);
      sim_session_cmd_host_mac_event (session, cmd);
    }
    else //ok, this is not a MAC change event, its a service change event
    {
      event = sim_event_new ();
    
			if (command->data.host_service_event.sensor)
				event->sensor = g_strdup (command->data.host_service_event.sensor);
			if (!(sensor = gnet_inetaddr_new_nonblock (event->sensor, 0))) //sanitize
				return;
		
      port = command->data.host_service_event.port;
      protocol = command->data.host_service_event.protocol;
      application_and_service = sim_container_db_get_host_service_ul (ossim.container, ossim.dbossim, ia, port, protocol, sensor);
			
			if (!application_and_service)	//FIXME: check this with a new event. will it work?.
				return;
			
			application = application_and_service[0];
			service = application_and_service[1];			

      if (!application) //first time this service (apache, IIS...) is saw
      {
				event->plugin_sid = EVENT_NEW;
      }
      else
      if (!g_ascii_strcasecmp (application, command->data.host_service_event.application)) //service is the same
      {
				if (!g_ascii_strcasecmp (service, command->data.host_service_event.service))
		       event->plugin_sid = EVENT_SAME;
				else
				   event->plugin_sid = EVENT_CHANGE;				
      }
      else //The service is different
				event->plugin_sid = EVENT_CHANGE;

/*	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event app1: %s", application);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event app2: %s", command->data.host_service_event.application);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event service1: %s", service);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event service2: %s", command->data.host_service_event.service);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_service_event event: %d", event->plugin_sid);
  */
      event->type = SIM_EVENT_TYPE_DETECTOR;
      event->alarm = FALSE;
      event->plugin_id = command->data.host_service_event.plugin_id;
			event->protocol=SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT;
  
	    event->interface = g_strdup (command->data.host_service_event.interface);
      if(command->data.host_service_event.date)
        event->time=command->data.host_service_event.date;
      else
        if(command->data.host_service_event.date_str)
          if (strptime (command->data.host_service_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
            event->time =  mktime (&tm);
      if(!event->time)
          event->time = time (NULL);
 
	    gchar *ip_temp = gnet_inetaddr_get_canonical_name (ia);
			if (ip_temp)
	      event->src_ia = ia;
			else
	    {
  	    event->src_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);
    	  g_message("Error: Data sent from agent; host Service event wrong IP %s",command->data.host_service_event.host);
	    }
			g_free (ip_temp);

		  //we want to process only the hosts defined in Policy->hosts or inside a network from policy->networks
		  if ((sim_container_get_host_by_ia (ossim.container, event->src_ia) == NULL) &&
					(sim_container_get_nets_has_ia (ossim.container, event->src_ia) == NULL))
    		return;
	
	  	event->dst_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);							
      event->data = g_strdup_printf ("%d/%d - %s/%s", port, protocol, command->data.host_service_event.service, (application) ? application: command->data.host_service_event.application );
			
	    //this is used to pass the event data to sim-organizer, so it can insert it into database
  	  event->data_storage = g_new(gchar*, 5);
			event->data_storage[0] = g_strdup_printf ("%d", port); 
			event->data_storage[1] = g_strdup_printf ("%d", protocol); 
    	event->data_storage[2] = g_strdup(command->data.host_service_event.service);
	    event->data_storage[3] = g_strdup( (application) ? application: command->data.host_service_event.application);
    	event->data_storage[4] = NULL;  //this is needed for g_strfreev(). Don't remove.

			event->buffer = g_strdup (command->buffer); //we need this to resend data to other servers, or to send
	                                                //events that matched with policy to frameworkd (future implementation)
			
			event->userdata1 = g_strdup (command->data.host_service_event.application);	//may be needed in correlation
			event->userdata2 = g_strdup (command->data.host_service_event.service);
			
     	sim_container_push_event (ossim.container, event);
			sim_container_set_sensor_event_number (ossim.container, SIM_EVENT_HOST_SERVICE_EVENT, sensor);
			
			if (application)	
	      g_free (application);
			if (service)
				g_free (service);
			if (application_and_service)
				g_free (application_and_service);
      gnet_inetaddr_unref (sensor);
    }	
  }
	else
    g_message("Error: Data sent from agent; host MAC or OS event wrong IP %s",command->data.host_service_event.host);


}

/*
 *
 * HIDS
 *
 */
static void
sim_session_cmd_host_ids_event (SimSession  *session,
															  SimCommand  *command)
{
  SimConfig	*config;
  SimEvent	*event;
  GInetAddr	*ia=NULL;
  GInetAddr	*ia_temp=NULL;
  GInetAddr	*sensor;
  struct tm	tm;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.host_ids_event.date);
  g_return_if_fail (command->data.host_ids_event.host);
  g_return_if_fail (command->data.host_ids_event.hostname);
  g_return_if_fail (command->data.host_ids_event.event_type);
  g_return_if_fail (command->data.host_ids_event.target);
  g_return_if_fail (command->data.host_ids_event.what);
  g_return_if_fail (command->data.host_ids_event.extra_data);
  g_return_if_fail (command->data.host_ids_event.sensor);
  g_return_if_fail (command->data.host_ids_event.plugin_id > 0);
  g_return_if_fail (command->data.host_ids_event.plugin_sid > 0);
  g_return_if_fail (command->data.host_ids_event.log);

  if (ia = gnet_inetaddr_new_nonblock (command->data.host_ids_event.host, 0))
  {
    config = session->_priv->config;

    event = sim_event_new ();
    event->type = SIM_EVENT_TYPE_DETECTOR;
    event->alarm = FALSE;
  
    if (command->data.host_ids_event.sensor)
			event->sensor = g_strdup (command->data.host_ids_event.sensor);
	  if (!(ia_temp = gnet_inetaddr_new_nonblock (event->sensor, 0))) //sanitize
		  return;
		else
			gnet_inetaddr_unref (ia_temp);
		
    if(command->data.host_ids_event.date)
      event->time=command->data.host_ids_event.date;
    else
      if(command->data.host_ids_event.date_str)
        if (strptime (command->data.host_ids_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
          event->time =  mktime (&tm);

    if(!event->time)
        event->time = time (NULL);
 
    event->plugin_id = command->data.host_ids_event.plugin_id;
    event->plugin_sid = command->data.host_ids_event.plugin_sid;

	  gchar *ip_temp = gnet_inetaddr_get_canonical_name (ia);
    if (ip_temp)
      event->src_ia = ia;
    else
    {
      event->src_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);
      g_message("Error: Data sent from agent; host Service event wrong IP %s",command->data.host_ids_event.host);
    }
		g_free (ip_temp);

	  event->dst_ia = gnet_inetaddr_new_nonblock ("0.0.0.0", 0);							
		event->interface = g_strdup("unknown");
  
    event->data = g_strdup(command->data.host_ids_event.log);

		//this is used to pass the event data to sim-organizer, so it can insert it into database
		event->data_storage = g_new(gchar*, 6);
		event->data_storage[0] = g_strdup(command->data.host_ids_event.hostname);
		event->data_storage[1] = g_strdup(command->data.host_ids_event.event_type);
		event->data_storage[2] = g_strdup(command->data.host_ids_event.target);
		event->data_storage[3] = g_strdup(command->data.host_ids_event.what);
		event->data_storage[4] = g_strdup(command->data.host_ids_event.extra_data);
		event->data_storage[5] = NULL;	//this is needed to free this (inside sim_organizer_snort, btw)
		
		event->protocol=SIM_PROTOCOL_TYPE_HOST_IDS_EVENT;
              
//		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_ids_event filename: %s", command->data.host_ids_event.filename);
							
		if (command->data.host_ids_event.filename)
			event->filename = g_strdup (command->data.host_ids_event.filename);
		if (command->data.host_ids_event.username)
			event->username = g_strdup (command->data.host_ids_event.username);
		if (command->data.host_ids_event.password)
			event->password = g_strdup (command->data.host_ids_event.password);
		if (command->data.host_ids_event.userdata1)
			event->userdata1 = g_strdup (command->data.host_ids_event.userdata1);
		if (command->data.host_ids_event.userdata2)
			event->userdata2 = g_strdup (command->data.host_ids_event.userdata2);
		if (command->data.host_ids_event.userdata3)
			event->userdata3 = g_strdup (command->data.host_ids_event.userdata3);
		if (command->data.host_ids_event.userdata4)
			event->userdata4 = g_strdup (command->data.host_ids_event.userdata4);
		if (command->data.host_ids_event.userdata5)
			event->userdata5 = g_strdup (command->data.host_ids_event.userdata5);
		if (command->data.host_ids_event.userdata6)
			event->userdata6 = g_strdup (command->data.host_ids_event.userdata6);
		if (command->data.host_ids_event.userdata7)
			event->userdata7 = g_strdup (command->data.host_ids_event.userdata7);
		if (command->data.host_ids_event.userdata8)
			event->userdata8 = g_strdup (command->data.host_ids_event.userdata8);
		if (command->data.host_ids_event.userdata9)
			event->userdata9 = g_strdup (command->data.host_ids_event.userdata9);

		event->buffer = g_strdup (command->buffer);	//we need this to resend data to other servers, or to send
																								//events that matched with policy to frameworkd (future implementation)
																							 
    sim_container_push_event (ossim.container, event);
    sim_container_set_sensor_event_number (ossim.container, SIM_EVENT_HOST_IDS_EVENT, sensor);
		
  }
  else
    g_message("Error: Data sent from agent; error from host ids event, IP: %s",command->data.host_ids_event.host);
}

/*
 * This function is used when arrives a msg from a children server requesting for data from DB.
 * Here we will generate a msg that will be sent to the children server, so it can work without DB.
 */
void
sim_session_cmd_database_query (SimSession  *session,
																SimCommand  *command)
{
	GdaDataModel *dm;
  SimCommand  *cmd;
	GList				*list, *rservers, *list2, *list_targets;
	gchar *aux = NULL, *s = NULL, *s2 = NULL;
	gint n;
  gboolean	 	for_this_server = TRUE;
	gboolean		found = FALSE; //variable used to check server's name
	SimRole *role;

	guint	base64_len;
	gchar base64_stored [BUFFER_SIZE]; //stores the data needed to be sent in base64.
	memset (base64_stored, 0, BUFFER_SIZE);

	gchar				*blank = g_strdup_printf (" ");	//used to fill places where some string fields doesn't exists to be sure that 
																							//always there are a string.

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_children_server (session) || sim_session_is_sensor (session))
	{
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_query");
    SimServer *server = session->_priv->server;

		rservers = ossim.config->rservers;
		SimConfigRServer *rserver;
    while (rservers)
    {
	    rserver = (SimConfigRServer*) list->data;
			if (rserver->primary)	//If there are some primary server, this can't be the server that has to answer to the message.
			{
				for_this_server = FALSE;
				break;
			}
			rservers = rservers->next;
		}
				
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_query: %s, %s", sim_server_get_name (server), command->data.database_query.servername);
		
		if (for_this_server)	//execute the command in this server. 
	  {
			//as this is a query, we have to construct an answer to send the response.
			//FIXME: the three definitions below appears in all cases, test if any problem appears if its put outside switch.
			cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_DATABASE_ANSWER);
			cmd->data.database_answer.database_element_type = command->data.database_query.database_element_type;
			cmd->data.database_answer.servername = g_strdup (command->data.database_query.servername);
			switch (command->data.database_query.database_element_type)
			{
				//I don't want to use the GScanner here. As this will be a lot of info to send, I think may be better to use
				//static fields in static places.
				case SIM_DB_ELEMENT_TYPE_PLUGINS:
							if (sim_session_is_sensor (session)) return; //the sensor must able to load only the Policy
							list = sim_container_get_plugins (ossim.container);
							while (list)
							{
								SimPlugin *plugin = (SimPlugin *) list->data;
							
								gchar	*plugin_name = sim_plugin_get_name (plugin);
								gchar	*plugin_description = sim_plugin_get_description (plugin);

								//May be that in the description appears some strange characters so we convert it to BASE64 before send it over the network.
								sim_base64_encode (	plugin_description,
																		strlen(plugin_description),
																		base64_stored,
																		BUFFER_SIZE,
																		&base64_len);
	

								//we will use this separation character: |
								//id, type, name, description
								cmd->data.database_answer.answer = g_strdup_printf("%d|%d|%s|%s",	sim_plugin_get_id (plugin), 
																																									plugin->type, 
																																									plugin_name ? plugin_name	: blank,
																																									plugin_description ? base64_stored : blank);
								sim_session_write (session, cmd);				
								g_free (cmd->data.database_answer.answer);
								memset (base64_stored, 0, BUFFER_SIZE);
								list = list->next;
							}
							g_list_free (list);
							break;
				case SIM_DB_ELEMENT_TYPE_PLUGIN_SIDS:
							if (sim_session_is_sensor (session)) return; 
							list = sim_container_get_plugin_sids (ossim.container);
							while (list)
							{
								SimPluginSid *plugin_sid = (SimPluginSid *) list->data;
								gchar	*plugin_sid_name = sim_plugin_sid_get_name (plugin_sid);

								sim_base64_encode (	plugin_sid_name,
																		sim_strnlen(plugin_sid_name, BUFFER_SIZE),
																		base64_stored,
																		BUFFER_SIZE,
																		&base64_len);
	
								//plugin_id, sid, reliability, priority, name.
								cmd->data.database_answer.answer = g_strdup_printf("%d|%d|%d|%d|%s",	sim_plugin_sid_get_plugin_id (plugin_sid), 
																																											sim_plugin_sid_get_sid (plugin_sid), 
																																											sim_plugin_sid_get_reliability (plugin_sid), 
																																											sim_plugin_sid_get_priority (plugin_sid), 
																																											plugin_sid_name? base64_stored : blank); 
								if (!sim_session_write (session, cmd))
									g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: sim_session_cmd_database_query NO Session");
								g_free (cmd->data.database_answer.answer);
								memset (base64_stored, 0, BUFFER_SIZE);
								list = list->next;
							}
							g_list_free (list);
							break;
				case SIM_DB_ELEMENT_TYPE_SENSORS:
							if (sim_session_is_sensor (session)) return; 
              list = sim_container_get_sensors (ossim.container);
              while (list)
              {
                SimSensor *sensor = (SimSensor *) list->data;
								
								GInetAddr	*ia = sim_sensor_get_ia (sensor);
								gchar *ip = gnet_inetaddr_get_canonical_name (ia);

							  //name, ip, port
                cmd->data.database_answer.answer = g_strdup_printf("%s|%s|%d",  sim_sensor_get_name (sensor),
																																							  ip,
																																								sim_sensor_get_port (sensor));
								g_free (ip);
                sim_session_write (session, cmd);
								g_free (cmd->data.database_answer.answer);
                list = list->next;
              }
              g_list_free (list);
							break;
				case SIM_DB_ELEMENT_TYPE_HOSTS:
							if (sim_session_is_sensor (session)) return; 
              list = sim_container_get_hosts (ossim.container);
              while (list)
              {
                SimHost *host = (SimHost *) list->data;
								GInetAddr	*ia = sim_host_get_ia (host);
								gchar *ip = gnet_inetaddr_get_canonical_name (ia);
								
                //name, ip, asset
                cmd->data.database_answer.answer = g_strdup_printf("%s|%s|%d",  sim_host_get_name (host),
																																							  ip,
																																								sim_host_get_asset (host));
								g_free (ip);
                sim_session_write (session, cmd);
								g_free (cmd->data.database_answer.answer);
                list = list->next;
              }
              g_list_free (list);
							break;
				case SIM_DB_ELEMENT_TYPE_NETS:
							if (sim_session_is_sensor (session)) return; 
              list = sim_container_get_nets (ossim.container);
              while (list)
              {
                SimNet *net = (SimNet *) list->data;
								
                //name, ips (string with multiple networks), asset
                cmd->data.database_answer.answer = g_strdup_printf("%s|%s|%d",  sim_net_get_name (net),
																																							  sim_net_get_ips (net),
																																								sim_net_get_asset (net));
                sim_session_write (session, cmd);
								g_free (cmd->data.database_answer.answer);
                list = list->next;
              }
              g_list_free (list);
							break;
				case SIM_DB_ELEMENT_TYPE_POLICIES:
              list = sim_container_get_policies (ossim.container);
              while (list)
              {
                SimPolicy *policy = (SimPolicy *) list->data;
/*
								//first, we check if we have to send this policy to the children server.
								//FIXME: at this time we don't know the entire list of servers down in the architecture, 
								//just the servers directly connected with this one. so this is not possible to do, we have to send all
								//the server data to all the servers
							  list_servers = sim_policy_get_servers (policy);
							  while (list_servers)
							  {
							    gchar *server_name = (gchar *) list_servers->data;
							    if (!g_ascii_strcasecmp (server_name, cmd->data.database_answer.servername) ||
							        !g_ascii_strcasecmp (server_name, SIM_IN_ADDR_ANY_CONST))
							    {
								    found = TRUE;
							      break;
							    }
							    list_servers = list_servers->next;
								}
								if (!found)
								{
									list = list->next;
									continue; //try next policy... 
								}
*/
								//As the sensor is directly connected to the server, we can check if it has some policy
								if (sim_session_is_sensor (session))
								{
									list_targets = sim_policy_get_targets (policy);
									while (list_targets)
									{
										gchar *target_name = (gchar *) list_targets->data;
										if (!g_ascii_strcasecmp (target_name, cmd->data.database_answer.servername) || //to simplify things, we store sensor name in servername variable
												!g_ascii_strcasecmp (target_name, SIM_IN_ADDR_ANY_CONST))
										{
											found = TRUE;
											break;
										}
										list_targets = list_targets->next;
									}
									if (!found)
									{
										list = list->next;
										continue; //try next policy... 
									}
								}

								//There are multiple possible policy answers. It depends on the kind of policy data sended:
								//First, we say the kind of element inside policy that we're going to send. Then, the data itself.
								// -- General Info --
								//id, priority, begin_hour, end_hour, begin_day, end_day
								cmd->data.database_answer.answer = g_strdup_printf("%d|%d|%d|%d|%d|%d|%d", SIM_POLICY_ELEMENT_TYPE_GENERAL,
																																												sim_policy_get_id (policy),
																																												sim_policy_get_priority (policy),
																																												sim_policy_get_begin_hour (policy),
																																												sim_policy_get_end_hour (policy),
																																												sim_policy_get_begin_day (policy),
																																												sim_policy_get_end_day (policy));
                sim_session_write (session, cmd);
								g_free (cmd->data.database_answer.answer);
								cmd->data.database_answer.answer = NULL;

								// --	Policy role --
								// correlate, cross_correlate, store, qualify, resend_event, resend_alarm
								SimRole	*role = sim_policy_get_role (policy);
								if (role)
								{
									cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%d|%d|%d|%d|%d|%d", SIM_POLICY_ELEMENT_TYPE_ROLE,
																																													sim_policy_get_id (policy),
																																													role->correlate,
																																													role->cross_correlate,
																																													role->store,
																																													role->qualify,
																																													role->resend_event,
																																													role->resend_alarm);
	                sim_session_write (session, cmd);
									g_free (cmd->data.database_answer.answer);
								}
								cmd->data.database_answer.answer = NULL;

								// -- Src: GList SimInet objects --
								// string sended, ie.: 192.168.1.0/24,192.168.1.1,192.168.6/10
								GList *src= sim_policy_get_src (policy);
								s = NULL;
								while (src)
								{
									SimInet *HostOrNet = (SimInet *) src->data;
									aux = sim_inet_cidr_ntop (HostOrNet);
									s2 = s;
									if (src->next == NULL)
										s = g_strdup_printf ("%s%s",s ? s : "", aux);	
									else
										s = g_strdup_printf ("%s%s%s",s ? s : "", aux, SIM_DELIMITER_LIST); //if there are more src's we have to separate it with a ","

									g_free (s2);	
									g_free (aux);	
									src = src->next;
								}
								if (s)
								{
									cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%s", SIM_POLICY_ELEMENT_TYPE_SRC, 
																																									sim_policy_get_id (policy),
																																									s);
	                sim_session_write (session, cmd);
									g_free (cmd->data.database_answer.answer);
									g_free (s);
								}
								cmd->data.database_answer.answer = NULL;

								// -- Dst: GList SimInet objects --
								// string sended, ie.: 192.168.1.0/24,192.168.1.1,192.168.6/10
								GList *dst = sim_policy_get_dst (policy);
								s = NULL;
								while (dst)
								{
									SimInet *HostOrNet = (SimInet *) dst->data;
									aux = sim_inet_cidr_ntop (HostOrNet);
									s2 = s;

									if (dst->next == NULL)
										s = g_strdup_printf ("%s%s",s ? s : "", aux);
									else
										s = g_strdup_printf ("%s%s%s",s ? s : "", aux, SIM_DELIMITER_LIST);

									g_free (s2);	
									g_free (aux);	
									dst = dst->next;
								}
								if (s)
								{
									cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%s", SIM_POLICY_ELEMENT_TYPE_DST,
																																									sim_policy_get_id (policy),
																																									s);
	                sim_session_write (session, cmd);
									g_free (cmd->data.database_answer.answer);
									g_free (s);
								}
								cmd->data.database_answer.answer = NULL;

								// -- Port: GList SimPortProtocol objects --
								// port-protocol ie string:  110-6,53-17  ---> (equal to 110-TCP, 53-UDP)
								list2 = sim_policy_get_ports (policy);
								s = NULL;
								while (list2)
								{
									SimPortProtocol *pp = (SimPortProtocol *) list2->data;
									aux = g_strdup_printf ("%d%s%d", pp->port, SIM_DELIMITER_RANGE, pp->protocol);
									s2 = s;
									
									if (list2->next == NULL)
										s = g_strdup_printf ("%s%s",s ? s : "", aux);
									else	
										s = g_strdup_printf ("%s%s%s",s ? s : "", aux, SIM_DELIMITER_LIST);//if next not null, we'll need a ","

									g_free (s2);	
									g_free (aux);	
									list2 = list2->next;
								}
								if (s)
								{
									cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%s", SIM_POLICY_ELEMENT_TYPE_PORTS,
																																									sim_policy_get_id (policy),
																																									s);
	                sim_session_write (session, cmd);
									g_free (cmd->data.database_answer.answer);
									g_free (s);
								}
								cmd->data.database_answer.answer = NULL;

								// -- Sensors: GList gchar* --
								// sensor; string sended ie: 192.138.1.1,3.3.3.3,192.168.0.2
								list2 = sim_policy_get_sensors (policy);
								s = NULL;
								while (list2)
								{
									gchar *sensor = (gchar *) list2->data; //each sensor
									s2 = s;
									
									if (list2->next == NULL)
										s = g_strdup_printf ("%s%s",s ? s : "", sensor);
									else	
										s = g_strdup_printf ("%s%s%s",s ? s : "", sensor, SIM_DELIMITER_LIST);//if next not null, we'll need a ","

									g_free (s2);	
									list2 = list2->next;
								}
								if (s)
								{
									cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%s", SIM_POLICY_ELEMENT_TYPE_SENSORS,
																																									sim_policy_get_id (policy),
																																									s);
	                sim_session_write (session, cmd);
									g_free (cmd->data.database_answer.answer);
									g_free (s);
								}
								cmd->data.database_answer.answer = NULL;

								// -- Plugin groups: GList Plugin_PluginSid objects --		
								// multiple strings like:						
								// plugin-plugin_sid list ie string:  1001-100,101,102,103  ---> (1001 plugin_id, and 100, 101, 102, 103 plugin_sid)
								list2 = sim_policy_get_plugin_groups (policy);
								while (list2)
								{
									s = NULL;
									Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list2->data;

									aux = g_strdup_printf ("%d%s", plugin_group->plugin_id, SIM_DELIMITER_RANGE);
									GList	*sids = plugin_group->plugin_sid;
									while (sids)
									{
						        gint *aux_plugin_sid = (gint *) sids->data;

										s2 = s;
										if (sids->next == NULL)
											s = g_strdup_printf ("%s%d",s ? s : "", *aux_plugin_sid);
										else	
											s = g_strdup_printf ("%s%d%s",s ? s : "", *aux_plugin_sid, SIM_DELIMITER_LIST);//if next not null, we'll need a ","
										
										sids = sids->next;
										g_free (s2);	
									}
									//there will be multiple msgs, one for each plugin_id.
									if (s)
									{
			              cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%s%s", SIM_POLICY_ELEMENT_TYPE_PLUGIN_GROUPS,
							                                                                        sim_policy_get_id (policy),
																																											aux, //plugin_id- (ie. "1001-")
						                                                                          s);  //plugin_sid's 
										sim_session_write (session, cmd);
										g_free (cmd->data.database_answer.answer);
										g_free (aux);	
										g_free (s);
									}
									cmd->data.database_answer.answer = NULL;
									list2 = list2->next;
								}

								// -- Targets: GList gchar* --
								// target; string sended ie: serverA,happy_server,sensor_dmz
								list2 = sim_policy_get_targets (policy);
								s = NULL;
								while (list2)
								{
									gchar *target_aux = (gchar *) list2->data; //each server
									s2 = s;
									
									if (list2->next == NULL)
										s = g_strdup_printf ("%s%s",s ? s : "", target_aux);
									else	
										s = g_strdup_printf ("%s%s%s",s ? s : "", target_aux, SIM_DELIMITER_LIST);//if next not null, we'll need a ","

									g_free (s2);	
									list2 = list2->next;
								}
								if (s)
								{
									cmd->data.database_answer.answer = g_strdup_printf ("%d|%d|%s", SIM_POLICY_ELEMENT_TYPE_TARGETS,
																																									sim_policy_get_id (policy),
																																									s);
	                sim_session_write (session, cmd);
									g_free (cmd->data.database_answer.answer);
									g_free (s); 
								}
								cmd->data.database_answer.answer = NULL;



                list = list->next;
              }
              g_list_free (list);

							break;
				case SIM_DB_ELEMENT_TYPE_HOST_LEVELS:
							if (sim_session_is_sensor (session)) return; 
              list = sim_container_get_host_levels (ossim.container);
              while (list)
              {
                SimHostLevel *host_level = (SimHostLevel *) list->data;
								
								GInetAddr	*ia = sim_host_level_get_ia (host_level);
								gchar *ip = gnet_inetaddr_get_canonical_name (ia);
								
                //ip, c, a
                cmd->data.database_answer.answer = g_strdup_printf("%s|%f|%f",  ip,
																																							  sim_host_level_get_c (host_level),
																																							  sim_host_level_get_a (host_level));
								g_free (ip);
                sim_session_write (session, cmd);
								g_free (cmd->data.database_answer.answer);
                list = list->next;
              }
              g_list_free (list);

							break;
				case SIM_DB_ELEMENT_TYPE_NET_LEVELS:
							if (sim_session_is_sensor (session)) return; 
              list = sim_container_get_net_levels (ossim.container);
              while (list)
              {
                SimNetLevel *net_level = (SimNetLevel *) list->data;
								
                //name, c, a
                cmd->data.database_answer.answer = g_strdup_printf("%s|%f|%f",  sim_net_level_get_name (net_level),
																																							  sim_net_level_get_c (net_level),
																																							  sim_net_level_get_a (net_level));
                sim_session_write (session, cmd);
								g_free (cmd->data.database_answer.answer);
                list = list->next;
              }
              g_list_free (list);
							break;

				case SIM_DB_ELEMENT_TYPE_SERVER_ROLE:
							if (sim_session_is_sensor (session)) return; 
						  role = sim_server_get_role (ossim.server);
							// correlate, cross_correlate, store, qualify, resend_event, resend_alarm
              cmd->data.database_answer.answer = g_strdup_printf("%d|%d|%d|%d|%d|%d", role->correlate,
																																									role->cross_correlate,
																																									role->store,
																																									role->qualify,
																																									role->resend_event,
																																									role->resend_alarm);
              sim_session_write (session, cmd);
							g_free (cmd->data.database_answer.answer);
							break;

				case SIM_DB_ELEMENT_TYPE_LOAD_COMPLETE:	//Not a DB type. We only have to send a simple msg to children server so it knows
																								// that we have ended the data loading, so it can start to work.
              sim_session_write (session, cmd);
							break;

			}
			g_object_unref (cmd);
		}
		else	//resend the command buffer _only_ to the primary master server. We have to check that exists a session between us and it.
		{
			list2 = sim_server_get_sessions (server);
		  while (list2)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list2->data;
				if (sim_session_is_master_server (sess) && 
						!g_ascii_strcasecmp (rserver->name, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
					break;
				}
				list2 = list2->next;
			}
	  	g_list_free (list2);
		}
		
		//Here we don't need to send an OK message to the children server, as we sent the response to the query done.

	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
	
}

/*
 *
 */
void
sim_session_cmd_database_answer (SimSession  *session,
																SimCommand  *command)
{
	GdaDataModel	*dm;
  SimCommand		*cmd;
	GList					*list, *list2;
	gint					n,i;
  gchar					**values, **values2, **values3;
	gboolean			for_this_server;
	GInetAddr			*ia;

	//variables to store data
  SimPlugin			*plugin; 
  SimPluginSid	*plugin_sid; 
	SimSensor			*sensor;
	SimHost				*host;
	SimNet				*net;
	SimPolicy			*policy;
	SimHostLevel	*host_level;
	SimNetLevel		*net_level;
	SimRole				*role;

	guint	base64_len;
	gchar base64_stored [BUFFER_SIZE]; //stores the data from the base64 encoding
	memset (base64_stored, 0, BUFFER_SIZE);

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session))
	{
//    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.database_answer.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (ossim.server), command->data.database_answer.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_answer: %s, %s", sim_server_get_name (ossim.server), command->data.database_answer.servername);

		list = sim_server_get_sessions (ossim.server);
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				
				switch (command->data.database_answer.database_element_type)
				{
					g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_answer: string: %s",sim_command_get_string (command));
					//I don't want to use the GScanner here. As this will be a lot of info to send, I think may be better to use
	        //static fields in static places.
		      case SIM_DB_ELEMENT_TYPE_PLUGINS:
                plugin = sim_plugin_new();
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
							
								//NOTE: From here and in this function, this comment will be the sort of elements sended.
								//ie. the following line means that: id == values[0], type == values[1] name==values[2], description==values[3]
								//id, type, name, description
								//
								sim_base64_decode	(values[2], sim_strnlen(values[2], BUFFER_SIZE), base64_stored, &base64_len); //the data comes here in base64, so we decode it

								sim_plugin_set_id						(plugin, atoi (values[0]));
								sim_plugin_set_sim_type			(plugin, atoi (values[1]));
								sim_plugin_set_name					(plugin, g_strdup (base64_stored));
								sim_plugin_set_description	(plugin, g_strdup (values[3]));

								G_LOCK (s_mutex_plugins);
				        sim_container_append_plugin (ossim.container, plugin);
							  G_UNLOCK (s_mutex_plugins);

				        g_strfreev (values);              
								memset (base64_stored, 0, BUFFER_SIZE);
              break;

	        case SIM_DB_ELEMENT_TYPE_PLUGIN_SIDS:
					      plugin_sid = sim_plugin_sid_new();
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
								
								//plugin_id, sid, reliability, priority, name.

								sim_base64_decode	(values[4], sim_strnlen(values[4], BUFFER_SIZE), base64_stored, &base64_len);

								sim_plugin_sid_set_plugin_id		(plugin_sid, atoi (values[0]));
								sim_plugin_sid_set_sid					(plugin_sid, atoi (values[1]));
								sim_plugin_sid_set_reliability	(plugin_sid, atoi (values[2]));
								sim_plugin_sid_set_priority			(plugin_sid, atoi (values[3]));
								sim_plugin_sid_set_name					(plugin_sid, g_strdup (base64_stored));

								G_LOCK (s_mutex_plugin_sids);
				        sim_container_append_plugin_sid (ossim.container, plugin_sid);
							  G_UNLOCK (s_mutex_plugin_sids);

				        g_strfreev (values); 
								memset (base64_stored, 0, BUFFER_SIZE);
		            break;

			    case SIM_DB_ELEMENT_TYPE_SENSORS:
					      sensor = sim_sensor_new();
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
							
								ia = gnet_inetaddr_new_nonblock (values[1], atoi (values[2]));
								
                //name, ip, port
								sim_sensor_set_name		(sensor, values[0]);	//no needed g_strdup()
								sim_sensor_set_ia			(sensor, ia);
								sim_sensor_set_port		(sensor, atoi (values[2]));

								sim_sensor_debug_print(sensor);

								G_LOCK (s_mutex_sensors);
				        sim_container_append_sensor (ossim.container, sensor);
							  G_UNLOCK (s_mutex_sensors);

				        g_strfreev (values);
 
				        break;
	        case SIM_DB_ELEMENT_TYPE_HOSTS:
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
							
								ia = gnet_inetaddr_new_nonblock (values[1], 0);
								
                //name, ip, asset -> name= vlaues[0], ip=values[1], asset=values[2]
					      host = sim_host_new (ia, values[0], atoi (values[2]));

								G_LOCK (s_mutex_hosts);
				        sim_container_append_host (ossim.container, host);
							  G_UNLOCK (s_mutex_hosts);

				        g_strfreev (values);
 
		            break;
			    case SIM_DB_ELEMENT_TYPE_NETS:
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
							
                //name, ips (string with multiple ips) , asset.
					      net = sim_net_new (values[0], values[1], atoi (values[2]));

								G_LOCK (s_mutex_nets);
				        sim_container_append_net (ossim.container, net);
							  G_UNLOCK (s_mutex_nets);

				        g_strfreev (values);

				        break;
					case SIM_DB_ELEMENT_TYPE_POLICIES:
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
								SimPolicy *pol = NULL;
								if ( (!sim_string_is_number (values[0], FALSE)) || (!sim_string_is_number (values[1], FALSE)))
									break;	//check SIM_POLICY_ELEMENT_TYPE_* and policy id as a partial sanity check

								//we obtain the policies to know in what policy must we insert data.
								//Obviously this is not needed for the first msg of all, the SIM_POLICY_ELEMENT_TYPE_GENERAL,
								//but we do this anyway to remove duplicated code inside switch (values[0])
								list2 = sim_container_get_policies (ossim.container);
								g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_answer list2: %x", list2);
								while (list2)	//find the policy that matches with id.
								{
									pol = (SimPolicy *) list2->data;
									g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_answer pol 1: %x", pol);
									if (sim_policy_get_id (pol) == atoi (values[1]))
										break;
									else
										pol = NULL;				//maintain this NULL if there are some error
									list2 = list2->next;
								}
								
								switch (atoi (values[0]))		//In all the policy msgs, the first field is the kind of message.
								{
									case SIM_POLICY_ELEMENT_TYPE_GENERAL:
												// -- General Info --
												//id, priority, begin_hour, end_hour, begin_day, end_day
												policy = sim_policy_new (); //only is a new policy if its the first kind of message (general). This is only my convention.
												sim_policy_set_id (policy, atoi (values[1]));	
												sim_policy_set_priority (policy, atoi (values[2]));	
												sim_policy_set_begin_hour (policy, atoi (values[3]));	
												sim_policy_set_end_hour (policy, atoi (values[4]));	
												sim_policy_set_begin_day (policy, atoi (values[5]));	
												sim_policy_set_end_day (policy, atoi (values[6]));	

												G_LOCK (s_mutex_policies);
												sim_container_append_policy (ossim.container, policy);
												G_UNLOCK (s_mutex_policies);
												g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_answer pol 2: %x", policy);
												break;
									case SIM_POLICY_ELEMENT_TYPE_ROLE:
												// -- Policy role --
												// correlate, cross_correlate, store, qualify, resend_event, resend_alarm
												role = g_new0 (SimRole, 1);

												role->correlate				=	atoi (values[2]);
												role->cross_correlate = atoi (values[3]);
												role->store						= atoi (values[4]);
												role->qualify					= atoi (values[5]);
												role->resend_event		= atoi (values[6]);
												role->resend_alarm		= atoi (values[7]);
												
												sim_policy_set_role (pol, role);
												g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_database_answer pol 3: %x", pol);
												break;

									case SIM_POLICY_ELEMENT_TYPE_SRC:
												values2 = g_strsplit (values[2], SIM_DELIMITER_LIST, 0);	//192.168.1.0/24,192.168.1.1,192.168.6/10
												for (i=0; values2[i] != NULL; i++)
												{					
													SimInet *new_inet	= sim_inet_new (values2[i]);
													sim_policy_append_src (pol, new_inet);
												}
												g_strfreev (values2);
												break;

									case SIM_POLICY_ELEMENT_TYPE_DST:
												values2 = g_strsplit (values[2], SIM_DELIMITER_LIST, 0);
												for (i=0; values2[i] != NULL; i++)
												{					
													SimInet *new_inet	= sim_inet_new (values2[i]);
													sim_policy_append_dst (pol, new_inet);
												}
												g_strfreev (values2);
												break;

									case SIM_POLICY_ELEMENT_TYPE_PORTS:
												values2 = g_strsplit (values[2], SIM_DELIMITER_LIST, 0); //values[2] == 110-6,53-17   i.e. 
												for (i=0; values2[i] != NULL; i++)
												{	
													values3 = g_strsplit (values2[i], SIM_DELIMITER_RANGE, 0);  //values3[i][0] == 110 ; values3[i][1] == 6				
													SimPortProtocol	*pp = sim_port_protocol_new (atoi (values3[0]), atoi (values3[1]));
													sim_policy_append_port (pol, pp);
													g_strfreev (values3);
												}
												g_strfreev (values2);
												break;

									case SIM_POLICY_ELEMENT_TYPE_SENSORS:
												values2 = g_strsplit (values[2], SIM_DELIMITER_LIST, 0); //string values[2] ie: 192.138.1.1,3.3.3.3,192.168.0.2
												for (i=0; values2[i] != NULL; i++)
													sim_policy_append_sensor (pol, g_strdup (values2[i]));

												g_strfreev (values2);
												break;

									case SIM_POLICY_ELEMENT_TYPE_PLUGIN_GROUPS:
												values2 = g_strsplit (values[2], SIM_DELIMITER_RANGE, 0); //string values[2] ie: 1001-100,101,102,103  

												Plugin_PluginSid *plugin_group =  g_new0 (Plugin_PluginSid, 1);
												plugin_group->plugin_id = atoi (values2[0]);								//plugin_id (1001)
												values3 = g_strsplit (values2[1], SIM_DELIMITER_LIST, 0);		//plugin_sids (100,101,102,103...)
												for (i=0; values3[i] != NULL; i++)
												{
													gint *sid = g_new0 (gint, 1);
													*sid = atoi (values3[i]);
													plugin_group->plugin_sid = g_list_append (plugin_group->plugin_sid, sid);			
												}
												sim_policy_append_plugin_group (pol, plugin_group);

												g_strfreev (values3);
												g_strfreev (values2);
												break;
									case SIM_POLICY_ELEMENT_TYPE_TARGETS:
												values2 = g_strsplit (values[2], SIM_DELIMITER_LIST, 0); //string values[2] ie: serverA,sensor_dmz,funny_server
												for (i=0; values2[i] != NULL; i++)
													sim_policy_append_target (pol, g_strdup (values2[i]));

												g_strfreev (values2);
												break;

								}							
								
						    break;
	        case SIM_DB_ELEMENT_TYPE_HOST_LEVELS:
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
							
								ia = gnet_inetaddr_new_nonblock (values[0], 0);

                //ip, c, a
					      host_level = sim_host_level_new (ia, g_ascii_strtod (values[1], NULL), g_ascii_strtod (values[2], NULL));

								G_LOCK (s_mutex_host_levels);
				        sim_container_append_host_level (ossim.container, host_level);
							  G_UNLOCK (s_mutex_host_levels);

				        g_strfreev (values);
								gnet_inetaddr_unref (ia);
		            break;

			    case SIM_DB_ELEMENT_TYPE_NET_LEVELS:
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
							
                //name, c, a
					      net_level = sim_net_level_new (values[0], g_ascii_strtod (values[1], NULL), g_ascii_strtod (values[2], NULL));

								G_LOCK (s_mutex_net_levels);
				        sim_container_append_net_level (ossim.container, net_level);
							  G_UNLOCK (s_mutex_net_levels);

				        g_strfreev (values);
				        break;
					case SIM_DB_ELEMENT_TYPE_SERVER_ROLE://not in container, stored in ossim.server object.
							  values = g_strsplit (command->data.database_answer.answer, SIM_DELIMITER_PIPE, 0);
								SimConfig *config = sim_server_get_config (ossim.server);

								// correlate, cross_correlate, store, qualify, resend_event, resend_alarm
								config->server.role->store 						= atoi (values[0]);
								config->server.role->cross_correlate	= atoi (values[1]);
								config->server.role->correlate				= atoi (values[2]);
							  config->server.role->qualify 					= atoi (values[3]);
							  config->server.role->resend_event 		= atoi (values[4]);
							  config->server.role->resend_alarm 		= atoi (values[5]);
								break;
			    case SIM_DB_ELEMENT_TYPE_LOAD_COMPLETE:
								sim_container_set_rload_complete (ossim.container);
								break;
	      }	
	

			}
			else	//resend the command buffer to ALL the children servers. No matter if the name doesn't matches, so we can have
			{			//more than three levels (in fact, n levels).
				if (sim_session_is_children_server (sess))
						//&& !g_ascii_strcasecmp (command->data.database_answer.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (list);
			
/*	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);*/
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
	
}



/*
 *
 *
 *
 */
static void
sim_session_cmd_ok (SimSession  *session,
								    SimCommand  *command)
{
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_error (SimSession  *session,
		       SimCommand  *command)
{
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

}



/*
 *
 *
 *
 */
gboolean
sim_session_read (SimSession  *session)
{
  SimCommand  *cmd = NULL;
  SimCommand  *res;
  GIOError     error;
  //gchar        buffer[BUFFER_SIZE];
  gchar        buffer[BUFFER_SIZE];
  gsize		     n;

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  memset(buffer, 0, BUFFER_SIZE);

  while ( (!session->_priv->close) && 
					(error = gnet_io_channel_readline (session->_priv->io, buffer, BUFFER_SIZE, &n)) == G_IO_ERROR_NONE && (n>0) )
  {
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Entering while. Session: %x", session);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: strlen(buffer)=%d; n=%d",strlen(buffer),n);
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Buffer: %s", buffer);

//      g_message("GIOError: %d",error);	 
      
      //sanity checks...
    if (error != G_IO_ERROR_NONE)
    {
		  g_message ("Received error, closing socket: %d: %s", error, g_strerror(error));
	  	return FALSE;
    }
		
		//FIXME: This not a OSSIM fixme, IMHO this is a GLib fixme. If strlen(buffer) > n, gscanner will crash
		//This can be easily reproduced commenting the "if" below, and doing a telnet to the server port, and sending one event. After that, do
		//a CTRL-C, and a quit. Next event will crash the server, and gdb will show:
		//(gdb) bt
		//#0  0xb7d8765e in g_scanner_scope_add_symbol () from /usr/lib/libglib-2.0.so.0
		//#1  0xb7d88a52 in g_scanner_get_next_token () from /usr/lib/libglib-2.0.so.0
		//#2  0x0807e840 in sim_command_scan (command=0x8397980,
		//Also, scanner->buffer is not 0 in the next iteration. If we set it to 0, it still crashes.
		//I'll be very glad is someone has some time to check what's happening) :)
		if (strlen(buffer) != n)
		{
		  g_message ("Received error. Inconsistent data entry, closing socket. Received:%d Buffer lenght: %d: %d: %s", n, strlen(buffer), error, g_strerror(error));
	  	return FALSE;
		}
/*
      if (sim_strnlen(buffer,BUFFER_SIZE) == BUFFER_SIZE) 
      {
        g_message("Error: Data received from the agent > %d, line truncated.");
	return FALSE;
      }
      
      if (sim_strnlen(buffer,BUFFER_SIZE) < n-1 )
      {
         g_message("Error: Data received from the agent has a \"0\" character before newline");
         return FALSE;
      }
  */   
    if (n == 0)
		{
		  g_message ("0 bytes read (closing socket)");
	  	return FALSE;
		}

    if (!buffer)
		{
    	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Buffer NULL");
			return FALSE;
		}

		//FIXME: WHY the F*CK this happens?? strlen(buffer) sometimes is =1!!!
		//g_message("Data received: -%s- Count: %d  n: %d",buffer,sim_strnlen(buffer,BUFFER_SIZE),n);	 
		if (strlen (buffer) <= 2) 
		{
	    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Buffer <= 2 bytes");
			memset(buffer, 0, BUFFER_SIZE);
			continue;
//			return FALSE; 
		}

    cmd = sim_command_new_from_buffer (buffer); //this gets the command and all of the parameters associated.

		if (!cmd)
		{
		  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: error command null");
			continue; //we don't break the connection if the event is strange, we just reject the event
	  	//return FALSE;
		}

    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Command from buffer type:%d ; id=%d",cmd->type,cmd->id);
      
    if (cmd->type == SIM_COMMAND_TYPE_NONE)
		{
	  	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: error command type none");
		  g_object_unref (cmd);
		  return FALSE;
		}

		if (sim_session_get_is_initial (session))		//is this the session started in sim_container_new();?
		{
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: This is a initial session load");
			if (cmd->type == SIM_COMMAND_TYPE_DATABASE_ANSWER)
			{
        sim_session_cmd_database_answer (session, cmd); 
			}
			else
			if	(cmd->type == SIM_COMMAND_TYPE_OK)	// 
			{ 
				g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Mutex lock in OK");
				//this will permit to load data the first time the server gets data from rservers.
				//Take a look at sim-container
				if (session->_priv->fully_stablished == FALSE)	//we only need to do the mutex the first time, when we are not sure that the
					sim_session_set_fully_stablished (session);		//connection is open
				
				g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: Mutex unlock in OK");
			}
			else
			{
				g_message ("Error: someone has tried to connect to the server when it still hasn't loaded everything needed");
        res = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
        res->id = cmd->id;

        sim_session_write (session, res);
        g_object_unref (res);
				return FALSE;
			}
			memset(buffer, 0, BUFFER_SIZE);
			continue; //we only want to listen database answer events.
		}

		//this two variables are used in SIM_COMMAND_TYPE_EVENT
		SimServer	*server = session->_priv->server;
		SimConfig	*config = sim_server_get_config (server);
		gboolean *r = FALSE;
	
		//this messages can arrive from other servers (up in the architecture -a master server-, down in the
		//architecture -a children server-, or at the same level -HA server-), from some sensor (an agent) or from the frameworkd.
    switch (cmd->type)
		{
			case SIM_COMMAND_TYPE_CONNECT:															//from children server / frameworkd / sensor
						sim_session_cmd_connect (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SERVER_GET_SENSORS:										//from frameworkd / master server
						sim_session_cmd_server_get_sensors (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR:																// [from children server]-> To Master server / frameworkd 
						sim_session_cmd_sensor (session, cmd);
						break;	
			case SIM_COMMAND_TYPE_SERVER_GET_SERVERS:										//from frameworkd / master server
						sim_session_cmd_server_get_servers (session, cmd);
						break;	
			case SIM_COMMAND_TYPE_SERVER:																// [from children server]-> To Master server / frameworkd 
						sim_session_cmd_server (session, cmd);
						break;	
			case SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS:						//from frameworkd / master server
						sim_session_cmd_server_get_sensor_plugins (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE:									//from frameworkd / master server
						sim_session_cmd_server_set_data_role (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_START:									//from frameworkd / master server
						sim_session_cmd_sensor_plugin_start (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP:										//from frameworkd / master server
						sim_session_cmd_sensor_plugin_stop (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE:								//from frameworkd / master server
						sim_session_cmd_sensor_plugin_enable (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE:								//from frameworkd / master server
						sim_session_cmd_sensor_plugin_disable (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_PLUGINS:
						sim_session_cmd_reload_plugins (session, cmd);				// from frameworkd / master server
						break;
			case SIM_COMMAND_TYPE_RELOAD_SENSORS:												// from frameworkd / master server
						sim_session_cmd_reload_sensors (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_HOSTS:													// from frameworkd / master server
						sim_session_cmd_reload_hosts (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_NETS:													// from frameworkd / master server
						sim_session_cmd_reload_nets (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_POLICIES:											// from frameworkd / master server
						sim_session_cmd_reload_policies (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_DIRECTIVES:										// from frameworkd / master server
						sim_session_cmd_reload_directives (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_ALL:														// from frameworkd / master server
						sim_session_cmd_reload_all (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN:								//from sensor
						sim_session_cmd_session_append_plugin (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN:								//from sensor
						sim_session_cmd_session_remove_plugin (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED:									//from sensor (just information for the server)
						sim_session_cmd_plugin_state_started (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_STATE_UNKNOWN:									//from sensor
						sim_session_cmd_plugin_state_unknown (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_STATE_STOPPED:									//from sensor
						sim_session_cmd_plugin_state_stopped (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_ENABLED:												//from sensor
						sim_session_cmd_plugin_enabled (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_DISABLED:											//from sensor
						sim_session_cmd_plugin_disabled (session, cmd);
						break;
			case SIM_COMMAND_TYPE_EVENT:																//from sensor / server children
						//if we're just a "redirecter", only send the buffer to other servers. If the server
						//is a redirecter, and also some other thing, this will be done later. This is just to try to accelerate
						//up to the maximum the functionality.
						if ((!config->server.role->correlate) &&
								(!config->server.role->cross_correlate) &&
								(!config->server.role->store) &&
								(!config->server.role->qualify) &&
								(!config->server.role->resend_alarm))
						{
	            g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: DENTRO");
							sim_session_resend_buffer (buffer);
						}
						else		
							sim_session_cmd_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_HOST_OS_EVENT:								        // from sensor / children server
						sim_session_cmd_host_os_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_HOST_MAC_EVENT:												// from sensor / children server
						sim_session_cmd_host_mac_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_HOST_SERVICE_EVENT:										// from sensor / children server
						sim_session_cmd_host_service_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_HOST_IDS_EVENT:												// from sensor / children server
						sim_session_cmd_host_ids_event (session, cmd); 
						break;
			case SIM_COMMAND_TYPE_OK:																		//from *
						sim_session_cmd_ok (session, cmd);
						break;
			case SIM_COMMAND_TYPE_ERROR:																//from *
						sim_session_cmd_error (session, cmd);
						break;
			case SIM_COMMAND_TYPE_DATABASE_QUERY:												// from children server
						sim_session_cmd_database_query (session, cmd); 
						break;							
			case SIM_COMMAND_TYPE_DATABASE_ANSWER:											// from master server
						sim_session_cmd_database_answer (session, cmd); 
						break;							
			case SIM_COMMAND_TYPE_SNORT_EVENT:
					 sim_session_cmd_event (session, cmd);
		   		break;
			case SIM_COMMAND_TYPE_AGENT_DATE:														//from sensor. still it doesn't do nothing
		   		break;
			default:
						g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: error command unknown type");
						res = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
						res->id = cmd->id;

						sim_session_write (session, res);
						g_object_unref (res);
						break;
		}

    g_object_unref (cmd);
		cmd = NULL;

		n=0;
  	memset(buffer, 0, BUFFER_SIZE);
		

	}
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_read: exiting function in session: %x", session);
			
  return TRUE;
}

/*
 * Send the command specified (usually it will be a SIM_COMMAND_TYPE_EVENT or something like that) 
 * to all the master servers (servers UP in the architecture).
 */
void 
sim_session_resend_command (SimSession *session,	//FIXME: is this function deprecated?
														SimCommand	*command)
{
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	SimServer *server = session->_priv->server;

	GList *list = sim_server_get_sessions (server);
	while (list)
	{
		SimSession *session = (SimSession *) list->data;
		if (sim_session_is_master_server (session))
			sim_session_write (session, command);	//FIXME: use another thread ,this will block a lot.

		list = list->next;
	}


}

/*
 * This will resend the buffer specified to master servers.
 */
void 
sim_session_resend_buffer (gchar	*buffer)
{
  g_return_if_fail (buffer != NULL);

	GList *list = sim_server_get_sessions (ossim.server);
	while (list)
	{
		SimSession *session = (SimSession *) list->data;
		if (sim_session_is_master_server (session))
			sim_session_write_from_buffer (session, buffer);

		list = list->next;
	}
	
	g_list_free (list);

}
/*
 * This function may be used to send data to sensors, to other servers, or to the frameworkd (if needed)
 *
 *
 */
gint
sim_session_write (SimSession  *session,
								   SimCommand  *command)
{
  GIOError  error;
  gchar    *str;
  gsize     n;

	g_return_val_if_fail (session != NULL, 0);
  g_return_val_if_fail (SIM_IS_SESSION (session), 0);
  g_return_val_if_fail (session->_priv->io != NULL, 0);

  str = sim_command_get_string (command);
  if (!str)
    return 0;

  // cipher

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_write: %s", str);
	sim_util_block_signal(SIGPIPE);
  error = gnet_io_channel_writen (session->_priv->io, str, strlen(str), &n);
	sim_util_unblock_signal(SIGPIPE);

  g_free (str);

  if  (error != G_IO_ERROR_NONE)
    {
      session->_priv->close = TRUE;
      return 0;
    }

  return n;
}

/*
 * write a specific buffer into a session channel. returns the bytes written.
 * FIXME: Use another thread, this will block.
 */
guint
sim_session_write_from_buffer (SimSession	*session,
																gchar			*buffer)
{
	GIOError	error;
	gsize n; //gsize = unsigned integer 32 bits
	
  g_return_val_if_fail (session != NULL, 0);
  g_return_val_if_fail (SIM_IS_SESSION (session), 0);
  g_return_val_if_fail (session->_priv->io != NULL, 0);
	sim_util_block_signal (SIGPIPE);	
  error = gnet_io_channel_writen (session->_priv->io, buffer, strlen (buffer), &n);
	sim_util_unblock_signal (SIGPIPE); 
		
	if	(error != G_IO_ERROR_NONE)
  {
    session->_priv->close = TRUE;
    return 0;
  }
	
return n; 
}


/*
 *
 *
 *
 */
gboolean
sim_session_has_plugin_type (SimSession     *session,
			     SimPluginType   type)
{
  GList  *list;
  gboolean  found = FALSE;

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);
  
  list = session->_priv->plugins;
  while (list)
    {
      SimPlugin *plugin = (SimPlugin *) list->data;

      if (plugin->type == type)
	{
	  found = TRUE;
	  break;
	}

      list = list->next;
    }

  return found;
}

/*
 *
 *
 *
 */
gboolean
sim_session_has_plugin_id (SimSession     *session,
												   gint            plugin_id)
{
  GList  *list;
  gboolean  found = FALSE;

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);
  
  list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);

    if (sim_plugin_get_id (plugin) == plugin_id)
		{
		  found = TRUE;
	  	break;
		}

    list = list->next;
  }

  return found;
}


/*
 *
 *
 *
 */
void
sim_session_reload (SimSession     *session)
{
  GList  *list;
  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      gint plugin_id = sim_plugin_state_get_plugin_id (plugin_state);

      SimPlugin *plugin = sim_container_get_plugin_by_id (ossim.container, plugin_id);

      sim_plugin_state_set_plugin (plugin_state, plugin);

      list = list->next;
    }
}

/*
 *
 *
 *
 */
SimSensor*
sim_session_get_sensor (SimSession *session)
{
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  return session->_priv->sensor;
}

/*
 * Returns the server associated with this session (this server);
 */
SimServer*
sim_session_get_server (SimSession *session)
{
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  return session->_priv->server;
}


/*
 *Is the session from a sensor ?
 */
gboolean
sim_session_is_sensor (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_SENSOR) 
		return TRUE;

  return FALSE;
}

/*
 * Is the session from a master server? (a server which is "up" in the architecture)
 */
gboolean
sim_session_is_master_server (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_SERVER_UP)
    return TRUE;

  return FALSE;
}

/*
 * Is the session from a children server? (a server which is "down" in the architecture)
 */
gboolean
sim_session_is_children_server (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_SERVER_DOWN)
    return TRUE;

  return FALSE;
}


/*
Is the session from the web ? FIXME: soon this will be from the frameworkd
*/
gboolean
sim_session_is_web (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_WEB)
    return TRUE;

  return FALSE;
}


/*
 *
 *
 *
 */
gboolean
sim_session_is_connected (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  return session->_priv->connect; 
} 

/*
 *
 *
 *
 */
void
sim_session_close (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  
  session->_priv->close = TRUE;
			
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_close: closing session: %x",session);
		
}

/*
 *
 *
 *
 */
gboolean
sim_session_must_close (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  
  return session->_priv->close;
}

/*
 *
 */
void
sim_session_set_is_initial (SimSession *session,
														gboolean tf)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	session->_priv->is_initial = tf;
}

gboolean
sim_session_get_is_initial (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	return session->_priv->is_initial;
}

/*
 * If this function is called, that means that this is a children server without DB.
 * Wait until we sent in the initial session with the primary rserver the "connect" and we receive the "OK" msg,
 * so we know that we are connected and we can ask for things.
 */
void
sim_session_wait_fully_stablished (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	g_mutex_lock (session->_priv->initial_mutex);

	while (!session->_priv->fully_stablished)	//this is set in sim_session_read().
		g_cond_wait (session->_priv->initial_cond, session->_priv->initial_mutex);

	g_mutex_unlock (session->_priv->initial_mutex);

}

/*
 * first session with a primary rserver is ok. This server is the children server; it
 * has sent a "connect" to the primary master server, and the master server answer with ok,
 * so the session is fully stablished.
 */
void
sim_session_set_fully_stablished (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	g_mutex_lock (session->_priv->initial_mutex);
	session->_priv->fully_stablished = TRUE;	
	g_cond_signal(session->_priv->initial_cond);
	g_mutex_unlock (session->_priv->initial_mutex);

}

void
sim_session_set_id (SimSession *session, gint id)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

  session->_priv->id = id; 
} 

gint
sim_session_get_id (SimSession *session)
{
  g_return_val_if_fail (session, -1);
  g_return_val_if_fail (SIM_IS_SESSION (session), -1);

  return session->_priv->id; 
} 




// vim: set tabstop=2 sts=2 noexpandtab:
