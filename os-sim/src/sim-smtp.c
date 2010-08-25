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


#include "sim-smtp.h"
#include <config.h>

#define SMTP_CONNECT   "220 "
#define SMTP_OK        "250 "
#define SMTP_DATA      "354 "
#define SMTP_QUIT      "221 "

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimSmtpPrivate 
{
  GTcpSocket  *socket;
  GIOChannel  *io;

  gchar       *hostname;
  gint         port;
};

static gpointer parent_class = NULL;
static gint sim_smtp_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void 
sim_smtp_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_smtp_impl_finalize (GObject  *gobject)
{
  SimSmtp *smtp = SIM_SMTP (gobject);

  g_free (smtp->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_smtp_class_init (SimSmtpClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_smtp_impl_dispose;
  object_class->finalize = sim_smtp_impl_finalize;
}

static void
sim_smtp_instance_init (SimSmtp *smtp)
{
  smtp->_priv = g_new0 (SimSmtpPrivate, 1);
}

/* Public Methods */

GType
sim_smtp_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimSmtpClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_smtp_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimSmtp),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_smtp_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimSmtp", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimSmtp*
sim_smtp_new (const gchar  *hostname,
	      gint          port)
{
  GTcpSocket  *socket;
  SimSmtp     *smtp;

  g_return_val_if_fail (hostname, NULL);
  g_return_val_if_fail (port > 0, NULL);

  socket = gnet_tcp_socket_connect (hostname, port);
  if (!socket) return NULL;
  gnet_tcp_socket_unref (socket);

  smtp = SIM_SMTP (g_object_new (SIM_TYPE_SMTP, NULL));
  smtp->_priv->hostname = g_strdup (hostname);
  smtp->_priv->port = port;

  return smtp;
}
// vim: set tabstop=2:
