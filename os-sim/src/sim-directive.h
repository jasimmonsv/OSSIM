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

#ifndef __SIM_DIRECTIVE_H__
#define __SIM_DIRECTIVE_H__ 1

#include <glib.h>
#include <glib-object.h>

#include "sim-enums.h"
#include "sim-event.h"
#include "sim-action.h"
#include "sim-rule.h"
#include "sim-directive-group.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_DIRECTIVE                  (sim_directive_get_type ())
#define SIM_DIRECTIVE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_DIRECTIVE, SimDirective))
#define SIM_DIRECTIVE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_DIRECTIVE, SimDirectiveClass))
#define SIM_IS_DIRECTIVE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_DIRECTIVE))
#define SIM_IS_DIRECTIVE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_DIRECTIVE))
#define SIM_DIRECTIVE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_DIRECTIVE, SimDirectiveClass))

G_BEGIN_DECLS

typedef struct _SimDirective        SimDirective;
typedef struct _SimDirectiveClass   SimDirectiveClass;
typedef struct _SimDirectivePrivate SimDirectivePrivate;

struct _SimDirective {
  GObject parent;

  SimDirectivePrivate  *_priv;
};

struct _SimDirectiveClass {
  GObjectClass parent_class;
};

GType             sim_directive_get_type                        (void);
SimDirective*     sim_directive_new                             (void);

gint              sim_directive_get_id                          (SimDirective     *directive);
void              sim_directive_set_id                          (SimDirective     *directive,
								 gint              id);

void		sim_directive_append_group			(SimDirective		*directive,
								 SimDirectiveGroup	*group);
void		sim_directive_remove_group			(SimDirective		*directive,
								 SimDirectiveGroup	*group);
void		sim_directive_free_groups			(SimDirective		*directive);
GList*		sim_directive_get_groups			(SimDirective		*directive);
gboolean	sim_directive_has_group				(SimDirective		*directive,
								 SimDirectiveGroup	*group);

gint              sim_directive_get_backlog_id                  (SimDirective     *directive);
void              sim_directive_set_backlog_id                  (SimDirective     *directive,
								 gint              backlog_id);
gchar*            sim_directive_get_name                        (SimDirective     *directive);
void              sim_directive_set_name                        (SimDirective     *directive,
								 const gchar      *name);
time_t             sim_directive_get_time_out                    (SimDirective     *directive);
void              sim_directive_set_time_out                    (SimDirective     *directive,
								 time_t             time_out);
time_t             sim_directive_get_time_last                   (SimDirective     *directive);
void              sim_directive_set_time_last                   (SimDirective     *directive,
								 time_t             time_out);

GNode*            sim_directive_get_root_node                   (SimDirective     *directive);
void              sim_directive_set_root_node                   (SimDirective     *directive,
								 GNode            *rule_root);
GNode*            sim_directive_get_curr_node                   (SimDirective     *directive);
void              sim_directive_set_curr_node                   (SimDirective     *directive,
								 GNode            *rule_root);

SimRule*          sim_directive_get_root_rule                   (SimDirective     *directive);
SimRule*          sim_directive_get_curr_rule                   (SimDirective     *directive);

gint              sim_directive_get_rule_level                  (SimDirective     *directive);

time_t             sim_directive_get_rule_curr_time_out_max      (SimDirective     *directive);

void              sim_directive_append_action                   (SimDirective     *directive,
								 SimAction        *action);
void              sim_directive_remove_action                   (SimDirective     *directive,
								 SimAction        *action);
GList*            sim_directive_get_actions                     (SimDirective     *directive);
void              sim_directive_free_actions                    (SimDirective     *directive);


gint              sim_directive_get_level                       (SimDirective     *directive);

gboolean          sim_directive_match_by_event                  (SimDirective     *directive,
								 SimEvent         *event);
gboolean          sim_directive_backlog_match_by_event          (SimDirective     *directive,
								 SimEvent         *event);
void              sim_directive_set_rule_vars                   (SimDirective     *directive,
								 GNode            *node);

GNode*            sim_directive_get_node_branch_by_level        (SimDirective     *directive,
								 GNode            *node,
								 gint              level);

gboolean          sim_directive_get_matched                     (SimDirective     *directive);
gboolean          sim_directive_is_time_out                     (SimDirective     *directive);

GNode*            sim_directive_node_data_clone                 (GNode            *node);
void              sim_directive_node_data_destroy               (GNode            *node);
SimDirective*     sim_directive_clone                           (SimDirective     *directive);

gchar*            sim_directive_backlog_get_insert_clause       (SimDirective     *directive);
gchar*            sim_directive_backlog_get_update_clause       (SimDirective     *directive);
gchar*            sim_directive_backlog_get_delete_clause       (SimDirective     *directive);
gchar*            sim_directive_backlog_to_string               (SimDirective     *directive);

gchar*            sim_directive_backlog_event_get_insert_clause (SimDirective     *directive,
								 SimEvent         *event);
void							sim_directive_backlog_get_uuid(SimDirective *directive,uuid_t out);
G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_DIRECTIVE_H__ */

// vim: set tabstop=2:

