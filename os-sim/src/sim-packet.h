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

#ifndef __SIM_PACKET_H__
#define __SIM_PACKET_H__ 1
#include <glib.h>
#include <glib-object.h>
#include <config.h>
#ifdef __cplusplus
extern "C" {
#endif
G_BEGIN_DECLS
#define SIM_TYPE_PACKET			(sim_packet_get_type())
#define SIM_PACKET(obj)			(G_TYPE_CHECK_INSTANCE_CAST (obj,SIM_TYPE_PACKET,SimPacket))
#define SIM_PACKET_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_PACKET, SimPacketClass))
#define SIM_IS_PACKET(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_PACKET))
#define SIM_IS_PACKET_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_PACKET))
#define SIM_PACKET_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_PACKET, SimPacketClass))



typedef struct _SimPacket	SimPacket;
typedef struct _SimPacketClass	SimPacketClass;
struct _SimPacket{
	GObject parent;
	struct sim_ip{
	#if SIMBIGENDIAN == 0
		guint8 ip_hl:4;
		guint8 ip_v:4;
	#elif SIMBIGENDIAN == 1
		guint8 ip_v:4;
		guint8 ip_hl:4;
	#else
		#error "Please fix SIMBIGENDIAN value (0 litte endian, 1 big endian)"
	#endif
		guint8 ip_tos;
		guint16  ip_len;
		guint16 ip_id;
		guint16 ip_off;
		guint8  ip_ttl;
		guint8 ip_p;
		guint16 ip_sum;
		guint32 ip_src;
		guint32 ip_dst;
		guint8 options[10*4];
		guint8 nOptions;
	} sim_iphdr;
	union{
		struct sim_udp{
			guint16 uh_sport;
			guint16 uh_dport;
			guint16 uh_ulen;
			guint16 uh_sum;
		} sim_udphdr;
		struct sim_icmp{
			guint8 icmp_type;
			guint8 icmp_code;
			guint16 icmp_cksum;
			union{
				struct{
					guint16 id;
		  			guint16  sequence;
			 	} echo; 
			} un;
		} sim_icmphdr;
		struct sim_tcp{
			guint16 th_sport;
			guint16 th_dport;
			guint32 th_seq;
			guint32 th_ack;
		#if SIMBIGENDIAN == 0
			guint8 th_x2:4;
			guint8 th_off:4;
		#elif SIMBIGENDIAN == 1
			guint8 th_off:4;
			guint8 th_x2:4;
		#else
			#error "Please fix SIMBIGENDIAN value (0 litte endian, 1 big endian)"
		#endif
			guint8 th_flags;
			guint16  th_win;
			guint16  th_sum;
			guint16  th_urp;
			guint8 th_opt[10*4]; /* IP options */
			guint8 nOptions;
		}sim_tcphdr;
	} hdr;
	guint8 *payload;
	guint  payloadlen;
};

struct _SimPacketClass {
  GObjectClass parent_class;
  };
static void
sim_packet_impl_dispose (GObject *gobject);
static void
sim_packet_impl_finalize (GObject *gobject);
static void
sim_packet_class_init(SimPacketClass *class);
void
sim_packet_class_init(SimPacketClass *class);
SimPacket *sim_packet_new(void);
GType
sim_packet_get_type (void);

G_END_DECLS

#ifdef __cplusplus
}
#endif 

#endif 
