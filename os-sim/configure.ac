AC_PREREQ(2.52)
AC_INIT(src/main.c)
AM_CONFIG_HEADER(config.h)

OS_SIM_MAJOR_VERSION=2
OS_SIM_MINOR_VERSION=4
OS_SIM_MICRO_VERSION=9
OS_SIM_RELEASE_VERSION=001
OS_SIM_VERSION=$OS_SIM_MAJOR_VERSION.$OS_SIM_MINOR_VERSION.$OS_SIM_MICRO_VERSION.$OS_SIM_RELEASE_VERSION

AC_SUBST(OS_SIM_MAJOR_VERSION)
AC_SUBST(OS_SIM_MINOR_VERSION)
AC_SUBST(OS_SIM_MICRO_VERSION)
AC_SUBST(OS_SIM_VERSION)

AM_INIT_AUTOMAKE(os-sim, $OS_SIM_VERSION)

AM_MAINTAINER_MODE

AC_ISC_POSIX
AC_PROG_CC
AC_PROG_CXX
AC_STDC_HEADERS
AC_PROG_LIBTOOL
AC_PROG_INSTALL

AC_CANONICAL_HOST

case "$host" in
    *darwin*)
    LDFLAGS="${LDFLAGS} -bind_at_load"
    ;;
esac

# Check for structures
AC_CHECK_MEMBER([struct sockaddr.sa_len],
                AC_DEFINE(HAVE_SOCKADDR_SA_LEN, 1,
                  [Define if struct sockaddr has sa_len field]),,
                [#include <sys/types.h>
                 #include <sys/socket.h>])

AC_MSG_CHECKING([for struct sockaddr_storage])
AC_TRY_COMPILE([ #include <sys/types.h>
#include <sys/socket.h>], [struct sockaddr_storage s; s],
  have_sockaddr_storage=yes, have_sockaddr_storage=no)
if test "$have_sockaddr_storage" = "yes" ; then
  AC_DEFINE(HAVE_SOCKADDR_STORAGE,1,[Whether you have struct sockaddr_storage])
  AC_MSG_RESULT(yes)
else
  AC_MSG_RESULT(no)
fi

dnl check for strcasecmp
dnl 
AC_CHECK_FUNC(strcasecmp,,[
	AC_LIBOBJ(strcasecmp)
])

# Check for IPv6 support
AC_MSG_CHECKING([for IPv6 availability])
AC_TRY_LINK([ #include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>], [struct sockaddr_in6 s; struct in6_addr t=in6addr_any; int i=AF_INET6; s; t.s6_addr[0] = 0;],
  [
    AC_DEFINE(HAVE_IPV6, 1, [Define if IPv6 is potentially available])
    AC_MSG_RESULT(yes)
  ],
  [
    AC_MSG_RESULT(no)
  ]
)


PKG_CHECK_MODULES(GLIB, glib-2.0 >= 2.2 gobject-2.0 >= 2.2 gthread-2.0 >= 2.2)
PKG_CHECK_MODULES(GNET, gnet-2.0 > 2.0)
PKG_CHECK_MODULES(LIBXML, libxml-2.0)
PKG_CHECK_MODULES(OPENSSL, openssl >= 0.9.7)
PKG_CHECK_MODULES(LIBGDA, libgda >= 1.0)
#
# Check four uuid lib
#
AC_CHECK_HEADER([uuid/uuid.h],
		AC_CHECK_LIB([uuid], [uuid_generate],
			AC_DEFINE([ENABLE_UUID], 1,
			[Define this to 1 if you want to enable generation of
			DCE compliant UUIDs.])
			LIBUUID_LIBS="-luuid",
			AC_MSG_WARN([Linux-NTFS DCE compliant UUID generation code requires the uuid library.]),
		),
		AC_MSG_WARN([Linux-NTFS DCE compliant UUID generation code requires the uuid library.]),
	)


AC_SUBST(GLIB_CFLAGS)
AC_SUBST(GLIB_LIBS)
AC_SUBST(LIBGDA_CFLAGS)
AC_SUBST(LIBGDA_LIBS)
AC_SUBST(LIBXML_CFLAGS)
AC_SUBST(LIBXML_LIBS)
AC_SUBST(GNET_CFLAGS)
AC_SUBST(GNET_LIBS)
AC_SUBST(OPENSSL_CFLAGS)
AC_SUBST(OPENSSL_LIBS)
AC_SUBST(LIBUUID_LIBS)
AC_OUTPUT([
Makefile
src/Makefile
etc/Makefile
etc/server/Makefile
etc/framework/Makefile
etc/cron.d/Makefile
etc/cron.daily/Makefile
etc/init.d/Makefile
etc/logrotate.d/Makefile
db/Makefile
db/plugins/Makefile
pixmaps/Makefile
include/Makefile
include/classes/Makefile
include/upgrades/Makefile
include/panel/Makefile
include/panel/plugins/Makefile
include/xajax/Makefile
www/Makefile
www/action/Makefile
www/business_processes/Makefile
www/conf/Makefile
www/control_panel/Makefile
www/directives/Makefile
www/editor/Makefile
www/event_viewer/Makefile
www/feed/Makefile
www/hids/Makefile
www/host/Makefile
www/incidents/Makefile
www/maps/Makefile
www/nagios/Makefile
www/net/Makefile
www/ntop/Makefile
www/panel/Makefile
www/pixmaps/Makefile
www/pixmaps/os/Makefile
www/pixmaps/panel/Makefile
www/pixmaps/top/Makefile
www/pixmaps/therm/Makefile
www/policy/Makefile
www/port/Makefile
www/report/Makefile
www/report/graphs/Makefile
www/report/jgraphs/Makefile
www/riskmeter/Makefile
www/rrd_conf/Makefile
www/scan/Makefile
www/sensor/Makefile
www/server/Makefile
www/session/Makefile
www/setup/Makefile
www/style/Makefile
www/vulnmeter/Makefile
www/backup/Makefile
www/java/Makefile
www/java/images/Makefile
www/js/Makefile
www/js/rico/Makefile
www/js/scriptaculous/Makefile
www/js/mattkruse/Makefile
www/js/OpenLayers/img/Makefile
www/js/OpenLayers/Makefile
www/netscan/Makefile
www/response/Makefile
www/upgrade/Makefile
www/userlog/Makefile
scripts/Makefile
scripts/correlation/Makefile
scripts/extract_nessus/Makefile
scripts/extract_osvdb/Makefile
scripts/extract_osvdb/OS/Makefile
contrib/Makefile
contrib/fonts/Makefile
contrib/ntop/Makefile
contrib/mrtg/Makefile
contrib/arpwatch/Makefile
contrib/base/Makefile
contrib/debian/Makefile
contrib/debian/agent/Makefile
contrib/debian/framework/Makefile
contrib/debian/httpd/Makefile
contrib/debian/init.d/Makefile
contrib/fedora/Makefile
contrib/fedora/init.d/Makefile
contrib/fedora/sysconfig/Makefile
contrib/oinkmaster/Makefile
contrib/osvdb/Makefile
contrib/spade/Makefile
contrib/snort/Makefile
contrib/patches/Makefile
contrib/py-rrdtool/Makefile
contrib/barnyard/Makefile
locale/zh_TW/LC_MESSAGES/Makefile
locale/zh_TW/Makefile
locale/es/LC_MESSAGES/Makefile
locale/es/Makefile
locale/fr/LC_MESSAGES/Makefile
locale/fr/Makefile
locale/zh_CN/LC_MESSAGES/Makefile
locale/zh_CN/Makefile
locale/Makefile
locale/ru/LC_MESSAGES/Makefile
locale/ru/Makefile
locale/en/LC_MESSAGES/Makefile
locale/en/Makefile
locale/de/LC_MESSAGES/Makefile
locale/de/Makefile
locale/ja/LC_MESSAGES/Makefile
locale/ja/Makefile
locale/pt_BR/LC_MESSAGES/Makefile
locale/pt_BR/Makefile
])
