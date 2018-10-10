#!/usr/bin/python

from synology import DiskStation
from ConfigParser import ConfigParser
from time import sleep
import socket
import logging
import os

def main():
    """get a list of all installed cameras"""
    # create file strings from os environment variables
    lbplog = os.environ['LBPLOG'] + "/synology/synology.log"
    lbpdata = os.environ['LBPDATA'] + "/synology/cameras.dat"

    # creating log file and set log format
    logging.basicConfig(filename=lbplog,level=logging.INFO,format='%(asctime)s: %(message)s ')
    logging.info("<INFO> camera.py: init...")
    # open config file and read options
        
    try:
        ds = DiskStation()
        s = ds.Login()
    except:
        logging.info("<ERROR> login to DiskStation was not possible")
        quit()
    if s == True:
        cam_file = open(lbpdata, "w")
        cam_list = ds.GetCams()
        if cam_list != '':
            for c in cam_list.json().get('data').get('cameras'):
                c_id = str(c.get('id'))
                c_vendor = str(c.get('vendor'))
                c_model = str(c.get('model'))
                cam_file.write(c_id + ":" + c_vendor + " - " + c_model)
        cam_file.close()
        ds.Logout()
    else:
        quit()

if __name__ == "__main__":
    main()
