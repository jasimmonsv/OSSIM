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

#ifndef __SIM_CATEGORY_H__
#define __SIM_CATEGORY_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <libgda/libgda.h>

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_CATEGORY                  (sim_category_get_type ())
#define SIM_CATEGORY(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_CATEGORY, SimCategory))
#define SIM_CATEGORY_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_CATEGORY, SimCategoryClass))
#define SIM_IS_CATEGORY(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_CATEGORY))
#define SIM_IS_CATEGORY_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_CATEGORY))
#define SIM_CATEGORY_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CATEGORY, SimCategoryClass))

G_BEGIN_DECLS

typedef struct _SimCategory          SimCategory;
typedef struct _SimCategoryClass     SimCategoryClass;
typedef struct _SimCategoryPrivate   SimCategoryPrivate;

struct _SimCategory {
  GObject parent;

  SimCategoryPrivate *_priv;
};

struct _SimCategoryClass {
  GObjectClass parent_class;
};

GType             sim_category_get_type                    (void);
SimCategory*      sim_category_new                         (void);
SimCategory*      sim_category_new_from_data               (gint              id,
							    const gchar      *name);
SimCategory*      sim_category_new_from_dm                 (GdaDataModel     *dm,
							    gint              row);
  
gint              sim_category_get_id                      (SimCategory      *category);
void              sim_category_set_id                      (SimCategory      *category,
							    gint              id);
gchar*            sim_category_get_name                    (SimCategory      *category);
void              sim_category_set_name                    (SimCategory      *category,
							    const gchar      *name);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_CATEGORY_H__ */

// vim: set tabstop=2:

