#!/usr/bin/python 
# 2008 DK @ ossim
# 2010/06 DK @ ossim: add .cfg support
# TODO: Match rules from .cfg in the same order as the Agent does
# Please, check regexp.txt to see an example

import sys,re
import ConfigParser

############################## Function definitions ###########################

def hitems(config, section):
    hash = {}
    for item in config.items(section):
        hash[item[0]] = _strip_value(item[1])
    return hash

def _strip_value(value):
    from string import strip
    return strip(strip(value, '"'), "'")

def get_entry(config, section, option):
    value = config.get(section, option)
    value = _strip_value(value)
    return value


############################## End definitions ###########################


try:
   tmp = sys.argv[3]
except:
    print "\n\t%s log_filename regexp modifier" % sys.argv[0]
    print "\n\tmodifier can be V/v/y/n/a number indicating the offset to show"
    print "\ty --> show not matched lines"
    print "\tn --> do not show not matched lines"
    print "\tnumber --> Show $number"
    print "\tv --> verbose, show matching line"
    print "\tV --> vverbose, show matching line and regexp"
    print "\tq --> quiet, just show a summary"
    print "\tIf regexp ends in '.cfg' a plugin file is assumed as input and all regexps in that file will be checked\n"
    sys.exit()

f = open(sys.argv[1], 'r')
data = f.readlines()
cfg_file=exp=sys.argv[2]
single_regexp=True
if exp.endswith(".cfg"):
    single_regexp=False
    print "Multiple regexp mode used, parsing %s " % exp
else:
    print sys.argv[2]

line_match = 0

matched = 0

if single_regexp == True:
    # single regexp mode
    for line in data:
        result = re.findall(exp,line)
        try:
            tmp = result[0]
        except IndexError:
            if sys.argv[3] is "y":
                print "Not matched:", line
            continue
        # Matched
        if sys.argv[3] is "v":
            print line
        if sys.argv[3] is "V":
            print exp
            print line
        try:
            if int(sys.argv[3]) > 0:
                print "Match $%d: %s" % (int(sys.argv[3]),tmp[int(sys.argv[3])-1])
                #print "Match %d: %s" % (int(sys.argv[3]),result[int(sys.argv[3])])
            else: 
                if sys.argv[3] is not "q":
                    print result
        except ValueError:
            if sys.argv[3] is not "q":
                print result
        matched += 1

    print "Counted", len(data), "lines."
    print "Matched", matched, "lines."
else:
    SECTIONS_NOT_RULES = ["config", "info", "translation"]
    rules = {}
    sorted_rules = {}
    rule_stats = []
    # .cfg file mode
    config = ConfigParser.RawConfigParser()
    config.read(cfg_file)
    for section in config.sections():
        if section.lower() not in SECTIONS_NOT_RULES :
            rules[section] = hitems(config,section)
    keys = rules.keys()
    keys.sort()
    for line in data:
        for rule in rules.iterkeys():
            rulename = rule
            regexp = get_entry(config, rule, 'regexp')
            result = re.findall(regexp,line)
            try:
                tmp = result[0]
            except IndexError:
                if sys.argv[3] is "y":
                    print "Not matched", line
                continue
            # Matched
            if sys.argv[3] is not "q":
                print "Matched using %s" % rulename
            if sys.argv[3] is "v":
                print line
            if sys.argv[3] is "V":
                print regexp
                print line
            try:
                if int(sys.argv[3]) > 0:
                    print "Match $%d: %s" % (int(sys.argv[3]),tmp[int(sys.argv[3])-1])
                else:
                    if sys.argv[3] is not "q":
                        print result
            except ValueError:
                if sys.argv[3] is not "q":
                    print result
            # Do not match more rules for this line
            rule_stats.append(str(rulename))
            matched += 1
            break

    print "-----------------------------------------------------------------------------"

    for key in keys:
        print "Rule: \t%s\n\t\t\t\t\t\tMatched %d times" % (str(key), rule_stats.count(str(key)))

    print "Counted", len(data), "lines."
    print "Matched", matched, "lines."


