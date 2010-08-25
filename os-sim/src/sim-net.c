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

 
#include "sim-net.h"
#include "sim-util.h"
#include <config.h>
#include <string.h>

enum
{
  DESTROY,
  LAST_SIGNAL
};

/*
 * Format can be for example as follows:
 * name: LaElipaNet
 * ips: 192.168.1.0/24,192.168.1.2/32,192.168.1.0-40
 * the following (or something simmilar) is NOT accepted: 192.168.0-40.0
 */

struct _SimNetPrivate {
  gchar           *name;
  gchar           *ips; //this info will be stored inside 'inets' variable 
  gint             asset;

  GList           *inets; //SimInet objects are stored here so we can store multiple networks under the same name
};

static gpointer parent_class = NULL;
static gint sim_net_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_net_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_net_impl_finalize (GObject  *gobject)
{
  SimNet  *net = SIM_NET (gobject);

  if (net->_priv->name)
    g_free (net->_priv->name);
  if (net->_priv->ips)
    g_free (net->_priv->ips);

  sim_net_free_inets (net);

  g_free (net->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_net_class_init (SimNetClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_net_impl_dispose;
  object_class->finalize = sim_net_impl_finalize;
}

static void
sim_net_instance_init (SimNet *net)
{
  net->_priv = g_new0 (SimNetPrivate, 1);

  net->_priv->name = NULL;
  net->_priv->ips = NULL;
  net->_priv->asset = 2;

  net->_priv->inets = NULL;
}

/* Public Methods */

GType
sim_net_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimNetClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_net_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimNet),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_net_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimNet", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 * 
 * FIXME: convert this function and sim_net_new_from_dm into something better
 *
 *
 */
SimNet*
sim_net_new (const gchar   *name,
				     const gchar   *ips,
				     gint			     asset)
{
  SimNet *net = NULL;
  gint        i;

  g_return_val_if_fail (name, NULL);
  g_return_val_if_fail (ips, NULL);

  net = SIM_NET (g_object_new (SIM_TYPE_NET, NULL));
  net->_priv->name = g_strdup (name);
  net->_priv->ips = g_strdup (ips);
  net->_priv->asset = asset;
  
	return (sim_net_split_internal_ips (net));  // == return net

  return net;
}

/*
 *
 * Load one network string from DB (Policy->Networks, table net, column ips) and store it in a SimNet object.
 *
 */
SimNet*
sim_net_new_from_dm (GdaDataModel  *dm,
		     							gint           row)
{
  SimNet     *net;
  GdaValue   *value;
  gint        i;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  net = SIM_NET (g_object_new (SIM_TYPE_NET, NULL));

  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  net->_priv->name = gda_value_stringify (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row); 
  net->_priv->ips = gda_value_stringify (value);
  
  value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
  net->_priv->asset = gda_value_get_integer (value);
  
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_net_new_from_dm: %s",net->_priv->ips);
  sim_net_debug_print (net);

	return (sim_net_split_internal_ips (net));	// == return net
}

/*
 *
 */
gchar*
sim_net_get_ips (SimNet  *net)
{
  g_return_val_if_fail (net, NULL);
  g_return_val_if_fail (SIM_IS_NET (net), NULL);

  return net->_priv->ips;
}

/*
 *
 */
void
sim_net_set_ips (SimNet  *net,
									gchar	*ips) //string with multiple IPs
{
  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));

  net->_priv->ips = ips;
}



/*
 *
 *
 *
 */
gchar*
sim_net_get_name (SimNet  *net)
{
  g_return_val_if_fail (net, NULL);
  g_return_val_if_fail (SIM_IS_NET (net), NULL);

  return net->_priv->name;
}

/*
 *
 *
 *
 */
void
sim_net_set_name (SimNet       *net,
		  const gchar  *name)
{
  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));
  g_return_if_fail (name);

  if (net->_priv->name)
    g_free (net->_priv->name);

  net->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 */
gint
sim_net_get_asset (SimNet  *net)
{
  g_return_val_if_fail (net, 0);
  g_return_val_if_fail (SIM_IS_NET (net), 0);

  return net->_priv->asset;
}

/*
 *
 *
 *
 */
void
sim_net_set_asset (SimNet  *net,
		   gint     asset)
{
  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));

  net->_priv->asset = asset;
}

/*
 *
 * Append the 2nd parameter, the SimInet object (a single network) to the SimNet (wich can own multiple networks)
 *
 */
void
sim_net_append_inet (SimNet     *net,
		     SimInet    *inet)
{
  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));
  g_return_if_fail (inet);
  g_return_if_fail (SIM_IS_INET (inet));
  
  net->_priv->inets = g_list_append (net->_priv->inets, inet);
}

/*
 *
 *
 *
 */
void
sim_net_remove_inet (SimNet     *net,
		     SimInet    *inet)
{
  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));
  g_return_if_fail (inet);
  g_return_if_fail (SIM_IS_INET (inet));

  net->_priv->inets = g_list_remove (net->_priv->inets, inet);
}

/*
 *
 *
 *
 */
GList*
sim_net_get_inets (SimNet        *net)
{
  g_return_val_if_fail (net, NULL);
  g_return_val_if_fail (SIM_IS_NET (net), NULL);

  return net->_priv->inets;
}

/*
 *
 *
 *
 */
void
sim_net_set_inets (SimNet           *net,
		   GList            *list)
{
  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));
  g_return_if_fail (list);

  net->_priv->inets = g_list_concat (net->_priv->inets, list);
}

/*
 *
 *
 *
 */
void 
sim_net_free_inets (SimNet           *net)
{
  GList   *list;

  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));

  list =  net->_priv->inets;
  while (list)
    {
      SimInet *inet = (SimInet *) list->data;
      g_object_unref (inet);
      list = list->next;
    }

  g_list_free (net->_priv->inets);
}

/*
 * The SimNet object has one field, "ips", where are stored all the IPs in a single string.
 * To be able to operate with the multiple networks that there are in that string, they has to be splitted and stored
 * inside SimInet GList.
 */
SimNet*
sim_net_split_internal_ips (SimNet	*net)
{
	gint i;

  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));

  if (net->_priv->ips)
  {    
	  if (strchr (net->_priv->ips, ',')) //may be that there are more than one Network under the same name
		{
			gchar **values = g_strsplit (net->_priv->ips, ",", 0);
		  for (i = 0; values[i] != NULL; i++)			//example: values[i]="192.168.0.1/24-192.168.0.0/24"
		  {
			  GList *list = sim_get_inets (values[i]); 
				while (list)
				{
				  SimInet *inet = (SimInet *) list->data;
					sim_net_append_inet (net, inet);		//append inet (SimInet) object to net (SimNet) list.
				  list = list->next;
				}
		  }
			g_strfreev (values);
		}
		else //just one network
		{
		  GList *list = sim_get_inets (net->_priv->ips);
			while (list)
		  {	
			  SimInet *inet = (SimInet *) list->data;
		    sim_net_append_inet (net, inet);
				list = list->next;
		  }
		}
  }

	return net;
}

/*
 * Check with each one of the SimInet objects inside net, if someone matches with the inet object. 
 *
 */
gboolean
sim_net_has_inet (SimNet         *net,
								  SimInet        *inet)
{
  GList  *list;

  g_return_val_if_fail (net, FALSE);
  g_return_val_if_fail (SIM_IS_NET (net), FALSE);
  g_return_val_if_fail (inet, FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  list = net->_priv->inets;
  while (list)
  {
    SimInet *cmp = (SimInet *) list->data;

    if (sim_inet_has_inet (cmp, inet))
			return TRUE;
      
    list = list->next;
	}  
  
  return FALSE;
}

void
sim_net_debug_print (SimNet	*net)
{

  g_return_if_fail (net);
  g_return_if_fail (SIM_IS_NET (net));

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_net_debug_print");
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                 name: %s", net->_priv->name);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                 ips: %s", net->_priv->ips);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                 asset: %d", net->_priv->asset);
}
// vim: set tabstop=2:
