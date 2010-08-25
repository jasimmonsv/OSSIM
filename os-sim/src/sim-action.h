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

#ifndef __SIM_ACTION_H__
#define __SIM_ACTION_H__ 1

#include <glib.h>
#include <glib-object.h>

#include "sim-enums.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_ACTION                  (sim_action_get_type ())
#define SIM_ACTION(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_ACTION, SimAction))
#define SIM_ACTION_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_ACTION, SimActionClass))
#define SIM_IS_ACTION(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_ACTION))
#define SIM_IS_ACTION_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_ACTION))
#define SIM_ACTION_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_ACTION, SimActionClass))

G_BEGIN_DECLS

typedef struct _SimAction         SimAction;
typedef struct _SimActionClass    SimActionClass;
typedef struct _SimActionPrivate  SimActionPrivate;

struct _SimAction {
  GObject parent;

  SimActionType      type;

  SimActionPrivate  *_priv;
};

struct _SimActionClass {
  GObjectClass parent_class;
};

GType             sim_action_get_type                        (void);
SimAction*        sim_action_new                             (void);
SimAction*        sim_action_clone                           (SimAction *action);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_ACTION_H__ */

// vim: set tabstop=2:

