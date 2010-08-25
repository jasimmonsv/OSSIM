#!/usr/bin/env python

import glob, os
from distutils.core import setup

from ossimframework.Const import VERSION

lib  = [ ('share/ossim-framework/ossimframework/', 
    glob.glob(os.path.join('ossimframework', '*.py')))
]

setup (
    name            = "ossim-framework",
    version         = VERSION,
    description     = "OSSIM framework",
    author          = "OSSIM Development Team",
    author_email    = "ossim@ossim.net",
    url             = "http://www.ossim.net",
#    packages        = [ 'ossimframework' ],
    scripts         = [ 'ossim-framework' ],
    data_files      = lib
)

