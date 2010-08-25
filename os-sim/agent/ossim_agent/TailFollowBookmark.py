#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import os, stat, sys, time

#
# LOCAL IMPORTS
#
from Logger import *

#
# GLOBAL VARIABLES
#
logger = Logger.logger

class TailFollowBookmark(object):
    """
    Tail a file and follow as additional data is appended.

    An optional bookmark is updated for the current file in the event that
    logging needs to resume from the last place left off. 

    TailBookmarkFollow can be used to monitor log files and can even track
    when a file has been moved (eg via log rotation )

    In this case, TailBookmarkFollow will automatically close the old file,
    and re-open the new file.
    """

    def __init__(self, filename, track=1, bookmark_dir=""):
        """Constructor that specifies the file to be tailed.  An
        optional keyword argument specifies whether or not the file
        should be tracked.
        """
        # bookmarks enabled based on existence of path
        self.bookmark = os.path.exists(bookmark_dir)

        self.lines = []

        self.track = track
        self.filename = filename

        if self.bookmark:
            self.bookmark_path = "%s/%s.bmk" % (os.path.dirname(bookmark_dir + "/"), os.path.basename(filename))
            logger.info('Bookmarking "%s" at: %s' % (self.filename, self.bookmark_path))

        self._stat_file()
        self._open_file()


    def __iter__(self):
        """Returns an iterator that can be used to iterate over the
        lines of the file as they are appended.  TailFollow implements
        the iterator contract, as a result, self is returned to the
        caller.
        """

        return self


    def next(self):
        """Returns the next line from the file being tailed.  This
        method is part of the iterator contract.  StopIteration is
        thrown when there an EOF has been reached.
        """

        bookmark_pos = self._current_file.tell()
        line = self._current_file.readline()

        if not line:
            if self.track:
                self._check_for_file_modification()
            raise StopIteration

        # check if we should be bookmarking
        elif self.bookmark and line != "":
            try:
                b = open(self.bookmark_path, 'w')

                try:
                    data = "%s\n%s" % (str(bookmark_pos), line)
                    b.write(data)
            
                finally:
                    b.close()

            except IOError:
                logger.warning('Unable to write bookmark file "%s" for log "%s"' % (self.bookmark_path, self.filename))
            

        return line

    def close(self):
        """Closes the current file."""

        self._current_file.close()

    def _stat_file(self):
        """Stats the file and verifies it is a regular file; otherwise
        an IOError exception is thrown.  Furthermore, the _current_stat
        attribute is set as a side-effect.
        """

        self._current_stat = os.stat(self.filename)

        if not stat.S_ISREG(self._current_stat.st_mode):
            raise IOError, self.filename + " is not a regular file"

    def _open_file(self):
        """
        Opens the file and seeks to the specified position based on
        the keyword arguments: offset and whence.  Furthermore, the
        _current_file attribute is set as a side-effect.
        """

        self._current_file = open(self.filename, 'r')
        self._current_file.seek(0, os.SEEK_END)

        # check if we are using bookmarks and seek accordingly
        if self.bookmark:
            bookmark_pos = 0
            tail_pos = self._current_file.tell()

            try:
                b = open(self.bookmark_path, 'r')

                try:
                    bookmark_pos = long(b.readline())
                    bookmark_line = b.readline()

                    # seek to the bookmarked position
                    self._current_file.seek(bookmark_pos, os.SEEK_SET)
            
                    # check that the current line is what we last read (and noted in the bookmark)
                    line = self._current_file.readline()

                    if line != bookmark_line:
                        self._current_file.seek(tail_pos, os.SEEK_SET)
                        logger.warning('Bookmark expected "%s" but found "%s". Chasing tail instead.' % (bookmark_line, line))
                    else:
                        logger.info("Bookmark found. Offsetting to byte position: %d" % (bookmark_pos + len(bookmark_line)))
                except ValueError:
                    logger.info('Bookmark appears empty or corrupt. Ignoring.')

                finally:
                    b.close()

            except IOError:
                logger.warning('Unable to read bookmark file "%s" for log "%s"' % (self.bookmark_path, self.filename))




    def _check_for_file_modification(self):
        """Checks to see if the file has been moved/deleted and as a
        result requires the closure of the existing file and re-opening
        of the original file.
        """

        try:
            old_stat = self._current_stat
            old_file = self._current_file

            self._stat_file()
            
            if self._current_stat.st_ino != old_stat.st_ino or \
               self._current_stat.st_dev != old_stat.st_dev or \
               self._current_stat.st_size < old_stat.st_size:
                self._open_file()
                old_file.close()

                if self.bookmark:
                    # delete the bookmark if we got here since we finished the file
                    os.unlink(self.bookmark_path)

        except (IOError, OSError):

            # The filename no longer exists, revert back, as it may
            # be in the process of being moved, a subsequent check
            # will find it and then take action.

            self._current_stat = old_stat
            self._current_file = old_file


