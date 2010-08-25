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

#ifndef __SIM_UTIL_H__
#define __SIM_UTIL_H__ 1

#include <glib.h>
#include <gnet.h>

#include "sim-enums.h"
#include "sim-database.h"

G_BEGIN_DECLS

//this struct is valid just for the Policy groups.
typedef struct _Plugin_PluginSid            Plugin_PluginSid;

struct _Plugin_PluginSid
{
  gint  plugin_id;
  GList *plugin_sid; // *gint list
};

typedef struct _SimPortProtocol    SimPortProtocol;
struct _SimPortProtocol {
  gint              port;
  SimProtocolType   protocol;
};

SimPortProtocol* sim_port_protocol_new (gint              port,
					SimProtocolType   protocol);

gboolean sim_port_protocol_equal (SimPortProtocol  *pp1,
				  SimPortProtocol  *pp2);

SimProtocolType sim_protocol_get_type_from_str (const gchar  *str);
gchar*          sim_protocol_get_str_from_type (SimProtocolType type);

SimConditionType sim_condition_get_type_from_str (const gchar  *str);
gchar*           sim_condition_get_str_from_type (SimConditionType  type);

SimRuleVarType sim_get_rule_var_from_char (const gchar *var);

SimAlarmRiskType sim_get_alarm_risk_from_char (const gchar *var);
SimAlarmRiskType sim_get_alarm_risk_from_risk (gint risk);

GList       *sim_get_ias (const gchar *value);
GList       *sim_get_inets (const gchar *value);
GList       *sim_get_SimInet_from_string (const gchar *value);

GList       *sim_string_hash_to_list (GHashTable *hash_table);

/*
 * File management utility functions
 */

gchar    *sim_file_load (const gchar *filename);
gboolean  sim_file_save (const gchar *filename, const gchar *buffer, gint len);

gulong						sim_inetaddr_aton						(GInetAddr		*ia);
inline gulong			sim_inetaddr_ntohl					(GInetAddr		*ia);
inline gulong			sim_ipchar_2_ulong					(gchar				*ip);
inline gboolean		sim_string_is_number				(gchar				*string, 
																							gboolean      may_be_float);
inline gchar *		sim_string_remove_char			(gchar *string,
																								gchar c);
inline gchar *		sim_string_substitute_char  (gchar *string,
										                            gchar c_orig,
										                            gchar c_dest);

guint							sim_g_strv_length						(gchar				**str_array);
gboolean					sim_base64_encode						(gchar *_in, 
																								guint inlen,
																								gchar *_out,
																								guint outmax,
																								guint *outlen);
gboolean					sim_base64_decode						(	gchar *in,
																								guint inlen, 
																								gchar *out, 
																								guint *outlen);

size_t						sim_strnlen									(	const char *str,
																								size_t maxlen);
gchar*						sim_normalize_host_mac			(gchar *old_mac);
guint8 * sim_hex2bin(gchar *);
gchar * sim_bin2hex(guint8*,guint);
	
G_END_DECLS

#endif
// vim: set tabstop=2:
