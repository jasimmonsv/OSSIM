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

#ifndef __SIM_INET_H__
#define __SIM_INET_H__ 1

#include <config.h>
#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-enums.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#ifndef HAVE_SOCKADDR_STORAGE
struct sockaddr_storage {
#ifdef HAVE_SOCKADDR_LEN
                unsigned char ss_len;
                unsigned char ss_family;
#else
        unsigned short ss_family;
#endif
        char info[126];
};
#endif

#define SIM_TYPE_INET                  (sim_inet_get_type ())
#define SIM_INET(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_INET, SimInet))
#define SIM_INET_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_INET, SimInetClass))
#define SIM_IS_INET(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_INET))
#define SIM_IS_INET_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_INET))
#define SIM_INET_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_INET, SimInetClass))

G_BEGIN_DECLS

//A SimInet object defines a single network object. It can be a host or a network.

typedef struct _SimInet        SimInet;
typedef struct _SimInetClass   SimInetClass;
typedef struct _SimInetPrivate SimInetPrivate;

struct _SimInet {
  GObject parent;

  SimInetPrivate *_priv;
};

struct _SimInetClass {
  GObjectClass parent_class;
};

GType             sim_inet_get_type                        (void);
gint              sim_inet_get_mask                        (SimInet          *inet);

SimInet*          sim_inet_new                             (const gchar      *hostname_ip);
SimInet*          sim_inet_new_from_ginetaddr              (const GInetAddr  *ia);

SimInet*          sim_inet_clone                           (SimInet          *inet);

gboolean          sim_inet_equal                           (SimInet          *inet1,
							    SimInet          *inet2);
gboolean          sim_inet_has_inet                        (SimInet          *inet1,
							    SimInet          *inet2);

gboolean          sim_inet_is_reserved                     (SimInet          *inet);

gchar*            sim_inet_ntop                            (SimInet          *inet);
gchar*            sim_inet_cidr_ntop                       (SimInet          *inet);

gboolean          sim_inet_debug_print                     (SimInet          *inet);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_INET_H__ */
// vim: set tabstop=2:
