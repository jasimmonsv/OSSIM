%define vendor OSSIM

Summary:   OSSIM Agent
Name:      ossim-agent
Version:   1.0.0rc1
Release:   2
License:   BSD
Group:     Applications/Security
URL:       http://www.ossim.net
Distribution: %{vendor}
Source:       %{name}-%{version}.tar.gz
BuildArch:    noarch
BuildRoot:    %{_tmppath}/%{name}-%{version}-root
Group:        Applications/Security
Requires: python >= 2.3 MySQL-python >= 0.9.2 python-adodb

%description
OSSIM Agent
An agent in OSSIM is set of python script that gathers and sends the
output of the different plugin or tool to the correlation engine for
further process.

%prep
%setup -q

%install
python setup.py install --root=$RPM_BUILD_ROOT

# fedora init scripts
%{__install} -D -m0755 contrib/fedora/init.d/ossim-agent $RPM_BUILD_ROOT/etc/init.d/ossim-agent
%{__install} -D -m0755 etc/logrotate.d/ossim-agent $RPM_BUILD_ROOT/etc/logrotate.d/ossim-agent
%{__install} -D -m0755 contrib/fedora/sysconfig/ossim-agent $RPM_BUILD_ROOT/etc/sysconfig/ossim-agent

%files
%defattr(-,root,root,0755)
%config %{_sysconfdir}/ossim/agent/
%{_datadir}/ossim-agent/
%{_datadir}/doc/ossim-agent/
%{_mandir}/man8/ossim-agent.8.gz
%{_bindir}/ossim-agent
/etc/logrotate.d/ossim-agent
/etc/init.d/ossim-agent
/etc/sysconfig/ossim-agent

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%changelog
* Tue Feb 12 2008 Tomas V.V.Cox <tvvcox@ossim.net> 0.9.9rc5
- mv BUILD_ROOT/usr/etc BUILD_ROOT/etc no longer needed

* Wed Oct 10 2007 Tomas V.V.Cox <tvvcox@ossim.net> 0.9.9rc5
- Changed --prefix to --root
- Now target arch is noarch

* Tue Oct  2 2007 Tomas V.V.Cox <tvvcox@ossim.net> 0.9.9rc5
- Initial SPEC release
