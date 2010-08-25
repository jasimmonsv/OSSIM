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

#include <glib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <time.h>

/****debug****/
#include <stdio.h>
/****debug****/

#include "sim-util.h"
#include "sim-log.h"
#include "os-sim.h"
#include <config.h>

/*
 * This function is useful if its needed to reopen the log file (logrotate and so on)
 */
guint
sim_log_reopen(void)
{
  gboolean ok=TRUE;

  if (ossim.log.level == G_LOG_LEVEL_DEBUG)
  {
    if ((ossim.log.fd = open (ossim.log.filename, O_WRONLY|O_CREAT|O_APPEND, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP|S_IROTH|S_IWOTH)) < 0)
    {
      ok=FALSE;
			gchar *msg = g_strdup_printf ("Log File %s: Can't create", ossim.log.filename);
			g_log_default_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL | G_LOG_FLAG_RECURSION, msg, NULL);	//show this in stdout
			g_free(msg);
    }
  }
  else
  if ((ossim.log.fd = open (ossim.log.filename, O_WRONLY|O_CREAT|O_APPEND, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP|S_IROTH|S_IWOTH))< 0)
  {
    ok=FALSE;
		gchar *msg = g_strdup_printf ("Log File %s: Can't create", ossim.log.filename);
		g_log_default_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL | G_LOG_FLAG_RECURSION, msg, NULL);
		g_free(msg);
  }

	return ok;
}

/*
 * Return a log timestamp. Die if case of errors
 * The user must deallocate the string
 */

inline gchar *sim_log_timestamp(void){
   gchar *msg;
   time_t t;
  struct tm ltime;
  char timebuf[TIMEBUF_SIZE];
  if ((t = time(NULL))==(time_t)-1){
       g_message("OSSIM-Critical: can't obtain current time in %s:%s",__FILE__,__LINE__);
    exit(EXIT_FAILURE);
  }
  if (localtime_r(&t,&ltime)==NULL){
     g_message("OSSIM-Critical: can't obtain local time in %s:%s",__FILE__,__LINE__);
    exit(EXIT_FAILURE);
  }
  if (strftime(timebuf,TIMEBUF_SIZE,"%F %T",&ltime)==0){
   g_message("OSSIM-Critical: can't generate timestamp in %s:%s",__FILE__,__LINE__);
   exit(EXIT_FAILURE);
  }
  msg = g_strdup(timebuf);
  if (msg == NULL){
    g_message("OSSIM-Critical: can't generate timestamp in %s:%s",__FILE__,__LINE__);
    exit(EXIT_FAILURE);
  }
  return msg;
}

/*
 * Helper function to sim_log_handler. Try to write in the log.
 */
inline void
sim_log_write(gchar *msg, const gchar *log_domain)
{
  struct stat tmp;
	
  if (stat(ossim.log.filename, &tmp) == 0)  // check if the file log exists or not.
    write (ossim.log.fd, msg, strlen(msg));
  else
  {
		if (sim_log_reopen())
	    write (ossim.log.fd, msg, strlen(msg));


/*    if ( !sim_log_reopen()               //check if its possible to reopen the log file
         && (ossim.log.handler[0] != 0)  //check if the log handler has been created or not.
         && (ossim.log.handler[1] != 0)
         && (ossim.log.handler[2] != 0) )
    {
      g_log_remove_handler(log_domain, ossim.log.handler[0]);
      g_log_remove_handler(log_domain, ossim.log.handler[1]);
      g_log_remove_handler(log_domain, ossim.log.handler[2]);
    }*/
  }
  g_free (msg); 
}

/*
 *
 * Log handler called each time an event occurs.
 *
 *
 */
static void
sim_log_handler (const gchar     *log_domain,
                 GLogLevelFlags   log_level,
                 const gchar     *message,
                 gpointer         data)
{
  gchar   *msg = NULL;
  gchar   *timestamp = NULL;

  g_return_if_fail (message);
  g_return_if_fail (ossim.log.fd);

  if (ossim.log.level < log_level)
    return;

  timestamp = sim_log_timestamp();
  switch (log_level)
  {
      case G_LOG_LEVEL_ERROR: /*A G_LOG_LEVEL_ERROR is always a FATAL error. FIXME?.  */
        msg = g_strdup_printf ("%s %s-Error: %s\n",timestamp,log_domain, message);
        sim_log_write(msg,log_domain);
        break;
      case G_LOG_LEVEL_CRITICAL:
        msg = g_strdup_printf ("%s %s-Critical: %s\n",timestamp, log_domain, message);
        sim_log_write(msg,log_domain);
        break;
      case G_LOG_LEVEL_WARNING:
        msg = g_strdup_printf ("%s %s-Warning: %s\n", timestamp, log_domain, message);
        sim_log_write(msg,log_domain);
        break;
      case G_LOG_LEVEL_MESSAGE:
        msg = g_strdup_printf ("%s %s-Message: %s\n", timestamp, log_domain, message);
        sim_log_write(msg,log_domain);
        break;
      case G_LOG_LEVEL_INFO:
        msg = g_strdup_printf ("%s %s-Info: %s\n", timestamp,log_domain, message);
        sim_log_write(msg,log_domain);
        break;
      case G_LOG_LEVEL_DEBUG:
        msg = g_strdup_printf ("%s %s-Debug: %s\n",timestamp, log_domain, message);
        sim_log_write(msg,log_domain);
        break;
  }
  g_free(timestamp);
}


/*
 *
 *  Starts logging and pass all the messages from the Glib (thanks to G_LOG_LEVEL_MASK)
 *      to the ossim logger.
 *
 */
void 
sim_log_init (void)
{
  /* Init */
  ossim.log.filename = NULL;
  ossim.log.fd = 0;
  ossim.log.handler[0] = 0;
  ossim.log.handler[1] = 0;
  ossim.log.handler[2] = 0;
  ossim.log.level = G_LOG_LEVEL_MESSAGE;

  /* File Logs */
  if (ossim.config->log.filename)
  {
    ossim.log.filename = g_strdup (ossim.config->log.filename);
  }
  else
  {
    /* Verify Directory */
    if (!g_file_test (OS_SIM_LOG_DIR, G_FILE_TEST_IS_DIR))
      g_error ("Log Directory %s: Is invalid", OS_SIM_LOG_DIR);

    ossim.log.filename = g_strdup_printf ("%s/%s", OS_SIM_LOG_DIR, SIM_LOG_FILE);
  }

  switch (simCmdArgs.debug)
  {
    case 0:
      ossim.log.level = 0;
      break;
    case 1:
      ossim.log.level = G_LOG_LEVEL_ERROR;
      break;
    case 2:
      ossim.log.level = G_LOG_LEVEL_CRITICAL;
      break;
    case 3:
      ossim.log.level = G_LOG_LEVEL_WARNING;
      break;
    case 4:
      ossim.log.level = G_LOG_LEVEL_MESSAGE;
      break;
    case 5:
      ossim.log.level = G_LOG_LEVEL_INFO;
      break;
    case 6:
      ossim.log.level = G_LOG_LEVEL_DEBUG;
      break;
    default:
      ossim.log.level = 0;
      break;
  }
  sim_log_reopen(); //well, in this case this is not a reopen, just an open :)

	sim_log_set_handlers(); //set the handlers

}

/*
 *
 *
 *
 */
void
sim_log_free (void)
{
  g_free (ossim.log.filename);
  close (ossim.log.fd);
}

/*
 *
 */
void 
sim_log_set_handlers()
{
 /* Log Handler. We store it so we can do a g_log_remove_handler in case the logging fails sometime.*/
  ossim.log.handler[0] = g_log_set_handler (NULL, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL
                     | G_LOG_FLAG_RECURSION, sim_log_handler, NULL);

  ossim.log.handler[1] = g_log_set_handler ("GLib", G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL
                     | G_LOG_FLAG_RECURSION, sim_log_handler, NULL);

  ossim.log.handler[2] = g_log_set_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL
                     | G_LOG_FLAG_RECURSION, sim_log_handler, NULL);

}



// vim: set tabstop=2:
