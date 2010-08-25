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

 
#include "sim-category.h"
#include <config.h>

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimCategoryPrivate {
  gint             id;
  gchar           *name;
};

static gpointer parent_class = NULL;
static gint sim_category_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_category_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_category_impl_finalize (GObject  *gobject)
{
  SimCategory  *category = SIM_CATEGORY (gobject);

  if (category->_priv->name)
    g_free (category->_priv->name);

  g_free (category->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_category_class_init (SimCategoryClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_category_impl_dispose;
  object_class->finalize = sim_category_impl_finalize;
}

static void
sim_category_instance_init (SimCategory *category)
{
  category->_priv = g_new0 (SimCategoryPrivate, 1);

  category->_priv->id = 0;
  category->_priv->name = NULL;
}

/* Public Methods */

GType
sim_category_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimCategoryClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_category_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimCategory),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_category_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimCategory", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimCategory*
sim_category_new (void)
{
  SimCategory *category = NULL;
  
  category = SIM_CATEGORY (g_object_new (SIM_TYPE_CATEGORY, NULL));
  
  return category;
}

/*
 *
 *
 *
 *
 */
SimCategory*
sim_category_new_from_data (gint           id,
			    const gchar   *name)
{
  SimCategory *category = NULL;

  g_return_val_if_fail (id > 0, NULL);
  g_return_val_if_fail (name, NULL);

  category = SIM_CATEGORY (g_object_new (SIM_TYPE_CATEGORY, NULL));
  category->_priv->id = id;
  category->_priv->name = g_strdup (name);

  return category;
}

/*
 *
 *
 *
 */
SimCategory*
sim_category_new_from_dm (GdaDataModel  *dm,
			  gint           row)
{
  SimCategory     *category;
  GdaValue        *value;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);
  g_return_val_if_fail (row >= 0, NULL);

  category = SIM_CATEGORY (g_object_new (SIM_TYPE_CATEGORY, NULL));

  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  category->_priv->id = gda_value_get_integer (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
  category->_priv->name = gda_value_stringify (value);

  return category;
}

/*
 *
 *
 *
 */
gint
sim_category_get_id (SimCategory  *category)
{
  g_return_val_if_fail (category, 0);
  g_return_val_if_fail (SIM_IS_CATEGORY (category), 0);
  
  return category->_priv->id;
}

/*
 *
 *
 *
 */
void
sim_category_set_id (SimCategory  *category,
		     gint     id)
{
  g_return_if_fail (category);
  g_return_if_fail (SIM_IS_CATEGORY (category));
  
  category->_priv->id = id;
}

/*
 *
 *
 *
 */
gchar*
sim_category_get_name (SimCategory  *category)
{
  g_return_val_if_fail (category, NULL);
  g_return_val_if_fail (SIM_IS_CATEGORY (category), NULL);

  return category->_priv->name;
}

/*
 *
 *
 *
 */
void
sim_category_set_name (SimCategory       *category,
		       const gchar       *name)
{
  g_return_if_fail (category);
  g_return_if_fail (SIM_IS_CATEGORY (category));
  g_return_if_fail (name);
  
  if (category->_priv->name)
    g_free (category->_priv->name);

  category->_priv->name = g_strdup (name);
}

// vim: set tabstop=2:

