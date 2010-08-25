#!/usr/bin/env python

from distutils.core import setup
from ossim_agent.__init__ import __version__

import glob, os

doc = [ ('share/doc/ossim-agent', 
            ['doc/INSTALL', 'doc/LICENSE', 'doc/ChangeLog'] )]

man  = [ ('share/man/man8', ['doc/ossim-agent.8.gz']) ]

lib  = [ ('share/ossim-agent/ossim_agent',
            glob.glob(os.path.join('ossim_agent', '*.py'))) ]

etc = [ ('/etc/ossim/agent',
            glob.glob(os.path.join('etc', 'agent', '*.cfg')) ),
        ('/etc/ossim/agent/plugins',
            glob.glob(os.path.join('etc', 'agent', 'plugins', '*.cfg'))) ]

data = etc + doc + man + lib


setup (
    name            = "ossim-agent",
    version         = __version__,
    description     = "Open Source Security Information Management (Agent)",
    author          = "Ossim Development Team",
    author_email    = "devel@ossim.net",
    url             = "http://www.ossim.net",
    license         = "BSD",
    scripts         = [ 'ossim-agent' ],
    data_files      = data
)

