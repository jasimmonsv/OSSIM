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

#ifndef __SIM_SMTP_H__
#define __SIM_SMTP_H__ 1


#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-enums.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_SMTP                  (sim_smtp_get_type ())
#define SIM_SMTP(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SMTP, SimSmtp))
#define SIM_SMTP_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SMTP, SimSmtpClass))
#define SIM_IS_SMTP(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SMTP))
#define SIM_IS_SMTP_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SMTP))
#define SIM_SMTP_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SMTP, SimSmtpClass))

G_BEGIN_DECLS

typedef struct _SimSmtp        SimSmtp;
typedef struct _SimSmtpClass   SimSmtpClass;
typedef struct _SimSmtpPrivate SimSmtpPrivate;

struct _SimSmtp {
  GObject parent;

  SimSmtpPrivate *_priv;
};

struct _SimSmtpClass {
  GObjectClass parent_class;
};

GType             sim_smtp_get_type                        (void);

SimSmtp*          sim_smtp_new                             (const gchar   *hostname,
							    gint           port);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_SMTP_H__ */
// vim: set tabstop=2:
