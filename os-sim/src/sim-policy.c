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


#include "sim-policy.h"
#include "sim-sensor.h"
#include "sim-inet.h"
#include "sim-event.h"
#include "os-sim.h"
/*****/
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <string.h>
#include <stdlib.h>
#include <limits.h>

#ifdef BSD
#define KERNEL
#include <netinet/in.h>
#endif
/*****/
#include <config.h>

extern SimMain    ossim;

enum 
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimPolicyPrivate {
  gint    id;
  gint    priority;
  gchar  *description;

  gint    begin_hour;
  gint    end_hour;
  gint    begin_day;
  gint    end_day;
	gint		begin_dayhour;
	gint		end_dayhour;

	gint    has_actions;
  SimRole	*role;				//this is not intended to match. This is the behaviour of the events that matches with this policy

  GList  *src;  				// SimInet objects
  GList  *dst;
  GList  *ports;				//port & protocol list, SimPortProtocol object.
  GList  *sensors; 			//gchar* sensor's ip (i.e. "1.1.1.1")
  GList  *plugin_ids; 	//(guint *) list with each one of the plugin_id's
  GList  *plugin_sids;	//
  GList  *plugin_groups;	// *Plugin_PluginSid structs

	GList	 *targets;			//gchar* target's name (i.e. "target_A"). This is a bit different to the other policy fields.
												//This field tell us if this policy has to been executed in this server. It doesn't compares anything
												//from the event received, and that's why we don't need the IP, just the name.
};

static gpointer parent_class = NULL;
static gint sim_policy_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_policy_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_policy_impl_finalize (GObject  *gobject)
{
  SimPolicy  *policy = SIM_POLICY (gobject);

  if (policy->_priv->description)
    g_free (policy->_priv->description);

  sim_policy_free_src (policy);
  sim_policy_free_dst (policy);
  sim_policy_free_ports (policy);
  sim_policy_free_sensors (policy);
	//FIXME: sim_policy_free_plugin_id y sid.

	if (policy->_priv->role)
		g_free (policy->_priv->role);
	
  g_free (policy->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_policy_class_init (SimPolicyClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_policy_impl_dispose;
  object_class->finalize = sim_policy_impl_finalize;
}

static void
sim_policy_instance_init (SimPolicy *policy)
{
  policy->_priv = g_new0 (SimPolicyPrivate, 1);

  policy->_priv->id = 0;
  policy->_priv->priority = 1;
  policy->_priv->description = NULL;

  policy->_priv->begin_hour = 0;
  policy->_priv->end_hour = 0;
  policy->_priv->begin_day = 0;
  policy->_priv->end_day = 0;
	policy->_priv->begin_dayhour = 0;
	policy->_priv->end_dayhour = 0;

  policy->_priv->src = NULL;
  policy->_priv->dst = NULL;
  policy->_priv->ports = NULL;
  policy->_priv->sensors = NULL;
  policy->_priv->plugin_ids = NULL;
  policy->_priv->plugin_sids = NULL;
  policy->_priv->plugin_groups = NULL;
 
	policy->_priv->role = g_new0 (SimRole, 1);
	policy->_priv->has_actions= 0;
}

/* Public Methods */

GType
sim_policy_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimPolicyClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_policy_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimPolicy),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_policy_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimPolicy", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimPolicy*
sim_policy_new (void)
{
  SimPolicy *policy;

  policy = SIM_POLICY (g_object_new (SIM_TYPE_POLICY, NULL));

  return policy;
}

/*
 *
 *
 *
 */
SimPolicy*
sim_policy_new_from_dm (GdaDataModel  *dm,
			gint           row)
{
  SimPolicy  *policy;
  GdaValue   *value;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  policy = SIM_POLICY (g_object_new (SIM_TYPE_POLICY, NULL));

  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  policy->_priv->id = gda_value_get_integer (value);
  
  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
  policy->_priv->priority = gda_value_get_smallint (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
  policy->_priv->description = gda_value_stringify (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 3, row);
  policy->_priv->begin_hour = gda_value_get_smallint (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 4, row);
  policy->_priv->end_hour = gda_value_get_smallint (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 5, row);
  policy->_priv->begin_day = gda_value_get_smallint (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 6, row);
  policy->_priv->end_day = gda_value_get_smallint (value);

	// Sunday 0 -> Saturday 6
	policy->_priv->begin_dayhour = (policy->_priv->begin_day % 7) * 24 + policy->_priv->begin_hour;
	policy->_priv->end_dayhour =  (policy->_priv->end_day % 7) * 24 + policy->_priv->end_hour;

  return policy;
}

/*
 *
 *
 *
 */
gint
sim_policy_get_id (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->id;
}

/*
 *
 *
 *
 */
void
sim_policy_set_id (SimPolicy* policy,
		   gint       id)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->id = id;
}

/*
 *
 *
 *
 */
gint
sim_policy_get_priority (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  if (policy->_priv->priority < -1) //-1 means "don't change priority"
    return 0;
  if (policy->_priv->priority > 5)
    return 5;

  return policy->_priv->priority;
}

/*
 *
 *
 *
 */
void
sim_policy_set_priority (SimPolicy* policy,
												 gint       priority)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  if (priority < -1)
    policy->_priv->priority = 0;
  else if (priority > 5)
    policy->_priv->priority = 5;
  else policy->_priv->priority = priority;
}

gint
sim_policy_get_has_actions (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->has_actions;
}

/*
*
*
* Set if the policy has actions
*/
void
sim_policy_set_has_actions (SimPolicy* policy, gint actions)
{
  policy->_priv->has_actions=actions;
}

/*
 *
 *
 *
 */
gint
sim_policy_get_begin_day (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->begin_day;
}

/*
 *
 *
 *
 */
void
sim_policy_set_begin_day (SimPolicy* policy,
			 gint       begin_day)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->begin_day = begin_day;
	policy->_priv->begin_dayhour =  (begin_day - 1) * 24 + policy->_priv->begin_hour;
}

/*
 *
 *
 *
 */
gint
sim_policy_get_end_day (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->end_day;
}

/*
 *
 *
 *
 */
void
sim_policy_set_end_day (SimPolicy* policy,
			 gint       end_day)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->end_day = end_day;
	policy->_priv->end_dayhour = (end_day - 1) * 24 + policy->_priv->end_hour;
}

/*
 *
 *
 *
 */
gint
sim_policy_get_begin_hour (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->begin_hour;
}

/*
 *
 *
 *
 */
void
sim_policy_set_begin_hour (SimPolicy* policy,
			 gint       begin_hour)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->begin_hour = begin_hour;
	policy->_priv->begin_dayhour = (policy->_priv->begin_day - 1) * 24 + begin_hour;
}

/*
 *
 *
 *
 */
gint
sim_policy_get_end_hour (SimPolicy* policy)
{
  g_return_val_if_fail (policy, 0);
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->end_hour;
}

/*
 *
 *
 *
 */
void
sim_policy_set_end_hour (SimPolicy* policy,
			 gint       end_hour)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->end_hour = end_hour;
	policy->_priv->end_dayhour = (policy->_priv->end_day - 1) * 24 + end_hour;
}

/*
 * This set, tells if the events that match in the policy must be stored in database
 * or not.
 *//*
void
sim_policy_set_store (SimPolicy *policy, gboolean store)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->store_in_DB = store;  
}*/

/*
 * Get if the events that match in the policy must be stored.
 *//*
gboolean
sim_policy_get_store (SimPolicy *policy)
{
  g_return_val_if_fail (policy, FALSE);
  g_return_val_if_fail (SIM_IS_POLICY (policy), FALSE);

  return policy->_priv->store_in_DB;
}*/
/*
 *
 *
 *
 */
void
sim_policy_append_src (SimPolicy     *policy,
								       SimInet        *src) //SimInet objects can store hosts or networks, so we'll use it in the policy
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (src);

  policy->_priv->src = g_list_append (policy->_priv->src, src); //FIXME: I'll probably change it with g_list_prepend to increase efficiency
}

/*
 *
 *
 *
 */
void
sim_policy_remove_src (SimPolicy        *policy,
		       SimInet           *src)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (src);

  policy->_priv->src = g_list_remove (policy->_priv->src, src);
}

/*
 *
 * Returns all the src's from a policy
 *
 */
GList*
sim_policy_get_src (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->src;
}

/*
 *
 *
 *
 */
void
sim_policy_free_src (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->src;
  while (list)
    {
      SimInet *src = (SimInet *) list->data;
      g_object_unref(src);
      list = list->next;
    }
  g_list_free (policy->_priv->src);
}

/*
 *
 *
 *
 */
void
sim_policy_append_dst (SimPolicy        *policy,
		       SimInet        	*dst)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (dst);

  policy->_priv->dst = g_list_append (policy->_priv->dst, dst);
}

/*
 *
 *
 *
 */
void
sim_policy_remove_dst (SimPolicy        *policy,
		       SimInet 	    	*dst)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (dst);

  policy->_priv->dst = g_list_remove (policy->_priv->dst, dst);
}

/*
 *
 * Returns a SimNet object with all the hosts and/or networks in a specific policy rule.
 *
 */
GList*
sim_policy_get_dst (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->dst;
}

/*
 *
 *
 *
 */
void
sim_policy_free_dst (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->dst;
  while (list)
    {
      SimInet *dst = (SimInet *) list->data;
      g_object_unref (dst);
      list = list->next;
    }
  g_list_free (policy->_priv->dst);
}

/*
 *
 *
 *
 */
void
sim_policy_append_port (SimPolicy        *policy,
			SimPortProtocol  *pp)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pp);

  policy->_priv->ports = g_list_append (policy->_priv->ports, pp);
}

/*
 *
 *
 *
 */
void
sim_policy_remove_port (SimPolicy        *policy,
			SimPortProtocol  *pp)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pp);

  policy->_priv->ports = g_list_remove (policy->_priv->ports, pp);
}

/*
 *
 *
 *
 */
GList*
sim_policy_get_ports (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->ports;
}

/*
 *
 *
 *
 */
void
sim_policy_free_ports (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->ports;
  while (list)
    {
      SimPortProtocol *port = (SimPortProtocol *) list->data;
      g_free (port);
      list = list->next;
    }
  g_list_free (policy->_priv->ports);
}



/*
 *
 *
 *
 */
void
sim_policy_append_sensor (SimPolicy        *policy,
								          gchar            *sensor)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (sensor);

  policy->_priv->sensors = g_list_append (policy->_priv->sensors, sensor);
}

/*
 *
 *
 *
 */
void
sim_policy_remove_sensor (SimPolicy        *policy,
								           gchar            *sensor)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (sensor);

  policy->_priv->sensors = g_list_remove (policy->_priv->sensors, sensor);
}


/*
 *
 *
 *
 */
GList*
sim_policy_get_sensors (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->sensors;
}

/*
 *
 *
 *
 */
void
sim_policy_free_sensors (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->sensors;
  while (list)
  {
    gchar *sensor = (gchar *) list->data;
    g_free (sensor);
    list = list->next;
  }
  g_list_free (policy->_priv->sensors);
}


/*
 *
 */
void
sim_policy_append_plugin_id (SimPolicy        *policy,
		                         guint            *plugin_id)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_id);
	
  policy->_priv->plugin_ids = g_list_append (policy->_priv->plugin_ids, plugin_id);
}

/*
 * 
 */
void
sim_policy_remove_plugin_id (SimPolicy        *policy,
                             guint            *plugin_id)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_id);

  policy->_priv->plugin_ids = g_list_remove (policy->_priv->plugin_ids, plugin_id);
}

/*
 *
 */
GList*
sim_policy_get_plugin_ids (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->plugin_ids;
}

/*
 *
 *
 *
 */
void
sim_policy_free_plugin_ids (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->plugin_ids;
  while (list)
  {
    guint *plugin_id = (guint *) list->data;
    g_free (plugin_id);
    list = list->next;
  }
  g_list_free (policy->_priv->plugin_ids);
}


/*
 *
 */
void
sim_policy_append_plugin_sid (SimPolicy        *policy,
		                      		guint            *plugin_sid)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_sid);

  policy->_priv->plugin_sids = g_list_append (policy->_priv->plugin_sids, plugin_sid);
}

/*
 * 
 */
void
sim_policy_remove_plugin_sid (SimPolicy        *policy,
	                            guint            *plugin_sid)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_sid);

  policy->_priv->plugin_sids = g_list_remove (policy->_priv->plugin_sids, plugin_sid);
}

/*
 *
 */
GList*
sim_policy_get_plugin_sids (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->plugin_sids;
}

/*
 *
 *
 *
 */
void
sim_policy_free_plugin_sids (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->plugin_sids;
  while (list)
  {
    guint *plugin_sid = (guint *) list->data;
    g_free (plugin_sid);
    list = list->next;
  }
  g_list_free (policy->_priv->plugin_sids);
}

/*
 *
 */
void
sim_policy_append_plugin_group (SimPolicy					 *policy,
																Plugin_PluginSid   *plugin_group)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_group);
	
  policy->_priv->plugin_groups = g_list_append (policy->_priv->plugin_groups, plugin_group);
}

/*
 * 
 */
void
sim_policy_remove_plugin_group (SimPolicy        *policy,
																Plugin_PluginSid   *plugin_group)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_group);

  policy->_priv->plugin_groups = g_list_remove (policy->_priv->plugin_groups, plugin_group);
}

/*
 *
 */
GList*
sim_policy_get_plugin_groups (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->plugin_groups;
}

/*
 *
 *
 *
 */
void
sim_policy_free_plugin_groups (SimPolicy* policy)
{
  GList   *list;
  GList   *list2;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->plugin_groups;
  while (list)
  {
    Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list->data;
		list2 = plugin_group->plugin_sid;
		while (list2)
		{
			gint *plugin_sid = (gint *) list2->data;
			g_free (plugin_sid);
			list2 = list2->next;
		}			
    g_free (plugin_group);
    list = list->next;
  }
  g_list_free (policy->_priv->plugin_groups);
}

/*
 *
 */
void
sim_policy_append_target (SimPolicy        *policy,
		                    	gchar           *target_name)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (target_name);

  policy->_priv->targets = g_list_append (policy->_priv->targets, target_name);
}

/*
 * This removes the link in the list, but not the target's name string in memory. To remove all the data, you'll need to use sim_policy_free_targets()
 */
void
sim_policy_remove_target (SimPolicy        *policy,
                          gchar            *target_name)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (target_name);

  policy->_priv->targets = g_list_remove (policy->_priv->targets, target_name);
}

/*
 *
 */
GList*
sim_policy_get_targets (SimPolicy* policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->targets;
}

/*
 *
 *
 *
 */
void
sim_policy_free_targets (SimPolicy* policy)
{
  GList   *list;
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->targets;
  while (list)
  {
    gchar *target_name = (gchar *) list->data;
    g_free (target_name);
    list = list->next;
  }
  g_list_free (policy->_priv->targets);
}


/*
 *
 *
 *
 */
gboolean
sim_policy_match (SimPolicy        *policy,
								  gint              date,
								  GInetAddr        *src_ia,
								  GInetAddr        *dst_ia,
								  SimPortProtocol  *pp,
									gchar							*sensor,
									guint							plugin_id,
									guint							plugin_sid)
{
  GList     *list;
  gboolean   found = FALSE;

  g_return_val_if_fail (policy, FALSE);
  g_return_val_if_fail (SIM_IS_POLICY (policy), FALSE);
  g_return_val_if_fail (src_ia, FALSE);
  g_return_val_if_fail (dst_ia, FALSE);
  g_return_val_if_fail (pp, FALSE);
  g_return_val_if_fail (sensor, FALSE);
    
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_policy_match, Policy ID: %d", policy->_priv->id);

	//First, test if this policy has to been executed in this server
	found = FALSE;
	list = policy->_priv->targets;
	while (list)	
	{
		gchar *target = (gchar *) list->data;
		gchar *server_aux = sim_server_get_name (ossim.server);
		if (!g_ascii_strcasecmp (target, server_aux) ||
				!g_ascii_strcasecmp (target, SIM_IN_ADDR_ANY_CONST))
		{
			found = TRUE;
			break;
		}
		list = list->next;
	}
  if (!found) return FALSE;

  if(policy->_priv->begin_dayhour <= policy->_priv->end_dayhour)
	{
    if ((policy->_priv->begin_dayhour > date) || (policy->_priv->end_dayhour < date))
	  {
		  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_policy_match: Not match: BAD DATE");
      return FALSE;
	  }
	}
	else
	{
		if ((policy->_priv->begin_dayhour > date) && (policy->_priv->end_dayhour < date))
		{
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_policy_match: Not match: BAD DATE");
    	return FALSE;
		}
	}
			
	
  /* Find source ip*/
  found = FALSE;
  list = policy->_priv->src;

	if (!list)
	  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_policy_match: NO POLICY!!!");
			
  while (list)
  {
    SimInet *cmp = (SimInet *) list->data;

//    gchar *ip_temp = gnet_inetaddr_get_canonical_name(src_ia);
//    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       src_ip: %s", ip_temp);
//    g_free (ip_temp);

		if (sim_inet_is_reserved(cmp)) //check if "any" is the source
 	  {
	    found = TRUE;
		  break;
	  }
		
	  SimInet *src = sim_inet_new_from_ginetaddr(src_ia); //a bit speed improve separating both checks...
		if (sim_inet_has_inet(cmp, src))  //check if src belongs to cmp.
		{
			found=TRUE;
			g_object_unref (src); //------
			break;
		}
		else
			g_object_unref (src); //------

    list = list->next;
  }
	
  if (!found)
	{
//    gchar *ip_temp = gnet_inetaddr_get_canonical_name(src_ia);
//	  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       src_ip: %s Doesn't matches with any", ip_temp);
//	  g_free (ip_temp);
		return FALSE;
	}
//	else {
//    gchar *ip_temp = gnet_inetaddr_get_canonical_name(src_ia);
//	  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       src_ip: %s OK!; Match with policy: %d", ip_temp,policy->_priv->id);
//	  g_free (ip_temp);
//	}

  /* Find destination ip */
  found = FALSE;
  list = policy->_priv->dst;
  while (list)
  {
    SimInet *cmp = (SimInet *) list->data;
	
		/**********DEBUG**************
    //struct sockaddr_in* sa_in = (struct sockaddr_in*) &cmp->_priv->sa;
    struct sockaddr_in* sa_in = (struct sockaddr_storage*) &cmp->_priv->sa;

    guint32 val1 = ntohl (sa_in->sin_addr.s_addr);

    gchar *temp = g_strdup_printf ("%d.%d.%d.%d",
                             (val1 >> 24) & 0xFF,
                             (val1 >> 16) & 0xFF,
                             (val1 >> 8) & 0xFF,
                             (val1) & 0xFF);
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Policy dst: %d bits: %s",cmp->_priv->bits, temp);*/
//    gchar *ip_temp = gnet_inetaddr_get_canonical_name(dst_ia);
//    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       dst_ip: %s", ip_temp);
//    g_free (ip_temp);
//		g_free(temp);

		/**************end debug**************/
	
    if (sim_inet_is_reserved(cmp))
    {
      found = TRUE;
      break;
    }

    SimInet *dst = sim_inet_new_from_ginetaddr(dst_ia);
    if (sim_inet_has_inet(cmp, dst)) 
 	  {
 	    found=TRUE;
	    break;
    }
    
		list = list->next;
  }
	
  if (!found) return FALSE;

  //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       dst_ip MATCH");
	
  /* Find port & protocol */
  found = FALSE;
  list = policy->_priv->ports;
  while (list)
  {
    SimPortProtocol *cmp = (SimPortProtocol *) list->data;
      
    if (sim_port_protocol_equal (cmp, pp))
		{
		  found = TRUE;
		//	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       port MATCH");
	  	break;
		}
    list = list->next;
  }
  if (!found) return FALSE;

	/* Find sensor */
  found = FALSE;
  list = policy->_priv->sensors;
  while (list)
  {
    gchar *cmp = (gchar *) list->data;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       event sensor: -%s-",sensor);
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       policy sensor:-%s-",cmp);
		
    if (!strcmp (sensor, cmp) || !strcmp (cmp, "0")) //if match
		{
		  found = TRUE;
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       sensor MATCH");
	  	break;
		}
    list = list->next;
  }
  if (!found)
	{
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       sensor NOT MATCH");
		return FALSE;
	}
	
  /* Find plugin_groups */
	
  found = FALSE;
  list = policy->_priv->plugin_groups;
  while (list)
  {
    Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list->data;
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               plugin_id: %d",plugin_group->plugin_id);
		gint cmp = plugin_group->plugin_id;
		if (cmp == 0) //if match (0 is ANY)
    {
      found = TRUE;
      break;
    }
		if (plugin_id == cmp) //if match
		{
	    GList *list2 = plugin_group->plugin_sid;
  	  while (list2)
  	 	{
	      gint *aux_plugin_sid = (gint *) list2->data;
				if ((*aux_plugin_sid == plugin_sid) || (*aux_plugin_sid == 0)) //match!
				{
					found = TRUE;
					break;
				}
      	list2 = list2->next;
	    }
		}
  	list = list->next;
  }
  if (!found) return FALSE;

  //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       plugin group MATCH");
	
  return TRUE;
}

void sim_policy_debug_print	(SimPolicy	*policy) 
{
	GList *list;
	gchar	*aux;

	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_policy_debug_print       : policy %x",policy);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               id: %d",policy->_priv->id);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               description: %s",policy->_priv->description);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               begin_day:  %d",policy->_priv->begin_day);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               begin_hour:  %d",policy->_priv->begin_hour);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               begin_dayhour:  %d",policy->_priv->begin_dayhour);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               end_day:  %d",policy->_priv->end_day);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               end_hour:  %d",policy->_priv->end_hour);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               end_dayhour:  %d",policy->_priv->end_dayhour);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               src:         %x",policy->_priv->src);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               dst:         %x",policy->_priv->dst);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               ports:       %x",policy->_priv->ports);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               sensors:     %x",policy->_priv->sensors);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               targets:     %x",policy->_priv->targets);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               plugin_groups: %x",policy->_priv->plugin_groups);
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               priority: %d",policy->_priv->priority);
//	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               plugin_ids:  %x",policy->_priv->plugin_ids);
//	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               plugin_sids: %x",policy->_priv->plugin_sids);

	SimRole *role = sim_policy_get_role (policy);
  if (role)
  	sim_role_print (role);

	list = policy->_priv->src;
	while (list)
	{
		SimInet *HostOrNet = (SimInet *) list->data;
		aux = sim_inet_cidr_ntop (HostOrNet);
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               src:         %s", aux);
		list = list->next;
		g_free (aux);
	}

	list = policy->_priv->dst;
	while (list)
	{
		SimInet *HostOrNet = (SimInet *) list->data;
		aux = sim_inet_cidr_ntop (HostOrNet);
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               dst:         %s", aux);
		list = list->next;
		g_free (aux);
	}


	list = policy->_priv->ports;
	while (list)
	{
		SimPortProtocol *pp = (SimPortProtocol *) list->data;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               port:        %d/%d",pp->port, pp->protocol);
		list = list->next;
	}

	list = policy->_priv->sensors;
	while (list)
	{
		gchar *s = (gchar *) list->data;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               sensor:        %s",s);
		list = list->next;
	}
		

	list = policy->_priv->plugin_groups;
  while (list)
  {
    Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list->data;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               plugin_id: %d",plugin_group->plugin_id);
    GList *list2 = plugin_group->plugin_sid;
    while (list2)
    {
      gint *plugin_sid = (gint *) list2->data;
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               plugin_sids: %d",*plugin_sid);
      list2 = list2->next;
    }
    list = list->next;
	}

	list = policy->_priv->targets;
	while (list)
	{
		gchar *s = (gchar *) list->data;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "                               target:        %s",s);
		list = list->next;
	}
	

}

/*
 * Given a specific policy, it returns the role associated to it.
 */
SimRole *
sim_policy_get_role	(SimPolicy *policy)
{
  g_return_val_if_fail (policy, NULL);
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

	return policy->_priv->role;
}

void
sim_policy_set_role	(SimPolicy *policy,
											SimRole	*role)
{
  g_return_if_fail (policy);
  g_return_if_fail (SIM_IS_POLICY (policy));

	policy->_priv->role = role;
}
// vim: set tabstop=2:
