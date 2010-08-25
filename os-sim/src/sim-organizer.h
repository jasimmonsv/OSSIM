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

#ifndef __SIM_ORGANIZER_H__
#define __SIM_ORGANIZER_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

#include "sim-container.h"
#include "sim-config.h"
#include "sim-event.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_ORGANIZER                  (sim_organizer_get_type ())
#define SIM_ORGANIZER(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_ORGANIZER, SimOrganizer))
#define SIM_ORGANIZER_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_ORGANIZER, SimOrganizerClass))
#define SIM_IS_ORGANIZER(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_ORGANIZER))
#define SIM_IS_ORGANIZER_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_ORGANIZER))
#define SIM_ORGANIZER_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_ORGANIZER, SimOrganizerClass))

#define MAX_DIFF_TIME 60	//max time that the events could be in the agent without send it to the server. If this time is exceeded
													//the event won't update the C & A, and won't enter into correlation. (it still will qualify, generate alarms..)

G_BEGIN_DECLS

typedef struct _SimOrganizer        SimOrganizer;
typedef struct _SimOrganizerClass   SimOrganizerClass;
typedef struct _SimOrganizerPrivate SimOrganizerPrivate;

struct _SimOrganizer {
  GObject parent;

  SimOrganizerPrivate *_priv;
};

struct _SimOrganizerClass {
  GObjectClass parent_class;
};

GType             sim_organizer_get_type                        (void);
SimOrganizer*     sim_organizer_new                             (SimConfig     *config);

void              sim_organizer_run                             (SimOrganizer  *organizer);

void              sim_organizer_correlation_plugin              (SimOrganizer *organizer, 
																																 SimEvent     *event);

void              sim_organizer_mac_os_change                   (SimOrganizer *organizer, 
																																 SimEvent     *event);
SimPolicy*				sim_organizer_get_policy											(SimOrganizer *organizer,
			                                                           SimEvent     *event);
	
/* Priority Function */
gint              sim_organizer_reprioritize                     (SimOrganizer  *organizer,
																																 SimEvent      *event,
																																 SimPolicy			*policy);
gint              sim_organizer_risk_levels                      (SimOrganizer  *organizer,
																																 SimEvent      *event);


/* Correlate Function */
void              sim_organizer_correlation                     (SimOrganizer  *organizer,
																																 SimEvent      *event);
/* Store Functions */
void              sim_organizer_snort                           (SimOrganizer  *organizer,
																																 SimEvent      *event);
gint							sim_organizer_snort_signature_get_id					(SimDatabase  *db_snort,
																																	gchar        *name);

void							sim_organizer_snort_extra_data_insert 				(SimDatabase  *db_snort,
                  													                     SimEvent     *event,
													                                       gint          sid,
                          													             gulong        cid);

void							sim_organizer_snort_event_update_acid_event_ac (SimDatabase  *db_snort,
																																SimEvent     *event,
																																gint         sid,
																																gulong       cid,
																																gchar        *timestamp);



void							sim_organizer_snort_event_sidcid_insert				(SimDatabase  *db_snort,
																																	SimEvent      *event,
												                                          gint          sid,
												                                          gulong        cid,
																																	gint					sig_id);

/* RRD anomaly Function */
void              sim_organizer_rrd           	                (SimOrganizer  *organizer,
																																 SimEvent      *event);
/* Util Function */
void              sim_organizer_backlog_match                   (SimDatabase   *db_ossim,
																																 SimDirective  *backlog,
																																 SimEvent      *event);
void              sim_organizer_resend                          (SimEvent  *event, 
                                                                 SimRole   *role);
void							sim_organizer_store_event_tmp									(SimEvent *event);

static gpointer		sim_organizer_thread_monitor_rule							(gpointer data);

G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_ORGANIZER_H__ */
// vim: set tabstop=2:
