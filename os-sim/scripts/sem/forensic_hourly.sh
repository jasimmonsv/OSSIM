#!/bin/sh

# check if already running
if pidof -x $(basename $0) > /dev/null; then
 for p in $(pidof -x $(basename $0)); do
   if [ $p -ne $$ ]; then
     echo "Script $0 is already running: exiting"
     exit
   fi
 done
fi

# extract logs dir from ini
LOGS='/var/ossim/logs/'
eval `egrep "^indexer" /usr/share/ossim/www/sem/everything.ini `
eval `egrep "^log_dir" /usr/share/ossim/www/sem/everything.ini `
if [ -d $log_dir ];then
	LOGS=$log_dir
fi

# indexer
if [ -e $indexer ]; then
	YESTERDAY=`date --date='last day' "+%Y/%m/%d/"`
	TODAY=`date "+%Y/%m/%d/"`
	$indexer $LOGS$YESTERDAY
	$indexer $LOGS$TODAY
fi
# --force command line option forces recalculation all stats files from last hour new logs
if [ "$1" != "--force" ];then
    cd /usr/share/ossim/scripts/sem/ && sh /usr/share/ossim/scripts/sem/forensic_stats_last_hour.sh
    cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/generate_stats.pl $LOGS
else
    cd /usr/share/ossim/scripts/sem/ && sh /usr/share/ossim/scripts/sem/forensic_stats_last_hour-force.sh
    cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/generate_stats.pl $LOGS force
fi

# generate totals by sensors
cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/gen_sensor_totals.pl $LOGS

# generate stats into mysql table to logger report facility
cd /usr/share/ossim/scripts/sem/ && perl /usr/share/ossim/scripts/sem/generate_sem_stats.pl $LOGS

# update index file
cd /usr/share/ossim/scripts/sem/ && sh /usr/share/ossim/scripts/sem/update_db.sh
