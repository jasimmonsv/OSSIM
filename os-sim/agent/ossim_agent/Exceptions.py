
# Use this class only for non-recoverable errors
#
# The exception is handled in the main loop
#  - logging the error with [CRITICAL] severity
#  - stopping agent closing all descriptors
#
class AgentCritical(Exception):

    def __init__(self, msg=''):
        self.msg = msg

    def __str__(self):
        return repr(self.msg)


