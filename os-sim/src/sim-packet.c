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
#include <string.h>
#include "sim-packet.h"
static gpointer parent_class = NULL;
static void
sim_packet_impl_dispose (GObject *gobject)
{
	G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_packet_impl_finalize (GObject *gobject)
{
	SimPacket *packet = SIM_PACKET(gobject);
	if (packet->payload)
		g_free(packet->payload);
	G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_packet_class_init(SimPacketClass *class)
{
	GObjectClass *object_class = G_OBJECT_CLASS(class);
	parent_class = g_type_class_ref (G_TYPE_OBJECT);
	object_class->dispose = sim_packet_impl_dispose;
	object_class->finalize = sim_packet_impl_finalize;
}

static void
sim_packet_instance_init (SimPacket *packet){
	memset(packet,sizeof(packet),0);
}


GType
sim_packet_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimPacketClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_packet_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimPacket),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_packet_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimPacket", &type_info, 0);
    }
    return object_type;
}  

SimPacket *sim_packet_new(void){
	SimPacket *packet = NULL;
	packet = SIM_PACKET(g_object_new(SIM_TYPE_PACKET,NULL));
	return packet;
}

