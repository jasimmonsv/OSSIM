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

#ifndef __SIM_EVENT_H__
#define __SIM_EVENT_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>
#include <uuid/uuid.h>

#include "sim-enums.h"
#include "sim-plugin.h"
#include "sim-plugin-sid.h"
#include "sim-packet.h"
#include "sim-text-fields.h"

#ifdef __cplusplus
extern "C"
{
#endif /* __cplusplus */

#define SIM_TYPE_EVENT                  (sim_event_get_type ())
#define SIM_EVENT(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_EVENT, SimEvent))
#define SIM_EVENT_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_EVENT, SimEventClass))
#define SIM_IS_EVENT(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_EVENT))
#define SIM_IS_EVENT_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_EVENT))
#define SIM_EVENT_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_EVENT, SimEventClass))

/* Needed to know which correlation process matched with a event. */
enum
{
	EVENT_MATCH_NOTHING = 0,
	EVENT_MATCH_DIRECTIVE_CORR,
	EVENT_MATCH_CROSS_CORR,
	EVENT_MATCH_DIRECTIVE_AND_CROSS
};


//SimPolicy is each one of the "lines" in the policy. It has one or more sources, one or more destinations, a time range, and so on.

#ifndef __TYPEPOLICY__
#define __TYPEPOLICY__
  typedef struct _SimPolicy SimPolicy;
  typedef struct _SimPolicyClass SimPolicyClass;
  typedef struct _SimPolicyPrivate SimPolicyPrivate;

  struct _SimPolicy
  {
    GObject parent;

    SimPolicyPrivate *_priv;
  };

  struct _SimPolicyClass
  {
    GObjectClass parent_class;
  };

#endif

  typedef struct _SimRole SimRole; //different event role

  struct _SimRole //this hasn't got any data from sensor associated.
  {
    gboolean correlate;
    gboolean cross_correlate;
    gboolean store;
    gboolean qualify;
    gboolean resend_event;
    gboolean resend_alarm;
  };

  typedef struct _SimHostServices SimHostServices; //used only for cross_correlation at this moment.
  struct _SimHostServices
  {
    gchar *ip;
    gint port;
    gint protocol;
    gchar *service;
    gchar *version;
    gchar *date;
    gchar *sensor;
  };

  G_BEGIN_DECLS

  typedef struct _SimEvent SimEvent;
  typedef struct _SimEventClass SimEventClass;

  struct _SimEvent
  {
    GObject parent;
    guint signature;
    gchar *sql_text_fields;
    guint id;
    guint id_tmp; //this applies only to table event_tmp, the column "id". It has nothing to do with the above id.
    //This id is needed to keep control about what events from that table.
    guint snort_sid;
    guint snort_cid;

    SimEventType type;

    /* Event Info */
    time_t time;
    gchar *time_str; // time as string
    time_t diff_time; //as soon as the event arrives, this is setted. Here is stored the difference between the parsed time from agent log
    //line, and the time when the event arrives to server.
    gchar *sensor;
    gchar *device;
    gchar *interface;
    gfloat tzone;

    /* Plugin Info */
    gint plugin_id;
    gint plugin_sid;
    gchar* plugin_sid_name; //needed for event_tmp table.

    /* Plugin Type Detector */
    SimProtocolType protocol;
    GInetAddr *src_ia;
    GInetAddr *dst_ia;
    gint src_port;
    gint dst_port;

    /* Plugin Type Monitor */
    SimConditionType condition;
    gchar *value;
    gint interval;
    gint absolute;

    /* Extra data */
    gboolean alarm;
    gint priority;
    gint reliability;
    gint asset_src;
    gint asset_dst;
    gdouble risk_c;
    gdouble risk_a;

    gchar *data;
    gchar *log;

    SimPlugin *plugin;
    SimPluginSid *pluginsid;

    /* Directives */
    gboolean sticky;
    gboolean match; // TRUE if this has been matched the rule in sim_rule_match_by_event()
    gboolean matched;
    gint count;
    gint level;
    guint32 backlog_id;
    gchar *rule_name;

    /* replication  server */
    gboolean rserver;

    gchar **data_storage; // This variable must be used ONLY to pass data between the sim-session and
    //sim-organizer, where the event is stored in DB.
    gboolean store_in_DB; //variable used to know if this specific event should be stored in DB or not. Used in Policy.
    gchar *buffer; //used to speed up the resending events so it's not needed to turn it again into a string

    gboolean is_correlated; //Just needed for MAC, OS, Service and HIDS events.
    // Take an example: server1 resend data to server2. We have correlated in server1 a MAC event.
    // Then we resend the event to server2 in both ways: "host_mac_event...." and "event...". Obviously,
    // "event..." is the event correlated, with priority, risk information and so on. But we don't want
    // to re-correlate "host_mac_event...", because the correlation information is in "event...". So in
    // sim_organizer_correlation() we check this variable. Also, in this way, we are able to correlate
    // the event with another event wich arrives to server2.

	gint              correlation;    /* Needed to know which correlation mechanism has matched this event.
																		 * Valid values:
																		 * EVENT_MATCH_NOTHING : 0             (didn't matched)
																		 * EVENT_MATCH_DIRECTIVE_CORR : 1      (matched with directive in correlation)
																		 * EVENT_MATCH_CROSS_CORR : 2          (matched in cross correlation process)
																		 * EVENT_MATCH_DIRECTIVE_AND_CROSS : 3 (matched with directive and cross correlation)
																		 */

    gboolean is_prioritized; // Needed to know in the master server if the event sent from children server has the priority changed or not.
    gboolean is_reliability_setted; //I dont' know how to reduce this variable, it's auto-explained :)
    SimRole *role;
    SimPolicy *policy;

    /* additional data (not necessary used) */
    /*
     gchar							*filename;
     gchar							*username;
     gchar							*password;
     gchar							*userdata1;
     gchar							*userdata2;
     gchar							*userdata3;
     gchar							*userdata4;
     gchar							*userdata5;
     gchar							*userdata6;
     gchar							*userdata7;
     gchar							*userdata8;
     gchar							*userdata9;
     */
    gchar *rulename;
    gchar *textfields[N_TEXT_FIELDS];
    gchar *ev_textfields[N_TEXT_FIELDS];
    gboolean isTextMatched[N_TEXT_FIELDS];
    /* packet data */
    SimPacket *packet;
    /* uuid */
    uuid_t uuid;
    uuid_t uuid_backlog;

    GList *backlog_list;

  };

  struct _SimEventClass
  {
    GObjectClass parent_class;
    gchar *sql_text_fields;
  };

  GType
  sim_event_get_type(void);
  SimEvent*
  sim_event_new(void);
  SimEvent*
  sim_event_new_from_type(SimEventType type);

  SimEvent*
  sim_event_clone(SimEvent *event);

  gchar*
  sim_event_get_insert_clause(SimEvent *event);
  gchar*
  sim_event_get_update_clause(SimEvent *event);
  gchar*
  sim_event_get_replace_clause(SimEvent *event);

  gchar*
  sim_event_get_alarm_insert_clause(SimEvent *event);
  gchar*
  sim_event_get_insert_into_event_tmp_clause(SimEvent *event);

  gchar*
  sim_event_to_string(SimEvent *event);

  void
  sim_event_print(SimEvent *event);

  gchar*
  sim_event_get_msg(SimEvent *event);
  gboolean
  sim_event_is_special(SimEvent *event);
  gchar*
  sim_event_get_str_from_type(SimEventType type);
  void
  sim_event_add_backlog_ref_ul(SimEvent *event, GObject *directive);
  const gchar *
  sim_event_get_sql_fields(void);
G_END_DECLS
#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_EVENT_H__ */

// vim: set tabstop=2:

