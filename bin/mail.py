import os
import logging
import smtplib
import base64
try:
    from ConfigParser import ConfigParser
except:
    from configparser import ConfigParser
try:
    from email.MIMEMultipart import MIMEMultipart
except:
    from email.mime.multipart import MIMEMultipart
try:
    from email.MIMEText import MIMEText
except:
    from email.mime.text import MIMEText
try:
    from email.MIMEBase import MIMEBase
except:
    from email.mime.base import MIMEBase
try:
    from email import Encoders
except:
    from email import encoders as Encoders

class Email(object):
    def __init__(self, LOGLEVEL="loglevel.INFO"):
        """Creates an object for an Email smtp connection. These methods are implemented:
        SendMsg(subject, body)
        SendAttachment(subject, body, file)"""

        # create file strings from os environment variables
        lbplog = os.environ['LBPLOG'] + "/synology/synology.log"
        lbpconfig = os.environ['LBPCONFIG'] + "/synology/plugin.cfg"

        logging.basicConfig(filename=lbplog,level=LOGLEVEL,format='%(asctime)s: %(message)s ')
        cfg = ConfigParser()
        cfg.read(lbpconfig)
        self.email_user = cfg.get("EMAIL", "USER")
        self.mail_to = cfg.get("DISKSTATION", "NOTIFICATION")
        self.smtp_server = cfg.get("EMAIL", "SERVER")
        self.smtp_port = int(cfg.get("EMAIL", "PORT"))
        try:
            self.email_pwd = base64.b64decode(cfg.get("EMAIL", "PWD")).decode()
        except:
            logging.info("<ERROR> mail.py: password could not be decoded!")

    def GetVars(self):
        """print out all variables used in this class"""
        print(self.email_user)
        print(self.mail_to)
        print(self.smtp_server)
        print(self.smtp_port)
        
    def ServerConnect(self, msg):
        """makes the connection to the smtp server and sends the mail"""
        try:
            server = smtplib.SMTP(self.smtp_server, self.smtp_port)
            server.ehlo()
            server.starttls()
            server.login(self.email_user, self.email_pwd)
            server.sendmail(self.email_user, self.mail_to, msg)
            server.quit()
            return True
        except:
            logging.info("<ERROR> SMTP server connection failed")
            return False

    def SendMsg(self, subj, body):
        """Send Email as text message"""
        try:
            msg = MIMEText(body)
            msg['Subject'] = subj
            msg['From'] = self.email_user
            msg['To'] = self.mail_to
            response = self.ServerConnect(msg.as_string())
            logging.debug("<DEBUG> mail.py: ServerConnect response: %s" % response)
            if (response == True):
                return True
            else:
                return False
        except:
            logging.info("<ERROR> mail not sent")
            return False

    def SendAttachment(self, subj, body, filename):
        """Send Email with attachment"""
        try:
            msg = MIMEMultipart()
            msg['Subject'] = subj 
            msg['From'] = self.email_user
            msg['To'] = self.mail_to
            part = MIMEBase('application', "octet-stream")
            try:
                part.set_payload(open(filename, "rb").read())
            except:
                logging.info("<ERROR> snapshot could not be opened")
                return False
            Encoders.encode_base64(part)
            part.add_header('Content-Disposition', "attachment; filename=%s" % filename)
            msg.attach(part)
            response = self.ServerConnect(msg.as_string())
            logging.debug("<DEBUG> mail.py: ServerConnect response: %s" % response)
            if (response == True):
                return True
            else:
                return False
        except e:
            logging.info("<ERROR> mail not sent")
            return False

