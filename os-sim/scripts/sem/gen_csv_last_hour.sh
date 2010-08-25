#!/bin/sh

LOG_DIR="/var/ossim/logs/"
eval `egrep "^log_dir" /usr/share/ossim/www/sem/everything.ini `
if [ -d $log_dir ];then
	LOG_DIR=$log_dir
fi

update_csv()
{
  echo Updating csv for $1
	if test -d $LOG_DIR$1/../ ;then
		ls $LOG_DIR$1/../[0-9][0-9]

		# For total_events that day take the hours and its count
		:> $LOG_DIR$1/../.csv_total_events
		for f in  $LOG_DIR$1/../[0-9][0-9];do
			if [ -d $f ];then
				tot=`cat $f/.total_events`
				month=`echo $f|awk -v FS="/" '{print $NF}'` 
				echo "$month,$tot">> $LOG_DIR$1/../.csv_total_events
			fi
		done

		# For total_events that month take the days and its count
		:> $LOG_DIR$1/../../.csv_total_events
		for f in  $LOG_DIR$1/../../[0-9][0-9];do
			if [ -d $f ];then
				tot=`cat $f/.total_events`
				year=`echo $f|awk -v FS="/" '{print $NF}'` 
				echo "$year,$tot">> $LOG_DIR$1/../../.csv_total_events
			fi
		done

		# For total_events that year take the months and its count
		:> $LOG_DIR$1/../../../.csv_total_events
		for f in  $LOG_DIR$1/../../../[0-9][0-9]*;do
			if [ -d $f ];then
				tot=`cat $f/.total_events`
				year=`echo $f|awk -v FS="/" '{print $NF}'` 
				echo "$year,$tot">> $LOG_DIR$1/../../../.csv_total_events
			fi
		done

		# For total_events take the years and its count
		:> $LOG_DIR$1/../../../../.csv_total_events
		ls $LOG_DIR$1/../../../../
		for f in  $LOG_DIR$1/../../../../[0-9][0-9][0-9][0-9];do
			if [ -d $f ];then
				tot=`cat $f/.total_events`
				echo "Total events year $f: $tot"
				year=`echo $f|awk -v FS="/" '{print $NF}'` 
				echo "$year,$tot">> $LOG_DIR$1/../../../../.csv_total_events
			fi
		done

	else
		echo update_csv: invalid Arg \$1 = $LOG_DIR$1/../. it must be a directory with the format year/month/day/sensor_ip/ and it must exist under $LOG_DIR
	fi
}

cd /usr/share/ossim/scripts/sem/
COMPLETED_DAY=$1

update_csv $COMPLETED_DAY/ $2

exit 0
