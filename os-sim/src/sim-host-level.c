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


#include "sim-host-level.h"
 
#include <math.h>
#include <config.h>

enum 
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimHostLevelPrivate {
  GInetAddr  *ia;
  gdouble     a;
  gdouble     c;
};

static gpointer parent_class = NULL;
static gint sim_host_level_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_host_level_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_host_level_impl_finalize (GObject  *gobject)
{
  SimHostLevel *host_level = SIM_HOST_LEVEL (gobject);

  if (host_level->_priv->ia)
    gnet_inetaddr_unref (host_level->_priv->ia);

  g_free (host_level->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_host_level_class_init (SimHostLevelClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_host_level_impl_dispose;
  object_class->finalize = sim_host_level_impl_finalize;
}

static void
sim_host_level_instance_init (SimHostLevel *host_level)
{
  host_level->_priv = g_new0 (SimHostLevelPrivate, 1);

  host_level->_priv->ia = NULL;
  host_level->_priv->c = 1;
  host_level->_priv->a = 1;
}

/* Public Methods */
GType
sim_host_level_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimHostLevelClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_host_level_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimHostLevel),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_host_level_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimHostLevel", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 */
SimHostLevel*
sim_host_level_new (const GInetAddr     *ia,
		    gint                 c,
		    gint                 a)
{
  SimHostLevel *host_level = NULL;

  g_return_val_if_fail (ia, NULL);

	if (c < 0) c = 0; //The RRDupdate.py file modifies this to 0.0001 or something like that; RRD's need not be 0. If the value is 0 the graphic didn't print a line.
  if (a < 0) a = 0; // sooooo the framework need to change that a bit to fix that RRD problem.

  host_level = SIM_HOST_LEVEL (g_object_new (SIM_TYPE_HOST_LEVEL, NULL));
  gchar *ip_temp = gnet_inetaddr_get_canonical_name(ia);
  if (ip_temp)
  {        
    g_free (ip_temp);
    host_level->_priv->ia = gnet_inetaddr_clone (ia);
    host_level->_priv->c = c;
    host_level->_priv->a = a;  
    return host_level;
  }
  else
    return NULL;
}

/*
 *
 *
 *
 */
SimHostLevel*
sim_host_level_new_from_dm (GdaDataModel  *dm,
			    gint           row)
{
  SimHostLevel  *host_level;
  GdaValue      *value;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  host_level = SIM_HOST_LEVEL (g_object_new (SIM_TYPE_HOST_LEVEL, NULL));

  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  host_level->_priv->ia = gnet_inetaddr_new_nonblock (gda_value_get_string (value), 0);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
  host_level->_priv->c = gda_value_get_integer (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
  host_level->_priv->a = gda_value_get_integer (value);

  return host_level;
}

/*
 *
 *
 *
 */
GInetAddr*
sim_host_level_get_ia (SimHostLevel  *host_level)
{
  g_return_val_if_fail (host_level, NULL);
  g_return_val_if_fail (SIM_IS_HOST_LEVEL (host_level), NULL);

  return host_level->_priv->ia;
}

/*
 *
 *
 *
 */
void
sim_host_level_set_ia (SimHostLevel  *host_level,
		       const GInetAddr     *ia)
{
  g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));
  g_return_if_fail (ia);

  if (host_level->_priv->ia)
    gnet_inetaddr_unref (host_level->_priv->ia);

  host_level->_priv->ia = gnet_inetaddr_clone (ia);
}

/*
 *
 *
 *
 */
gdouble
sim_host_level_get_c (SimHostLevel  *host_level)
{
  g_return_val_if_fail (host_level, 0);
  g_return_val_if_fail (SIM_IS_HOST_LEVEL (host_level), 0);

  return host_level->_priv->c;
}

/*
 *
 *
 *
 */
void
sim_host_level_set_c (SimHostLevel  *host_level,
		      gdouble        c)
{
  g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));

  if (c < 1) c = 1;

  host_level->_priv->c = c;
}

/*
 *
 *
 *
 */
void
sim_host_level_plus_c (SimHostLevel  *host_level,
		       gdouble        c)
{
  g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));

  host_level->_priv->c += c;
}

/*
 *
 *
 *
 */
gdouble
sim_host_level_get_a (SimHostLevel  *host_level)
{
  g_return_val_if_fail (host_level, 0);
  g_return_val_if_fail (SIM_IS_HOST_LEVEL (host_level), 0);

  return host_level->_priv->a;
}

/*
 *
 *
 *
 */
void
sim_host_level_set_a (SimHostLevel  *host_level,
		      gdouble        a)
{
  g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));

  if (a < 1) a = 1;

  host_level->_priv->a = a;
}

/*
 *
 *
 *
 */
void
sim_host_level_plus_a (SimHostLevel  *host_level,
		       gdouble        a)
{
  g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));

  host_level->_priv->a += a;
}

/*
 *
 *
 *
 */
void
sim_host_level_set_recovery (SimHostLevel  *host_level,
			     gint           recovery)
{
  g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));
  g_return_if_fail (recovery >= 0);

  if (host_level->_priv->c > recovery)
    host_level->_priv->c -= recovery;
  else
    host_level->_priv->c = 0;

  if (host_level->_priv->a > recovery)
    host_level->_priv->a -= recovery;
  else
    host_level->_priv->a = 0;
}

/*
 *
 * Insert the level of a host into host_qualification table. Returns NULL on error.
 *
 */
gchar*
sim_host_level_get_insert_clause (SimHostLevel  *host_level)
{
  gchar *query=NULL;
  gchar *name;
  gint   c = 0;
  gint   a = 0;

  g_return_val_if_fail (host_level, NULL);
  g_return_val_if_fail (SIM_IS_HOST_LEVEL (host_level), NULL);
  g_return_val_if_fail (host_level->_priv->ia, NULL);

  c = rint (host_level->_priv->c);
  a = rint (host_level->_priv->a);

  if (name = gnet_inetaddr_get_canonical_name (host_level->_priv->ia))
  {
    query = g_strdup_printf ("INSERT INTO host_qualification VALUES ('%s', %d, %d)",
			   name, c, a);
    g_free (name);
  }

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_host_level_get_update_clause (SimHostLevel  *host_level)
{
  gchar *query = NULL;
  gchar *name = NULL;
  gint   c = 0;
  gint   a = 0;

  g_return_val_if_fail (host_level, NULL);
  g_return_val_if_fail (SIM_IS_HOST_LEVEL (host_level), NULL);
  g_return_val_if_fail (host_level->_priv->ia, NULL);

  c = rint (host_level->_priv->c);
  a = rint (host_level->_priv->a);

  if (gnet_inetaddr_is_ipv4(host_level->_priv->ia))
    name = gnet_inetaddr_get_canonical_name (host_level->_priv->ia);
  
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_host_level_get_update_clause: name: -%s-",name);
  if (name)
  { 
    query = g_strdup_printf ("UPDATE host_qualification SET compromise = %d, attack = %d WHERE host_ip = '%s'",
			   c, a, name);

    g_free (name);
  }
  
	return query;
}

/*
 *
 *
 *
 */
gchar*
sim_host_level_get_delete_clause (SimHostLevel  *host_level)
{
  gchar *query=NULL;
  gchar *name;

  g_return_val_if_fail (host_level, NULL);
  g_return_val_if_fail (SIM_IS_HOST_LEVEL (host_level), NULL);
  g_return_val_if_fail (host_level->_priv->ia, NULL);

  if (name = gnet_inetaddr_get_canonical_name (host_level->_priv->ia))
	{
   query = g_strdup_printf ("DELETE FROM host_qualification WHERE host_ip = '%s'", name);
   g_free (name);
	}

  return query;
}

void
sim_host_level_debug_print (SimHostLevel  *host_level)
{
	g_return_if_fail (host_level);
  g_return_if_fail (SIM_IS_HOST_LEVEL (host_level));

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_host_level_debug_print");
  gchar *ip_temp = gnet_inetaddr_get_canonical_name (host_level->_priv->ia);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                ip host: %s", ip_temp);
  g_free (ip_temp);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                c: %f", host_level->_priv->c);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                a: %f", host_level->_priv->a);


}
