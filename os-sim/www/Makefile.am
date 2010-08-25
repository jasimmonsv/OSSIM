SUBDIRS= action \
	backup \
	business_processes \
	conf \
	control_panel \
	directives \
	editor \
	event_viewer \
	feed \
	hids \
	host \
	incidents \
	java \
	js \
	maps \
	nagios \
	net \
	netscan \
	ntop \
	panel \
	pixmaps \
	policy \
	port \
	report \
	response \
	riskmeter \
	rrd_conf \
	scan \
	sensor \
	server \
	session \
	setup \
	signature \
	style \
	upgrade \
	userlog \
	vulnmeter 
	
wwwdir = $(datadir)/ossim/www
www_DATA = index.php \
	top.php

EXTRA_DIST   = $(www_DATA)

install-data-hook:
	mkdir -p $(localstatedir)/lib/ossim/rrd/host_qualification
	mkdir -p $(localstatedir)/lib/ossim/rrd/net_qualification
	mkdir -p $(localstatedir)/lib/ossim/rrd/global_qualification
	mkdir -p $(localstatedir)/lib/ossim/rrd/level_qualification
	   
DISTCLEANFILES = Makefile.in
