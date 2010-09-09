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

#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <sys/socket.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <sim-util.h>
#include <gnet.h>
#include <string.h>
#include <stdlib.h>
#include <limits.h>
#include <regex.h>
#include <signal.h>
#include <errno.h>
#include "sim-inet.h"
#include "sim-util.h"

#define CHAR64(c)           (((c) < 0 || (c) > 127) ? -1 : index_64[(c)])
static gchar     base64_table[] =
"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
static gchar     index_64[128] = {
   -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
   -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
   -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63,
   52, 53, 54, 55, 56, 57, 58, 59, 60, 61, -1, -1, -1, -1, -1, -1,
   -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
   15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, -1, -1, -1, -1, -1,
   -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
   41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1, -1, -1
};
static gchar *hexdigitchar = "0123456789ABCDEF";
gchar *sim_bin2hex(guint8 *data,guint len){
	gchar *d = g_new(gchar,len*2+1);
	int i,j=0;
	if (d!=NULL){
		for (i=0;i<(len);i++){
			d[i*2] = hexdigitchar[(data[i]&0xf0)>>4];
			d[i*2+1] = hexdigitchar[data[i]&0xf];
		}
		d[i*2]='\0';
	}

	return d;
}

guint8 *sim_hex2bin(gchar *data){
  int i,j=0,k;
	size_t l;
	gchar *st=NULL;
	gchar temp[3];
	if (data!=NULL){
		  l = strlen(data);
			if (l % 2) return NULL;
			st=g_new(gchar,l/2);
			if (st!=NULL){
				for(i=0;i<l;i+=2){
					if (g_ascii_isxdigit(data[i]) && g_ascii_isxdigit(data[i+1])){
						st[j++] =   g_ascii_xdigit_value(data[i])*16+  g_ascii_xdigit_value(data[i+1]);
					}else{
						g_free(st);
						st = NULL;
						break;
					}
				}
			}
	  }
	return st;
}




/*
 *
 *
 *
 */
SimProtocolType
sim_protocol_get_type_from_str (const gchar  *str)
{
  g_return_val_if_fail (str, SIM_PROTOCOL_TYPE_NONE);

  if (!g_ascii_strcasecmp (str, "ICMP"))
    return SIM_PROTOCOL_TYPE_ICMP;
  else if (!g_ascii_strcasecmp (str, "UDP"))
    return SIM_PROTOCOL_TYPE_UDP;
  else if (!g_ascii_strcasecmp (str, "TCP"))
    return SIM_PROTOCOL_TYPE_TCP;
  else if (!g_ascii_strcasecmp (str, "Host_ARP_Event"))
    return SIM_PROTOCOL_TYPE_HOST_ARP_EVENT;
  else if (!g_ascii_strcasecmp (str, "Host_OS_Event"))
    return SIM_PROTOCOL_TYPE_HOST_OS_EVENT;
  else if (!g_ascii_strcasecmp (str, "Host_Service_Event"))
    return SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT;
  else if (!g_ascii_strcasecmp (str, "Host_IDS_Event"))
    return SIM_PROTOCOL_TYPE_HOST_IDS_EVENT;
  else if (!g_ascii_strcasecmp (str, "Information_Event"))
    return SIM_PROTOCOL_TYPE_INFORMATION_EVENT;
  else if (!g_ascii_strcasecmp (str, "OTHER"))
    return SIM_PROTOCOL_TYPE_OTHER;
 
  return SIM_PROTOCOL_TYPE_NONE;
}

/*
 *
 *
 */
gchar*
sim_protocol_get_str_from_type (SimProtocolType type)
{
  switch (type)
    {
    case SIM_PROTOCOL_TYPE_ICMP:
      return g_strdup ("ICMP");
    case SIM_PROTOCOL_TYPE_UDP:
      return g_strdup ("UDP");
    case SIM_PROTOCOL_TYPE_TCP:
      return g_strdup ("TCP");
    case SIM_PROTOCOL_TYPE_HOST_ARP_EVENT:
      return g_strdup ("Host_ARP_Event");
    case SIM_PROTOCOL_TYPE_HOST_OS_EVENT:
      return g_strdup ("Host_OS_Event");
    case SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT:
      return g_strdup ("Host_Service_Event");
    case SIM_PROTOCOL_TYPE_HOST_IDS_EVENT:
      return g_strdup ("Host_IDS_Event");
    case SIM_PROTOCOL_TYPE_INFORMATION_EVENT:
      return g_strdup ("Information_Event");
    default:
      return g_strdup ("OTHER");
    }
}

/*
 *
 *
 *
 */
SimConditionType
sim_condition_get_type_from_str (const gchar  *str)
{
  g_return_val_if_fail (str, SIM_CONDITION_TYPE_NONE);

  if (!g_ascii_strcasecmp (str, "eq"))
    return SIM_CONDITION_TYPE_EQ;
  else if (!g_ascii_strcasecmp (str, "ne"))
    return SIM_CONDITION_TYPE_NE;
  else if (!g_ascii_strcasecmp (str, "lt"))
    return SIM_CONDITION_TYPE_LT;
  else if (!g_ascii_strcasecmp (str, "le"))
    return SIM_CONDITION_TYPE_LE;
  else if (!g_ascii_strcasecmp (str, "gt"))
    return SIM_CONDITION_TYPE_GT;
  else if (!g_ascii_strcasecmp (str, "ge"))
    return SIM_CONDITION_TYPE_GE;

  return SIM_CONDITION_TYPE_NONE;
}

/*
 *
 *
 *
 */
gchar*
sim_condition_get_str_from_type (SimConditionType  type)
{
  switch (type)
    {
    case SIM_CONDITION_TYPE_EQ:
      return g_strdup ("eq");
    case SIM_CONDITION_TYPE_NE:
      return g_strdup ("ne");
    case SIM_CONDITION_TYPE_LT:
      return g_strdup ("lt");
    case SIM_CONDITION_TYPE_LE:
      return g_strdup ("le");
    case SIM_CONDITION_TYPE_GT:
      return g_strdup ("gt");
    case SIM_CONDITION_TYPE_GE:
      return g_strdup ("ge");
    default:
      return NULL;
    }
}

/*
 *
 *
 *
 */
SimPortProtocol*
sim_port_protocol_new (gint              port,
								       SimProtocolType   protocol)
{
  SimPortProtocol  *pp;

  g_return_val_if_fail (port >= 0, NULL);
  g_return_val_if_fail (protocol >= -1, NULL);

  pp = g_new0 (SimPortProtocol, 1);
  pp->port = port;
  pp->protocol = protocol;

  return pp;
}

/*
 *
 *
 *
 */
gboolean
sim_port_protocol_equal (SimPortProtocol  *pp1,
												 SimPortProtocol  *pp2)
{
  g_return_val_if_fail (pp1, FALSE);  
  g_return_val_if_fail (pp2, FALSE);  

//      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Policy port: %d , protocol: %d", pp1->port, pp1->protocol);
//      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "       port: %d , protocol: %d", pp2->port, pp2->protocol);
      
  if (pp1->port == 0)	//if the port defined in policy is "0", its like ANY and all the ports will match
    return TRUE;    

  if ((pp1->port == pp2->port) && (pp1->protocol == pp2->protocol))
    return TRUE;

  return FALSE;
}





/*
 *
 * FIXME:I think that this function is useless until we make a "sim_xml_directive_set_rule_*" function.  
 * This returns the var type of the n level in a rule from a directive
 *
 */
SimRuleVarType
sim_get_rule_var_from_char (const gchar *var)
{
  g_return_val_if_fail (var != NULL, SIM_RULE_VAR_NONE);

  if (!strcasecmp (var, SIM_SRC_IP_CONST))
    return SIM_RULE_VAR_SRC_IA;
  else if (!strcasecmp (var, SIM_DST_IP_CONST))
    return SIM_RULE_VAR_DST_IA;
  else if (!strcasecmp (var, SIM_SRC_PORT_CONST))
    return SIM_RULE_VAR_SRC_PORT;
  else if (!strcasecmp (var, SIM_DST_PORT_CONST))
    return SIM_RULE_VAR_DST_PORT;
  else if (!strcasecmp (var, SIM_PROTOCOL_CONST))
    return SIM_RULE_VAR_PROTOCOL;
  else if (!strcasecmp (var, SIM_PLUGIN_SID_CONST))
    return SIM_RULE_VAR_PLUGIN_SID;
  else if (!strcasecmp (var, SIM_SENSOR_CONST))
    return SIM_RULE_VAR_SENSOR;
  else if (!strcasecmp (var, SIM_FILENAME_CONST))
    return SIM_RULE_VAR_FILENAME;
  else if (!strcasecmp (var, SIM_USERNAME_CONST))
    return SIM_RULE_VAR_USERNAME;
  else if (!strcasecmp (var, SIM_PASSWORD_CONST))
    return SIM_RULE_VAR_PASSWORD;
  else if (!strcasecmp (var, SIM_USERDATA1_CONST))
    return SIM_RULE_VAR_USERDATA1;
  else if (!strcasecmp (var, SIM_USERDATA2_CONST))
    return SIM_RULE_VAR_USERDATA2;
  else if (!strcasecmp (var, SIM_USERDATA3_CONST))
    return SIM_RULE_VAR_USERDATA3;
  else if (!strcasecmp (var, SIM_USERDATA4_CONST))
    return SIM_RULE_VAR_USERDATA4;
  else if (!strcasecmp (var, SIM_USERDATA5_CONST))
    return SIM_RULE_VAR_USERDATA5;
  else if (!strcasecmp (var, SIM_USERDATA6_CONST))
    return SIM_RULE_VAR_USERDATA6;
  else if (!strcasecmp (var, SIM_USERDATA7_CONST))
    return SIM_RULE_VAR_USERDATA7;
  else if (!strcasecmp (var, SIM_USERDATA8_CONST))
    return SIM_RULE_VAR_USERDATA8;
  else if (!strcasecmp (var, SIM_USERDATA9_CONST))
    return SIM_RULE_VAR_USERDATA9;
	
  return SIM_RULE_VAR_NONE;
}

/*
 * Used to get the variable type from properties in the directive
 */
/*
SimRuleVarType:
sim_get_rule_var_from_property (const gchar *var)
{

  if (!strcmp (var, PROPERTY_FILENAME))
    return SIM_RULE_VAR_FILENAME;
  else if (!strcmp (var, PROPERTY_USERNAME))
    return SIM_RULE_VAR_USERNAME;
  else if (!strcmp (var, PROPERTY_PASSWORD))
    return SIM_RULE_VAR_PASSWORD;
  else if (!strcmp (var, PROPERTY_USERDATA1))
    return SIM_RULE_VAR_USERDATA1;
  else if (!strcmp (var, PROPERTY_USERDATA2))
    return SIM_RULE_VAR_USERDATA2;
  else if (!strcmp (var, PROPERTY_USERDATA3))
    return SIM_RULE_VAR_USERDATA3;
  else if (!strcmp (var, PROPERTY_USERDATA4))
    return SIM_RULE_VAR_USERDATA4;
  else if (!strcmp (var, PROPERTY_USERDATA5))
    return SIM_RULE_VAR_USERDATA5;
  else if (!strcmp (var, PROPERTY_USERDATA6))
    return SIM_RULE_VAR_USERDATA6;
  else if (!strcmp (var, PROPERTY_USERDATA7))
    return SIM_RULE_VAR_USERDATA7;
  else if (!strcmp (var, PROPERTY_USERDATA8))
    return SIM_RULE_VAR_USERDATA8;
  else if (!strcmp (var, PROPERTY_USERDATA9))
    return SIM_RULE_VAR_USERDATA9;
	
  return SIM_RULE_VAR_NONE;
}
*/

/*
 *
 *
 *
 */
SimAlarmRiskType
sim_get_alarm_risk_from_char (const gchar *var)
{
  g_return_val_if_fail (var != NULL, SIM_ALARM_RISK_TYPE_NONE);

  if (!g_ascii_strcasecmp (var, "low"))
    return SIM_ALARM_RISK_TYPE_LOW;
  else if (!g_ascii_strcasecmp (var, "medium"))
    return SIM_ALARM_RISK_TYPE_MEDIUM;
  else if (!g_ascii_strcasecmp (var, "high"))
    return SIM_ALARM_RISK_TYPE_HIGH;
  else if (!g_ascii_strcasecmp (var, "all"))
    return SIM_ALARM_RISK_TYPE_ALL;
  else
    return SIM_ALARM_RISK_TYPE_NONE;
}

/*
 *
 *
 *
 */
SimAlarmRiskType
sim_get_alarm_risk_from_risk (gint risk)
{
  if ((risk >= 1) && risk <= 4)
    return SIM_ALARM_RISK_TYPE_LOW;
  else if (risk >= 5 && risk <= 7)
    return SIM_ALARM_RISK_TYPE_MEDIUM;
  else if (risk >= 8 && risk <= 10)
    return SIM_ALARM_RISK_TYPE_HIGH;
  else
    return SIM_ALARM_RISK_TYPE_NONE;
}

/*
 *
 *
 *
 */
GList*
sim_get_ias (const gchar *value)
{
  GInetAddr  *ia;
  GList      *list = NULL;

  g_return_val_if_fail (value != NULL, NULL);

  ia = gnet_inetaddr_new_nonblock (value, 0);

  list = g_list_append (list, ia);

  return list;
}

/*
 *
 * Given a string with network(s) or hosts, it returns a GList of SimInet objects (one network or host each object).
 * The format can be only: "192.168.1.1-40" or  "192.168.1.0/24" or "192.168.1.1".
 * This function doesn't accepts multiple hosts or nets.
 */
GList*
sim_get_inets (const gchar *value)
{
  SimInet    *inet;
  GList      *list = NULL;
  gchar      *endptr;
  gchar      *slash;
  gint        from;
  gint        to;
  gint        i;

  g_return_val_if_fail (value != NULL, NULL);

  /* Look for a range: 192.168.0.1-20. This kind of network is stored in memory using hosts with 32 bit network mask */
  slash = strchr (value, '-');
  if (slash)
  {
    gchar **values0 = g_strsplit(value, ".", 0);
    if (values0[3])
  	{
	    gchar **values1 = g_strsplit(values0[3], "-", 0);

	  	from = strtol (values1[0], &endptr, 10);
		  to = strtol (values1[1], &endptr, 10);	

		  for (i = 0; i <= (to - from); i++)  //transform every IP into a host SimInet object and store into it
	    {
	      gchar *ip = g_strdup_printf ("%s.%s.%s.%d/32",
					   values0[0], values0[1],
					   values0[2], from + i);

	      inet = sim_inet_new (ip); 	//is this a host or a network? well, it's the same :)
				if (inet)
		      list = g_list_append (list, inet);
				else
					g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: sim_get_inets: %s", ip);

	      g_free (ip);
	    }

	  	g_strfreev (values1);
		}

    g_strfreev (values0);
  }
  else
  {
    inet = sim_inet_new (value);
		if (inet)
      list = g_list_append (list, inet);
		else
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: sim_get_inets: %s", value);
  }

  return list;
}

/*
 *
 * Takes any string like "192.168.1.0-40,192.168.1.0/24,192.168.5.6", transform everything into SimInet objects
 * and put them into a GList. If the string has some "ANY", every other ip or network is removed. Then, inside the
 * GList wich is returned just will be one SimInet object wich contains "0.0.0.0".
 */
GList*
sim_get_SimInet_from_string (const gchar *value)
{
  SimInet    *inet;
  GList      *list = NULL;
  GList      *list_temp = NULL;
	gint i;

  g_return_val_if_fail (value != NULL, NULL);

  if ( g_strstr_len (value, strlen(value), SIM_IN_ADDR_ANY_CONST) ||
			 g_strstr_len (value, strlen(value), "any")) //if appears "ANY" anywhere in the string
  {
    inet = sim_inet_new(SIM_IN_ADDR_ANY_IP_STR);
    list = g_list_append(list, inet);
		return list;
  }

  if (strchr (value, ','))  		//multiple networks or hosts
  {
    gchar **values = g_strsplit (value, ",", 0);
    for (i = 0; values[i] != NULL; i++)
		{
			//g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_get_SimInet_from_string: values[%d] = %s", i, values[i]);
		  list_temp = sim_get_inets(values[i]);
			while (list_temp)
			{
				inet = (SimInet *) list_temp->data;
				list = g_list_append (list, inet);
				
				list_temp = list_temp->next;
			}
		}
		g_strfreev (values);
  }
  else 													//uh, just one network or one host.
	{
    list_temp = sim_get_inets (value);
    while (list_temp)
    {
      inet = (SimInet *) list_temp->data;
      list = g_list_append (list, inet);
			list_temp = list->next;
    }
	}

  return list;
}


/* function called by g_hash_table_foreach to add items to a GList */
static void
add_string_key_to_list (gpointer key, gpointer value, gpointer user_data)
{
        GList **list = (GList **) user_data;

        *list = g_list_append (*list, g_strdup (key));
}

/**
 * sim_string_hash_to_list
 */
GList *
sim_string_hash_to_list (GHashTable *hash_table)
{
	GList *list = NULL;

        g_return_val_if_fail (hash_table != NULL, NULL);

        g_hash_table_foreach (hash_table, (GHFunc) add_string_key_to_list, &list);
        return list;
}

/**
 * sim_file_load
 * @filename: path for the file to be loaded.
 *
 * Loads a file, specified by the given @uri, and returns the file
 * contents as a string.
 *
 * It is the caller's responsibility to free the returned value.
 *
 * Returns: the file contents as a newly-allocated string, or NULL
 * if there is an error.
 */
gchar *
sim_file_load (const gchar *filename)
{
  gchar *retval = NULL;
  gsize length = 0;
  GError *error = NULL;
  
  g_return_val_if_fail (filename != NULL, NULL);
  
  if (g_file_get_contents (filename, &retval, &length, &error))
    return retval;
  
  g_message ("Error while reading %s: %s", filename, error->message);
  g_error_free (error);
  
  return NULL;
}

/**
 * sim_file_save
 * @filename: path for the file to be saved.
 * @buffer: contents of the file.
 * @len: size of @buffer.
 *
 * Saves a chunk of data into a file.
 *
 * Returns: TRUE if successful, FALSE on error.
 */
gboolean
sim_file_save (const gchar *filename, const gchar *buffer, gint len)
{
  gint fd;
  gint res;
  
  g_return_val_if_fail (filename != NULL, FALSE);
  
  fd = open (filename, O_RDWR | O_CREAT, 0644);
  if (fd == -1) {
    g_message ("Could not create file %s", filename);
    return FALSE;
  }
  
  res = write (fd, (const void *) buffer, len);
  close (fd);
  
  return res == -1 ? FALSE : TRUE;
}

/**
 *
 *
 *
 *
 *
 */
gulong
sim_inetaddr_aton (GInetAddr     *ia)
{
  struct   in_addr in;
  gchar   *ip;
  gulong   val = -1;

  g_return_val_if_fail (ia, -1);

  if (!(ip = gnet_inetaddr_get_canonical_name (ia)))
    return -1;

  if (inet_aton (ip, &in)) val = in.s_addr;

  g_free (ip);

  return val;
}

/**
 *
 *
 * Transforms a GInetAddr into an unsigned long.
 *
 *
 */
inline gulong
sim_inetaddr_ntohl (GInetAddr     *ia)
{
  struct   in_addr in;
  gchar   *ip;
  gulong   val = -1;

  g_return_val_if_fail (ia, -1);

  if (!(ip = gnet_inetaddr_get_canonical_name (ia)))
    return -1;

  if (inet_aton (ip, &in))
		val = g_ntohl (in.s_addr);

  g_free (ip);

  return val;
}

/*
 * Transforms a gchar * (i.e. 192.168.1.1) into an unsigned long
 */
inline gulong
sim_ipchar_2_ulong (gchar     *ip)
{
  struct   in_addr in;
  gulong   val = -1;

  if (inet_aton (ip, &in))
		val = g_ntohl (in.s_addr);

  return val;
}

/*
 * Check if all the characters in the given string are numbers, so we can transform
 * that string into a number if we want, or whatever.
 * The parameter may_be_float tell us if we have to check also if it's a
 * floating number, checking one "." in the string
 * may_be_float = 0 means no float.
 */
inline gboolean
sim_string_is_number (gchar *string, 
                      gboolean may_be_float)
{
	int n;
	gboolean ok = FALSE;
  int count = 0;

	if (!string)
		return FALSE;

	for (n=0; n < strlen(string); n++)
	{
	  if (g_ascii_isdigit (string[n]))
	    ok=TRUE;
	  else
    if (may_be_float)
    { 
      if ((string[n] == '.') && (count == 0))
      {
        count++;
        ok = TRUE;
      }			
    }
    else
	  {
	    ok = FALSE;
	    break;
	  }
	}
	return ok;
}

/*
 * Check if exists and remove all the appearances of the character from a string.
 * A pointer to the same string is returned to allow nesting (if needed).
 */
inline gchar *
sim_string_remove_char	(gchar *string,
													gchar c)
{
	if (!string)
		return FALSE;

	gchar *s = string;
	
	while ((s = strchr (s, c)) != NULL)
		memmove (s, s+1, strlen (s));
	
	return string;
}

/*
 * Check if exists and substitute all the appearances of c_orig in the string,
 * with the character c_dest.
 * A pointer to the same string is returned.
 */
inline gchar *
sim_string_substitute_char	(gchar *string,
														gchar c_orig,
														gchar	c_dest)
{
	if (!string)
		return FALSE;

	gchar *s = string;
	
	while ((s = strchr (s, c_orig)) != NULL)
		*s = c_dest;
	
	return string;
}


/*
 * Substitute for g_strv_length() as it's just supported in some environments
 */
guint 
sim_g_strv_length (gchar **str_array)
{
	  guint i = 0;
	  g_return_val_if_fail (str_array != NULL, 0);

	  while (str_array[i])
	    ++i;

	  return i;
}


/*
 * 
 * Used to debug wich is the value from a GdaValue to know the right function to call.
 * 
 */
void sim_gda_value_extract_type(GdaValue *value)
{
	GdaValueType lala;
	lala = gda_value_get_type(value);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_gda_value_extract_type");
						
	switch (lala)
	{
		case GDA_VALUE_TYPE_NULL:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_NULL");
						break;
		case GDA_VALUE_TYPE_BIGINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_BIGINT");
						break;
		case GDA_VALUE_TYPE_BIGUINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_BIGUINT");
						break;
		case GDA_VALUE_TYPE_BINARY:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_BINARY");
						break;
		case GDA_VALUE_TYPE_BLOB:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_BLOB");
						break;
		case GDA_VALUE_TYPE_BOOLEAN:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_BOOLEAN");
						break;
		case GDA_VALUE_TYPE_DATE:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_DATE");
						break;
		case GDA_VALUE_TYPE_DOUBLE:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_DOUBLE");
						break;
		case GDA_VALUE_TYPE_GEOMETRIC_POINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_GEOMETRIC_POINT");
						break;
		case GDA_VALUE_TYPE_GOBJECT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_GOBJECT");
						break;
		case GDA_VALUE_TYPE_INTEGER:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_INTEGER");
						break;
		case GDA_VALUE_TYPE_LIST:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_LIST");
						break;
		case GDA_VALUE_TYPE_MONEY:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_MONEY");
						break;
		case GDA_VALUE_TYPE_NUMERIC:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_NUMERIC");
						break;
		case GDA_VALUE_TYPE_SINGLE:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_SINGLE");
						break;
		case GDA_VALUE_TYPE_SMALLINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_SMALLINT");
						break;
		case GDA_VALUE_TYPE_SMALLUINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_SMALLUINT");
						break;
		case GDA_VALUE_TYPE_STRING:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_STRING");
						break;
		case GDA_VALUE_TYPE_TIME:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_TIME");
						break;
		case GDA_VALUE_TYPE_TIMESTAMP:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_TIMESTAMP");
						break;
		case GDA_VALUE_TYPE_TINYINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_TINYINT");
						break;
		case GDA_VALUE_TYPE_TINYUINT:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_TINYUINT");
						break;
		case GDA_VALUE_TYPE_TYPE:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_TYPE");
						break;
		case GDA_VALUE_TYPE_UINTEGER:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_UINTEGER");
						break;
		case GDA_VALUE_TYPE_UNKNOWN:
			      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "GDA_VALUE_TYPE_UNKNOWN");
						break;
		default:
						g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: GDA_VALUE_TYPE Desconocido");									
	}

}

/*
 * Arguments:
 * GList: list of gchar*
 * string: string to check.
 *
 * this function will take a glist and will check if the string is any of the strings inside the GList
 * If the string is "ANY", any of the strings inside GList will match.
 *
 * Warning: Please, use this function just to check gchar's. Any other use will be very probably a segfault.
 */
gboolean
sim_cmp_list_gchar (GList *list, gchar *string)
{
	if (!string)
		return FALSE;

	gchar *cmp;
	while (list)
	{
		cmp = (gchar *) list->data;
	  if ((!g_ascii_strcasecmp (cmp, string)) || (!g_ascii_strcasecmp (cmp, "ANY")))
			return TRUE;							//found!
		list = list->next;
	}
	return FALSE;
	
}

void
sim_role_print (SimRole *role)
{
	g_return_if_fail (role);

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "SimRole printing data:");
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "        correlate:       %d", role->correlate);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "        cross correlate: %d", role->cross_correlate);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "        store:           %d", role->store);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "        qualify:         %d", role->qualify);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "        resend_event:    %d", role->resend_event);
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "        resend_alarm:    %d", role->resend_alarm);
}
/*
 *
 * 
 *
 *dentro de hostmac:
 * sim_event_counter(event->time, SIM_COMMAND_SYMBOL_HOST_MAC_EVENT, event->sensor);
 */
/*

 * BASE64 encoding to send data over the network.
 * _in: src buffer 
 * inlen: strlen (src buffer)
 * _out: dst buffer (reserved outside this function). This is where the base64 string will be stored.
 * outmax: max size of the dst buffer to avoid overflows
 * outlen: modified bytes (not needed to perform the encode, just information)
 *
 */
//FIXME: Remove outlen and subtitute it with a return?
gboolean sim_base64_encode (gchar *_in, 
														guint inlen,
														gchar *_out,
														guint outmax,
														guint *outlen)
{
	g_return_val_if_fail (_in, FALSE);

  const guchar *in = (const guchar *) _in;
  guchar  *out = (guchar *) _out;
  guchar   oval;
  gchar   *temp;
  guint   olen;
	

   olen = (inlen + 2) / 3 * 4;
//   if (outlen)
//       *outlen = olen;
   if (outmax < olen)
       return FALSE;

   temp = (gchar *) out;
   while (inlen >= 3)
   {
       *out++ = base64_table[in[0] >> 2];
       *out++ = base64_table[((in[0] << 4) & 0x30) | (in[1] >> 4)];
       *out++ = base64_table[((in[1] << 2) & 0x3c) | (in[2] >> 6)];
       *out++ = base64_table[in[2] & 0x3f];
       in += 3;
       inlen -= 3;
   }
   if (inlen > 0)
   {
       *out++ = base64_table[in[0] >> 2];
       oval = (in[0] << 4) & 0x30;
       if (inlen > 1)
           oval |= in[1] >> 4;
       *out++ = base64_table[oval];
       *out++ = (inlen < 2) ? '=' : base64_table[(in[1] << 2) & 0x3c];
       *out++ = '=';
   }

   if (olen < outmax)
       *out = '\0';

   return TRUE;

}

/*
 * BASE64 decoding to receive data over the network.
 * in: src buffer in BASE64 to decode
 * inlen: strlen (src buffer)
 * out: dst buffer (reserved outside this function). This will contain the data in clear.
 * outlen: number of modified bytes (just information)
 *
 */
gboolean sim_base64_decode(	gchar *in,
														guint inlen, 
														gchar *out, 
														guint *outlen)
{
   guint        len = 0,
                   lup;
   gint            c1,
                   c2,
                   c3,
                   c4;



   if (in[0] == '+' && in[1] == ' ')
       in += 2;

   if (*in == '\0')
       return FALSE; 

   for (lup = 0; lup < inlen / 4; lup++)
   {
       c1 = in[0];
       if (CHAR64(c1) == -1)
           return FALSE;
       c2 = in[1];
       if (CHAR64(c2) == -1)
           return FALSE;
       c3 = in[2];
       if (c3 != '=' && CHAR64(c3) == -1)
           return FALSE;
       c4 = in[3];
       if (c4 != '=' && CHAR64(c4) == -1)
           return FALSE;
       in += 4;
       *out++ = (CHAR64(c1) << 2) | (CHAR64(c2) >> 4);
       ++len;
       if (c3 != '=')
       {
           *out++ = ((CHAR64(c2) << 4) & 0xf0) | (CHAR64(c3) >> 2);
           ++len;
           if (c4 != '=')
           {
               *out++ = ((CHAR64(c3) << 6) & 0xc0) | CHAR64(c4);
               ++len;
           }
       }
   *outlen = len;
   }

   *out = 0;
   return TRUE;

}


// As BSD hasn't got strnlen, we copy here the strnlen from libc

/* Find the length of STRING, but scan at most MAXLEN characters.
   Copyright (C) 1991, 1993, 1997, 2000, 2001 Free Software Foundation, Inc.
   Contributed by Jakub Jelinek <jakub@redhat.com>.

   Based on strlen written by Torbjorn Granlund (tege@sics.se),
   with help from Dan Sahlin (dan@sics.se);
   commentary by Jim Blandy (jimb@ai.mit.edu).

   The GNU C Library is free software; you can redistribute it and/or
   modify it under the terms of the GNU Lesser General Public License as
   published by the Free Software Foundation; either version 2.1 of the
   License, or (at your option) any later version.

   The GNU C Library is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
   Lesser General Public License for more details.

   You should have received a copy of the GNU Lesser General Public
   License along with the GNU C Library; see the file COPYING.LIB.  If not,
   write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
   Boston, MA 02111-1307, USA.  */


/* Find the length of S, but scan at most MAXLEN characters.  If no
   '\0' terminator is found in that many characters, return MAXLEN.  */
size_t
sim_strnlen (const char *str, size_t maxlen)
{
  const char *char_ptr, *end_ptr = str + maxlen;
  const unsigned long int *longword_ptr;
  unsigned long int longword, magic_bits, himagic, lomagic;

  if (maxlen == 0)
    return 0;

  if (__builtin_expect (end_ptr < str, 0))
    end_ptr = (const char *) ~0UL;

  /* Handle the first few characters by reading one character at a time.
     Do this until CHAR_PTR is aligned on a longword boundary.  */
  for (char_ptr = str; ((unsigned long int) char_ptr
			& (sizeof (longword) - 1)) != 0;
       ++char_ptr)
    if (*char_ptr == '\0')
      {
	if (char_ptr > end_ptr)
	  char_ptr = end_ptr;
	return char_ptr - str;
      }

  /* All these elucidatory comments refer to 4-byte longwords,
     but the theory applies equally well to 8-byte longwords.  */

  longword_ptr = (unsigned long int *) char_ptr;

  /* Bits 31, 24, 16, and 8 of this number are zero.  Call these bits
     the "holes."  Note that there is a hole just to the left of
     each byte, with an extra at the end:

     bits:  01111110 11111110 11111110 11111111
     bytes: AAAAAAAA BBBBBBBB CCCCCCCC DDDDDDDD

     The 1-bits make sure that carries propagate to the next 0-bit.
     The 0-bits provide holes for carries to fall into.  */
  magic_bits = 0x7efefeffL;
  himagic = 0x80808080L;
  lomagic = 0x01010101L;
  if (sizeof (longword) > 4)
    {
      /* 64-bit version of the magic.  */
      /* Do the shift in two steps to avoid a warning if long has 32 bits.  */
      magic_bits = ((0x7efefefeL << 16) << 16) | 0xfefefeffL;
      himagic = ((himagic << 16) << 16) | himagic;
      lomagic = ((lomagic << 16) << 16) | lomagic;
    }
  if (sizeof (longword) > 8)
    abort ();

  /* Instead of the traditional loop which tests each character,
     we will test a longword at a time.  The tricky part is testing
     if *any of the four* bytes in the longword in question are zero.  */
  while (longword_ptr < (unsigned long int *) end_ptr)
    {
      /* We tentatively exit the loop if adding MAGIC_BITS to
	 LONGWORD fails to change any of the hole bits of LONGWORD.

	 1) Is this safe?  Will it catch all the zero bytes?
	 Suppose there is a byte with all zeros.  Any carry bits
	 propagating from its left will fall into the hole at its
	 least significant bit and stop.  Since there will be no
	 carry from its most significant bit, the LSB of the
	 byte to the left will be unchanged, and the zero will be
	 detected.

	 2) Is this worthwhile?  Will it ignore everything except
	 zero bytes?  Suppose every byte of LONGWORD has a bit set
	 somewhere.  There will be a carry into bit 8.  If bit 8
	 is set, this will carry into bit 16.  If bit 8 is clear,
	 one of bits 9-15 must be set, so there will be a carry
	 into bit 16.  Similarly, there will be a carry into bit
	 24.  If one of bits 24-30 is set, there will be a carry
	 into bit 31, so all of the hole bits will be changed.

	 The one misfire occurs when bits 24-30 are clear and bit
	 31 is set; in this case, the hole at bit 31 is not
	 changed.  If we had access to the processor carry flag,
	 we could close this loophole by putting the fourth hole
	 at bit 32!

	 So it ignores everything except 128's, when they're aligned
	 properly.  */

      longword = *longword_ptr++;

      if ((longword - lomagic) & himagic)
	{
	  /* Which of the bytes was the zero?  If none of them were, it was
	     a misfire; continue the search.  */

	  const char *cp = (const char *) (longword_ptr - 1);

	  char_ptr = cp;
	  if (cp[0] == 0)
	    break;
	  char_ptr = cp + 1;
	  if (cp[1] == 0)
	    break;
	  char_ptr = cp + 2;
	  if (cp[2] == 0)
	    break;
	  char_ptr = cp + 3;
	  if (cp[3] == 0)
	    break;
	  if (sizeof (longword) > 4)
	    {
	      char_ptr = cp + 4;
	      if (cp[4] == 0)
		break;
	      char_ptr = cp + 5;
	      if (cp[5] == 0)
		break;
	      char_ptr = cp + 6;
	      if (cp[6] == 0)
		break;
	      char_ptr = cp + 7;
	      if (cp[7] == 0)
		break;
	    }
	}
      char_ptr = end_ptr;
    }

  if (char_ptr > end_ptr)
    char_ptr = end_ptr;
  return char_ptr - str;
}

gchar*
sim_normalize_host_mac (gchar *old_mac)
{
  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_normalize_host_mac old mac: %s", old_mac);
  // if size OK, just put MAC to uppercase
  if (strlen(old_mac) == 17)
  {
    return g_ascii_strup(old_mac, -1);
  }

  regex_t compre;
  if(regcomp(&compre, "^([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2})$", REG_EXTENDED) != 0)
  {
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_normalize_host_mac: Failed regcomp");
    return NULL;
  }

  size_t nmatch = compre.re_nsub + 1;
  regmatch_t *pmatch = g_new(regmatch_t, nmatch);

  int match = regexec(&compre, old_mac, nmatch, pmatch, 0);
  regfree(&compre);

  if (match != 0)
  {
    g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_session_cmd_host_mac_event: Failed match regexp");
    g_free(pmatch);
    return NULL;
  }

  gchar *good_mac = g_malloc(18);
  gchar *mac = good_mac;
  int i;
  for(i=1; i<nmatch; i++)
  {
    int start = pmatch[i].rm_so;
    int end = pmatch[i].rm_eo;
    size_t size = end - start;

    if (size == 1) {
      *mac++ = '0';
      *mac++ = g_ascii_toupper(old_mac[start]);
    }
    else {
      *mac++ = g_ascii_toupper(old_mac[start]);
      *mac++ = g_ascii_toupper(old_mac[start+1]);
    }
    if (i < nmatch-1)
      *mac++ = ':';
  }
  *mac = '\0';
  g_free(pmatch);

  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_normalize_host_mac good mac: %s", good_mac);
  return good_mac;
}

gboolean
sim_util_block_signal(int sig){
	sigset_t sigmask;
	sigaddset(&sigmask,sig);
	gboolean result = TRUE;
	if (pthread_sigmask(SIG_BLOCK,&sigmask,NULL)){
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_MESSAGE, "%s: Error blocking signal",__FUNCTION__);
		result = FALSE;
	}
	return result;
}
gboolean
sim_util_unblock_signal(int sig){
	sigset_t sigmask;
	sigaddset(&sigmask,sig);
	gboolean result = TRUE;
	/* Consume any pending SIGNAL*/
	sigset_t pending;
	sigpending(&pending);
	if (sigismember(&pending,sig)){
		struct timespec nowait = {0,0};
		int res;
		do{
			res = sigtimedwait(&sigmask,NULL,&nowait);
		}while (res == -1 && errno == EINTR);
	}
	if (pthread_sigmask(SIG_UNBLOCK,&sigmask,NULL)){
		g_log (G_LOG_DOMAIN, G_LOG_LEVEL_MESSAGE, "%s: Error unblocking signal",__FUNCTION__);
		result = FALSE;
	}
	return result;
}
gboolean
sim_util_wait_for_signal(int sig){
	
}

// vim: set tabstop=2 sts=2 noexpandtab:

