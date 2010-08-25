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

#ifndef __SIM_DATABASE_H__
#define __SIM_DATABASE_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <libgda/libgda.h>

#include "sim-enums.h"
#include "sim-config.h"


#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */


#define SIM_TYPE_DATABASE                  (sim_database_get_type ())
#define SIM_DATABASE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_DATABASE, SimDatabase))
#define SIM_DATABASE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_DATABASE, SimDatabaseClass))
#define SIM_IS_DATABASE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_DATABASE))
#define SIM_IS_DATABASE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_DATABASE))
#define SIM_DATABASE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_DATABASE, SimDatabaseClass))

G_BEGIN_DECLS

typedef struct _SimDatabase        SimDatabase;
typedef struct _SimDatabaseClass   SimDatabaseClass;
typedef struct _SimDatabasePrivate SimDatabasePrivate;

struct _SimDatabase {
  GObject parent;

  SimDatabaseType     type;

  SimDatabasePrivate *_priv;
};

struct _SimDatabaseClass {
  GObjectClass parent_class;
};

GType           sim_database_get_type(void);
SimDatabase*    sim_database_new(SimConfigDS  *config);
gint            sim_database_execute_no_query(SimDatabase  *database, const gchar  *buffer);
GdaDataModel*   sim_database_execute_single_command(SimDatabase  *database, const gchar  *buffer);
gchar*          sim_database_get_name(SimDatabase  *database);
GdaConnection*	sim_database_get_conn (SimDatabase  *database);

G_END_DECLS
#ifdef __cplusplus
}
#endif /* __cplusplus */
#endif /* __SIM_DATABASE_H__ */

// vim: set tabstop=2:

