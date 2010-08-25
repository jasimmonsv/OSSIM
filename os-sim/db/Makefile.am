SUBDIRS = plugins
dbdir   = $(datadir)/ossim/db
db_DATA =  00-create_datawarehouse_tbls_mysql.sql \
		00-create_snort_tbls_mysql.sql \
		00-create_jasperserver_tbls_mysql.sql \
		01-create_ossim_data_config.sql	\
		00-create_ossim_acl_tbls_mysql.sql \
		02-create_ossim_data_data.sql \
		00-create_ossim_tbls_mysql.sql \
		03-create_ossim_data_croscor_snort_nessus.sql	

EXTRA_DIST   = $(db_DATA)

DISTCLEANFILES = Makefile.in
