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

#include <libgda/libgda.h>

#include "sim-database.h"
#include "os-sim.h"
#include <config.h>

#define PROVIDER_MYSQL   "MySQL"
#define PROVIDER_PGSQL   "PostgreSQL"
#define PROVIDER_ORACLE  "Oracle"
#define PROVIDER_ODBC    "odbc"

extern SimMain    ossim;

gboolean static restarting_mysql = FALSE; //no mutex needed 

enum 
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimDatabasePrivate {
  GStaticRecMutex	*mutex;
  GdaClient       *client;      /* Connection Pool */
  GdaConnection   *conn;        /* Connection */

  gchar           *name;        /* DS Name */
  gchar           *provider  ;  /* Data Source */
  gchar           *dsn;         /* User Name */

  gboolean        local_DB;			//if False: database queries are executed against other ossim server in other machine. 
	gchar						*rserver_name;
};

static gpointer parent_class = NULL;
static gint sim_database_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_database_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_database_impl_finalize (GObject  *gobject)
{
  SimDatabase *database = SIM_DATABASE (gobject);

  if (database->_priv->name)
    g_free (database->_priv->name);
  if (database->_priv->provider)
    g_free (database->_priv->provider);
  if (database->_priv->dsn)
    g_free (database->_priv->dsn);
  if (database->_priv->rserver_name)
    g_free (database->_priv->rserver_name);

  gda_connection_close (database->_priv->conn);
  g_object_unref (database->_priv->client);

  g_static_rec_mutex_free (database->_priv->mutex);

  g_free (database->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_database_class_init (SimDatabaseClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_database_impl_dispose;
  object_class->finalize = sim_database_impl_finalize;
}

static void
sim_database_instance_init (SimDatabase *database)
{
  database->_priv = g_new0 (SimDatabasePrivate, 1);

  database->type = SIM_DATABASE_TYPE_NONE;

  database->_priv->client = NULL;
  database->_priv->conn = NULL;
  database->_priv->name = NULL;
  database->_priv->provider = NULL;
  database->_priv->dsn = NULL;
  database->_priv->local_DB = TRUE;

	database->_priv->mutex = g_new0 (GStaticRecMutex, 1); 
  g_static_rec_mutex_init (database->_priv->mutex);
}

/* Public Methods */

GType
sim_database_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimDatabaseClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_database_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimDatabase),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_database_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimDatabase", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 */
SimDatabase*
sim_database_new (SimConfigDS  *config)
{
  g_return_val_if_fail (config, NULL);

  SimDatabase    *db = NULL;
  GdaError       *error;
  GList          *errors = NULL;
  gint            i;
	
	if (config->local_DB)
	{

		g_return_val_if_fail (config->name, NULL);
	  g_return_val_if_fail (config->provider, NULL);
		g_return_val_if_fail (config->dsn, NULL);

	  db = SIM_DATABASE (g_object_new (SIM_TYPE_DATABASE, NULL));

		db->_priv->name = g_strdup (config->name);
	  db->_priv->provider = g_strdup (config->provider);
		db->_priv->dsn = g_strdup (config->dsn);

	  if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_MYSQL))
		  db->type = SIM_DATABASE_TYPE_MYSQL;
	  else if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_PGSQL))
		  db->type = SIM_DATABASE_TYPE_PGSQL;
	  else if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_ORACLE))
		  db->type = SIM_DATABASE_TYPE_ORACLE;
	  else if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_ODBC))
		  db->type = SIM_DATABASE_TYPE_ODBC;
	  else
		  db->type = SIM_DATABASE_TYPE_NONE;

	  db->_priv->client = gda_client_new ();
		db->_priv->conn = gda_client_open_connection_from_string  (db->_priv->client,
								     db->_priv->provider,
								     db->_priv->dsn,
								     GDA_CONNECTION_OPTIONS_DONT_SHARE);

	  if (!gda_connection_is_open (db->_priv->conn))
    {
      g_print (" CONNECTION ERROR\n");
      g_print (" NAME: %s\n", db->_priv->name);
      g_print (" PROVIDER: %s\n", db->_priv->provider);
      g_print (" DSN: %s\n", db->_priv->dsn);
      g_print ("We can't open the database connection. Please check that your DB is up.\n");
      exit (EXIT_FAILURE);
    }

	  errors = gda_error_list_copy (gda_connection_get_errors (db->_priv->conn));
		for (i = 0; i < g_list_length(errors); i++)
    {
      error = (GdaError *) g_list_nth_data (errors, i);
      
      g_message ("ERROR %u: %s", gda_error_get_number (error), gda_error_get_description (error));
    }
	  gda_error_list_free (errors);
	}
	else //remote DB.
	{
		db = SIM_DATABASE (g_object_new (SIM_TYPE_DATABASE, NULL));

		db->_priv->name = g_strdup (config->name);
		db->_priv->local_DB = FALSE;
		db->_priv->rserver_name = g_strdup (config->rserver_name);
	}

  return db;
}

/*
 * Executes a query in the database specified and returns the number of affected rows (-1
 * on error)
 *
 */
gint
sim_database_execute_no_query  (SimDatabase  *database,
																const gchar  *buffer)
{
	
  GdaCommand     *command;
  GdaError       *error;
  GList          *errors = NULL;
  gint            ret, i;
	gboolean				mysql_down = FALSE;

  g_return_val_if_fail (database != NULL, -1);
  g_return_val_if_fail (SIM_IS_DATABASE (database), -1);
  g_return_val_if_fail (buffer != NULL, -1);

	ret = 0; 

	if (database->_priv->local_DB)
	{
  g_static_rec_mutex_lock (database->_priv->mutex);

  command = gda_command_new (buffer, 
												     GDA_COMMAND_TYPE_SQL, 
												     GDA_COMMAND_OPTION_STOP_ON_ERRORS);

  while (!GDA_IS_CONNECTION (database->_priv->conn) || !gda_connection_is_open (database->_priv->conn)) //if database connection is not open, try to open it.
  {
    g_message ("Error (1): DB Connection is closed. Trying to open it again....");
    database->_priv->conn = gda_client_open_connection_from_string (database->_priv->client,
																															      database->_priv->provider,
																															      database->_priv->dsn,
																															      GDA_CONNECTION_OPTIONS_DONT_SHARE);
		if (!database->_priv->conn)
    {
      g_message (" CONNECTION ERROR");
      g_message (" NAME: %s", database->_priv->name);
      g_message (" PROVIDER: %s", database->_priv->provider);
      g_message (" DSN: %s", database->_priv->dsn);
      g_message (" We can't open the database connection. Please check that your DB is up.");
      g_message (" Waiting 10 seconds until next try....");
			sleep(10);	//we'll wait to check if database is availabe again...
    }
    else
			g_message ("DB Connection restored");

  }
	

  ret = gda_connection_execute_non_query (database->_priv->conn, command, NULL);

  if (ret < 0)
  {
    errors = gda_error_list_copy (gda_connection_get_errors (database->_priv->conn));
    for (i = 0; i < g_list_length(errors); i++)
		{
		  error = (GdaError *) g_list_nth_data (errors, i);
		  g_message ("ERROR %s %u: %s", buffer, gda_error_get_number (error), gda_error_get_description (error));
			if (gda_error_get_number (error) == 2006) //if "MySQL server has gone away"...
				mysql_down = TRUE;
		}
    gda_error_list_free (errors);
  }

	if (mysql_down)
	{
    gda_connection_close (database->_priv->conn);
		ret = sim_database_execute_no_query (database, buffer);

	}

  gda_command_free (command);

  g_static_rec_mutex_unlock (database->_priv->mutex);
	}
/*	else	//remote DB
	{
	
	  GList *list = sim_server_get_sessions (ossim.server);
	  while (list)
  	{
	    SimSession *session = (SimSession *) list->data;
  	  if ((sim_session_is_master_server (session)) &&
					(g_ascii_strcmp (database->_priv->rserver_name, sim_session_get_hostname (session))) )
			{
				//FIXME: check if integrate this in sim_command_get_string() could be interesting: compare speed in both ways.
				gchar *query = g_strdup_printf ("database-query ds_name='%s' query='%s'", database->_priv->name, buffer);

	  	  sim_session_write_from_buffer (session, query);
				break;
			}

	    list = list->next;
  	}

	  g_list_free (list);
	



	}*/

  return ret;
}

/*
 *
 *
 *
 */
GdaDataModel*
sim_database_execute_single_command (SimDatabase  *database,
																     const gchar  *buffer)
{
  GdaCommand     *command;
  GdaDataModel   *model = NULL;
  GdaError       *error;
  GList          *errors = NULL;
	gint 						i;
	gboolean				mysql_down = FALSE;

  g_return_val_if_fail (database != NULL, NULL);
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail (buffer != NULL, NULL);

  g_static_rec_mutex_lock (database->_priv->mutex);

  command = gda_command_new (buffer,
												     GDA_COMMAND_TYPE_SQL,
												     GDA_COMMAND_OPTION_STOP_ON_ERRORS);

  while (!GDA_IS_CONNECTION (database->_priv->conn) || !gda_connection_is_open (database->_priv->conn))
  {
    g_message ("Error (2): DB Connection is closed. Trying to open it again....");
    database->_priv->conn = gda_client_open_connection_from_string (database->_priv->client,
                                                                    database->_priv->provider,
                                                                    database->_priv->dsn,
                                                                    GDA_CONNECTION_OPTIONS_DONT_SHARE);
		if (!database->_priv->conn)
    {
      g_message (" CONNECTION ERROR");
      g_message (" NAME: %s", database->_priv->name);
      g_message (" PROVIDER: %s", database->_priv->provider);
      g_message (" DSN: %s", database->_priv->dsn);
      g_message (" We can't open the database connection. Please check that your DB is up.");
      g_message (" Waiting 10 seconds until next try....");
			sleep(10);	//we'll wait to check if database is availabe again...
    }
    else
			g_message ("DB Connection restored");
  }

  model = gda_connection_execute_single_command (database->_priv->conn, command, NULL);

  if (model == NULL)
  {
    errors = gda_error_list_copy (gda_connection_get_errors (database->_priv->conn));
    for (i = 0; i < g_list_length (errors); i++)
    {
      error = (GdaError *) g_list_nth_data (errors, i);
      g_message ("ERROR %s %u: %s", buffer, gda_error_get_number (error), gda_error_get_description (error));
			if (gda_error_get_number (error) == 2006) //if "MySQL server has gone away"...
				mysql_down = TRUE;
    }
    gda_error_list_free (errors);
  }

	if (mysql_down)
	{							
    gda_connection_close (database->_priv->conn);
		model = sim_database_execute_single_command (database, buffer);
	}


  gda_command_free (command);

  g_static_rec_mutex_unlock (database->_priv->mutex);

  return model;
}

/*
 *
 *
 *
 */
GdaConnection*
sim_database_get_conn (SimDatabase  *database)
{
  g_return_val_if_fail (database != NULL, NULL);
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  return database->_priv->conn;  
}

/*
 *	Returns the DS name of the database (defined in server's config.xml)
 */
gchar*
sim_database_get_name (SimDatabase  *database)
{
  g_return_val_if_fail (database != NULL, NULL);
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  return database->_priv->name;  
}


/*
 * This returns from the database and table specified, a sequence number.
 * The number is "reserved".
 * The table specified should be something like blablabla_seq or lalalala_seq, you know ;)
 * Beware! if you write the name of a non-existant table this function will fail and will return 0.
 */
guint
sim_database_get_id (SimDatabase  *database,
											gchar				*table_name)
{

  GdaDataModel  *dm;
  GdaValue      *value;
  gchar         *query;
	guint					id=0;

  g_return_val_if_fail (database != NULL, 0);
  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);
  g_return_val_if_fail (table_name != NULL, 0);
	

  query = g_strdup_printf ("UPDATE %s SET id=LAST_INSERT_ID(id+1)", table_name);
  sim_database_execute_no_query (database, query);
	g_free (query);

	query = g_strdup_printf ("SELECT LAST_INSERT_ID(id) FROM %s", table_name);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    value = (GdaValue *) gda_data_model_get_value_at (dm, 0, 0);
    if (gda_data_model_get_n_rows(dm) !=0) 
    {
      if (!gda_value_is_null (value))
				id =value->value.v_uinteger;	//Again, I have to use this instead the commented below one. 
																			//If I use sim_gda_value_extract_type() to know the type of the value, I get that it's a
																			//GDA_VALUE_TYPE_BIGINT, although in mysql DB it has been created with: id INTEGER UNSIGNED NOT NULL.
        //id = gda_value_get_uinteger (value);
    }
    else
      id=0;

    g_object_unref(dm);
  }
  else
    g_message ("sim_database_get_id: %s table DATA MODEL ERROR", table_name);

  g_free (query);

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_database_get_id: id obtained: %d", id);

  return id;
}

/*
 * returns if this Database is local, or this server has to ask for data to other server
 */
gboolean
sim_database_is_local (SimDatabase  *database)
{
  g_return_val_if_fail (database != NULL, 0);
  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);

	return database->_priv->local_DB;
}

// vim: set tabstop=2:

