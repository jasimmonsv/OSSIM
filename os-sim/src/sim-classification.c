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

 
#include "sim-classification.h"
#include <config.h>

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimClassificationPrivate {
  gint             id;
  gchar           *name;
  gchar           *description;
  gint             priority;
};

static gpointer parent_class = NULL;
static gint sim_classification_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_classification_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_classification_impl_finalize (GObject  *gobject)
{
  SimClassification  *classification = SIM_CLASSIFICATION (gobject);

  if (classification->_priv->name)
    g_free (classification->_priv->name);
  if (classification->_priv->description)
    g_free (classification->_priv->description);

  g_free (classification->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_classification_class_init (SimClassificationClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_classification_impl_dispose;
  object_class->finalize = sim_classification_impl_finalize;
}

static void
sim_classification_instance_init (SimClassification *classification)
{
  classification->_priv = g_new0 (SimClassificationPrivate, 1);

  classification->_priv->id = 0;
  classification->_priv->name = NULL;
  classification->_priv->description = NULL;
  classification->_priv->priority = 0;
}

/* Public Methods */

GType
sim_classification_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimClassificationClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_classification_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimClassification),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_classification_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimClassification", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimClassification*
sim_classification_new (void)
{
  SimClassification *classification = NULL;
  
  classification = SIM_CLASSIFICATION (g_object_new (SIM_TYPE_CLASSIFICATION, NULL));
  
  return classification;
}

/*
 *
 *
 *
 *
 */
SimClassification*
sim_classification_new_from_data (gint           id,
				  const gchar   *name,
				  const gchar   *description,
				  gint           priority)
{
  SimClassification *classification = NULL;

  g_return_val_if_fail (id > 0, NULL);
  g_return_val_if_fail (name, NULL);

  classification = SIM_CLASSIFICATION (g_object_new (SIM_TYPE_CLASSIFICATION, NULL));
  classification->_priv->id = id;
  classification->_priv->name = g_strdup (name);
  if (description) classification->_priv->description = g_strdup (description);
  if (priority >= 0) classification->_priv->priority = priority;

  return classification;
}

/*
 *
 *
 *
 */
SimClassification*
sim_classification_new_from_dm (GdaDataModel  *dm,
				gint           row)
{
  SimClassification     *classification;
  GdaValue        *value;

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);
  g_return_val_if_fail (row >= 0, NULL);
  
  classification = SIM_CLASSIFICATION (g_object_new (SIM_TYPE_CLASSIFICATION, NULL));
  
  value = (GdaValue *) gda_data_model_get_value_at (dm, 0, row);
  classification->_priv->id = gda_value_get_integer (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 1, row);
  classification->_priv->name = gda_value_stringify (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 2, row);
  classification->_priv->description = gda_value_stringify (value);

  value = (GdaValue *) gda_data_model_get_value_at (dm, 3, row);
  classification->_priv->priority = gda_value_get_integer (value);
  
  return classification;
}

/*
 *
 *
 *
 */
gint
sim_classification_get_id (SimClassification  *classification)
{
  g_return_val_if_fail (classification, 0);
  g_return_val_if_fail (SIM_IS_CLASSIFICATION (classification), 0);
  
  return classification->_priv->id;
}

/*
 *
 *
 *
 */
void
sim_classification_set_id (SimClassification  *classification,
			   gint                id)
{
  g_return_if_fail (classification);
  g_return_if_fail (SIM_IS_CLASSIFICATION (classification));
  
  classification->_priv->id = id;
}

/*
 *
 *
 *
 */
gchar*
sim_classification_get_name (SimClassification  *classification)
{
  g_return_val_if_fail (classification, NULL);
  g_return_val_if_fail (SIM_IS_CLASSIFICATION (classification), NULL);

  return classification->_priv->name;
}

/*
 *
 *
 *
 */
void
sim_classification_set_name (SimClassification  *classification,
			     const gchar        *name)
{
  g_return_if_fail (classification);
  g_return_if_fail (SIM_IS_CLASSIFICATION (classification));
  g_return_if_fail (name);
  
  if (classification->_priv->name)
    g_free (classification->_priv->name);

  classification->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 */
gchar*
sim_classification_get_description (SimClassification  *classification)
{
  g_return_val_if_fail (classification, NULL);
  g_return_val_if_fail (SIM_IS_CLASSIFICATION (classification), NULL);

  return classification->_priv->description;
}

/*
 *
 *
 *
 */
void
sim_classification_set_description (SimClassification  *classification,
				    const gchar        *description)
{
  g_return_if_fail (classification);
  g_return_if_fail (SIM_IS_CLASSIFICATION (classification));
  g_return_if_fail (description);
  
  if (classification->_priv->description)
    g_free (classification->_priv->description);

  classification->_priv->description = g_strdup (description);
}

/*
 *
 *
 *
 */
gint
sim_classification_get_priority (SimClassification  *classification)
{
  g_return_val_if_fail (classification, 0);
  g_return_val_if_fail (SIM_IS_CLASSIFICATION (classification), 0);
  
  return classification->_priv->priority;
}

/*
 *
 *
 *
 */
void
sim_classification_set_priority (SimClassification  *classification,
				 gint                priority)
{
  g_return_if_fail (classification);
  g_return_if_fail (SIM_IS_CLASSIFICATION (classification));
  
  classification->_priv->priority = priority;
}

// vim: set tabstop=2:

