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

#ifndef __SIM_HOST_LEVEL_H__
#define __SIM_HOST_LEVEL_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>
#include <libgda/libgda.h>

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_HOST_LEVEL             (sim_host_level_get_type ())
#define SIM_HOST_LEVEL(obj)             (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_HOST_LEVEL, SimHostLevel))
#define SIM_HOST_LEVEL_CLASS(klass)     (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_HOST_LEVEL, SimHostLevelClass))
#define SIM_IS_HOST_LEVEL(obj)          (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_HOST_LEVEL))
#define SIM_IS_HOST_LEVEL_CLASS(klass)  (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_HOST_LEVEL))
#define SIM_HOST_LEVEL_GET_CLASS(obj)   (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_HOST_LEVEL, SimHostLevelClass))

G_BEGIN_DECLS

typedef struct _SimHostLevel            SimHostLevel;
typedef struct _SimHostLevelClass       SimHostLevelClass;
typedef struct _SimHostLevelPrivate     SimHostLevelPrivate;

struct _SimHostLevel {
  GObject parent;

  SimHostLevelPrivate *_priv;
};

struct _SimHostLevelClass {
  GObjectClass parent_class;
};

GType             sim_host_level_get_type                        (void);
SimHostLevel*     sim_host_level_new                             (const GInetAddr  *ia,
																																  gint              c,
																																  gint              a);
SimHostLevel*     sim_host_level_new_from_dm                     (GdaDataModel     *dm,
								  gint              row);

GInetAddr*        sim_host_level_get_ia                          (SimHostLevel     *host_level);
void              sim_host_level_set_ia                          (SimHostLevel     *host_level,
																																  const GInetAddr  *ia);

gdouble           sim_host_level_get_c                           (SimHostLevel     *host_level);
void              sim_host_level_set_c                           (SimHostLevel     *host_level,
																																  gdouble           c);
void              sim_host_level_plus_c                          (SimHostLevel     *host_level,
																																  gdouble           c);

gdouble           sim_host_level_get_a                           (SimHostLevel     *host_level);
void              sim_host_level_set_a                           (SimHostLevel     *host_level,
																																  gdouble           a);
void              sim_host_level_plus_a                          (SimHostLevel     *host_level,
																																  gdouble           a);

void              sim_host_level_set_recovery                    (SimHostLevel     *host_level,
																																  gint              recovery);

gchar*            sim_host_level_get_insert_clause               (SimHostLevel     *host_level);
gchar*            sim_host_level_get_update_clause               (SimHostLevel     *host_level);
gchar*            sim_host_level_get_delete_clause               (SimHostLevel     *host_level);
void							sim_host_level_debug_print										 (SimHostLevel		 *host_level);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_HOST_LEVEL_H__ */
