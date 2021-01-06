import os
import socket
import logging
from synology import DiskStation
from mail import Email
from time import sleep
try:
    from ConfigParser import ConfigParser
except:
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
    logging.basicConfig(filename=lbplog,level=logging.INFO,format='%(asctime)s: %(message)s ')
    logging.info("<INFO> initialise logging...")
    # open config file and read options
    try:
        cfg = ConfigParser()
        global_cfg = ConfigParser()
        cfg.read(lbpconfig)
        global_cfg.read(lbsconfig)
    except:
        msg = "<ERROR> Error parsing config files..."
        logging.error(msg)

    # set logging level as in config file specified
    LOGLEVEL = "loglevel." + str(cfg.get("SERVER", "LOGLEVEL"))
    logging.basicConfig(filename=lbplog,level=LOGLEVEL,format='%(asctime)s: %(message)s ')

    # define UDP server
    UDP_IP = "0.0.0.0"
    UDP_PORT = int(cfg.get("SERVER", "PORT"))
    try:
        # start the server listening on UDP socket
        sock = socket.socket( socket.AF_INET, socket.SOCK_DGRAM )
        sock.bind( (UDP_IP,UDP_PORT) )
        logging.debug("<DEBUG> socket established: \"%s:%s\"" % (UDP_IP, UDP_PORT))
    except:
        logging.error("<ERROR> failed to bind socket!")

    # initialise variable(s)
    result = False
    CIDS = cfg.get("DISKSTATION", "CIDS")
    try:
        SENT_VIA = int(cfg.get("DISKSTATION", "SENT_VIA"))
        NROFPICS = int(cfg.get("DISKSTATION", "NROFPICS"))
        pic_calc = NROFPICS + 1
    except:
        SENT_VIA = 0
        pic_calc = 4
    MINISERVER = global_cfg.get("MINISERVER1", "IPADDRESS")
    logging.info("<INFO> loading configuration...")

    while True:
        data, addr = sock.recvfrom( 1024 ) # read data with buffer size of 1024 bytes
        data = str(data.decode('ascii'))
        logging.info("<INFO> received message from %s: %s" % (addr[0], data))
        try:
            cam_id = int(data.split(":")[1])
        except:
            cam_id = 0

        if ( str(data).__contains__("TestMail") ):
            email = Email(LOGLEVEL)
            response = email.SendMsg("Test from Loxberry", "This message was sent sucessfully from your Loxberry! \nHave fun :-) ")
            if response == True:
                logging.info("<INFO> successful executed \"%s\" " % data)
            else:
                logging.error("<ERROR> %s not executed" % data)
            continue

        if (addr[0] == MINISERVER or addr[0] == "127.0.0.1"):     # only the miniserver and loxberry are allowed to send commands
            # create DS object and login
            ds = DiskStation(LOGLEVEL)
            s = ds.Login()
            # on login error the answer will be "False"
            if ( s == False ):
                continue
            # StartRec sends also the ID of the camera
            elif ( str(data).__contains__("StartRec:") ):
                logging.info("<INFO> %s: executing..." % data)
                response = ds.StartRec(cam_id)
            # StopRec sends also the ID of the camera
            elif ( str(data).__contains__("StopRec:") ):
                logging.info("<INFO> %s: executing..." % data)
                response = ds.StopRec(cam_id)
            # Motion detection enable/disable for all cameras
            elif ( str(data).__contains__("MotionDetection") ):
                for c in CIDS.split(","):
                    #logging.info("camera id: %s" % str(c))
                    sleep(3)        # wait before the next request will be sent
                    if ( (data == "MotionDetectionOn") and c != 0):
                        logging.info("<INFO> %s: executing for camera ID %s ..." % (data, str(c)))
                        response = ds.MotionDetectionOn(int(c))
                    elif ( (data == "MotionDetectionOff") and c != 0):
                        logging.info("<INFO> %s: executing for camera ID %s ..." % (data, str(c)))
                        response = ds.MotionDetectionOff(int(c))
                    else:
                        continue
            # request does not match
            elif ( str(data).__contains__("Snapshot:") ):
                logging.info("<INFO> executing \"%s\"..." % data)
                for i in range(1, pic_calc):
                    response = ds.GetSnapshot(cam_id)
                    if response == True and SENT_VIA != 0:
                        response = ds.SendSnapshot(SENT_VIA)	# send snapshot via: 1 - Telegram bot, 2 - Email
                        sleep(3)
            else:
                logging.info("<INFO> \"%s\" is no valid message!" % data)
                response = False
                continue
            # log the responses
            if response == True:
                logging.info("<INFO> successful executed \"%s\" " % data)
            else:
                logging.error("<ERROR> \"%s\" not executed" % data)
            # logout from Diskstation
            ds.Logout()
        else:
            msg = "<INFO> message \"%s\" not from \"%s\" " % (data, MINISERVER)
            logging.info(msg)
            continue

if __name__ == "__main__":
    main()
