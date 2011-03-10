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

#include "os-sim.h"
#include "sim-organizer.h"
#include "sim-server.h"
#include "sim-host.h"
#include "sim-net.h"
#include "sim-plugin-sid.h"
#include "sim-policy.h"
#include "sim-rule.h"
#include "sim-directive-group.h"
#include "sim-directive.h"
#include "sim-host-level.h"
#include "sim-net-level.h"
#include "sim-connect.h"
#include <math.h>
#include <time.h>
#include <config.h>

extern SimMain ossim;

enum
{
  DESTROY, LAST_SIGNAL
};

struct _SimOrganizerPrivate
{
  SimConfig *config;
};

static gpointer parent_class = NULL;
static gint sim_container_signals[LAST_SIGNAL] =
  { 0 };

void
config_send_notify_email(SimConfig *config, SimEvent *event);
void
insert_event_alarm(SimEvent *event);

/* GType Functions */

static void
sim_organizer_impl_dispose(GObject *gobject)
{
  G_OBJECT_CLASS(parent_class)->dispose(gobject);
}

static void
sim_organizer_impl_finalize(GObject *gobject)
{
  SimOrganizer *organizer = SIM_ORGANIZER (gobject);

  g_free(organizer->_priv);

  G_OBJECT_CLASS(parent_class)->finalize(gobject);
}

static void
sim_organizer_class_init(SimOrganizerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS(class);

  parent_class = g_type_class_peek_parent(class);

  object_class->dispose = sim_organizer_impl_dispose;
  object_class->finalize = sim_organizer_impl_finalize;
}

static void
sim_organizer_instance_init(SimOrganizer *organizer)
{
  organizer->_priv = g_new0(SimOrganizerPrivate, 1);

  organizer->_priv->config = NULL;
}

/* Public Methods */

GType
sim_organizer_get_type(void)
{
  static GType object_type = 0;

  if (!object_type)
    {
      static const GTypeInfo type_info =
        { sizeof(SimOrganizerClass), NULL, NULL,
            (GClassInitFunc) sim_organizer_class_init, NULL, NULL, /* class data */
            sizeof(SimOrganizer),

            0, /* number of pre-allocs */
            (GInstanceInitFunc) sim_organizer_instance_init, NULL /* value table */
        };

      g_type_init();

      object_type = g_type_register_static(G_TYPE_OBJECT, "SimOrganizer",
          &type_info, 0);
    }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimOrganizer*
sim_organizer_new(SimConfig *config)
{
  SimOrganizer *organizer = NULL;

  g_return_val_if_fail(config, NULL);
  g_return_val_if_fail(SIM_IS_CONFIG (config), NULL);

  organizer = SIM_ORGANIZER (g_object_new (SIM_TYPE_ORGANIZER, NULL));
  organizer->_priv->config = config;

  return organizer;
}

/*
 * Send the monitor rules to the agent
 */
static gpointer
sim_organizer_thread_monitor_rule(gpointer data)
{
  SimRule *rule;

  while (TRUE)
    {
      rule = (SimRule *) sim_container_pop_monitor_rule(ossim.container);//get and remove the last monitor rule in queue

      if (!rule)
        continue;

      sim_server_push_session_plugin_command(ossim.server,
          SIM_SESSION_TYPE_SENSOR, sim_rule_get_plugin_id(rule), rule);

    }

}

/*
 *
 *
 *
 */
void
sim_organizer_run(SimOrganizer *organizer)
{

  /****************
   GList *alist;
   GNode *anode;
   SimRule *arule;
   g_mutex_lock (ossim.mutex_directives);
   alist = sim_container_get_directives_ul (ossim.container);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "99999999999999999999999999999999999999999999");
   SimDirective *adirective = (SimDirective *) alist->data;
   anode = sim_directive_get_root_node (adirective);
   GNode *achildren = anode->children;
   arule = achildren->data;
   sim_rule_print(arule);
   g_mutex_unlock (ossim.mutex_directives);
   ******************/

  SimEvent *event = NULL;
  SimCommand *cmd = NULL;
  gchar *str;
  SimConfig *config;
  GThread *thread;
  GThread *ar_thread;

  g_return_if_fail(organizer != NULL);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));

  // New thread for the Monitor requests. Rules will be inserted into a queue, and then extracted in this thread
  thread
      = g_thread_create(sim_organizer_thread_monitor_rule, NULL, FALSE, NULL);
  g_return_if_fail(thread);
  g_thread_set_priority(thread, G_THREAD_PRIORITY_NORMAL);
  ar_thread = g_thread_create_full(sim_connect_send_alarm,
      organizer->_priv->config, 0, FALSE, TRUE, G_THREAD_PRIORITY_HIGH, NULL);
  g_return_if_fail(ar_thread);
  g_thread_set_priority(ar_thread, G_THREAD_PRIORITY_URGENT);

  while (TRUE)
    {
      event = sim_container_pop_event(ossim.container);//gets and remove the last event in queue
      sim_server_debug_print_sessions(ossim.server);

      if (!event)
        continue;

      if (event->type == SIM_EVENT_TYPE_NONE)
        {
          g_object_unref(event);
          continue;
        }

      /********debug******/
      str = sim_event_to_string(event);
      g_message("Event received: %s", str);
      g_free(str);
      /*********************/
      if (uuid_is_null(event->uuid))
        {
          uuid_generate(event->uuid);
        }
      //sim_event_sanitize (event); //do some checks.

      config = sim_server_get_config(ossim.server);

      //now we can segregate and tell this server to do a specific	thing.
      //For example we can decide that this server will be able to qualify events, but not to correlate them.

      SimPolicy *policy;
      policy = sim_organizer_get_policy(organizer, event);

      SimRole *role;

      //The policy role (if any) supersedes the general server role.
      if (policy)
        {
          //get the role of this event to know if it should been treat as a specific case.
          role = sim_policy_get_role(policy);
          event->policy = policy;
        }
      else
        role = config->server.role;

      if (role == NULL) //FIXME: temporary fix.
        role = config->server.role;

      sim_role_print(role);

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_run BEFORE event->reliability: %d", event->reliability);

      //FIXME: Really temporal check. I have to change this to let loading of correlation_plugin in memory and do the check against it.
      //I'll start to change this on 1st November. Now, if there aren't local DB, cross-correlation won't work. In fact, the cross-correlation
      //is only needed to change the priority & reliability of all plugin_sids of the dst from the event, so if correlation is done in a
      //master server it's not a real problem.
      if (sim_database_is_local(ossim.dbossim))
        if (role->cross_correlate)
          {
            GInetAddr *ia_zero = gnet_inetaddr_new_nonblock("0.0.0.0", 0);
            if (!gnet_inetaddr_noport_equal(event->dst_ia, ia_zero))
              sim_organizer_correlation_plugin(organizer, event); //Actualize reliability. Also, event -> alarm.
            g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                "sim_organizer_run AFTER event->reliability: %d",
                event->reliability);
            gnet_inetaddr_delete(ia_zero);
          }

      if (role->qualify)
        {
          if (!sim_organizer_reprioritize(organizer, event, policy)) //actualice priority (if match with some policy)
            {
              g_object_unref(event);
              continue;
            }

          sim_organizer_risk_levels(organizer, event); // actualice c&a, event->alarm (get risk)
        }

      if (sim_database_is_local(ossim.dbossim))
        {
          if (role->store)
            sim_organizer_snort(organizer, event); //insert the snort or other event into snort db.  Events regarding alarms are not stored
          //here. They're stored inside sim_organizer_correlation(). Data from service & OS events is
          //stored here in ossim.host_plugin_sid.
          sim_organizer_rrd(organizer, event);

          if (role->correlate)
            {
              insert_event_alarm(event); //insert alarm in ossim db & assign event->id

              //If the event is too old (i.e. when the agent-server has been disconnected some time) , we can't do the correlation. We can't correlate a old event
              //because it could give false information to the Alarms. This happens also in C&A updating in sim_organizer_qualify,
              //as it would show strange data in Metrics
              //FIXME: We have to re-think this
              //				if (event->diff_time < MAX_DIFF_TIME)
              //				{
              sim_organizer_correlation(organizer, event); //correlation process
              //				}
              //				else
              //				{
              //					g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: sim_organizer_snort time_diff is more than MAX_DIFF_TIME");
              //				}
            }
        }

      //needed for action/respose.
      gboolean ev_act = FALSE;
      if (event->policy)
        if (sim_policy_get_has_actions(event->policy))
          ev_act = TRUE;

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_run: event->policy: %x, bool: %d", event->policy,
          ev_act);
      if (event->alarm || ev_act)
        {
          g_object_ref(event);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_run: Inserting it into queue to send it to frameworkd");
          sim_container_push_ar_event(ossim.container, event);

          // Uncomment this to get only the alarms in the event viewer
          //if (sim_database_is_local (ossim.dbossim))
          //sim_organizer_store_event_tmp (event);  //stores the event inside the dinamic panel so users can see the events in realtime.
          //as this includes risk information, it will be inserted just in case the event is prioritized
        }

      /*
       str = sim_event_to_string (event);
       g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_run after calculations: %s", str);
       g_free (str);
       */
      //time to resend the event/alarm  to master server/s
      //FIXME: This code is unstable and unmaintained, uncomment this at your own risk
      //if (role->resend_alarm || role->resend_event)
      //{
      //	sim_organizer_resend (event, role);
      //}

      //uncomment this if you want that each time a mac or os change event arrives, an alarm is sent to the framework
      //fixme: transform this unifying policy with action/responses.
      /*
       g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_run: plugin-id: %d, plugin-sid: %d", event->plugin_id, event->plugin_sid);
       if (((event->plugin_id == sim_event_host_os_event) || (event->plugin_id == sim_event_host_mac_event) )&& (event->plugin_sid == event_same))
       {
       g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sending mac event/alarm to frameworkd");
       sim_connect_send_alarm (organizer->_priv->config,event);
       }
       */
      g_object_unref(event);
    }
}

SimPolicy *
sim_organizer_get_policy(SimOrganizer *organizer, SimEvent *event)
{
  SimPluginSid *plugin_sid = NULL;
  SimPolicy *policy = NULL;

  g_return_if_fail(organizer != NULL);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event != NULL);
  g_return_if_fail(SIM_IS_EVENT (event));

  SimPortProtocol *pp;
  gint date = 0;
  gint i;
  struct tm *loctime;
  time_t curtime;

  /*
   * get current day and current hour
   * calculate date expresion to be able to compare dates
   *
   * for example, fri 21h = ((5 - 1) * 24) + 21 = 117
   *              sat 14h = ((6 - 1) * 24) + 14 = 134
   *
   * tm_wday returns the number of days since Sunday, in the range 0 to 6.
   *
   */

  curtime = time(NULL);
  loctime = localtime(&curtime);
  date = (loctime->tm_wday * 24) + loctime->tm_hour;

  //get the port/protocol used to obtain the policy that matches.
  pp = sim_port_protocol_new(event->dst_port, event->protocol);
  //	policy = (SimPolicy *)
  policy = sim_container_get_policy_match(
      ossim.container, //check if some policy applies, so we get the new priority
      date, event->src_ia, event->dst_ia, pp, event->sensor, event->plugin_id,
      event->plugin_sid);
  g_free(pp);

  if (policy)
    g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
        "sim_organizer_get_policy: Policy %d MATCH", sim_policy_get_id(policy));
  else
    g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
        "sim_organizer_get_policy: Policy No MATCH");

  return policy;

}

/*
 * all kind of events (including os, mac, service and hids).
 */
void
sim_organizer_resend(SimEvent *event, SimRole *role)
{
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  gchar *event_str = sim_event_to_string(event);
  gboolean alarm_sended = FALSE;

  if (role->resend_alarm)
    {
      if (event->alarm)
        {
          sim_session_resend_buffer(event_str); //FIXME: this will send only the "alarm", not the events wich generated
          //that alarm. check how to do this. (new message? index the alarms?...)
          alarm_sended = TRUE;
        }
    }

  if (role->resend_event)
    {
      if (sim_event_is_special(event))
        {
          sim_session_resend_buffer(event->buffer); //in this case, we prefer to send the data in the same
          //way we received it, so we can treat it specially (storing it in special tables).
          //the master server won't correlate this events again.
        }
      if ((!alarm_sended) && (!event->alarm))
        sim_session_resend_buffer(event_str); //this won't be an alarm, just an event with some modifications in priority, risk..


    }

}

/*
 *
 * This is usefull only if the event has the "alarm" flag. This can occur for example if the event has
 * priority&reliability very high and it has been converted automatically into an alarm. Also, this can occur
 * if the event is a directive_event wich has been re-inserted into container from sim_organizer_correlation().
 * 
 * we also assign here an event->id (if it hasn't got anyone, like the first time the event arrives).
 * event->id is just needed to know if that event belongs to a specific backlog_id (a directive), so if
 * an event is not part of an alarm, it hasn't got any sense to fill event->id.
 *
 */
void
insert_event_alarm(SimEvent *event)
{
  GdaDataModel *dm;
  GdaValue *value;
  guint backlog_id = 0;
  gint row;
  gchar *query0;
  gchar *query1;

  if (!event->alarm)
    return;

  if (!event->id)
    {
      sim_container_db_insert_event(ossim.container, ossim.dbossim, event); //the event (wooops, i mean, the alarm) is new
      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "insert_event_alarm: inserting new event into alarms. No id. event->id: %d",
          event->id);
    }
  else
    {
      sim_container_db_replace_event_ul(ossim.container, ossim.dbossim, event); //update the event (it depends on event id)
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "insert_event_alarm: updating event into alarms. event->id: %d",
          event->id);
    }

  //we check if the event could be part of an alarm.
  query0
      = g_strdup_printf(
          "SELECT backlog_id, MAX(event_id) FROM backlog_event GROUP BY backlog_id HAVING MAX(event_id) = %d",
          event->id); //one backlog_id can handle multiple event_id's. we choose the bigger event_id if it coincides with the event->id.
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "insert_event_alarm: query %s", query0);

  dm = sim_database_execute_single_command(ossim.dbossim, query0);
  if (dm)
    {
      if (!gda_data_model_get_n_rows(dm)) //first event inserted as alarm
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "insert_event_alarm: AA");
          if (event->backlog_id) //if the event is also part of an alarm
            {
              query1 = g_strdup_printf(
                  "DELETE FROM alarm WHERE backlog_id = %u", event->backlog_id);
              sim_database_execute_no_query(ossim.dbossim, query1);

              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "insert_event_alarm: query1:%s", query1);
              g_free(query1);
            }
          else
            {
              event->backlog_id = sim_database_get_id(ossim.dbossim,
                  BACKLOG_SEQ_TABLE);
            }

          query1 = sim_event_get_alarm_insert_clause(event);
          sim_database_execute_no_query(ossim.dbossim, query1);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "insert_event_alarm: query1: %s", query1);
          g_free(query1);
        }
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "insert_event_alarm: BB");

      for (row = 0; row < gda_data_model_get_n_rows(dm); row++) //all the events (the alarms, in fact) enter here (except the first)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          if (!gda_value_is_null(value))
            backlog_id = gda_value_get_bigint(value);

          query1 = g_strdup_printf("DELETE FROM alarm WHERE backlog_id = %u",
              backlog_id);
          sim_database_execute_no_query(ossim.dbossim, query1);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "insert_event_alarm: query1 -2: %s", query1);
          g_free(query1);

          event->backlog_id = backlog_id;
          query1 = sim_event_get_alarm_insert_clause(event);
          if (sim_database_execute_no_query(ossim.dbossim, query1) == -1)
            g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: Database problems");

          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "insert_event_alarm: query1 -3: %s", query1);
          g_free(query1);

        }
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "insert_event_alarm: CC");

      g_object_unref(dm);
    }
  else
    g_message("organizer alarm insert data model error");

  g_free(query0);

}

/*
 * fixme: this function isn't called from anywhere at this time.
 *
 *
 */
void
config_send_notify_email(SimConfig *config, SimEvent *event)
{
  SimAlarmRiskType type = SIM_ALARM_RISK_TYPE_NONE;
  GList *notifies;
  gint risk;

  g_return_if_fail(config);
  g_return_if_fail(SIM_IS_CONFIG (config));

  if (!config->notifies)
    return;

  risk = rint(event->risk_a);
  type = sim_get_alarm_risk_from_risk(risk);

  notifies = config->notifies;
  while (notifies)
    {
      SimConfigNotify *notify = (SimConfigNotify *) notifies->data;

      GList *risks = notify->alarm_risks;
      while (risks)
        {
          risk = GPOINTER_TO_INT(risks->data);

          if (risk == SIM_ALARM_RISK_TYPE_ALL || risk == type)
            {
              gchar *tmpname;
              gchar *cmd;
              gchar *msg;
              gint fd;

              tmpname = g_strdup("/tmp/ossim-mail.xxxxxx");
              fd = g_mkstemp(tmpname);

              msg = g_strdup_printf("subject: ossim alarm risk (%d)\n", risk);
              write(fd, msg, strlen(msg));
              g_free(msg);
              write(fd, "\n", 1);

              msg = sim_event_get_msg(event);
              write(fd, msg, strlen(msg));
              g_free(msg);

              write(fd, ".\n", 2);

              cmd = g_strdup_printf("%s %s < %s", config->notify_prog,
                  notify->emails, tmpname);
              system(cmd);
              g_free(cmd);

              close(fd);
              unlink(tmpname);
              g_free(tmpname);
            }

          risks = risks->next;
        }
      notifies = notifies->next;
    }
}

/*
 *
 * actualize the reliability of all the plugin_sids of the dst_ia from the event. *
 * also, if the event has plugin_sids associated, the event is transformed into an alarm.
 * this function has sense just with events with a defined dst. and those events has to have some relationship
 * with others (see sim_container_db_host_get_plugin_sids_ul())
 */
void
sim_organizer_correlation_plugin(SimOrganizer *organizer, SimEvent *event)
{
  //GList           *list;
  GList *list_host;
  GList *list_OS;
  GList *list_ports;
  GList *list_refsid;
  GList *list_base_name;
  GList *list_version_name;
  GInetAddr *ip_temp;
  gint plugin_id;
  gboolean aux_os = FALSE;
  gboolean aux_os_tested = FALSE; //this variable is needed if we want to reduce the number of iterations
  gboolean aux_nessus = FALSE;
  gboolean aux_port = FALSE;
  gboolean aux_string = FALSE;
  gboolean aux_version_name = FALSE;
  gboolean aux_base_name = FALSE;
  gboolean aux_generic = FALSE;
  SimHostServices *HostService;
  gint sid;
  gint name_nessus;

  GdaDataModel *dm;
  GdaValue *value;
  gchar *query;
  gchar *base_name;
  gchar *version_name;

  g_return_if_fail(organizer);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_correlation_plugin: entering");
  if (!event->dst_ia)
    return;

  list_host = sim_container_db_host_get_single_plugin_sid(ossim.container,
      ossim.dbossim, event->dst_ia);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_correlation_plugin: Number of entries: %d", g_list_length(
          list_host));

  if (!list_host) //if there aren't any plugin_sid associated with the dst_ia...
    return;

  while (list_host)
    {
      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "sim_organizer_correlation_plugin: Checking host : event->dst_ia: %u",
          sim_inetaddr_ntohl(event->dst_ia));

      SimPluginSid *plugin_sid = (SimPluginSid *) list_host->data;

      plugin_id = sim_plugin_sid_get_plugin_id(plugin_sid);
      sid = sim_plugin_sid_get_sid(plugin_sid);
      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "sim_organizer_correlation_plugin: BBDD: %d - %d *** Evento: %d - %d",
          plugin_id, sid, event->plugin_id, event->plugin_sid);

      if (plugin_id == sim_container_get_plugin_id_by_name(ossim.container,
          "nessus")) //match nessus attack
        {
          if (sim_container_db_plugin_reference_match(ossim.container,
              ossim.dbossim, event->plugin_id, event->plugin_sid, plugin_id,
              sid))
            {
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation_plugin: Match! Nessus vuln found");
              event->reliability = 10;
              event->is_reliability_setted = TRUE;
              aux_nessus = TRUE;
            }
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_correlation_plugin: NESSUS");
        }
      else if (plugin_id == sim_container_get_plugin_id_by_name(
          ossim.container, "os")) ////match O.S.
        {
          list_OS = sim_container_db_get_reference_sid(ossim.container,
              ossim.dbossim, plugin_id, //SO reference_id, probably 5001
              event->plugin_id, event->plugin_sid);
          aux_os_tested = TRUE; //needed if we want to "stop" the iteration when the OS is found.
          while (list_OS)
            {
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation_plugin: OS, list_OS= %d",
                  GPOINTER_TO_INT(list_OS->data));
              if (GPOINTER_TO_INT(list_OS->data) == sid) //match O.S.?
                aux_os = TRUE;
              list_OS = list_OS->next;
            }

          if (list_OS) //just in case there are some OS's listed
            {
              if (!aux_os)
                {
                  event->reliability = 0; //the host O.S. differs from the type of atack. this won't be successfull.
                  event->is_reliability_setted = TRUE;
                  return;
                }
              else
                {
                  event->reliability += 1;
                  event->is_reliability_setted = TRUE;
                }

            }
        }
      else if (plugin_id == sim_container_get_plugin_id_by_name(
          ossim.container, "services")) //match port &&/|| version
        {
          list_ports = sim_container_db_get_host_services(ossim.container,
              ossim.dbossim, event->dst_ia, event->sensor, event->dst_port);

          while (list_ports)
            {
              HostService = (SimHostServices *) list_ports->data;
              g_log(
                  G_LOG_DOMAIN,
                  G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation_plugin: SERVICES port/proto= %d/%d",
                  event->dst_port, event->protocol);
              g_log(
                  G_LOG_DOMAIN,
                  G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation_plugin: SERVICES HostService port/proto: %d/%d",
                  HostService->port, HostService->protocol);
              if (event->dst_port == sid)
                {
                  if (HostService->protocol != event->protocol) //event->protocol != protocol stored inside host_services table
                    aux_port = TRUE;
                  else
                    {
                      aux_port = FALSE;
                      break;
                    }
                } //event->port == sid
              list_ports = list_ports->next;
            }
          if (aux_port) //if the attack is (i.e.) UDP, but we know that this machine only has that specific TCP port open, this is not an attack and reliability is zero.
            {
              event->reliability = 0;
              event->is_reliability_setted = TRUE;
              return;
            }
#if 0			
          else //if the protocol (tcp or udp) matches...
          if (event->dst_port == sid) //and of course if the port matches...

            { //if there are relationship between OSVDB and the event, we'll try to check the strings
              if (list_refsid = sim_container_db_get_reference_sid (ossim.container,
                      ossim.dbossim,
                      5003, //OSVDB reference_id
                      event->plugin_id,
                      event->plugin_sid))
                { //in this case (osvdb checking) very probably here will appears only one base_name.
                  //It's a list just because we use that function in more places
                  while (list_refsid)
                    {
                      list_base_name = sim_container_db_get_osvdb_base_name (ossim.dbosvdb, GPOINTER_TO_INT (list_refsid->data));
                      while (list_base_name)
                        {
                          gchar *cmp_base_name = (gchar *) list_base_name->data;
                          g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_correlation_plugin: SERVICES OSVDB cmp base name= %s/%s", cmp_base_name, HostService->version);
                          //do the check always in lower case:
                          gchar *lower_hostversion = g_ascii_strdown (HostService->version, strlen (HostService->version));
                          gchar *lower_cmpbasename = g_ascii_strdown (cmp_base_name, strlen (cmp_base_name));

                          if (g_strstr_len (lower_hostversion, strlen (lower_hostversion), lower_cmpbasename))
                            {
                              event->reliability += 2; //if the base name ("Apache") matches...
                              event->is_reliability_setted = TRUE;
                              //we have to check if also matches the version number ("4.3.4" i.e.)
                              list_version_name = sim_container_db_get_osvdb_version_name (ossim.dbosvdb, GPOINTER_TO_INT (list_refsid->data));
                              while (list_version_name)
                                {
                                  gchar *cmp_version_name = (gchar *) list_version_name->data;
                                  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_correlation_plugin: SERVICES OSVDB cmp version name= %s", cmp_version_name);
                                  gchar *lower_versionname = g_ascii_strdown (cmp_version_name, strlen (cmp_version_name));
                                  if (g_strstr_len (lower_hostversion, strlen (lower_hostversion), lower_versionname))
                                    {
                                      event->reliability = 9;
                                      event->is_reliability_setted = TRUE;
                                      aux_version_name = TRUE;
                                      break;
                                    }
                                  g_free (lower_versionname);
                                  list_version_name = list_version_name->next;
                                }
                              if (aux_version_name)
                              break;
                            }
                          g_free (lower_hostversion);
                          g_free (lower_cmpbasename);
                          list_base_name = list_base_name->next;
                        }

                      list_refsid = list_refsid->next;
                      if (aux_version_name)
                      break;
                    }
                }
            }
#endif
        }
      else //this is a "generic" type. This will match any new type that the user defines.
        { //For example, a user may want to do cross-correlation between 2 new plugins, say 25000 and 25001.
          //He need to insert plugin_id 25001 and plugin_sid 1 (i.e.) into host_plugin_sid. After that he needs to fill the
          //plugin_reference table wiuth data like (25000, 1, 25001, 22), so plugin sid 1 has a relationship with plugin_sid 22.
          //If the correlation is done in this way, we set the reliability to 10.

          //this is exactly the same case than the nessus correlation
          if (sim_container_db_plugin_reference_match(ossim.container,
              ossim.dbossim, event->plugin_id, event->plugin_sid, plugin_id,
              sid))
            {
              event->reliability += sim_plugin_sid_get_reliability(plugin_sid);
              event->is_reliability_setted = TRUE;
              aux_generic = TRUE;
            }
        }

      if (aux_nessus || aux_generic) //we know that it's a real attack thanks to nessus or generic cross-correlation.
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_correlation_plugin: aux_nessus || aux_generic");
          break;
        }
      if ((!aux_os) && aux_os_tested) //if the OS doesn't matches, nothing else matters
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_correlation_plugin: aux_OS");
          break;
        }

      list_host = list_host->next;
    }
  g_list_free(list_host);

  if (event->reliability >= 1) //FIXME: generating an alarm with just 1?
    event->alarm = TRUE;

  if (event->reliability >= 10)
    event->reliability = 10;
}

/*
 * 1.- Modifies the priority if the event belongs to a policy
 * 
 * Remember that event could be a directive_event. In that case the plugin_id will be 1505 and the 
 * plugin_sid will be the directive id that matched. That directive_id is inserted into plugin_sid table when the
 * server starts. 
 * This function returns 0 on error.
 */
gint
sim_organizer_reprioritize(SimOrganizer *organizer, SimEvent *event,
    SimPolicy *policy)
{
  SimPlugin *plugin;
  SimPluginSid *plugin_sid = NULL;

  g_return_if_fail(organizer != NULL);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event != NULL);
  g_return_if_fail(SIM_IS_EVENT (event));

  //get plugin-sid objects from the plugin_id and plugin_sid of the event
  plugin_sid = sim_container_get_plugin_sid_by_pky(ossim.container,
      event->plugin_id, event->plugin_sid);
  if (!plugin_sid)
    {
      g_message(
          "sim_organizer_reprioritize: No priority/reliability info (Plugin_id %d, Plugin_Sid %d) Log: %s",
          event->plugin_id, event->plugin_sid, event->log);
      return 0;
    }

  //this is needed for event_tmp table; the dinamic event viewer
  event->plugin_sid_name = g_strdup(sim_plugin_sid_get_name(plugin_sid));

  //if the event has been prioritized in the children server, the master server can't modify it. Apart, the master server just
  //can modify the Priority in case the event matches with Policy.
  if (policy)
    {
      gint aux;
      if ((aux = sim_policy_get_priority(policy)) != -1) //-1 in policy means that it won't affect to the priority
        {
          event->priority = aux;
        }
      else if ((aux = sim_plugin_sid_get_priority(plugin_sid)) == -1) //if -1 (return value), priority doesn't exists.
        {
          g_message(
              "Error: Unable to fetch priority for plugin id %d, plugin sid %d",
              event->plugin_id, event->plugin_sid);
          return 0;
        }
      else if (!event->is_prioritized) //Take care that if it's prioritized, it wont be changed
        {
          event->is_prioritized = TRUE;
          event->priority = aux;
        }
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_reprioritize: Policy Match. new priority: %d",
          event->priority);
    }
  else //set the priority from plugin DB (if not prioritized early in other server down in architecture).
    {
      if (((event->priority = sim_plugin_sid_get_priority(plugin_sid)) == -1)
          && (!event->is_prioritized))
        {
          g_message(
              "Error:  Unable to fetch priority for plugin id %d, plugin sid %d",
              event->plugin_id, event->plugin_sid);
          return 0;
        }
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_reprioritize: Policy Doesn't match, priority: %d",
          event->priority);
    }

  // Get the reliability of the plugin sid. There is a reliability inside the directive, but the
  // reliability from the plugin is more important. So we usually try to get the plugin reliability from DB
  // and assign it to the event.
  if ((event->plugin_id != SIM_PLUGIN_ID_DIRECTIVE)
      && (!event->is_reliability_setted))
    {
      gint aux;
      if ((aux = sim_plugin_sid_get_reliability(plugin_sid)) != -1)
        {
          event->reliability = aux;
          event->is_prioritized = TRUE;
        }
    }

  return 1;
  //FIXME: When the event is a directive_event (plugin_id 1505), inside the event->data appears (inserted in sim_organizer_correlation)
  //with the "old" priority. Its needed to re-write the data and modify the priority (if the policy modifies it, of course).


}

/*
 * 1.- Update everything's C and A. If there are not local DB, it only will update memory, enough to forward events.
 * 2.- Calculate Risk. If Risk >= 1 then transform the event into an alarm
 * 
 */
gint
sim_organizer_risk_levels(SimOrganizer *organizer, SimEvent *event)
{
  SimHost *host;
  SimNet *net;
  SimHostLevel *host_level;
  SimNetLevel *net_level;
  GList *list_inet; //SimInet
  GList *list;
  GList *nets; //SimNet
  gint mask;
  gint best_mask;
  gchar *ip_temp;

  g_return_if_fail(organizer != NULL);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event != NULL);
  g_return_if_fail(SIM_IS_EVENT (event));

  // If the destination IP is "0.0.0.0" (p0f, MAc events...) it won't increase the C or A level.
  // FIXME: plugin_id doesn't matters because it hasn't got priority by itsel, although it should!.

  ip_temp = gnet_inetaddr_get_canonical_name(event->src_ia);

  if ((event->src_ia) && (ip_temp)) //error checking
    {
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_risk_levels event->src_ia: -%s-", ip_temp);

      /* Source Asset */
      host = sim_container_get_host_by_ia(ossim.container, event->src_ia);
      nets = sim_container_get_nets_has_ia(ossim.container, event->src_ia);

      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "sim_organizer_risk_levels 1: priority:%d asset:%d reliability:%d risk:%f",
          event->priority, event->asset_src, event->reliability, event->risk_c);
      if (host) //writes the event->asset_src choosing between host (if available) or net.
        {
          event->asset_src = sim_host_get_asset(host);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_risk_levels: asset_host %d-", event->asset_src);
        }
      else if (nets)
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_risk_levels: nets");
          list = nets;
          SimInet *src = sim_inet_new_from_ginetaddr(event->src_ia);

          best_mask = -1;
          while (list)
            {
              net = (SimNet *) list->data;

              // Search through nets an inet that match host and has the highest mask
              list_inet = sim_net_get_inets(net);
              while (list_inet)
                {
                  SimInet *cmp = (SimInet *) list_inet->data;

                  if (sim_inet_has_inet(cmp, src)) //check if src belongs to cmp.
                    {
                      mask = sim_inet_get_mask(cmp);
                      if (mask > best_mask)
                        {
                          best_mask = mask;
                          event->asset_src = sim_net_get_asset(net);
                          g_log(
                              G_LOG_DOMAIN,
                              G_LOG_LEVEL_DEBUG,
                              "sim_organizer_risk_levels: asset_net_src (%s) %d-",
                              sim_net_get_name(net), event->asset_src);
                        }
                    }

                  list_inet = list_inet->next;
                } // while inet

              list = list->next;
            } // while net
          g_object_unref(src);
        }

      //check if the source could be an alarm. This is our (errr) "famous" formula!
      event->risk_c = ((double) (event->priority * event->asset_src
          * event->reliability)) / 25;
      if (event->risk_c < 0)
        event->risk_c = 0;
      else if (event->risk_c > 10)
        event->risk_c = 10;

      if (event->risk_c >= 1)
        event->alarm = TRUE;

      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "sim_organizer_risk_levels: priority:%d asset:%d reliability:%d risk:%f",
          event->priority, event->asset_src, event->reliability, event->risk_c);

      //If the event is too old (i.e. when the agent-server has been disconnected some time) , we don't want to update C in order to avoid surprising peaks in the metrics
      //This happens also with the Correlation in sim_organizer_correlation() because we can't correlate something wich has occur in the past.
      //FIXME: We have reverted this change until further study
      //		if (event->diff_time < MAX_DIFF_TIME)
      //		{
      /* Updates Host Level C */
      host_level = sim_container_get_host_level_by_ia(ossim.container,
          event->src_ia);
      if (host_level)
        {
          sim_host_level_plus_c(host_level, event->risk_c); /* Memory update */
          if (sim_database_is_local(ossim.dbossim))
            sim_container_db_update_host_level(ossim.container, ossim.dbossim,
                host_level); /* DB update */
        }
      else
        {
          if (host_level = sim_host_level_new(event->src_ia, event->risk_c, 0)) /* Create new host_level */
            {
              sim_container_append_host_level(ossim.container, host_level); /* Memory addition */
              if (sim_database_is_local(ossim.dbossim))
                sim_container_db_insert_host_level(ossim.container,
                    ossim.dbossim, host_level); /* DB insert */
            }
        }

      /* Update Net Levels C */
      list = nets;
      while (list)
        {
          net = (SimNet *) list->data;

          net_level = sim_container_get_net_level_by_name(ossim.container,
              sim_net_get_name(net));
          if (net_level)
            {
              sim_net_level_plus_c(net_level, event->risk_c); /* Memory update */
              if (sim_database_is_local(ossim.dbossim))
                sim_container_db_update_net_level(ossim.container,
                    ossim.dbossim, net_level); /* DB update */
            }
          else
            {
              net_level = sim_net_level_new(sim_net_get_name(net),
                  event->risk_c, 0);
              sim_container_append_net_level(ossim.container, net_level); /* Memory addition */
              if (sim_database_is_local(ossim.dbossim))
                sim_container_db_insert_net_level(ossim.container,
                    ossim.dbossim, net_level); /* DB insert */
            }

          list = list->next;
        }
      //		} //event->diff_time < MAX_DIFF_TIME
      g_list_free(nets);
    }
  g_free(ip_temp);

  //if destination is "0.0.0.0", it will be very probably a MAC, a OS event, or something like that. And we shouldn't
  //update the C & A of destination because it doesn't exists.
  ip_temp = gnet_inetaddr_get_canonical_name(event->dst_ia);
  if ((event->dst_ia) && (ip_temp) && (strcmp(ip_temp, "0.0.0.0")))
    {
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_risk_levels event->dst_ia: %s", ip_temp);

      /* Destination Asset */
      host = (SimHost *) sim_container_get_host_by_ia(ossim.container,
          event->dst_ia);
      nets = sim_container_get_nets_has_ia(ossim.container, event->dst_ia);

      if (host)
        event->asset_dst = sim_host_get_asset(host);
      else if (nets)
        {
          list = nets;
          SimInet *dst = sim_inet_new_from_ginetaddr(event->dst_ia);

          best_mask = -1;
          while (list)
            {
              net = (SimNet *) list->data;

              // Search through nets an inet that match host and has the highest mask
              list_inet = sim_net_get_inets(net);
              while (list_inet)
                {
                  SimInet *cmp = (SimInet *) list_inet->data;

                  if (sim_inet_has_inet(cmp, dst)) //check if dst belongs to cmp.
                    {
                      mask = sim_inet_get_mask(cmp);
                      if (mask > best_mask)
                        {
                          best_mask = mask;
                          event->asset_dst = sim_net_get_asset(net);
                          g_log(
                              G_LOG_DOMAIN,
                              G_LOG_LEVEL_DEBUG,
                              "sim_organizer_risk_levels: asset_net_dst (%s) %d-",
                              sim_net_get_name(net), event->asset_dst);
                        }
                    }

                  list_inet = list_inet->next;
                } // while inet

              list = list->next;
            } // while net
          g_object_unref(dst);
        }

      event->risk_a = ((double) (event->priority * event->asset_dst
          * event->reliability)) / 25;
      if (event->risk_a < 0)
        event->risk_a = 0;
      else if (event->risk_a > 10)
        event->risk_a = 10;

      if (event->risk_a >= 1)
        event->alarm = TRUE;

      g_log(
          G_LOG_DOMAIN,
          G_LOG_LEVEL_DEBUG,
          "sim_organizer_risk_levels: priority:%d asset:%d reliability:%d risk:%f",
          event->priority, event->asset_dst, event->reliability, event->risk_a);

      //If the event is too old (i.e. when the agent-server has been disconnected some time) , we don't want to update A
      //		if (event->diff_time < MAX_DIFF_TIME)
      //		{
      /* Updates Host Level A */
      host_level = sim_container_get_host_level_by_ia(ossim.container,
          event->dst_ia);
      if (host_level)
        {
          sim_host_level_plus_a(host_level, event->risk_a); /* Memory update */
          if (sim_database_is_local(ossim.dbossim))
            sim_container_db_update_host_level(ossim.container, ossim.dbossim,
                host_level); /* DB update */
        }
      else
        {
          host_level = sim_host_level_new(event->dst_ia, 0, event->risk_a); /* Create new host*/
          sim_container_append_host_level(ossim.container, host_level); /* Memory addition */
          if (sim_database_is_local(ossim.dbossim))
            sim_container_db_insert_host_level(ossim.container, ossim.dbossim,
                host_level); /* DB insert */
        }

      /* Update Net Levels A */
      list = nets;
      while (list)
        {
          net = (SimNet *) list->data;

          net_level = sim_container_get_net_level_by_name(ossim.container,
              sim_net_get_name(net));
          if (net_level)
            {
              sim_net_level_plus_a(net_level, event->risk_a); /* Memory update */
              if (sim_database_is_local(ossim.dbossim))
                sim_container_db_update_net_level(ossim.container,
                    ossim.dbossim, net_level); /* DB update */
            }
          else
            {
              net_level = sim_net_level_new(sim_net_get_name(net), 0,
                  event->risk_a);
              sim_container_append_net_level(ossim.container, net_level); /* Memory addition */
              if (sim_database_is_local(ossim.dbossim))
                sim_container_db_insert_net_level(ossim.container,
                    ossim.dbossim, net_level); /* DB insert */
            }

          list = list->next;
        }
      //		} // (event->diff_time < MAX_DIFF_TIME)
      g_list_free(nets);
    }
  g_free(ip_temp);

}

/*
 *
 *
 *
 */
void
sim_organizer_correlation(SimOrganizer *organizer, SimEvent *event)
{
  GList *groups = NULL;
  GList *lgs = NULL;
  GList *list = NULL;
  GList *removes = NULL;
  GList *stickys = NULL;
  GList *tmp = NULL;
  SimEvent *new_event = NULL;
  gint id;
  gboolean found = FALSE;
  gboolean inserted;
  gboolean generate_root_rule_event = FALSE;
  GInetAddr *ia = NULL;
  int i;

  g_return_if_fail(organizer);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  if (event->is_correlated) //needed & setted just for OS,MAC,Service and HIDS in multilevel architecture.
    //If the event has been correlated early, we don't want to do it again.
    return;

  if (sim_event_is_special(event)) //other events doesn't need this. It doesn't matter that it doesn't match with any directive,
    event->is_correlated = TRUE; //the important thing is to know if it has been checked or not.

  /* Match Backlogs */
  g_mutex_lock(ossim.mutex_backlogs);
  list = sim_container_get_backlogs_ul(ossim.container); //1st time the server runs, this is empty

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_correlation: BEGIN backlogs %d", g_list_length(list));

  while (list)
    {
      SimDirective *backlog = (SimDirective *) list->data;
      id = sim_directive_get_id(backlog);

      inserted = FALSE;

      //if this is true (we check it aginst the children), inside sim_directive_backlog_match_by_event
      //we go down one level
      if (sim_directive_backlog_match_by_event(backlog, event))
        {
          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_directive_backlog_match_by_event TRUE. event->id: %d, id: %d, backlog_id : %d",
              event->id, sim_directive_get_id(backlog),
              sim_directive_get_backlog_id(backlog));

          GNode *rule_node;
          SimRule *rule_root;
          SimRule *rule_curr;
          uuid_t uuid_temp;
          rule_node = sim_directive_get_curr_node(backlog);
          rule_root = sim_directive_get_root_rule(backlog);
          rule_curr = sim_directive_get_curr_rule(backlog);

          event->matched = TRUE;
          /* Clear the match text*/
          sim_directive_backlog_get_uuid(backlog, event->uuid);
          /* Create New Event (directive_event) */
          new_event = sim_event_new();
          new_event->type = SIM_EVENT_TYPE_DETECTOR;
          new_event->time = time(NULL);
          uuid_generate(new_event->uuid);
          sim_directive_backlog_get_uuid(backlog, new_event->uuid_backlog);
          sim_event_add_backlog_ref_ul(new_event, (GObject*) backlog);

          new_event->sensor = g_strdup(event->sensor);
          if (event->interface)
            new_event->interface = g_strdup(event->interface);

          new_event->plugin_id = SIM_PLUGIN_ID_DIRECTIVE;
          new_event->plugin_sid = sim_directive_get_id(backlog);

          if ((ia = sim_rule_get_src_ia(rule_root)))
            new_event->src_ia = gnet_inetaddr_clone(ia);
          if ((ia = sim_rule_get_dst_ia(rule_root)))
            new_event->dst_ia = gnet_inetaddr_clone(ia);
          new_event->src_port = sim_rule_get_src_port(rule_root);
          new_event->dst_port = sim_rule_get_dst_port(rule_root);
          new_event->protocol = sim_rule_get_protocol(rule_root);
          new_event->data = sim_directive_backlog_to_string(backlog);
          if ((ia = sim_rule_get_sensor(rule_root)))
            new_event->sensor = gnet_inetaddr_get_canonical_name(ia);

          //FIXME: Is needed here to add filename, username, userdata, data, etc, to the new_event??? answer: YES. actualice also sim_container_db_insert_event()

          new_event->alarm = FALSE;
          new_event->level = event->level;

          event->backlog_id = sim_directive_get_backlog_id(backlog); //as the event generated belongs to the directive, the event must know
          //which is the backlog_id of that directive.
          new_event->backlog_id = event->backlog_id; //The new event (the alarm) must know also the backlog_id.

          /* Rule reliability */
          if (sim_rule_get_rel_abs(rule_curr))
            new_event->reliability = sim_rule_get_reliability(rule_curr);
          else
            new_event->reliability = sim_rule_get_reliability_relative(
                rule_node);

          /* Directive Priority */
          new_event->priority = sim_directive_get_priority(backlog);

          if (!event->id)
            {
              sim_container_db_insert_event(ossim.container, ossim.dbossim,
                  event);
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation1: insert event !event->id");
            }
          else
            g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                "sim_organizer_correlation1: event->id: %d", event->id);

          new_event->id = sim_database_get_id(ossim.dbossim, EVENT_SEQ_TABLE);
          /* Copy the event data to the "directive" event*/
          /* XXX quiero un objeto para evitar estas copias y usar cuenta de referencias*/
          /*new_event->filename = g_strdup(event->filename);
           new_event->username = g_strdup(event->username);
           new_event->password = g_strdup(event->password);
           new_event->userdata1 = g_strdup(event->userdata1);
           new_event->userdata2 = g_strdup(event->userdata2);
           new_event->userdata3 = g_strdup(event->userdata3);
           new_event->userdata4 = g_strdup(event->userdata4);
           new_event->userdata5 = g_strdup(event->userdata5);
           new_event->userdata6 = g_strdup(event->userdata6);
           new_event->userdata7 = g_strdup(event->userdata7);
           new_event->userdata8 = g_strdup(event->userdata8);
           new_event->userdata9 = g_strdup(event->userdata9);*/
          for (i = 0; i < N_TEXT_FIELDS; i++)
            {
              new_event->textfields[i] = g_strdup(event->textfields[i]);
            }

          new_event->rulename = g_strdup(event->rulename);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "%s: Nombre de regla:%s",
              __FUNCTION__, new_event->rulename);

          sim_container_push_event(ossim.container, new_event);

          sim_container_db_update_backlog_ul(ossim.container, ossim.dbossim,
              backlog);
          sim_container_db_insert_backlog_event_ul(ossim.container,
              ossim.dbossim, backlog, event);
          sim_container_db_insert_backlog_event_ul(ossim.container,
              ossim.dbossim, backlog, new_event);

          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_correlation: backlog_id: %d",
              sim_directive_get_backlog_id(backlog));

          inserted = TRUE;

          /* Children Rules with type MONITOR */
          if (!G_NODE_IS_LEAF(rule_node)) //if this is not the last node (i.e., if it has some children...)
            {

              GNode *children = rule_node->children;
              while (children)
                {
                  SimRule *rule = children->data;

                  if (rule->type == SIM_RULE_TYPE_MONITOR)
                    {
                      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                          "sim_organizer_correlation: Monitor rule");
                      sim_container_push_monitor_rule(ossim.container, rule);
                    }

                  children = children->next;
                }

            }
          else //if the rule is the last node, append the backlog (a directive with all the rules) to remove later.
          //Here is where the directive is stored to be destroyed later. As we have reached the last node, it has no sense
          //that we continue checking events against it.
            {
              removes = g_list_append(removes, backlog);
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation: Last node; adding it to be removed");
            }
        }
      else
        {
          //		  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_directive_backlog_match_by_event FALSE. event->id: %d, id: %d, backlog_id: %d",event->id, sim_directive_get_id(backlog), sim_directive_get_backlog_id(backlog));
        }

      if ((event->match) && (!inserted)) //When the ocurrence is > 1 in the directive, the first call to
      //sim_directive_backlog_match_by_event (above) will fail, and the event won't be
      //inserted. So we have to insert it here.
        {
          if (!event->id)
            sim_container_db_insert_event(ossim.container, ossim.dbossim, event);

          event->backlog_id = sim_directive_get_backlog_id(backlog);
          sim_container_db_insert_backlog_event_ul(ossim.container,
              ossim.dbossim, backlog, event);
        }

      if (event->sticky)
        stickys = g_list_append(stickys, GINT_TO_POINTER(id));

      event->matched = FALSE;
      event->match = FALSE;

      list = list->next;
    }

  list = removes;
  while (list)
    {
      SimDirective *backlog = (SimDirective *) list->data;
      sim_container_remove_backlog_ul(ossim.container, backlog);
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_correlation: Backlog id %d removed",
          sim_directive_get_backlog_id(backlog));

      g_object_unref(backlog);
      list = list->next;

      GList *list_aux = sim_container_get_backlogs_ul(ossim.container);
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_correlation: After removing backlogs, n: %d",
          g_list_length(list_aux));
    }
  g_list_free(removes);
  g_mutex_unlock(ossim.mutex_backlogs);

  /* Match Directives */
  g_mutex_lock(ossim.mutex_directives);
  list = sim_container_get_directives_ul(ossim.container);
  while (list)
    {
      SimDirective *directive = (SimDirective *) list->data;
      id = sim_directive_get_id(directive);

      found = FALSE;
      lgs = groups; //FIXME: ??? here groups is _always_ null...
      while (lgs)
        {
          SimDirectiveGroup *group = (SimDirectiveGroup *) lgs->data;

          if ((sim_directive_group_get_sticky(group))
              && (sim_directive_has_group(directive, group)))
            {
              found = TRUE;
              break;
            }

          lgs = lgs->next;
        }

      if (found)
        {
          list = list->next;
          continue;
        }

      tmp = stickys; //first time server runs this is null.
      while (tmp)
        {
          gint cmp = GPOINTER_TO_INT(tmp->data);
          if (cmp == id)
            {
              found = TRUE;
              break;
            }
          tmp = tmp->next;
        }

      if (found)
        {
          list = list->next;
          continue;
        }

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_correlation: MIDDLE backlogs %d", g_list_length(
              sim_container_get_backlogs_ul(ossim.container)));

      //The directive hasn't match yet, so we try to test if it match with the event itself. (for example, the 1st time)
      if (sim_directive_match_by_event(directive, event))
        {
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_directive_match_by_event TRUE. event->id: %d, id: %d ",
              event->id, sim_directive_get_id(directive));

          SimDirective *backlog;
          SimRule *rule_root;
          GNode *node_root;
          time_t time_last = time(NULL); //gets the actual time so we can update the rule
          uuid_t uuid_temp;
          if (sim_directive_get_groups(directive))
            groups = g_list_concat(groups, g_list_copy(
                sim_directive_get_groups(directive)));

          /* Create a backlog from directive */
          backlog = sim_directive_clone(directive);
          sim_directive_backlog_set_uuid(backlog);
          sim_directive_backlog_get_uuid(backlog, event->uuid_backlog);
          /* We must trace with directive has matched the event*/
          /* Gets the root node from backlog */
          node_root = sim_directive_get_curr_node(backlog);
          // Gets the root rule from backlog. Rule_root is the data field in node_root.
          rule_root = sim_directive_get_curr_rule(backlog);

          sim_rule_set_time_last(rule_root, time_last);
          // Set the event data to the rule_root. This will copy some fields from event (src_ip, port..)  into the directive (into the backlog)
          sim_rule_set_event_data(rule_root, event);

          event->matched = TRUE;

          //we need the event->id to reference this event into backlog. The event is inserted here to store the information of
          //events inside the alarm.
          if (!event->id)
            {
              sim_container_db_insert_event(ossim.container, ossim.dbossim,
                  event);
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_organizer_correlation2: insert event !event->id");
            }
          else
            g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                "sim_organizer_correlation2: event->id: %d", event->id);

          if (!G_NODE_IS_LEAF(node_root)) //if the node has some children...
            {
              GNode *children = node_root->children;
              while (children)
                {
                  SimRule *rule = children->data;

                  sim_rule_set_time_last(rule, time_last); // Actualice time in all the children
                  sim_directive_set_rule_vars(backlog, children); //this can be done only in children nodes, not in the root one.
                  //Store in the children the data from the node level specified
                  //in children.

                  if (rule->type == SIM_RULE_TYPE_MONITOR)
                    {
                      sim_container_push_monitor_rule(ossim.container, rule);
                    }
                  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                      "%s:There are children in directive id:%u", __FUNCTION__,
                      sim_directive_get_id(backlog));

                  children = children->next;
                }
              /* We must check here if the event causes an alarm*/
              /* if this is the case -> generate a directive event*/
              SimRule *r = (SimRule*) node_root->data;
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "%s Name:%s Level of rule:%d", __FUNCTION__,
                  sim_rule_get_name(r), sim_rule_get_level(r));
              if (sim_rule_get_level(r) == 1)
                {
                  int priority = sim_directive_get_priority(directive);
                  int reliability = sim_rule_get_reliability(r);
                  int asset = DEFAULT_ASSET;
                  int asset_src = DEFAULT_ASSET;
                  int asset_dst = DEFAULT_ASSET;
                  SimHost *host;
                  GList *nets;
                  host = sim_container_get_host_by_ia(ossim.container,
                      event->src_ia);
                  nets = sim_container_get_nets_has_ia(ossim.container,
                      event->src_ia);
                  if (host)
                    {
                      asset_src = sim_host_get_asset(host);
                    }
                  else
                    {
                      if (nets)
                        {
                          SimInet *src = sim_inet_new_from_ginetaddr(
                              event->src_ia);
                          int best_mask = -1;
                          while (nets)
                            {
                              SimNet *net = (SimNet *) nets->data;
                              // Search through nets an inet that match host and has the highest mask
                              GList *list_inet = sim_net_get_inets(net);
                              while (list_inet)
                                {
                                  SimInet *cmp = (SimInet *) list_inet->data;
                                  if (sim_inet_has_inet(cmp, src))
                                    { //check if src belongs to cmp.
                                      int mask = sim_inet_get_mask(cmp);
                                      if (mask > best_mask)
                                        {
                                          best_mask = mask;
                                          asset_src = sim_net_get_asset(net);
                                        }
                                    }
                                  list_inet = list_inet->next;
                                } // while inet
                              nets = nets->next;
                            } // while net
                          g_object_unref(src);
                        }
                      host = sim_container_get_host_by_ia(ossim.container,
                          event->dst_ia);
                      nets = sim_container_get_nets_has_ia(ossim.container,
                          event->dst_ia);
                      if (host)
                        {
                          asset_dst = sim_host_get_asset(host);
                        }
                      else
                        {
                          if (nets)
                            {
                              SimInet *dst = sim_inet_new_from_ginetaddr(
                                  event->dst_ia);
                              int best_mask = -1;
                              while (nets)
                                {
                                  SimNet *net = (SimNet *) nets->data;
                                  // Search through nets an inet that match host and has the highest mask
                                  GList *list_inet = sim_net_get_inets(net);
                                  while (list_inet)
                                    {
                                      SimInet *cmp =
                                          (SimInet *) list_inet->data;
                                      if (sim_inet_has_inet(cmp, dst))
                                        { //check if src belongs to cmp.
                                          int mask = sim_inet_get_mask(cmp);
                                          if (mask > best_mask)
                                            {
                                              best_mask = mask;
                                              asset_dst
                                                  = sim_net_get_asset(net);
                                            }
                                        }
                                      list_inet = list_inet->next;
                                    } // while inet
                                  nets = nets->next;
                                } // while net
                              g_object_unref(dst);
                            }
                        }
                      /* debug info*/
                      asset = MAX(asset_src, asset_dst);

                      /* debug info*/
                      double risk = ((double) (asset * reliability * priority))
                          / 25;
                      g_log(
                          G_LOG_DOMAIN,
                          G_LOG_LEVEL_DEBUG,
                          "%s First directive level: Asset:%d Reliability:%d Priority:%d Risk:%f",
                          __FUNCTION__, asset, reliability, priority, risk);
                      if (risk >= 1.0)
                        generate_root_rule_event = TRUE;
                    }
                }
              sim_container_append_backlog(ossim.container, backlog); //this is where the SimDirective gets inserted into backlog
              sim_container_db_insert_backlog_ul(ossim.container,
                  ossim.dbossim, backlog);
              sim_container_db_insert_backlog_event_ul(ossim.container,
                  ossim.dbossim, backlog, event);
              event->backlog_id = sim_directive_get_backlog_id(backlog); //FIXME: is this lost?
              if (generate_root_rule_event)
                {
                  sim_organizer_create_event_directive(backlog, event);
                  generate_root_rule_event = FALSE;
                }

            }
          else
            {
              sim_directive_set_matched(backlog, TRUE); //As this hasn't got any children we know that the directive has match.
              //Now we need to create a new event, fill it with data from the directive wich made the event match.
              //new_event is a directive event.
              new_event = sim_event_new();
              new_event->type = SIM_EVENT_TYPE_DETECTOR;
              new_event->alarm = FALSE;
              new_event->time = time(NULL);
              new_event->backlog_id = sim_directive_get_backlog_id(backlog);
              uuid_generate(new_event->uuid);
              sim_directive_backlog_get_uuid(backlog, new_event->uuid_backlog);
              sim_event_add_backlog_ref_ul(new_event, (GObject*) backlog);
              new_event->sensor = gnet_inetaddr_get_canonical_name(
                  sim_rule_get_sensor(rule_root));
              if (event->interface)
                new_event->interface = g_strdup(event->interface);

              new_event->plugin_id = SIM_PLUGIN_ID_DIRECTIVE;
              new_event->plugin_sid = sim_directive_get_id(backlog);

              if ((ia = sim_rule_get_src_ia(rule_root)))
                new_event->src_ia = gnet_inetaddr_clone(ia);
              if ((ia = sim_rule_get_dst_ia(rule_root)))
                new_event->dst_ia = gnet_inetaddr_clone(ia);
              new_event->src_port = sim_rule_get_src_port(rule_root);
              new_event->dst_port = sim_rule_get_dst_port(rule_root);
              new_event->protocol = sim_rule_get_protocol(rule_root);
              new_event->data = sim_directive_backlog_to_string(backlog);

              /* Rule reliability */
              if (sim_rule_get_rel_abs(rule_root))
                new_event->reliability = sim_rule_get_reliability(rule_root);
              else
                new_event->reliability = sim_rule_get_reliability_relative(
                    node_root);

              /* Directive Priority */
              new_event->priority = sim_directive_get_priority(backlog);

              //we need to assign a new_event->id before it's stored in memory. So we can repriorice & store it
              //in the next sim_organizer_run() loop. Of course, only if it matches with a policy with "directive_alert" plugin_id.
              new_event->id = sim_database_get_id(ossim.dbossim,
                  EVENT_SEQ_TABLE);
              //	      sim_container_db_insert_event_ul (ossim.container, ossim.dbossim, new_event);
              /* Copy the event data to the "directive" event*/
              /*new_event->filename = g_strdup(event->filename);
               new_event->username = g_strdup(event->username);
               new_event->password = g_strdup(event->password);
               new_event->userdata1 = g_strdup(event->userdata1);
               new_event->userdata2 = g_strdup(event->userdata2);
               new_event->userdata3 = g_strdup(event->userdata3);
               new_event->userdata4 = g_strdup(event->userdata4);
               new_event->userdata5 = g_strdup(event->userdata5);
               new_event->userdata6 = g_strdup(event->userdata6);
               new_event->userdata7 = g_strdup(event->userdata7);
               new_event->userdata8 = g_strdup(event->userdata8);
               new_event->userdata9 = g_strdup(event->userdata9);*/
              for (i = 0; i < N_TEXT_FIELDS; i++)
                {
                  new_event->textfields[i] = g_strdup(event->textfields[i]);
                }
              new_event->rulename = g_strdup(event->rulename);
              sim_container_push_event(ossim.container, new_event);

              sim_container_db_insert_backlog_ul(ossim.container,
                  ossim.dbossim, backlog);
              sim_container_db_insert_backlog_event_ul(ossim.container,
                  ossim.dbossim, backlog, event);
              new_event->backlog_id = sim_directive_get_backlog_id(backlog);
              sim_container_db_insert_backlog_event_ul(ossim.container,
                  ossim.dbossim, backlog, new_event);
              event->backlog_id = sim_directive_get_backlog_id(backlog);

              g_object_unref(backlog);
            }
        }
      else
        {
          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_directive_match_by_event FALSE. event->id: %d, directive: %d",
              event->id, sim_directive_get_id(directive));
        }
      event->matched = FALSE;
      event->match = FALSE;

      list = list->next;
    }
  g_mutex_unlock(ossim.mutex_directives);

  g_list_free(stickys);
  g_list_free(groups);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_correlation: END backlogs %d", g_list_length(
          sim_container_get_backlogs_ul(ossim.container)));
}

/*
 * This is called from sim_organizer_snort. It can be called with a snort event(plugin_name will be NULL)
 * or another event (plugin_name will be something like: "arp_watch: New Mac".
 *
 *
 */
gint
sim_organizer_snort_sensor_get_sid(SimDatabase *db_snort, SimEvent *event,
    gchar *plugin_name)
{
  GdaDataModel *dm;
  GdaValue *value;
  GdaValue *value2;
  guint sid = 0;
  gchar *query;
  gchar *insert;
  gint row;
  gchar *hostname = NULL;

  g_return_val_if_fail(db_snort, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (db_snort), 0);

  if (event->device)
    hostname = event->device;
  else
    hostname = event->sensor;

  /* SID */
  if (plugin_name)
    query
        = g_strdup_printf(
            "SELECT sid, sensor FROM sensor WHERE hostname = '%s-%s' AND interface = '%s'",
            hostname, plugin_name, event->interface);
  else
    query
        = g_strdup_printf(
            "SELECT sid, sensor FROM sensor WHERE hostname = '%s' AND interface = '%s'",
            hostname, event->interface);

  dm = sim_database_execute_single_command(db_snort, query);
  if (dm)
    {
      if (gda_data_model_get_n_rows(dm))
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
          //FIXME: I don't know why, but in some systems (tested under MACOS X), although the database has a structure
          //with an unsigned integer,GDA thinks that it's an integer, and the call to "gda_value_get_uinteger (value)"
          //fails. This direct access to data type ensures that we get what we want.
          sid = value->value.v_uinteger;
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_snort_sensor_get_sid sid1: %d. Query: %s", sid,
              query);
        }
      else //if it's the first time that this kind of event is saw
        {
          if (plugin_name)
            insert = g_strdup_printf(
                "INSERT INTO sensor (hostname, interface, encoding, last_cid, sensor) "
                  "VALUES ('%s-%s', '%s', 2, 0, '%s')", hostname, plugin_name,
                event->interface, event->sensor);
          else
            insert = g_strdup_printf(
                "INSERT INTO sensor (hostname, interface, detail, encoding, last_cid, sensor) "
                  "VALUES ('%s', '%s', 1, 0, 0, '%s')", hostname,
                event->interface, event->sensor);

          sim_database_execute_no_query(db_snort, insert);

          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_snort_sensor_get_sid sid2: %d. Query: %s", sid,
              insert);
          sid
              = sim_organizer_snort_sensor_get_sid(db_snort, event, plugin_name);
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_snort_sensor_get_sid sid3: %d. Query: %s", sid,
              insert);
          g_free(insert);
        }

      g_object_unref(dm);
    }
  else
    g_message("SENSOR SID DATA MODEL ERROR");

  g_free(query);

  return sid;
}

/*
 *
 *
 *
 *
 */
gint
sim_organizer_snort_event_get_max_cid(SimDatabase *db_snort, gint sid)
{
  GdaDataModel *dm;
  GdaValue *value;
  guint last_cid = 0;
  gchar *query;
  gint row;

  g_return_val_if_fail(db_snort, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (db_snort), 0);
  g_return_val_if_fail(sid > 0, 0);

  /* CID */
  query
      = g_strdup_printf("SELECT max(cid) FROM acid_event WHERE sid = %d", sid);
  dm = sim_database_execute_single_command(db_snort, query);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          if (!gda_value_is_null(value))
            {
              last_cid = value->value.v_uinteger;
              g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
                  "sim_organizer_snort_event_get_max_cid: %u Query: %s",
                  last_cid, query);
            }
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("LAST CID DATA MODEL ERROR");
    }
  g_free(query);

  return last_cid;
}

/* With each event, we must insert his cid&sid into the snort DB to be able to identify
 * that events individually
 *
 * Also, here is needed to pass the sig_id. The sig_id is the only way to acid/base 
 * to know the name of the event. In the signature table, the field sig_id is the same than here,
 * so acid can extract the name of the event.
 */
void
sim_organizer_snort_event_sidcid_insert(SimDatabase *db_snort, SimEvent *event,
    gint sid, gulong cid, gint sig_id)
{
  g_return_if_fail(db_snort);
  g_return_if_fail(SIM_IS_DATABASE (db_snort));

  gchar timestamp[TIMEBUF_SIZE];
  gchar *query;

  strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
      (time_t *) &event->time));

  query
      = g_strdup_printf(
          "INSERT INTO event (sid, cid, signature, timestamp) VALUES (%u, %u, %u, '%s')",
          sid, cid, sig_id, event->time_str);

  sim_database_execute_no_query(db_snort, query);
  g_free(query);
}

/*
 * update acid_event cache, making the terrible cache joins useless
 */
void
sim_organizer_snort_event_update_acid_event(SimDatabase *db_snort,
    SimEvent *event, gint sid, gulong cid)
{
  g_return_if_fail(db_snort);
  g_return_if_fail(SIM_IS_DATABASE (db_snort));

  gchar timestamp[TIMEBUF_SIZE];
  gchar *query;
  guint c, a;

  c = floor(event->risk_c);
  a = floor(event->risk_a);

  /*
   sid                 INT UNSIGNED NOT NULL,
   cid                 INT UNSIGNED NOT NULL,
   signature           INT UNSIGNED NOT NULL,
   sig_name            VARCHAR(255),
   sig_class_id        INT UNSIGNED,
   sig_priority        INT UNSIGNED,
   timestamp           DATETIME NOT NULL,
   ip_src              INT UNSIGNED,
   ip_dst              INT UNSIGNED,
   ip_proto            INT,
   layer4_sport        INT UNSIGNED,
   layer4_dport        INT UNSIGNED,
   */

  strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
      (time_t *) &event->time));

  query
      = g_strdup_printf(
          "INSERT INTO acid_event (sid, cid, timestamp, ip_src, ip_dst, \
          ip_proto, layer4_sport, layer4_dport, ossim_priority,\
          ossim_reliability, ossim_asset_src, ossim_asset_dst, \
          ossim_risk_c, ossim_risk_a, plugin_id, plugin_sid,tzone ) \
          VALUES (%u, %u, '%s', %u, %u, %d, %u, %u, %u, %u, %u, %u, %d, %d, %d, %d,%4.2f)",
          sid, cid, event->time_str, (event->src_ia) ? sim_inetaddr_ntohl(
              event->src_ia) : -1, (event->dst_ia) ? sim_inetaddr_ntohl(
              event->dst_ia) : -1, event->protocol, event->src_port,
          event->dst_port, event->priority, event->reliability,
          event->asset_src, event->asset_dst, c, a, event->plugin_id,
          event->plugin_sid,event->tzone);

  //query = g_strdup_printf ("INSERT INTO event (sid, cid, signature, timestamp) VALUES (%u, %u, %u, '%s')", sid, cid, sig_id, timestamp);

  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  sim_organizer_snort_event_update_acid_event_ac(db_snort, event, sid, cid,
      timestamp);

}

/*
 * Update/Insert in ac snort tables for Summary Statistics
 */
void
sim_organizer_snort_event_update_acid_event_ac(SimDatabase *db_snort,
    SimEvent *event, gint sid, gulong cid, gchar *timestamp)
{
  g_return_if_fail(db_snort);
  g_return_if_fail(SIM_IS_DATABASE (db_snort));
  gchar *query;

  // AC_SENSOR queries
  query
      = g_strdup_printf(
          "INSERT INTO ac_sensor_sid (sid,day,cid,first_timestamp,last_timestamp) VALUES (%u,DATE('%s'),1,'%s','%s') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='%s'",
          sid, timestamp, timestamp, timestamp, timestamp);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_sensor_signature (sid,day, plugin_id, plugin_sid) VALUES (%u,DATE('%s'), %d, %d)",
          sid, timestamp, event->plugin_id, event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_sensor_ipsrc (sid,day,ip_src) VALUES (%u,DATE('%s'),%u)",
          sid, timestamp, sim_inetaddr_ntohl(event->src_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_sensor_ipdst (sid,day,ip_dst) VALUES (%u,DATE('%s'),%u)",
          sid, timestamp, sim_inetaddr_ntohl(event->dst_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  // AC_ALERTS queries
  query
      = g_strdup_printf(
          "INSERT INTO ac_alerts_signature (day,sig_cnt,first_timestamp,last_timestamp, plugin_id, plugin_sid) VALUES (DATE('%s'),1,'%s','%s', %d, %d) ON DUPLICATE KEY UPDATE sig_cnt=sig_cnt+1,last_timestamp='%s'",
          timestamp, timestamp, timestamp, event->plugin_id, event->plugin_sid,
          timestamp);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_alerts_sid (day,sid, plugin_id, plugin_sid) VALUES (DATE('%s'),%u, %d, %d)",
          timestamp, sid, event->plugin_id, event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_alerts_ipsrc (day,ip_src, plugin_id, plugin_sid) VALUES (DATE('%s'),%u, %d, %d)",
          timestamp, sim_inetaddr_ntohl(event->src_ia), event->plugin_id,
          event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_alerts_ipdst (day,ip_dst, plugin_id, plugin_sid) VALUES (DATE('%s'),%u, %d, %d)",
          timestamp, sim_inetaddr_ntohl(event->dst_ia), event->plugin_id,
          event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  /*
   // AC_ALERTS_CLASS queries
   query = g_strdup_printf ("INSERT INTO ac_alertsclas_classid (sig_class_id,day,cid,first_timestamp,last_timestamp) VALUES (%u,DATE('%s'),1,'%s','%s') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='%s'", 0, timestamp, timestamp, timestamp, timestamp);
   sim_database_execute_no_query (db_snort, query);
   g_free (query);

   query = g_strdup_printf ("INSERT IGNORE INTO ac_alertsclas_sid (sig_class_id,day,sid) VALUES (%u,DATE('%s'),%u)", 0, timestamp, sid);
   sim_database_execute_no_query (db_snort, query);
   g_free (query);

   query = g_strdup_printf ("INSERT IGNORE INTO ac_alertsclas_signature (sig_class_id,day,signature) VALUES (%u,DATE('%s'),%u)", 0, timestamp, sig_id);
   sim_database_execute_no_query (db_snort, query);
   g_free (query);

   query = g_strdup_printf ("INSERT IGNORE INTO ac_alertsclas_ipsrc (sig_class_id,day,ip_src) VALUES (%u,DATE('%s'),%u)", 0, timestamp, sim_inetaddr_ntohl (event->src_ia));
   sim_database_execute_no_query (db_snort, query);
   g_free (query);

   query = g_strdup_printf ("INSERT IGNORE INTO ac_alertsclas_ipdst (sig_class_id,day,ip_dst) VALUES (%u,DATE('%s'),%u)", 0, timestamp, sim_inetaddr_ntohl (event->dst_ia));
   sim_database_execute_no_query (db_snort, query);
   g_free (query);
   */

  // AC_SRC_ADDRESS queries
  query
      = g_strdup_printf(
          "INSERT INTO ac_srcaddr_ipsrc (ip_src,day,cid) VALUES (%u,DATE('%s'),1) ON DUPLICATE KEY UPDATE cid=cid+1",
          sim_inetaddr_ntohl(event->src_ia), timestamp);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_srcaddr_sid (ip_src,day,sid) VALUES (%u,DATE('%s'),%u)",
          sim_inetaddr_ntohl(event->src_ia), timestamp, sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_srcaddr_signature (ip_src,day, plugin_id, plugin_sid) VALUES (%u,DATE('%s'), %d, %d)",
          sim_inetaddr_ntohl(event->src_ia), timestamp, event->plugin_id,
          event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_srcaddr_ipdst (ip_src,day,ip_dst) VALUES (%u,DATE('%s'),%u)",
          sim_inetaddr_ntohl(event->src_ia), timestamp, sim_inetaddr_ntohl(
              event->dst_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  // AC_DST_ADDRESS queries
  query
      = g_strdup_printf(
          "INSERT INTO ac_dstaddr_ipdst (ip_dst,day,cid) VALUES (%u,DATE('%s'),1) ON DUPLICATE KEY UPDATE cid=cid+1",
          sim_inetaddr_ntohl(event->dst_ia), timestamp);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_dstaddr_sid (ip_dst,day,sid) VALUES (%u,DATE('%s'),%u)",
          sim_inetaddr_ntohl(event->dst_ia), timestamp, sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_dstaddr_signature (ip_dst,day, plugin_id, plugin_sid) VALUES (%u,DATE('%s'),%d, %d)",
          sim_inetaddr_ntohl(event->dst_ia), timestamp, event->plugin_id,
          event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_dstaddr_ipsrc (ip_dst,day,ip_src) VALUES (%u,DATE('%s'),%u)",
          sim_inetaddr_ntohl(event->dst_ia), timestamp, sim_inetaddr_ntohl(
              event->src_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  // AC_LAYER4_SPORT queries
  query
      = g_strdup_printf(
          "INSERT INTO ac_layer4_sport (layer4_sport,ip_proto,day,cid,first_timestamp,last_timestamp) VALUES (%u,%u,DATE('%s'),1,'%s','%s') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='%s'",
          event->src_port, event->protocol, timestamp, timestamp, timestamp,
          timestamp);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_sport_sid (layer4_sport,ip_proto,day,sid) VALUE (%u,%u,DATE('%s'),%u)",
          event->src_port, event->protocol, timestamp, sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_sport_signature (layer4_sport,ip_proto,day, plugin_id, plugin_sid) VALUES (%u,%u,DATE('%s'), %d, %d)",
          event->src_port, event->protocol, timestamp, event->plugin_id,
          event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_sport_ipsrc (layer4_sport,ip_proto,day,ip_src) VALUES (%u,%u,DATE('%s'),%u)",
          event->src_port, event->protocol, timestamp, sim_inetaddr_ntohl(
              event->src_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_sport_ipdst (layer4_sport,ip_proto,day,ip_dst) VALUES (%u,%u,DATE('%s'),%u)",
          event->src_port, event->protocol, timestamp, sim_inetaddr_ntohl(
              event->dst_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  // AC_LAYER4_DPORT queries
  query
      = g_strdup_printf(
          "INSERT INTO ac_layer4_dport (layer4_dport,ip_proto,day,cid,first_timestamp,last_timestamp) VALUES (%u,%u,DATE('%s'),1,'%s','%s') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='%s'",
          event->dst_port, event->protocol, timestamp, timestamp, timestamp,
          timestamp);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_dport_sid (layer4_dport,ip_proto,day,sid) VALUE (%u,%u,DATE('%s'),%u)",
          event->dst_port, event->protocol, timestamp, sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_dport_signature (layer4_dport,ip_proto,day, plugin_id, plugin_sid) VALUES (%u,%u,DATE('%s'), %d, %d)",
          event->dst_port, event->protocol, timestamp, event->plugin_id,
          event->plugin_sid);
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_dport_ipsrc (layer4_dport,ip_proto,day,ip_src) VALUES (%u,%u,DATE('%s'),%u)",
          event->dst_port, event->protocol, timestamp, sim_inetaddr_ntohl(
              event->src_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

  query
      = g_strdup_printf(
          "INSERT IGNORE INTO ac_layer4_dport_ipdst (layer4_dport,ip_proto,day,ip_dst) VALUES (%u,%u,DATE('%s'),%u)",
          event->dst_port, event->protocol, timestamp, sim_inetaddr_ntohl(
              event->dst_ia));
  sim_database_execute_no_query(db_snort, query);
  g_free(query);

}

void
sim_organizer_snort_extra_data_insert(SimDatabase *db_snort, SimEvent *event,
    gint sid, gulong cid)
{

  gchar *aux1 = NULL, *aux2 = NULL, *aux3 = NULL;
  gchar * e_fields[N_TEXT_FIELDS];
  int i;
  GString *st;
  g_return_if_fail(db_snort);
  g_return_if_fail(SIM_IS_DATABASE (db_snort));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(sid > 0);
  g_return_if_fail(cid > 0);
  gchar *e_filename = NULL, *e_username = NULL, *e_password = NULL;
  gchar *e_userdata1 = NULL, *e_userdata2 = NULL, *e_userdata3 = NULL,
      *e_userdata4 = NULL, *e_userdata5 = NULL;
  gchar *e_userdata6 = NULL, *e_userdata7 = NULL, *e_userdata8 = NULL,
      *e_userdata9 = NULL;
  gchar *e_data;
  GdaConnection *conn;
  conn = sim_database_get_conn(db_snort);

  /*	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->filename: %s", event->filename);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->username: %s", event->username);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->password: %s", event->password);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata1: %s", event->userdata1);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata2: %s", event->userdata2);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata3: %s", event->userdata3);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata4: %s", event->userdata4);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata5: %s", event->userdata5);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata6: %s", event->userdata6);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata7: %s", event->userdata7);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata8: %s", event->userdata8);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_organizer_snort_extra_data_insert: event->userdata9: %s", event->userdata9);
   */
#if 0
  SimPacket *packet;
  if (event->packet) //if it's a snort event

    {
      //g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,"update_snort_database: snort payload %s",event->packet->payload);
      gchar *payload;
      payload = sim_bin2hex(event->packet->payload, event->packet->payloadlen);
      g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,"update_snort_database: snort payload %s",payload);
    }
#endif 
  /* Escape the characte*/
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      if (event->textfields[i])
        {
          e_fields[i] = g_new0(gchar, strlen(event->textfields[i]) * 2 + 1);
          gda_connection_escape_string(conn, event->textfields[i], e_fields[i]);
        }
      else
        e_fields[i] = NULL;
    }
  /*
   if (event->filename){
   e_filename = g_new0(gchar,strlen(event->filename)*2+1);
   gda_connection_escape_string (conn,event->filename,e_filename);
   }
   if (event->username){
   e_username = g_new0(gchar,strlen(event->username)*2+1);
   gda_connection_escape_string (conn,event->username,e_username);
   }
   if (event->password){
   e_password = g_new0(gchar,strlen(event->password)*2+1);
   gda_connection_escape_string (conn,event->password,e_password);
   }
   if (event->userdata1){
   e_userdata1 = g_new0(gchar,strlen(event->userdata1)*2+1);
   gda_connection_escape_string (conn,event->userdata1,e_userdata1);

   }
   if (event->userdata2){
   e_userdata2 = g_new0(gchar,strlen(event->userdata2)*2+1);
   gda_connection_escape_string (conn,event->userdata2,e_userdata2);
   }
   if (event->userdata3){
   e_userdata3 = g_new0(gchar,strlen(event->userdata3)*2+1);
   gda_connection_escape_string (conn,event->userdata3,e_userdata3);

   }
   if (event->userdata4){
   e_userdata4 = g_new0(gchar,strlen(event->userdata4)*2+1);
   gda_connection_escape_string (conn,event->userdata4,e_userdata4);
   }
   if (event->userdata5){
   e_userdata5 = g_new0(gchar,strlen(event->userdata5)*2+1);
   gda_connection_escape_string (conn,event->userdata5,e_userdata5);
   }
   if (event->userdata6){
   e_userdata6 = g_new0(gchar,strlen(event->userdata6)*2+1);
   gda_connection_escape_string (conn,event->userdata6,e_userdata6);
   }
   if (event->userdata7){
   e_userdata7 = g_new0(gchar,strlen(event->userdata7)*2+1);
   gda_connection_escape_string (conn,event->userdata7,e_userdata7);
   }
   if (event->userdata8){
   e_userdata8 = g_new0(gchar,strlen(event->userdata8)*2+1);
   gda_connection_escape_string (conn,event->userdata8,e_userdata8);
   }
   if (event->userdata9){
   e_userdata9 = g_new0(gchar,strlen(event->userdata9)*2+1);
   gda_connection_escape_string (conn,event->userdata9,e_userdata9);
   }*/
  if (event->data)
    {
      e_data = g_new0(gchar, strlen(event->data) * 2 + 1);
      gda_connection_escape_string(conn, event->data, e_data);
    }

  //  aux2 = g_strdup_printf ("(%u, %u,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", sid, cid, event->filename, event->username, event->password ,event->userdata1 ,event->userdata2 ,event->userdata3 ,event->userdata4 ,event->userdata5 ,event->userdata6 ,event->userdata7 ,event->userdata8 ,event->userdata9, payload);
  if (event->packet)
    {
      gchar *payload;
      st = g_string_new("");
      payload = sim_bin2hex(event->packet->payload, event->packet->payloadlen);
      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "update_snort_database: snort payload %s", payload);
      for (i = 0; i < N_TEXT_FIELDS; i++)
        {
          g_string_append_printf(st, " ,'%s'", e_fields[i]);
        }
      aux2 = g_strdup_printf("(%u,%u %s, '%s')", sid, cid, st->str, payload);
      /*
       aux2 = g_strdup_printf ("(%u, %u,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", sid, cid, event->filename ? e_filename : "", event->username ? e_username : "", event->password ? e_password : "" ,event->userdata1 ? e_userdata1: "", event->userdata2 ? e_userdata2 : "" , event->userdata3 ? e_userdata3 : "",
       event->userdata4 ? e_userdata4 : "", event->userdata5 ? e_userdata5 : "" ,
       event->userdata6 ? e_userdata6 : "", event->userdata7 ? e_userdata7 : "",
       event->userdata8 ? e_userdata8 : "", event->userdata9 ? e_userdata9 : "", payload ? payload : "");*/
      g_string_free(st, TRUE);
      g_free(payload);
    }
  else
    {
      st = g_string_new("");
      for (i = 0; i < N_TEXT_FIELDS; i++)
        {
          g_string_append_printf(st, ",'%s'",
              event->textfields[i] ? e_fields[i] : "");
        }
      aux2 = g_strdup_printf("(%u,%u %s, '%s')", sid, cid, st->str, e_data);

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "update_snort_database: no snort payload %s", event->data);
      //    aux2 = g_strdup_printf ("(%u, %u,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", sid, cid, event->filename, event->username, event->password ,event->userdata1 ,event->userdata2 ,event->userdata3 ,event->userdata4 ,event->userdata5 ,event->userdata6 ,event->userdata7 ,event->userdata8 ,event->userdata9, event->data);
      /*aux2 = g_strdup_printf ("(%u, %u,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", sid, cid, event->filename ? e_filename : "", event->username ? e_username : "", event->password ? e_password : "" ,event->userdata1 ? e_userdata1: "", event->userdata2 ? e_userdata2 : "" , event->userdata3 ? e_userdata3 : "",
       event->userdata4 ? e_userdata4 : "", event->userdata5 ? e_userdata5 : "" ,
       event->userdata6 ? e_userdata6 : "", event->userdata7 ? e_userdata7 : "",
       event->userdata8 ? e_userdata8 : "", event->userdata9 ? e_userdata9 : "", event->data ? e_data: "");*/
      g_string_free(st, TRUE);
    }
  st = g_string_new("");
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, ",%s", sim_text_field_get_name(i));
    }
  aux1 = g_strdup_printf(
      "INSERT IGNORE INTO extra_data (sid, cid %s,  data_payload) VALUES ",
      st->str);
  g_string_free(st, TRUE);
  aux3 = g_strconcat(aux1, aux2, NULL);
  sim_database_execute_no_query(db_snort, aux3);
  g_free(aux1);
  g_free(aux2);
  g_free(aux3);
  /* Free the escape strings*/
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_free(e_fields[i]);
    }
  /*
   if (e_filename)
   g_free (e_filename);
   if (e_username)
   g_free (e_username);
   if (e_password)
   g_free (e_password);
   if (e_userdata1)
   g_free (e_userdata1);
   if (e_userdata2)
   g_free (e_userdata2);
   if (e_userdata3)
   g_free (e_userdata3);
   if (e_userdata4)
   g_free (e_userdata4);
   if (e_userdata5)
   g_free (e_userdata5);
   if (e_userdata6)
   g_free (e_userdata6);
   if (e_userdata7)
   g_free (e_userdata7);
   if (e_userdata8)
   g_free (e_userdata8);
   if (e_userdata9)
   g_free (e_userdata9);
   */
  if (e_data)
    {
      g_free(e_data);
    }
}

/*
 *
 * Inserts an event in the snort DB in the table ossim_event. 
 * This doesn't inserts an event into the "event" table because that fields
 * are stored by snort directly to DB.
 *
 * returns the cid (don't needed, just info)
 */
guint
sim_organizer_snort_event_get_cid_from_event(SimDatabase *db_snort,
    SimEvent *event, gint sid)
{
  GdaDataModel *dm;
  GdaValue *value;
  gchar timestamp[TIMEBUF_SIZE];
  GString * select;
  GString *where;
  gint row;
  guint cid = 1;
  gchar *src_ip;
  gchar *dst_ip;

  g_return_if_fail(db_snort);
  g_return_if_fail(SIM_IS_DATABASE (db_snort));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(sid > 0);

  strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
      (time_t *) &event->time));

  select
      = g_string_new(
          "SELECT event.cid FROM event LEFT JOIN iphdr ON (event.sid = iphdr.sid AND event.cid = iphdr.cid)");
  where = g_string_new(" WHERE");

  g_string_append_printf(where, " event.sid = %u", sid);
  g_string_append_printf(where, " AND event.timestamp = '%s'", timestamp);

  if (event->src_ia)
    g_string_append_printf(where, " AND ip_src = %u", sim_inetaddr_ntohl(
        event->src_ia));
  if (event->dst_ia)
    g_string_append_printf(where, " AND ip_dst = %u", sim_inetaddr_ntohl(
        event->dst_ia));

  g_string_append_printf(where, " AND ip_proto = %d", event->protocol);

  switch (event->protocol)
    {
  case SIM_PROTOCOL_TYPE_ICMP:
    break;
  case SIM_PROTOCOL_TYPE_TCP:
    g_string_append(select,
        " LEFT JOIN tcphdr ON (event.sid = tcphdr.sid AND event.cid = tcphdr.cid)");

    if (event->src_port)
      g_string_append_printf(where, " AND tcp_sport = %d", event->src_port);
    if (event->dst_port)
      g_string_append_printf(where, " AND tcp_dport = %d", event->dst_port);
    break;
  case SIM_PROTOCOL_TYPE_UDP:
    g_string_append(select,
        " LEFT JOIN udphdr ON (event.sid = udphdr.sid AND event.cid = udphdr.cid)");

    if (event->src_port)
      g_string_append_printf(where, " AND udp_sport = %d ", event->src_port);
    if (event->dst_port)
      g_string_append_printf(where, " AND udp_dport = %d ", event->dst_port);
    break;
  default:
    break;
    }

  g_string_append(select, where->str);

  g_string_free(where, TRUE);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_snort_event_get_cid_from_event:Query: %s", select->str);

  dm = sim_database_execute_single_command(db_snort, select->str);
  if (dm)
    {
      for (row = 0; row < gda_data_model_get_n_rows(dm); row++)
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, row);
          cid = value->value.v_uinteger;
          g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
              "sim_organizer_snort_event_get_cid_from_event: cid: %d", cid);
          //sim_organizer_snort_ossim_event_insert (db_snort, event, sid, cid);
        }

      g_object_unref(dm);
    }
  else
    {
      g_message("EVENTS ID DATA MODEL ERROR");
    }

  g_string_free(select, TRUE);

  return cid; //just for information purposes.
}

/*
 * This function returns the number (sig_id) associated with each event kind. When a new
 * event arrives here, we have to insert it's name into "signature" table. Then we can recurse and get the sig_id
 * assigned to the name (this happens thanks to auto_increment in DB).
 *
 */
gint
sim_organizer_snort_signature_get_id(SimDatabase *db_snort, gchar *name)
{
  GdaDataModel *dm;
  GdaValue *value;
  gint sig_id;
  gchar *query;
  gchar *insert;
  gint row;
  gint ret;

  g_return_val_if_fail(db_snort, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (db_snort), 0);
  g_return_val_if_fail(name, 0);

  /* SID */
  query = g_strdup_printf("SELECT sig_id FROM signature WHERE sig_name = '%s'",
      name);
  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_snort_signature_get_id: Query: %s", query);
  dm = sim_database_execute_single_command(db_snort, query);
  if (dm)
    {
      if (gda_data_model_get_n_rows(dm)) //if the name of the plugin_sid is in database, get its sig_id (signature id).
        {
          value = (GdaValue *) gda_data_model_get_value_at(dm, 0, 0);
          sig_id = gda_value_get_uinteger(value);
        }
      else
        {
          insert
              = g_strdup_printf(
                  "INSERT INTO signature (sig_name, sig_class_id) " "VALUES ('%s', 0)",
                  name);

          ret = sim_database_execute_no_query(db_snort, insert);
          g_free(insert);

          if (ret < 0)
            g_critical("ERROR: CANT INSERT INTO SNORT DB");

          sig_id = sim_organizer_snort_signature_get_id(db_snort, name);
        }

      g_object_unref(dm);
    }
  else
    g_message("SIG ID DATA MODEL ERROR");

  g_free(query);

  return sig_id;
}

/*
 *
 * Insert the snort OR other event into DB
 *
 */
void
sim_organizer_snort(SimOrganizer *organizer, SimEvent *event)
{
  SimPlugin *plugin;
  SimPluginSid *plugin_sid;
  gchar *query;
  gint sid;
  gulong cid;
  GList *events = NULL;
  GList *list = NULL;
  gint sig_id;
  gboolean r = FALSE;
  GString *st;
  int i;
  gchar time[TIMEBUF_SIZE];
  gchar *timestamp;

  g_return_if_fail(organizer);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(event->sensor);
  g_return_if_fail(event->interface);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_snort Start: event->sid: %d ; event->cid: %u",
      event->snort_sid, event->snort_cid);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
      "sim_organizer_snort event->sensor: %s ; event->interface: %s",
      event->sensor, event->interface);

  if (event->data)
    g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
        "sim_organizer_snort event->data: -%s-", event->data);
  if (event->log)
    g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
        "sim_organizer_snort event->log:-%s-", event->log);


  if (event->log)
    {
      if (event->data)
        g_free(event->data);
      event->data = g_strdup(event->log);
    }

  //Copy all the extra data to the payload before insert it.
  //FIXME: This should be viewed each field in a separate place in ACID. We should replace ACID or something like that..
  //May be that we have to copy some fields to data. this is a bad way to do things..
  gchar *new_data_aux;
  st = g_string_new("");
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      g_string_append_printf(st, " %s",
          event->textfields[i] ? event->textfields[i] : " ");
    }
  new_data_aux = g_strdup_printf("%s %s", (event->data ? event->data : " "),
      st->str);
  g_string_free(st, TRUE);
  /*
   new_data_aux = g_strdup_printf ("%s %s %s %s %s %s %s %s %s %s %s %s %s",
   (event->data)? event->data: " ",
   (event->filename)? event->filename: " ",
   (event->username)? event->username: " ",
   (event->password)? event->password: " ",
   (event->userdata1)? event->userdata1: " ",
   (event->userdata2)? event->userdata2: " ",
   (event->userdata3)? event->userdata3: " ",
   (event->userdata4)? event->userdata4: " ",
   (event->userdata5)? event->userdata5: " ",
   (event->userdata6)? event->userdata6: " ",
   (event->userdata7)? event->userdata7: " ",
   (event->userdata8)? event->userdata8: " ",
   (event->userdata9)? event->userdata9: " ");
   */
  g_free(event->data);
  event->data = new_data_aux;

  plugin_sid = sim_container_get_plugin_sid_by_pky(ossim.container,
      event->plugin_id, event->plugin_sid);
  if (!plugin_sid)
    {
      g_message("sim_organizer_snort: Error Plugin %d, PlugginSid %d",
          event->plugin_id, event->plugin_sid);
      return;
    }
  sim_plugin_sid_debug_print(plugin_sid);
  /* Events SNORT */
  if ((event->plugin_id >= 1001) && (event->plugin_id < 1500))
    {
      guint snort_sid;
      guint snort_cid;

      snort_sid
          = sim_organizer_snort_sensor_get_sid(ossim.dbsnort, event, NULL);
      snort_cid = sim_organizer_snort_event_get_max_cid(ossim.dbsnort,
          snort_sid);
      snort_cid++;
      event->snort_sid = snort_sid; //sensor ID
      event->snort_cid = snort_cid; // event id

      // Here, insert the event in the snort database and then, if the insertion is OK in
      // the ossim_event table
      // If cid == 0 and sid == 0, this is and old agent snort event, use the old method
      if (event->packet != NULL)
        {
          r = FALSE;
          r = update_snort_database(event);
          if (!r)
            {
              g_log(
                  G_LOG_DOMAIN,
                  G_LOG_LEVEL_DEBUG,
                  "sim_organizer_snort: Error inserting snort event. The event had been correlated but its frame can't be inserted in the Snort Database");
              //return;
            }
          /*
           //now we must insert the event in ossim_event
           sim_organizer_snort_ossim_event_insert(ossim.dbsnort,
           event,
           event->snort_sid,
           event->snort_cid);
           */
        }
      else
        {
          /*		  sid = sim_organizer_snort_sensor_get_sid (ossim.dbsnort,
           event->sensor,
           event->interface,
           NULL);

           cid = sim_organizer_snort_event_get_cid_from_event (ossim.dbsnort,	//get the CID and insert it into ossim_event & extra_data
           event, sid);
           sig_id = sim_organizer_snort_signature_get_id (ossim.dbsnort, sim_plugin_sid_get_name (plugin_sid));
           sim_organizer_snort_event_update_acid_event (ossim.dbsnort, event, sid, cid); //insert into acid_event

           event->snort_cid = cid;
           event->snort_sid = sid;
           */
          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_organizer_snort: (snort without sid events) sid: %d, max_cid. %d",
              sid, cid);
        }
      //sig_id = sim_organizer_snort_signature_get_id (ossim.dbsnort, sim_plugin_sid_get_name (plugin_sid));

      sim_organizer_snort_event_update_acid_event(ossim.dbsnort, event,
          event->snort_sid, event->snort_cid); //insert into acid_event
      sim_organizer_snort_extra_data_insert(ossim.dbsnort, event,
          event->snort_sid, event->snort_cid);

    }
  else /* Other Events */
    {
      /*
       //Get the id from the signature name
       sig_id = sim_organizer_snort_signature_get_id (ossim.dbsnort, sim_plugin_sid_get_name (plugin_sid));
       */
      plugin
          = sim_container_get_plugin_by_id(ossim.container, event->plugin_id);

      if (!plugin)
        {
          g_log(
              G_LOG_DOMAIN,
              G_LOG_LEVEL_DEBUG,
              "sim_organizer_snort: Error: plugin %d not found. Please check your DB info for that plugin. Event rejected.",
              event->plugin_id);
        }

      sid = sim_organizer_snort_sensor_get_sid(ossim.dbsnort, event,
          sim_plugin_get_name(plugin));

      cid = sim_organizer_snort_event_get_max_cid(ossim.dbsnort, sid);
      cid++;
      event->snort_sid = sid;
      event->snort_cid = cid;

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_snort: (non-snort events) sid: %d, max_cid. %d", sid,
          cid);

      //sim_organizer_snort_event_sidcid_insert (ossim.dbsnort, event, sid, cid, sig_id); //insert into snort.event

      gchar timestamp[TIMEBUF_SIZE];

      sim_organizer_snort_event_update_acid_event(ossim.dbsnort, event, sid,
          cid); //insert into acid_event
      sim_organizer_snort_extra_data_insert(ossim.dbsnort, event, sid, cid);

      g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG,
          "sim_organizer_snort pluginid: %d , pluginsid: %d", event->plugin_id,
          event->plugin_sid);

      //host_mac_ host_os, host_service and host_id events are inserted here too.
      //The "right" way of doing this is using the sim_container_get_plugin_id_by_name() function,
      //but this function is executed with all events, so we need as much speed as possible.
      //We're going to check it directly against the plugin_id.
      //If we change the plugin_id's in the database, we'll have to modify it here too.

      switch (event->plugin_id)
        {

      case SIM_EVENT_HOST_MAC_EVENT: //arpwatch
        strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
            (time_t *) &event->time));

        if ((event->plugin_sid == EVENT_NEW) || (event->plugin_sid
            == EVENT_CHANGE))
          {
            sim_container_db_insert_host_mac_ul(ossim.container, ossim.dbossim,
                event->src_ia, timestamp, event->data_storage[0], //new mac
                event->data_storage[1], //vendor
                event->interface, event->sensor);
          }
        g_strfreev(event->data_storage);
        break;

      case SIM_EVENT_HOST_OS_EVENT: //P0f, OS event
        strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
            (time_t *) &event->time));
        if ((event->plugin_sid == EVENT_NEW) || (event->plugin_sid
            == EVENT_CHANGE))
          {
            sim_container_db_insert_host_os_ul(ossim.container, ossim.dbossim,
                event->src_ia, timestamp, event->sensor, event->interface,
                event->data_storage[0]); //OS
          }
        g_strfreev(event->data_storage);
        break;

      case SIM_EVENT_HOST_SERVICE_EVENT: //pads, service event
        strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
            (time_t *) &event->time));
        if ((event->plugin_sid == EVENT_NEW) || (event->plugin_sid
            == EVENT_CHANGE))
          {
            if (event->data_storage)
              {
                sim_container_db_insert_host_service_ul(ossim.container,
                    ossim.dbossim, event->src_ia, timestamp, atoi(
                        event->data_storage[0]), //port
                    atoi(event->data_storage[1]), //protocol
                    event->sensor, event->interface, event->data_storage[2], //service
                    event->data_storage[3]); //application

                g_strfreev(event->data_storage);
              }
            else
              g_message(
                  "sim_organizer_snort: Error: data from Service event incomplete.");

          }
        break;

        break;
      case SIM_EVENT_HOST_IDS_EVENT: //prelude, HIDS event
        if (event->data_storage)
          {
            strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
                (time_t *) &event->time));
            sim_container_db_insert_host_ids_event_ul(ossim.container,
                ossim.dbossim, ossim.dbsnort, event, timestamp, sid, //cid & sid needed for extra_data (username, userdata1.....)
                cid);

            g_strfreev(event->data_storage);
          }
        else
          g_message(
              "sim_organizer_snort: Error: data from HIDS event incomplete. Maybe someone is testing this app?...");

        break;

        }

    }
}

/*
 * 
 *
 * Insert rrd anomalies into separate tables
 */
void
sim_organizer_rrd(SimOrganizer *organizer, SimEvent *event)
{
  SimPluginSid *plugin_sid;
  gchar *insert;
  gchar *name;
  gchar *plugin_sid_name;
  gchar timestamp[TIMEBUF_SIZE];

  g_return_if_fail(organizer);
  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(event->sensor);
  g_return_if_fail(event->interface);

  if (event->plugin_id != 1508) // Return if not rrd_anomaly
    return;

  plugin_sid = sim_container_get_plugin_sid_by_pky(ossim.container,
      event->plugin_id, event->plugin_sid);
  if (!plugin_sid)
    {
      g_message("sim_organizer_rrd: Error Plugin %d, PlugginSid %d",
          event->plugin_id, event->plugin_sid);
      return;
    }

  strftime(timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime(
      (time_t *) &event->time));

  name = gnet_inetaddr_get_canonical_name(event->src_ia);
  if (name)
    {
      plugin_sid_name = sim_plugin_sid_get_name(plugin_sid);
      insert
          = g_strdup_printf(
              "INSERT INTO rrd_anomalies(ip, what, anomaly_time, anomaly_range) VALUES ('%s', '%s', '%s', '0')",
              name, plugin_sid_name, timestamp);
      sim_database_execute_no_query(ossim.dbossim, insert);
      g_free(insert);
      g_free(name);
    }
}

/*
 *
 */
void
sim_organizer_store_event_tmp(SimEvent *event)
{
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));

  event->id_tmp = sim_database_get_id(ossim.dbossim, EVENT_TMP_SEQ_TABLE);

  gchar *query = sim_event_get_insert_into_event_tmp_clause(event);
  sim_database_execute_no_query(ossim.dbossim, query);
  g_free(query);

  g_log(G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "ossim.config->max_event_tmp: %d",
      ossim.config->max_event_tmp);
  guint aux = event->id_tmp - ossim.config->max_event_tmp; //10.000 (default ) comes from 400 events each second, with a 30 seconds window (approximately).
  if (aux >= 0)
    {
      query = g_strdup_printf("DELETE FROM event_tmp WHERE id < %d", aux);
      sim_database_execute_no_query(ossim.dbossim, query);
      g_free(query);
    }

}
void
sim_organizer_create_event_directive(SimDirective *backlog, SimEvent *event)
{
  GNode *rule_node;
  SimRule *rule_root;
  SimRule *rule_curr;
  GInetAddr *ia = NULL;
  int i;
  rule_node = sim_directive_get_curr_node(backlog);
  rule_root = sim_directive_get_root_rule(backlog);
  rule_curr = sim_directive_get_curr_rule(backlog);
  SimEvent *new_event = NULL;
  new_event = sim_event_new();
  new_event->type = SIM_EVENT_TYPE_DETECTOR;
  new_event->time = time(NULL);
  new_event->sensor = g_strdup(event->sensor);
  if (event->interface)
    new_event->interface = g_strdup(event->interface);
  new_event->plugin_id = SIM_PLUGIN_ID_DIRECTIVE;
  new_event->plugin_sid = sim_directive_get_id(backlog);
  sim_event_add_backlog_ref_ul(new_event, (GObject*) backlog);
  if ((ia = sim_rule_get_src_ia(rule_root)))
    new_event->src_ia = gnet_inetaddr_clone(ia);
  if ((ia = sim_rule_get_dst_ia(rule_root)))
    new_event->dst_ia = gnet_inetaddr_clone(ia);
  new_event->src_port = sim_rule_get_src_port(rule_root);
  new_event->dst_port = sim_rule_get_dst_port(rule_root);
  new_event->protocol = sim_rule_get_protocol(rule_root);
  new_event->data = sim_directive_backlog_to_string(backlog);
  if ((ia = sim_rule_get_sensor(rule_root)))
    new_event->sensor = gnet_inetaddr_get_canonical_name(ia);
  new_event->alarm = TRUE;
  new_event->level = event->level;
  event->backlog_id = sim_directive_get_backlog_id(backlog); //as the event generated belongs to the directive, the event must know
  new_event->backlog_id = event->backlog_id; //The new event (the alarm) must know also the backlog_id.
  // ONE:
  // So why do we need to select the backlog_event table to get the id
  // and actualize the alarm after reinjecting & reprioritizing if we already know it?
  // Maybe we shouldn't update all the alarms/backlogs in memory, just the alarm of the
  // current backlog instead of all of them.
  uuid_generate(new_event->uuid);
  sim_directive_backlog_get_uuid(backlog, new_event->uuid_backlog);

  //			sim_container_push_event (ossim.container, new_event);

  /* Rule reliability */
  if (sim_rule_get_rel_abs(rule_curr))
    new_event->reliability = sim_rule_get_reliability(rule_curr);
  else
    new_event->reliability = sim_rule_get_reliability_relative(rule_node);
  /* Directive Priority */
  new_event->priority = sim_directive_get_priority(backlog);
  /*
   if (!event->id)
   {
   sim_container_db_insert_event (ossim.container, ossim.dbossim, event);
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "%s: insert event !event->id",__FUNCTION__);
   }
   else
   {
   g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "%s: event->id: %d",__FUNCTION__ event->id);
   }*/

  new_event->id = sim_database_get_id(ossim.dbossim, EVENT_SEQ_TABLE);
  /* Copy the event data to the "directive" event*/
  /*
   new_event->filename = g_strdup(event->filename);
   new_event->username = g_strdup(event->username);
   new_event->password = g_strdup(event->password);
   new_event->userdata1 = g_strdup(event->userdata1);
   new_event->userdata2 = g_strdup(event->userdata2);
   new_event->userdata3 = g_strdup(event->userdata3);
   new_event->userdata4 = g_strdup(event->userdata4);
   new_event->userdata5 = g_strdup(event->userdata5);
   new_event->userdata6 = g_strdup(event->userdata6);
   new_event->userdata7 = g_strdup(event->userdata7);
   new_event->userdata8 = g_strdup(event->userdata8);
   new_event->userdata9 = g_strdup(event->userdata9);*/
  for (i = 0; i < N_TEXT_FIELDS; i++)
    {
      new_event->textfields[i] = g_strdup(event->textfields[i]);
    }
  //direct insertion in queue if the organizer is the thread owner (so it doesn't block).
  //			if (sim_container_get_organizer_queue_owner (ossim.container) == 1)
  sim_container_push_event(ossim.container, new_event);
  sim_container_db_insert_backlog_event_ul(ossim.container, ossim.dbossim,
      backlog, new_event);
}

// vim: set ts=2:
