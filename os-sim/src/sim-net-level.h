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

#ifndef __SIM_NET_LEVEL_H__
#define __SIM_NET_LEVEL_H__ 1

#include <libgda/libgda.h>

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_NET_LEVEL                  (sim_net_level_get_type ())
#define SIM_NET_LEVEL(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_NET_LEVEL, SimNetLevel))
#define SIM_NET_LEVEL_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_NET_LEVEL, SimNetLevelClass))
#define SIM_IS_NET_LEVEL(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_NET_LEVEL))
#define SIM_IS_NET_LEVEL_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_NET_LEVEL))
#define SIM_NET_LEVEL_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_NET_LEVEL, SimNetLevelClass))

G_BEGIN_DECLS

typedef struct _SimNetLevel        SimNetLevel;
typedef struct _SimNetLevelClass   SimNetLevelClass;
typedef struct _SimNetLevelPrivate SimNetLevelPrivate;

struct _SimNetLevel {
  GObject parent;

  SimNetLevelPrivate *_priv;
};

struct _SimNetLevelClass {
  GObjectClass parent_class;
};

GType             sim_net_level_get_type                        (void);
SimNetLevel*      sim_net_level_new                             (const gchar   *name,
																																 gint           c,
																																 gint           a);
SimNetLevel*      sim_net_level_new_from_dm                     (GdaDataModel  *dm,
																																 gint           row);

gchar*            sim_net_level_get_name                        (SimNetLevel   *net_level);
void              sim_net_level_set_name                        (SimNetLevel   *net_level,
																																 const gchar   *name);

gdouble           sim_net_level_get_c                           (SimNetLevel   *net_level);
void              sim_net_level_set_c                           (SimNetLevel   *net_level,
																																 gdouble        c);
void              sim_net_level_plus_c                          (SimNetLevel   *net_level,
																																 gdouble        c);

gdouble           sim_net_level_get_a                           (SimNetLevel   *net_level);
void              sim_net_level_set_a                           (SimNetLevel   *net_level,
																																 gdouble        a);
void              sim_net_level_plus_a                          (SimNetLevel   *net_level,
																																 gdouble        a);

void              sim_net_level_set_recovery                    (SimNetLevel   *net_level,
																																 gint           recovery);
gchar*            sim_net_level_get_insert_clause               (SimNetLevel   *net_level);
gchar*            sim_net_level_get_update_clause               (SimNetLevel   *net_level);
gchar*            sim_net_level_get_delete_clause               (SimNetLevel   *net_level);
void							sim_net_level_debug_print											(SimNetLevel  *net_level);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_NET_LEVEL_H__ */
// vim: set tabstop=2:
