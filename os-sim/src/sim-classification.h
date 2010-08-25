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

#ifndef __SIM_CLASSIFICATION_H__
#define __SIM_CLASSIFICATION_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <libgda/libgda.h>

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_CLASSIFICATION                  (sim_classification_get_type ())
#define SIM_CLASSIFICATION(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_CLASSIFICATION, SimClassification))
#define SIM_CLASSIFICATION_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_CLASSIFICATION, SimClassificationClass))
#define SIM_IS_CLASSIFICATION(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_CLASSIFICATION))
#define SIM_IS_CLASSIFICATION_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_CLASSIFICATION))
#define SIM_CLASSIFICATION_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CLASSIFICATION, SimClassificationClass))

G_BEGIN_DECLS

typedef struct _SimClassification          SimClassification;
typedef struct _SimClassificationClass     SimClassificationClass;
typedef struct _SimClassificationPrivate   SimClassificationPrivate;

struct _SimClassification {
  GObject parent;

  SimClassificationPrivate *_priv;
};

struct _SimClassificationClass {
  GObjectClass parent_class;
};

GType                 sim_classification_get_type                    (void);
SimClassification*    sim_classification_new                         (void);
SimClassification*    sim_classification_new_from_data               (gint                 id,
								      const gchar         *name,
								      const gchar         *description,
								      gint                 priority);
SimClassification*    sim_classification_new_from_dm                 (GdaDataModel        *dm,
								      gint                 row);
  
gint                  sim_classification_get_id                      (SimClassification   *classification);
void                  sim_classification_set_id                      (SimClassification   *classification,
								      gint                 id);
gchar*                sim_classification_get_name                    (SimClassification   *classification);
void                  sim_classification_set_name                    (SimClassification   *classification,
								      const gchar         *name);
gchar*                sim_classification_get_description             (SimClassification   *classification);
void                  sim_classification_set_description             (SimClassification   *classification,
								      const gchar         *description);
gint                  sim_classification_get_priority                (SimClassification   *classification);
void                  sim_classification_set_priority                (SimClassification   *classification,
								      gint                 priority);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_CLASSIFICATION_H__ */

// vim: set tabstop=2:

