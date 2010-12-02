#!/bin/sh

LOGS='/var/ossim/logs/'
eval `egrep "^log_dir" /usr/share/ossim/www/sem/everything.ini `
if [ -d $log_dir ];then
	LOGS=$log_dir
fi

INDEX='/var/ossim/logs/locate.index'
eval `egrep "^locate_db" /usr/share/ossim/www/sem/everything.ini `
if [ -f $locate_db ];then
	INDEX=$locate_db
fi

updatedb.findutils --localpaths=$LOGS --netpaths=$LOGS --output=$INDEX
