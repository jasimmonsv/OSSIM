import os

class ActionExec:

    # TODO: check security
    def execCommand(self, command):
        os.system(command)

if __name__ == "__main__":
    
    c = ActionExec()
    c.execCommand("touch /tmp/kk")

# vim:ts=4 sts=4 tw=79 expandtab:
