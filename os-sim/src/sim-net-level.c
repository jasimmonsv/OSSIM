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


#include "sim-net-level.h"

#include <math.h>
#include <config.h>

enum 
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimNetLevelPrivate {
  gchar     *name;
  gdouble    a;
  gdouble    c;
};

static gpointer parent_class = NULL;
static gint sim_net_level_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_net_level_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_net_level_impl_finalize (GObject  *gobject)
{
  SimNetLevel  *net_level = SIM_NET_LEVEL (gobject);

  if (net_level->_priv->name)
    g_free (net_level->_priv->name);

  g_free (net_level->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_net_level_class_init (SimNetLevelClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_net_level_impl_dispose;
  object_class->finalize = sim_net_level_impl_finalize;
}

static void
sim_net_level_instance_init (SimNetLevel *net_level)
{
  net_level->_priv = g_new0 (SimNetLevelPrivate, 1);

  net_level->_priv->name = NULL;
  net_level->_priv->c = 1;
  net_level->_priv->a = 1;
}

/* Public Methods */

GType
sim_net_level_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimNetLevelClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_net_level_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimNetLevel),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_net_level_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimNetLevel", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimNetLevel*
sim_net_level_new (const gchar  *name,
		   gint          c,
		   gint          a)
{
  SimNetLevel *net_level = NULL;

  g_return_val_if_fail (name, NULL);

	if (c < 0) c = 0;
  if (a < 0) a = 0;

  net_level = SIM_NET_LEVEL (g_object_new (SIM_TYPE_NET_LEVEL, NULL));
  net_level->_priv->name = g_strdup (name);
  net_level->_priv->c = c;
  net_level->_priv->a = a;

  return net_level;
}

/*
 *
 *
 *
 */
SimNetLevel*
sim_net_level_new_from_dm (GdaDataModel  *dm,
			   gint           row)
{
  SimNetLevel  *net_level;
  GdaValue      *value;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  net_level = SIM_NET_LEVEL (g_object_new (SIM_TYPE_NET_LEVEL, NULL));

  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  net_level->_priv->name = gda_value_stringify (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
  net_level->_priv->c = gda_value_get_integer (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
  net_level->_priv->a = gda_value_get_integer (value);

  return net_level;
}

/*
 *
 *
 *
 */
gchar*
sim_net_level_get_name (SimNetLevel     *net_level)
{
  g_return_val_if_fail (net_level, NULL);
  g_return_val_if_fail (SIM_IS_NET_LEVEL (net_level), NULL);

  return net_level->_priv->name;
}

/*
 *
 *
 *
 */
void
sim_net_level_set_name (SimNetLevel     *net_level,
			const gchar     *name)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));
  g_return_if_fail (name);

  if (net_level->_priv->name)
    g_free (net_level->_priv->name);

  net_level->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 */
gdouble
sim_net_level_get_c (SimNetLevel  *net_level)
{
  g_return_val_if_fail (net_level, 0);
  g_return_val_if_fail (SIM_IS_NET_LEVEL (net_level), 0);

  return net_level->_priv->c;
}

/*
 *
 *
 *
 */
void
sim_net_level_set_c (SimNetLevel  *net_level,
		     gdouble       c)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));

  if (c < 1) c = 1;

  net_level->_priv->c = c;
}

/*
 *
 *
 *
 */
void
sim_net_level_plus_c (SimNetLevel  *net_level,
		      gdouble       c)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));

  net_level->_priv->c += c;
}

/*
 *
 *
 *
 */
gdouble
sim_net_level_get_a (SimNetLevel  *net_level)
{
  g_return_val_if_fail (net_level, 0);
  g_return_val_if_fail (SIM_IS_NET_LEVEL (net_level), 0);

  return net_level->_priv->a;
}

/*
 *
 *
 *
 */
void
sim_net_level_set_a (SimNetLevel  *net_level,
		     gdouble       a)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));

  if (a < 1) a = 1;

  net_level->_priv->a = a;
}

/*
 *
 *
 *
 */
void
sim_net_level_plus_a (SimNetLevel  *net_level,
		      gdouble       a)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));

  net_level->_priv->a += a;
}

/*
 *
 *
 *
 */
void
sim_net_level_set_recovery (SimNetLevel  *net_level,
			    gint          recovery)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));
  g_return_if_fail (recovery >= 0);

  if (net_level->_priv->c > recovery)
    net_level->_priv->c -= recovery;
  else
    net_level->_priv->c = 0;

  if (net_level->_priv->a > recovery)
    net_level->_priv->a -= recovery;
  else
    net_level->_priv->a = 0;
}

/*
 *
 *
 *
 */
gchar*
sim_net_level_get_insert_clause (SimNetLevel  *net_level)
{
  gchar *query;
  gint   c = 0;
  gint   a = 0;

  g_return_val_if_fail (net_level, NULL);
  g_return_val_if_fail (SIM_IS_NET_LEVEL (net_level), NULL);
  g_return_val_if_fail (net_level->_priv->name, NULL);

  c = rint (net_level->_priv->c);
  a = rint (net_level->_priv->a);

  query = g_strdup_printf ("INSERT INTO net_qualification VALUES ('%s', %d, %d)",
			   net_level->_priv->name, c, a);

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_net_level_get_update_clause (SimNetLevel  *net_level)
{
  gchar *query;
  gint   c = 0;
  gint   a = 0;

  g_return_val_if_fail (net_level, NULL);
  g_return_val_if_fail (SIM_IS_NET_LEVEL (net_level), NULL);
  g_return_val_if_fail (net_level->_priv->name, NULL);

  c = rint (net_level->_priv->c);
  a = rint (net_level->_priv->a);

  query = g_strdup_printf ("UPDATE net_qualification SET compromise = %d, attack = %d WHERE net_name = '%s'",
			   c, a, net_level->_priv->name);

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_net_level_get_delete_clause (SimNetLevel  *net_level)
{
  gchar *query;

  g_return_val_if_fail (net_level, NULL);
  g_return_val_if_fail (SIM_IS_NET_LEVEL (net_level), NULL);
  g_return_val_if_fail (net_level->_priv->name, NULL);

  query = g_strdup_printf ("DELETE FROM net_qualification WHERE net_name = '%s'",
			   net_level->_priv->name);

  return query;
}

void
sim_net_level_debug_print (SimNetLevel  *net_level)
{
  g_return_if_fail (net_level);
  g_return_if_fail (SIM_IS_NET_LEVEL (net_level));

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_net_level_debug_print");
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                name host: %s", net_level->_priv->name);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                c: %f", net_level->_priv->c);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                a: %f", net_level->_priv->a);


}


// vim: set tabstop=2:
