#!/bin/sh
OSSIM_HOME=/home/dk/cvs/ossim/
cd $OSSIM_HOME
rm -f filenames
find www -name "*.php" >> filenames
find include -name "*.php" >> filenames
find www -name "*.inc" >> filenames
find include -name "*.inc" >> filenames
xgettext -d ossim -s -o ossim.po -f filenames 
for i in `ls $OSSIM_HOME/locale/ | grep -v CVS`; 
do 
echo "Updating $i"; 
msgmerge -N -s -U $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.po $OSSIM_HOME/ossim.po;
msgfmt -c -v -o $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.mo $OSSIM_HOME/locale/$i/LC_MESSAGES/ossim.po;
echo "Finished updating $i"; 
done
