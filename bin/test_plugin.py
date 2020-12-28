import os
import socket
import logging
import base64
import telegram
from synology import DiskStation
from mail import Email
from time import sleep
from configparser import ConfigParser


def main():
    """loxberry plugin for synology surveillance station API functions:
        MailEnable:			enable mail notifications
        MailDisable:		disable mail notifications
        StartRec: 			start recording for one camera
        StopRec:			stop recording for one camera
        MotionDetectionOn:	enable motion detection for all cameras
        MotionDetectionOff:	disable motion detection for all cameras"""
    # create file strings from os environment variables
    lbplog = os.environ['LBPLOG'] + "/synology/synology.log"
    lbpconfig = os.environ['LBPCONFIG'] + "/synology/plugin.cfg"
    lbsconfig = os.environ['LBSCONFIG'] + "/general.cfg"

    # creating log file and set log format
    logging.basicConfig(filename=lbplog,level=logging.DEBUG,format='%(asctime)s: %(message)s ')
    logging.info("<DEBUG> initialise logging...")
    # open config file and read options
    try:
        cfg = ConfigParser()
        global_cfg = ConfigParser()
        cfg.read(lbpconfig)
        global_cfg.read(lbsconfig)
    except:
        msg = "<ERROR> Error parsing config files..." 
        logging.info(msg)

    # define UDP server
    UDP_IP = global_cfg.get("NETWORK", "IPADDRESS")
    UDP_PORT = 5555 #int(cfg.get("SERVER", "PORT"))
    try:
        # start the server listening on UDP socket
        sock = socket.socket( socket.AF_INET, socket.SOCK_DGRAM )
        sock.bind( (UDP_IP,UDP_PORT) )
    except:
        logging.info("<ERROR> failed to bind socket!")
        
    # initialise variable(s)
    result = False
    CIDS = cfg.get("DISKSTATION", "CIDS")
    DS_USER = cfg.get("DISKSTATION", "USER")
    DS_PWD = cfg.get("DISKSTATION", "PWD")
    DS_HOST = cfg.get("DISKSTATION", "HOST")
    DS_PORT = cfg.get("DISKSTATION", "PORT")
    EMAIL = cfg.get("DISKSTATION", "NOTIFICATION")
    tbotToken = cfg.get("TELEGRAM", "TOKEN")
    chatId = cfg.get("TELEGRAM", "CHAT_ID")
    lbpsnapshot = os.environ['LBPDATA'] + "/synology/snapshot.jpg"
    try:
        SENT_VIA = int(cfg.get("DISKSTATION", "SENT_VIA"))
    except:
        SENT_VIA = 0
    MINISERVER = global_cfg.get("MINISERVER1", "IPADDRESS")
    logging.info("<DEBUG> loading configuration...")

    print("This is the Synology Plugin for Loxberry TEST SCRIPT")
    print("Configuration Data:")
    print("Logfile:", lbplog)
    print("Plugin Configfile:", lbpconfig)
    print("System Configfile:", lbsconfig)
    print("UDP Server IP:", UDP_IP)
    print("UDP Server Port:", UDP_PORT)
    print("Miniserver IP:", MINISERVER)
    print("DS user:", DS_USER)
    print("DS pass:", DS_PWD)
    print("DS host:", DS_HOST)
    print("DS port:", DS_PORT)

    passwd = base64.b64decode(DS_PWD)
    print("Passwort: %s" % passwd)
    print("Passwort:", str(passwd.decode('ascii')))

    print("Creating DS object...")
    ds = DiskStation()
    print("Login to DS...")
    login = ds.Login()
    print("Logged In:", login)

    try:
        from tbot import MyTelegramBot
    except ImportError:
        logging.info("<ERROR> Importing telegram module was not possible!")
    print(lbpsnapshot)
    bot = telegram.Bot(token=tbotToken)
    response = bot.send_photo(chat_id=chatId, photo=open(lbpsnapshot, 'rb'), caption="test")
    print("response: ", response)
    if response == True:
        logging.info("<DEBUG> Photo sent to Telegram!")
    else:
        logging.info("<ERROR> Photo NOT sent to Telegram!")


if __name__ == "__main__":
    main()
