INCLUDES =  $(LIBGDA_CFLAGS) \
	$(GNET_CFLAGS) \
	$(OPENSSL_CFLAGS) \
	$(LIBXML_CFLAGS) \
	$(GLIB_CFLAGS) \
	-DG_LOG_DOMAIN=\"OSSIM\" \
	-DOS_SIM_GLOBAL_CONFIG_FILE=\""$(sysconfdir)/ossim/server/config.xml"\" \
	-DOS_SIM_GLOBAL_DIRECTIVE_FILE=\""$(sysconfdir)/ossim/server/directives.xml"\" \
	-DOS_SIM_LOG_DIR=\""$(localstatedir)/log/ossim/"\" \
	-DOS_SIM_RUN_DIR=\""$(localstatedir)/run/ossim-server.pid\"" \
	-DOS_SIM_VERSION=\""$(OS_SIM_VERSION)"\"

bin_PROGRAMS = ossim-server

ossim_server_SOURCES = \
		sim-util.c \
		sim-inet.c \
		sim-smtp.c \
		sim-config.c \
		sim-database.c \
		sim-event.c \
		sim-category.c \
		sim-classification.c \
		sim-plugin.c \
		sim-plugin-sid.c \
		sim-sensor.c \
		sim-host.c \
		sim-net.c \
		sim-policy.c \
		sim-action.c \
		sim-rule.c \
		sim-directive-group.c \
		sim-directive.c \
		sim-host-level.c \
		sim-net-level.c \
		sim-container.c \
		sim-xml-config.c \
		sim-xml-directive.c \
		sim-command.c \
		sim-plugin-state.c \
		sim-session.c \
		sim-scheduler.c \
		sim-organizer.c \
		sim-connect.c \
		sim-log.c \
		sim-server.c \
		sim-snort.c \
		sim-packet.c \
	  sim-text-fields.c \
		main.c

ossim_server_LDADD = $(LIBGDA_LIBS) $(GNET_LIBS) $(GLIB_LIBS) $(OPENSSL_LIBS) $(LIBUUID_LIBS) $(LIBPCRE_LIBS)


DISTCLEANFILES = Makefile.in
