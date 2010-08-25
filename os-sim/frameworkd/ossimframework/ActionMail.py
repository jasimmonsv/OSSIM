import smtplib
from email.MIMEText import MIMEText

class ActionMail:

    def __init__(self):
        
        self.__smtp = smtplib.SMTP()


    def sendmail(self, sender, recipients, subject, message):
    
        # Create a text/plain message
        msg = MIMEText(message, 'plain', 'latin-1')

        msg['Subject'] = subject
        msg['From'] = sender
        msg['To'] = ", ".join(recipients)

        # Send the message via our own SMTP server.
        self.__smtp.connect()
        try:
            self.__smtp.sendmail(sender, recipients, msg.as_string())
        except Exception, e:
            # TODO: Log error message
            pass
            
        self.__smtp.close()

if __name__ == "__main__":

    m = ActionMail()

    sender = "David Gil <dgil@ossim.net>"
    recipients = [ "David Gil <dgil@ossim.net>", "DK <dk@ossim.net>" ]
    subject = "Test message from Ossim frameworkd"
    message = "test.\r\ntest."

    m.sendmail(sender, recipients, subject, message)

# vim:ts=4 sts=4 tw=79 expandtab:
