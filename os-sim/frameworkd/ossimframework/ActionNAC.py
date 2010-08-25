import sys

class ActionNAC:

    def __init__(self):
    	pass

    def sendAlarm(self, host, port, message):
	s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	s.sendto(message, (host, port))
    	
if __name__ == "__main__":
	a = ActionNAC()
	host = "localhost"
	port = 41000
	message = "Alarm|test|23|192.168.1.123"
   	m.sendmail(host, port, message)

