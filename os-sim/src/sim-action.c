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


#include "sim-action.h"
#include <config.h>

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimActionPrivate {
};

static gpointer parent_class = NULL;

/* GType Functions */

static void 
sim_action_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_action_impl_finalize (GObject  *gobject)
{
  SimAction *action = SIM_ACTION (gobject);

  g_free (action->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_action_class_init (SimActionClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_action_impl_dispose;
  object_class->finalize = sim_action_impl_finalize;
}

static void
sim_action_instance_init (SimAction *action)
{
  action->_priv = g_new0 (SimActionPrivate, 1);

  action->type = SIM_ACTION_TYPE_NONE;
}

/* Public Methods */

GType
sim_action_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimActionClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_action_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimAction),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_action_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimAction", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimAction*
sim_action_new (void)
{
  SimAction *action = NULL;

  action = SIM_ACTION (g_object_new (SIM_TYPE_ACTION, NULL));

  return action;
}

/*
 *
 *
 *
 *
 */
SimAction*
sim_action_clone (SimAction *action)
{
  SimAction *new_action;
  
  g_return_val_if_fail (action != NULL, NULL);
  g_return_val_if_fail (SIM_IS_ACTION (action), NULL);

  new_action = SIM_ACTION (g_object_new (SIM_TYPE_ACTION, NULL));
  new_action->type = action->type;

  return new_action;
}

// vim: set tabstop=2:

