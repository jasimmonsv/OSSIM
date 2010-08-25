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


#include "sim-host.h"
#include <config.h>

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimHostPrivate {
  GInetAddr  *ia;
  gchar      *name;
  gint        asset;
};

static gpointer parent_class = NULL;
static gint sim_host_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_host_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_host_impl_finalize (GObject  *gobject)
{
  SimHost *host = SIM_HOST (gobject);

  if (host->_priv->ia)
    gnet_inetaddr_unref (host->_priv->ia);
  if (host->_priv->name)
    g_free (host->_priv->name);

  g_free (host->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_host_class_init (SimHostClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_host_impl_dispose;
  object_class->finalize = sim_host_impl_finalize;
}

static void
sim_host_instance_init (SimHost *host)
{
  host->_priv = g_new0 (SimHostPrivate, 1);

  host->_priv->ia = NULL;
  host->_priv->name = NULL;
  host->_priv->asset = 2;
}

/* Public Methods */

GType
sim_host_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimHostClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_host_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimHost),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_host_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimHost", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimHost*
sim_host_new (const GInetAddr  *ia,
	      const gchar      *name,
	      gint              asset)
{
  SimHost *host;

  g_return_val_if_fail (ia, NULL);
  g_return_val_if_fail (name, NULL);

  host = SIM_HOST (g_object_new (SIM_TYPE_HOST, NULL));
  host->_priv->ia = gnet_inetaddr_clone (ia);
  host->_priv->name = g_strdup (name);
  host->_priv->asset = asset;

  return host;
}

/*
 *
 *
 *
 */
SimHost*
sim_host_new_from_dm (GdaDataModel  *dm,
		      gint           row)
{
  SimHost    *host;
  GdaValue   *value;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  host = SIM_HOST (g_object_new (SIM_TYPE_HOST, NULL));

  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  host->_priv->ia = gnet_inetaddr_new_nonblock (gda_value_get_string (value), 0);
  
  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
  host->_priv->name = gda_value_stringify (value);
  
  value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
  host->_priv->asset = gda_value_get_smallint (value);

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_host_new_from_dm: %s", host->_priv->name);
  sim_host_debug_print	(host);

  return host;
}

/*
 *
 *
 *
 */
GInetAddr*
sim_host_get_ia (SimHost *host)
{
  g_return_val_if_fail (host, NULL);
  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  return host->_priv->ia;
}

/*
 *
 *
 *
 */
void
sim_host_set_ia (SimHost          *host,
		 const GInetAddr  *ia)
{
  g_return_if_fail (host);
  g_return_if_fail (SIM_IS_HOST (host));
  g_return_if_fail (ia);

  if (host->_priv->ia)
    gnet_inetaddr_unref (host->_priv->ia);

  host->_priv->ia = gnet_inetaddr_clone (ia);
}

/*
 *
 *
 *
 */
gchar*
sim_host_get_name (SimHost  *host)
{
  g_return_val_if_fail (host, NULL);
  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  return host->_priv->name;
}

/*
 *
 *
 *
 */
void
sim_host_set_name (SimHost      *host,
		   const gchar  *name)
{
  g_return_if_fail (host);
  g_return_if_fail (SIM_IS_HOST (host));
  g_return_if_fail (name);

  if (host->_priv->name)
    g_free (host->_priv->name);

  host->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 */
gint
sim_host_get_asset (SimHost  *host)
{
  g_return_val_if_fail (host, 0);
  g_return_val_if_fail (SIM_IS_HOST (host), 0);

  if (host->_priv->asset < 0)
    return 0;
  if (host->_priv->asset > 5)
    return 5;

  return host->_priv->asset;
}

/*
 *
 *
 *
 */
void
sim_host_set_asset (SimHost  *host,
		    gint      asset)
{
  g_return_if_fail (host);
  g_return_if_fail (SIM_IS_HOST (host));

  if (asset < 0)
    host->_priv->asset = 0;
  else if (asset > 5)
    host->_priv->asset = 5;
  else host->_priv->asset = asset;
}

void
sim_host_debug_print	(SimHost	*host)
{
  g_return_if_fail (host);
  g_return_if_fail (SIM_IS_HOST (host));
	
	gchar	*aux = gnet_inetaddr_get_canonical_name (host->_priv->ia);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_host_debug_print");
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "     name: %s",host->_priv->name);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "     ia: %s",aux);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "     asset: %d",host->_priv->asset);
	
	g_free (aux);

}

// vim: set tabstop=2:
