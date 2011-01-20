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
#include <uuid/uuid.h>
#include "os-sim.h"
#include "sim-directive.h"
#include "sim-rule.h"
#include "sim-action.h"
#include "sim-inet.h"
#include <config.h>

#include <time.h>

enum 
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimDirectivePrivate {
  guint      backlog_id;
	uuid_t		 uuid;	
  gint       id;
  gchar     *name;

  gint       priority;

  gboolean   matched;	//this is filled in the last level of the directive

  time_t      time_out;
  gint64     time;
  time_t      time_last;

  GNode     *rule_root; //this is tested in sim_rule_match_by_event. It's a SimRule.
  GNode     *rule_curr;

  GList		*groups;
};

static gpointer parent_class = NULL;
static gint sim_server_signals[LAST_SIGNAL] = { 0 };

extern SimMain ossim;
static void sim_directive_db_delete_backlog_by_id_ul (guint32      backlog_id);
static void sim_directive_delete_database_backlog(SimDirective *backlog);

/* GType Functions */

static void 
sim_directive_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_directive_impl_finalize (GObject  *gobject)
{
  SimDirective *directive = SIM_DIRECTIVE (gobject);
	sim_directive_delete_database_backlog (directive);

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_impl_finalize: Id %u, Name %s, BacklogId %u, Match %d", 
	 directive->_priv->id, directive->_priv->name, directive->_priv->backlog_id, directive->_priv->matched);

  if (directive->_priv->name)
    g_free (directive->_priv->name);

  sim_directive_node_data_destroy (directive->_priv->rule_root);
  g_node_destroy (directive->_priv->rule_root);

  g_free (directive->_priv);
  
  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_directive_class_init (SimDirectiveClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_directive_impl_dispose;
  object_class->finalize = sim_directive_impl_finalize;
}

static void
sim_directive_instance_init (SimDirective *directive)
{
  directive->_priv = g_new0 (SimDirectivePrivate, 1);

  //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_instance_init");

  directive->_priv->backlog_id = 0;

  directive->_priv->id = 0;
  directive->_priv->name = NULL;

  directive->_priv->time_out = 300;
  directive->_priv->time = 0;
  directive->_priv->time_last = 0;

  directive->_priv->priority = 0;
  directive->_priv->matched = FALSE;

  directive->_priv->rule_root = NULL;
  directive->_priv->rule_curr = NULL;

  directive->_priv->groups = NULL;
}

/* Public Methods */

GType
sim_directive_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimDirectiveClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_directive_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimDirective),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_directive_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimDirective", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimDirective*
sim_directive_new (void)
{
  SimDirective *directive = NULL;

  directive = SIM_DIRECTIVE (g_object_new (SIM_TYPE_DIRECTIVE, NULL));

  return directive;
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_id (SimDirective   *directive)
{
  g_return_val_if_fail (directive, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->id;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_id (SimDirective   *directive,
			   gint            id)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (id > 0);

  directive->_priv->id = id;
}

/*
 *
 *
 *
 *
 */
void
sim_directive_append_group (SimDirective	*directive,
			    SimDirectiveGroup	*group)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (group);
  g_return_if_fail (SIM_IS_DIRECTIVE_GROUP (group));

  directive->_priv->groups = g_list_append (directive->_priv->groups, group);
}

/*
 *
 *
 *
 *
 */
void
sim_directive_remove_group (SimDirective	*directive,
			    SimDirectiveGroup	*group)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (group);
  g_return_if_fail (SIM_IS_DIRECTIVE_GROUP (group));

  directive->_priv->groups = g_list_remove (directive->_priv->groups, group);
}

/*
 *
 *
 *
 *
 */
void
sim_directive_free_groups (SimDirective		*directive)
{
  GList	*list;

  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  list = directive->_priv->groups;
  while (list)
    {
      SimDirectiveGroup	*group = (SimDirectiveGroup *) list->data;
      g_object_unref (group);
      list = list->next;
    }
  g_list_free (directive->_priv->groups);
}

/*
 *
 *
 *
 *
 */
GList*
sim_directive_get_groups (SimDirective		*directive)
{
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->groups;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_directive_has_group	(SimDirective		*directive,
			 SimDirectiveGroup	*group)
{
  GList	*list;

  g_return_val_if_fail (directive, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (group, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE_GROUP (group), FALSE);

  list = directive->_priv->groups;
  while (list)
    {
      SimDirectiveGroup *cmp = (SimDirectiveGroup *) list->data;

      if (cmp == group)
	return TRUE;

      list = list->next;
    }

  return FALSE;
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_backlog_id (SimDirective   *directive)
{
  g_return_val_if_fail (directive, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->backlog_id;
}

/*
 *
 *
 *
 *
 */
void
sim_directive_set_backlog_id (SimDirective   *directive,
			      gint            backlog_id)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (backlog_id > 0);

  directive->_priv->backlog_id = backlog_id;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_directive_get_name (SimDirective   *directive)
{
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->name;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_name (SimDirective   *directive,
			     const gchar    *name)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (name);

  if (directive->_priv->name)
    g_free (directive->_priv->name);

  directive->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_priority (SimDirective   *directive)
{
  g_return_val_if_fail (directive != NULL, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  if (directive->_priv->priority <= 0)
    return 0;
  if (directive->_priv->priority >= 5)
    return 5;

  return directive->_priv->priority;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_priority (SimDirective   *directive,
				 gint            priority)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  if (priority < 0)
    directive->_priv->priority = 0;
  else if (priority > 5)
    directive->_priv->priority = 5;
  else
    directive->_priv->priority = priority;
}

/*
 *
 *
 *
 *
 */
time_t
sim_directive_get_time_out (SimDirective   *directive)
{
  g_return_val_if_fail (directive, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->time_out;
}

/*
 *
 *
 *
 *
 */
void 
sim_directive_set_time_out (SimDirective   *directive,
			    time_t           time_out)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (time_out >= 0);

  directive->_priv->time_out = time_out;
}

/*
 *
 *
 *
 *
 */
time_t
sim_directive_get_time_last (SimDirective   *directive)
{
  g_return_val_if_fail (directive, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->time_last;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_time_last (SimDirective   *directive,
				  time_t           time_last)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (time_last >= 0);

  directive->_priv->time_out = time_last;
}

/*
 *
 *
 *
 *
 */
GNode*
sim_directive_get_root_node (SimDirective  *directive)
{
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->rule_root;
}

/*
 *
 *
 *
 *
 */
void
sim_directive_set_root_node (SimDirective  *directive,
			     GNode         *root_node)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (root_node);

  directive->_priv->rule_root = root_node;
}

/*
 *
 *
 *
 *
 */
GNode*
sim_directive_get_curr_node (SimDirective  *directive)
{
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->rule_curr;
}

/*
 *
 *
 *
 *
 */
void
sim_directive_set_curr_node (SimDirective  *directive,
			     GNode         *curr_node)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (curr_node);

  directive->_priv->rule_curr = curr_node;
}

/*
 *
 *
 *
 *
 */
SimRule*
sim_directive_get_root_rule (SimDirective  *directive)
{
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (directive->_priv->rule_root, NULL);
  g_return_val_if_fail (directive->_priv->rule_root->data, NULL);

  return (SimRule *) directive->_priv->rule_root->data;
}

/*
 *
 *
 *
 *
 */
SimRule*
sim_directive_get_curr_rule (SimDirective  *directive)
{
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (directive->_priv->rule_curr, NULL);
  g_return_val_if_fail (directive->_priv->rule_curr->data, NULL);

  return (SimRule *) directive->_priv->rule_curr->data;
}

/*
 *
 *
 *
 *
 */
time_t
sim_directive_get_rule_curr_time_out_max (SimDirective  *directive)
{
  GNode  *node;
  time_t   time_out = 0;

  g_return_val_if_fail (directive, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);
  g_return_val_if_fail (directive->_priv->rule_curr, 0);

  node = directive->_priv->rule_curr->children;

  while (node)
    {
      SimRule *rule = (SimRule *) node->data;
      time_t   time = sim_rule_get_time_out (rule);

      if (!time)
	return 0;

      if (time > time_out)
	time_out = time;

      node = node->next;
    }

  return time_out;
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_rule_level (SimDirective   *directive)
{
  g_return_val_if_fail (directive, 0);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);
  g_return_val_if_fail (directive->_priv->rule_curr, 0);

  return g_node_depth (directive->_priv->rule_curr);
}

/*
 *
 * We want to know if the directive match with the root node directive.
 * We only check this against the root node. Here we don't check the children nodes of the directive
 *
 */
gboolean
sim_directive_match_by_event (SimDirective  *directive,
												      SimEvent      *event)
{
  SimRule *rule;
  gboolean match;

  g_return_val_if_fail (directive, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);
  g_return_val_if_fail (directive->_priv->rule_root, FALSE);
  g_return_val_if_fail (directive->_priv->rule_root->data, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (directive->_priv->rule_root->data), FALSE);
  g_return_val_if_fail (event, FALSE);
  g_return_val_if_fail (SIM_IS_EVENT (event), FALSE);

  rule = SIM_RULE (directive->_priv->rule_root->data);

  match = sim_rule_match_by_event (rule, event); 

  return match;
}

/*
 *
 * This will check if an event can match with some of the data in backlog. the backlog is in fact 
 * one directive with data from events.
 * 
 * Each backlog entry is a tree with all the rules from a directive (is a directive clone). And 
 * each one of those rules (SimRule) contains also the data from the event that matched with the rule.
 */
gboolean
sim_directive_backlog_match_by_event (SimDirective  *directive,
																      SimEvent    *event)
{
  GNode      *node = NULL;

  g_return_val_if_fail (directive, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);
  g_return_val_if_fail (directive->_priv->rule_curr, FALSE);
  g_return_val_if_fail (directive->_priv->rule_curr->data, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (directive->_priv->rule_curr->data), FALSE);
  g_return_val_if_fail (event, FALSE);
  g_return_val_if_fail (SIM_IS_EVENT (event), FALSE);

  node = directive->_priv->rule_curr->children;
  while (node)		//we have to check the event against all the rule nodes from backlog 
									//(except the root_node because it's checked in sim_directive_match_by_event 
									//wich is called from sim_organizer_correlation).
  {
    SimRule *rule = (SimRule *) node->data;
    
    if (sim_rule_match_by_event (rule, event))
		{
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_backlog_match_by_event; sim_rule_match_by_event: True");
		  time_t time_last = time (NULL);
			directive->_priv->rule_curr = node;		//each time that the event matches, the directive goes down one level to 
																						//the node that matched. next time, the event will be checked against this level
																						//FIXME: may be that there are a memory leak in the parent node? 
			event->rulename = g_strdup  (sim_rule_get_name(rule));
		  directive->_priv->time_last = time_last;
		  directive->_priv->time_out = sim_directive_get_rule_curr_time_out_max (directive);

			sim_rule_set_event_data (rule, event);		//here we asign the data from event to the fields in the rule,
																								//so each time we enter into the rule we can see the event that matched
		  sim_rule_set_time_last (rule, time_last);

		  if (!G_NODE_IS_LEAF (node))
	    {
	      GNode *children = node->children;
	      while (children)
				{
				  SimRule *rule_child = (SimRule *) children->data;

				  sim_rule_set_time_last (rule_child, time_last);

				  sim_directive_set_rule_vars (directive, children);
				  children = children->next;
					g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_backlog_match_by_event: There are childrens in %d directive", directive->_priv->id);
				}
			}
		  else
		  {
			  directive->_priv->matched = TRUE;
				g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_backlog_match_by_event: The directive %d has matched", directive->_priv->id);
		  }

		  return TRUE;
		}
		else
		{
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_backlog_match_by_event: sim_rule_match_by_event: False");
		}

	  node = node->next;
	}

  return FALSE;
}

/*
 * Check all the nodes (rules) in the directive to see if.......
 *
 *
 */
gboolean
sim_directive_backlog_match_by_not (SimDirective  *directive)
{
  GNode      *node = NULL;
  GNode      *children = NULL;

  g_return_val_if_fail (directive, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);
  g_return_val_if_fail (directive->_priv->rule_curr, FALSE);
  g_return_val_if_fail (directive->_priv->rule_curr->data, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (directive->_priv->rule_curr->data), FALSE);

  node = directive->_priv->rule_curr->children;

  while (node) 
  {
    SimRule *rule = (SimRule *) node->data;
		//if the rule is timeouted &&       
    if ((sim_rule_is_time_out (rule)) && (sim_rule_get_not (rule)) && (!sim_rule_is_not_invalid (rule))) 
		{
		  time_t time_last = time (NULL);
	  	directive->_priv->rule_curr = node;
		  directive->_priv->time_last = time_last;
		  directive->_priv->time_out = sim_directive_get_rule_curr_time_out_max (directive);

	  	sim_rule_set_not_data (rule);

		  if (!G_NODE_IS_LEAF (node)) //this isn't the last node, it has some children
	    {
	      children = node->children;
	      while (children)
				{
		  		SimRule *rule_child = (SimRule *) children->data;

				  sim_rule_set_time_last (rule_child, time_last);

				  sim_directive_set_rule_vars (directive, children);
				  children = children->next;
				}
	    }
	  	else //last node!
	    {
	      directive->_priv->matched = TRUE;
	    }
	  
	  	return TRUE;
		}
    node = node->next;
  }

  return FALSE;
}

/*
 * backlog & directives is almost the same: backlog is where directives are stored and filled with data from events.
 * 
 * The "node" function parameter is a children node. We need to add to that node the src_ip, port, etc from the
 * node whose level is referenced. ie. if "node" parameter is the children2 in root_node->children1->children2, and we
 * have something like 1:PLUGIN_SID in children2, we have to add the plugin_sid from root_node to children2
 *
 */
void
sim_directive_set_rule_vars (SimDirective     *directive,
												     GNode            *node)
{
  SimRule    *rule;
  SimRule    *rule_up;
  GNode      *node_up;
  GList      *vars;
  GInetAddr  *ia;
  GInetAddr  *sensor;
  gint        port;
  gint        sid;
  SimProtocolType  protocol;
	gchar				*aux = NULL;

  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (node);
  g_return_if_fail (g_node_depth (node) > 1);

  rule = (SimRule *) node->data;
  vars = sim_rule_get_vars (rule);	

  while (vars)	//just in case there are vars (ie. 1:PLUGIN_SID or 2:SRC_IP) in the rule.
  {
    SimRuleVar *var = (SimRuleVar *) vars->data;

		//now we need to know the node to wich is referencing the level in the SimRuleVar. 
		node_up = sim_directive_get_node_branch_by_level (directive, node, var->level); 
    if (!node_up)
		{
		  vars = vars->next;
			continue;
		}

		rule_up = (SimRule *) node_up->data;
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,"%s: Directive id:%u",__FUNCTION__,sim_directive_get_id (directive));
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_vars: rule name: %s",sim_rule_get_name(rule));					
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_vars: type: %d",var->type);
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_vars: attr: %d",var->attr);
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "%s: Variable index:%u",__FUNCTION__,var->varIndex);
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_vars: negated: %d",var->negated);
		
		
		//"node" function parameter is a children node. We need to add to that node the src_ip, port, etc from the
		//node whose level is referenced. ie. if this is the children2 in root_node->children1->children2, and we
		//have something like 1:PLUGIN_SID in children2, we have to add the plugin_sid from root_node to children2
		switch (var->type)
		{
			case SIM_RULE_VAR_SRC_IA:
						ia = sim_rule_get_src_ia (rule_up);

					  switch (var->attr)	
				    {										
					    case SIM_RULE_VAR_SRC_IA:
										if (var->negated)
									    sim_rule_append_src_inet_not (rule, sim_inet_new_from_ginetaddr (ia));
										else
									    sim_rule_append_src_inet (rule, sim_inet_new_from_ginetaddr (ia));
							      break;
					    case SIM_RULE_VAR_DST_IA:
										if (var->negated)
											sim_rule_append_dst_inet_not (rule, sim_inet_new_from_ginetaddr (ia));
										else
											sim_rule_append_dst_inet (rule, sim_inet_new_from_ginetaddr (ia));
							      break;
					    default:
							      break;
						}
					  break;

			case SIM_RULE_VAR_DST_IA:
						ia = sim_rule_get_dst_ia (rule_up);

						switch (var->attr)
						{
							case SIM_RULE_VAR_SRC_IA:
										if (var->negated)																				
											sim_rule_append_src_inet_not (rule, sim_inet_new_from_ginetaddr  (ia));
										else
											sim_rule_append_src_inet (rule, sim_inet_new_from_ginetaddr  (ia));
										break;
							case SIM_RULE_VAR_DST_IA:
										if (var->negated)																					
											sim_rule_append_dst_inet_not (rule, sim_inet_new_from_ginetaddr  (ia));
										else
											sim_rule_append_dst_inet (rule, sim_inet_new_from_ginetaddr  (ia));
										break;
							default:
										break;
						}
						break;

			case SIM_RULE_VAR_SRC_PORT:
						port = sim_rule_get_src_port (rule_up);

						switch (var->attr)
						{
							case SIM_RULE_VAR_SRC_PORT:
	                  if (var->negated)																			
											sim_rule_append_src_port_not (rule, port);
										else
											sim_rule_append_src_port (rule, port);
										break;
							case SIM_RULE_VAR_DST_PORT:
                    if (var->negated)
											sim_rule_append_dst_port_not (rule, port);
										else											
											sim_rule_append_dst_port (rule, port);
										break;
							default:
										break;
						}
						break;
	
			case SIM_RULE_VAR_DST_PORT:
						port = sim_rule_get_dst_port (rule_up);
						
/*-------------
g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_var1");
sim_rule_print(rule);
						g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_vars: negated: %d",var->negated);
-------------*/
			
						switch (var->attr)
						{
							case SIM_RULE_VAR_SRC_PORT:
                    if (var->negated)
											sim_rule_append_src_port_not (rule, port);
										else
											sim_rule_append_src_port (rule, port);
										break;
							case SIM_RULE_VAR_DST_PORT:
                    if (var->negated)
											sim_rule_append_dst_port_not (rule, port);
										else											
											sim_rule_append_dst_port (rule, port);
										break;
							default:
										break;
						}
						break;

/*-------------
g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_set_rule_var2");
sim_rule_print(rule);
-------------*/
	
			case SIM_RULE_VAR_PLUGIN_SID:
						sid = sim_rule_get_plugin_sid (rule_up);
            if (var->negated)
							sim_rule_append_plugin_sid_not (rule, sid);
						else
							sim_rule_append_plugin_sid (rule, sid);
						break;

			case SIM_RULE_VAR_PROTOCOL:
						protocol = sim_rule_get_protocol (rule_up);
            if (var->negated)
							sim_rule_append_protocol_not (rule, protocol);
						else
							sim_rule_append_protocol (rule, protocol);
						break;

      case SIM_RULE_VAR_SENSOR:
            sensor = sim_rule_get_sensor (rule_up);
						aux = gnet_inetaddr_get_canonical_name (sensor);
            if (var->negated)
							sim_rule_append_sensor_not (rule, sim_sensor_new_from_hostname(aux));
						else
							sim_rule_append_sensor (rule, sim_sensor_new_from_hostname(aux));
            break;
			case SIM_RULE_VAR_GENERIC_TEXT:
						aux = g_strdup (sim_rule_get_text_matched (rule_up,var->varIndex));
						sim_rule_set_match_text (rule, var->attr, aux,var->negated);
						g_log (G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"%s: Setting var->type = %u Target:%u Source:%u",
							__FUNCTION__, var->type,var->attr,var->varIndex);
						g_free (aux);
			
						break;
			
#if 0
			case SIM_RULE_VAR_FILENAME:
            aux = g_strdup (sim_rule_get_filename (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_FILENAME);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_FILENAME);
            break;
						
			case SIM_RULE_VAR_USERNAME:
            aux = g_strdup (sim_rule_get_username (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERNAME);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERNAME);
            break;

			case SIM_RULE_VAR_PASSWORD:
            aux = g_strdup (sim_rule_get_password (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_PASSWORD);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_PASSWORD);
            break;

			case SIM_RULE_VAR_USERDATA1:
            aux = g_strdup (sim_rule_get_userdata1 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA1);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA1);
            break;

			case SIM_RULE_VAR_USERDATA2:
            aux = g_strdup (sim_rule_get_userdata2 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA2);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA2);
            break;

			case SIM_RULE_VAR_USERDATA3:
            aux = g_strdup (sim_rule_get_userdata3 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA3);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA3);
            break;

			case SIM_RULE_VAR_USERDATA4:
            aux = g_strdup (sim_rule_get_userdata4 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA4);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA4);
            break;

			case SIM_RULE_VAR_USERDATA5:
            aux = g_strdup (sim_rule_get_userdata5 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA5);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA5);
            break;

			case SIM_RULE_VAR_USERDATA6:
            aux = g_strdup (sim_rule_get_userdata6 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA6);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA6);
            break;

			case SIM_RULE_VAR_USERDATA7:
            aux = g_strdup (sim_rule_get_userdata7 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA7);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA7);
            break;

			case SIM_RULE_VAR_USERDATA8:
            aux = g_strdup (sim_rule_get_userdata8 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA8);
						else
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA8);
            break;

			case SIM_RULE_VAR_USERDATA9:
            aux = g_strdup (sim_rule_get_userdata9 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_not (rule, aux, SIM_RULE_VAR_USERDATA9);
						else	
							sim_rule_append_generic (rule, aux, SIM_RULE_VAR_USERDATA9);
            break;
#endif

			default:
						break;
		}

    vars = vars->next;
  }
}

/*
 * This function returns the node to wich is referencing the directive level when you say something like "1:SRC_IP".
 * Take for example: root_node->children1->children2. If the "node" parameter in the function is children2, and the
 * level is 1, then this will return the root_node, as it's the 1st level of the children.
 */
GNode*
sim_directive_get_node_branch_by_level (SimDirective     *directive,
																				GNode            *node,
																				gint              level)
{
  GNode  *ret;
  gint    up_level;
  gint    i;
	
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (node, NULL);
  
  up_level = g_node_depth (node) - level;	//The root node has a depth of 1.For the children of the root node the depth is 2
  if (up_level < 1)
    return NULL;

  ret = node;
  for (i = 0; i < up_level; i++)
  {
    ret = ret->parent;
  }
  
  return ret;
}

/*
 *
 *
 *
 */
void
sim_directive_set_matched (SimDirective     *directive,
			   gboolean          matched)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  directive->_priv->matched = matched;
}


/*
 *
 *
 *
 */
gboolean
sim_directive_get_matched (SimDirective     *directive)
{
  g_return_val_if_fail (directive, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);

  return directive->_priv->matched;
}

/**
 * sim_directive_is_time_out:
 * @directive: a #SimDirective.
 *
 * Look if the #SimDirective is time out
 *
 * Returns: TRUE if is time out, FALSE otherwise.
 */
gboolean
sim_directive_is_time_out (SimDirective     *directive)
{
  g_return_val_if_fail (directive, FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);

	if (directive->_priv->matched) // If the directive has matched, then it should be removed.
		return TRUE;

  if ((!directive->_priv->time_out) || (!directive->_priv->time_last))	//if directive hasn't got any time, this
    return FALSE;																												//is the 1st time it enteres here, so no timeout.

  if (time (NULL) > (directive->_priv->time_last + directive->_priv->time_out))
    return TRUE;

  return FALSE;
}

/*
 *
 *
 *
 */
GNode*
sim_directive_node_data_clone (GNode *node)
{
  SimRule  *new_rule;
  GNode    *new_node;
  GNode    *child;

  g_return_val_if_fail (node, NULL);
  g_return_val_if_fail (node->data, NULL);
  g_return_val_if_fail (SIM_IS_RULE (node->data), NULL);

  new_rule = sim_rule_clone (SIM_RULE (node->data));
  new_node = g_node_new (new_rule);
  
  for (child = g_node_last_child (node); child; child = child->prev)
    g_node_prepend (new_node, sim_directive_node_data_clone (child));
   
  return new_node;
}

/*
 *
 *
 *
 */
void
sim_directive_node_data_destroy (GNode *node)
{
  GNode   *child;
  
  g_return_if_fail (node);
  g_return_if_fail (node->data);
  g_return_if_fail (SIM_IS_RULE (node->data));

  g_object_unref (SIM_RULE (node->data));
  
  for (child = g_node_last_child (node); child; child = child->prev)
    sim_directive_node_data_destroy (child);
}

/*
 *
 *
 *
 */
SimDirective*
sim_directive_clone (SimDirective     *directive)
{
  SimDirective     *new_directive;
  GTimeVal          curr_time;

  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  g_get_current_time (&curr_time);

  new_directive = SIM_DIRECTIVE (g_object_new (SIM_TYPE_DIRECTIVE, NULL));

  new_directive->_priv->id = directive->_priv->id;
  new_directive->_priv->name = g_strdup (directive->_priv->name);
  new_directive->_priv->priority = directive->_priv->priority;

  new_directive->_priv->rule_root = sim_directive_node_data_clone (directive->_priv->rule_root);
  new_directive->_priv->rule_curr = new_directive->_priv->rule_root;

  new_directive->_priv->time_out = directive->_priv->time_out;
  new_directive->_priv->time = ((gint64) curr_time.tv_sec * (gint64) G_USEC_PER_SEC) + (gint64) curr_time.tv_usec;
  new_directive->_priv->time_last = curr_time.tv_sec;
  new_directive->_priv->time_out = sim_directive_get_rule_curr_time_out_max (new_directive);

  new_directive->_priv->matched = directive->_priv->matched;

  return new_directive;
}

/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_get_insert_clause (SimDirective *directive)
{
  gchar    timestamp[TIMEBUF_SIZE];
  gchar    *query;
  time_t    time;
	gchar 		uuidtext[37];

  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  time = (time_t) (directive->_priv->time / G_USEC_PER_SEC);
  strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime (&time));
	/* XXX Corregir esto. No acceder a campos privados*/
	uuid_unparse_upper(directive->_priv->uuid,uuidtext);
  query = g_strdup_printf ("INSERT INTO backlog "
			   "(id, directive_id, timestamp, matched,uuid) "
			   "VALUES (%d, %d, '%s', %d,'%s')",
         directive->_priv->backlog_id,
			   directive->_priv->id,
			   timestamp,
			   directive->_priv->matched,
				 (!uuid_is_null(directive->_priv->uuid)? uuidtext:""));

  return query;
}

/*
 *
 *
 */
gchar*
sim_directive_backlog_get_update_clause (SimDirective *directive)
{
  gchar    *query;

  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  query = g_strdup_printf ("UPDATE backlog SET matched = %d"
			   " WHERE id = %u",
			   directive->_priv->matched,
			   directive->_priv->backlog_id);

  return query;
}


/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_get_delete_clause (SimDirective *directive)
{
  gchar *query;

  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  query = g_strdup_printf ("DELETE FROM backlog WHERE id = %u",
			   directive->_priv->backlog_id);

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_event_get_insert_clause (SimDirective *directive,
					    															   SimEvent     *event)
{
  gchar    *query;
  
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (event, NULL);
  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);
	gchar uuidtext[37];
	gchar uuidtext_backlog[37];
	uuid_unparse_upper(event->uuid,uuidtext);
	/* XXX Don't access to private fields. Fix that*/
	uuid_unparse_upper(directive->_priv->uuid,uuidtext_backlog);  
  query = g_strdup_printf ("INSERT INTO backlog_event"
			   " (backlog_id, event_id, time_out,"
			   " occurrence, rule_level, matched,uuid,uuid_event)"
			   " VALUES (%u, %u, %d, %d, %d, %d,\'%s\',\'%s\')",
			   directive->_priv->backlog_id,
			   event->id,
			   directive->_priv->time_out,
			   event->count,
			   event->level,
			   event->matched,
				 uuidtext_backlog,
				 uuidtext);

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_event_get_delete_clause (SimDirective *directive,
					       SimEvent     *event)
{
  gchar    *query;
  
  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (event, NULL);
  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);
  
  query = g_strdup_printf ("DELETE FROM backlog_event WHERE backlog_id ="
			   " (backlog_id, event_id, time_out,"
			   " occurrence, rule_level, matched)"
			   " VALUES (%u, %u, %d, %d, %d, %d)",
			   directive->_priv->backlog_id,
			   event->id,
			   directive->_priv->time_out,
			   event->count,
			   event->level,
			   event->matched);

  return query;
}


/*
 *
 *
 *
 */
void
sim_directive_print (SimDirective  *directive)
{
  g_return_if_fail (directive);
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  g_print ("DIRECTIVE: name=\"%s\"\n", directive->_priv->name);
}

/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_to_string (SimDirective  *directive)
{
  GString  *str, *vals;
  GNode    *node;
  gchar    *val;

  g_return_val_if_fail (directive, NULL);
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (directive->_priv->rule_curr, NULL);

  str = g_string_sized_new (0);
  g_string_append_printf (str, "%s, Priority: %d\n",
												  directive->_priv->name,
												  directive->_priv->priority);

  vals = g_string_sized_new (0);
  node = directive->_priv->rule_curr;
  while (node)
  {
    SimRule *rule = (SimRule *) node->data;
      
    if ((val = sim_rule_to_string (rule)))
		{
		  g_string_prepend (vals, val);
	  	g_free (val);
		}

    node = node->parent;
  }

  g_string_append (str, vals->str);
  g_string_free (vals, TRUE);

  return g_string_free (str, FALSE);
}

gboolean sim_directive_backlog_set_uuid(SimDirective *directive){
	g_return_val_if_fail (directive, FALSE);
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	uuid_generate(directive->_priv->uuid);
	return TRUE;
}

void sim_directive_backlog_get_uuid(SimDirective *directive,uuid_t out){
	uuid_copy(out,directive->_priv->uuid);
}

void static sim_directive_delete_database_backlog(SimDirective *backlog){
	g_return_if_fail (backlog);
	g_return_if_fail (SIM_IS_DIRECTIVE (backlog));
	guint   backlog_id = backlog->_priv->backlog_id;
	gchar *query;
	GdaDataModel *dm;
	g_log (G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"%s:Deleting backlog: %u",__FUNCTION__,backlog_id);
	query = g_strdup_printf ("SELECT backlog_id FROM alarm WHERE backlog_id = %u", backlog_id);
	dm = sim_database_execute_single_command (ossim.dbossim, query);
	if (dm)
	{
	if (!gda_data_model_get_n_rows (dm))
		sim_directive_db_delete_backlog_by_id_ul (backlog_id);
	g_object_unref(dm);
	}
	else
	g_message ("BACKLOG DELETE DATA MODEL ERROR");
	g_free (query);
}

static void
sim_directive_db_delete_backlog_by_id_ul (guint32	backlog_id)
{
  GdaDataModel	*dm;
	GdaDataModel	*dm1=NULL;
  GdaValue	*value;
	gchar		*query0;
	gchar		*query1;
	gchar		*query2;
	guint32	event_id;
	gint		row, count;
	query0 =  g_strdup_printf ("SELECT event_id FROM backlog_event WHERE backlog_id = %u",
						backlog_id);
	dm = sim_database_execute_single_command (ossim.dbossim, query0);
	if (dm)
	{
		for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
		{
			value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
			event_id = gda_value_get_bigint (value);

			query1 = g_strdup_printf ("SELECT COUNT(event_id) FROM backlog_event WHERE event_id = %u", event_id);
			//g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_container_db_delete_backlog_by_id_ul: event_id= %lu",event_id);
			dm1 = sim_database_execute_single_command (ossim.dbossim, query1);
			if (dm1)
			{
				//g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "query1: %s",query1);

				value = (GdaValue *) gda_data_model_get_value_at (dm1, 0, 0);						
				count = gda_value_get_bigint (value);

				//g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "count: %d",count);
	  
				if (count == 1)
				{
					query2 = g_strdup_printf ("DELETE FROM event WHERE id = %u", event_id);
					sim_database_execute_no_query (ossim.dbossim, query2);
					g_free (query2);
				}

				g_object_unref(dm1);
			}
			else
				g_message("Error: problem executing the following command in the DB: %s",query1);
			g_free (query1);
		}
		g_object_unref(dm);
	}
	g_free (query0);

	query0 = g_strdup_printf ("DELETE FROM backlog_event WHERE backlog_id = %u", backlog_id);
	sim_database_execute_no_query (ossim.dbossim, query0);
	g_free (query0);
  
	query0 = g_strdup_printf ("DELETE FROM backlog WHERE id = %u", backlog_id);
	sim_database_execute_no_query (ossim.dbossim, query0);
	g_free (query0);
}




// vim: set tabstop=2:

