#!/bin/sh

LOGS='/var/ossim/logs/'
eval `egrep "^log_dir" /usr/share/ossim/www/sem/everything.ini `
if [ -d $log_dir ];then
	LOGS=$log_dir
fi

 for f in ` find $LOGS -type d |egrep -io "[0-9]{4}/[0-9]{2}/[0-9]{2}/[0-9]{2}/" `;do sh forensic_stats_last_hour.sh $f;done
 for f in ` find $LOGS -type d |egrep -io "[0-9]{4}/[0-9]{2}/[0-9]{2}/" `;do sh forensic_stats_last_hour.sh $f;done
 for f in ` find $LOGS -type d |egrep -io "[0-9]{4}/[0-9]{2}/[0-9]{2}/[0-9]{2}/" `;do sh forensic_stats_last_hour.sh $f;done
