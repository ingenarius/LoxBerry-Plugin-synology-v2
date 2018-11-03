import os
import time
import urllib
import logging
from ConfigParser import ConfigParser

class MyTelegramBot(object):
    def __init__(self):
        """Creates an object for a Telegram Bot using the official Web API (2017)
        #######################################################
        #     TELEGRAM Bot
        #######################################################
        # create object for DiskStation functions
        # change data according to your setup in proxy.py
        #######################################################
        """
        # create logging instance
        lbplog = os.environ['LBPLOG'] + "/synology/synology.log"
        logging.basicConfig(filename=lbplog,level=logging.INFO,format='%(asctime)s: %(message)s ')
        # create file strings from os environment variables
        lbpconfig = os.environ['LBPCONFIG'] + "/synology/plugin.cfg"
        # create config object to the the telegram bot details
        cfg = ConfigParser()
        cfg.read(lbpconfig)
        self.token = cfg.get("TELEGRAM", "TOKEN")
        self.chat_id = cfg.get("TELEGRAM", "CHAT_ID")
        try:
	    logging.info("<INFO> tbot.py: trying to import module \"telegram\" ")
	    import telegram
        except:
            logging.info("<ERROR> tbot.py: Error while importing \"telegram\" ")
	    return None
        self.bot = telegram.Bot(token=self.token)
        
    def get_me(self):
        """get information about the telegram bot"""
        try:
            logging.info("<DEBUG> tbot-py: get_me... OK")
            return self.bot.getMe()
        except:
            logging.info("<ERROR> tbot-py: get_me")
            return False

    def get_updates(self, offset=None):
        """get last updates of Telegram Bot"""
        try:
            if offset:
                self.updates = self.bot.getUpdates(limit=5, timeout=60, offset=offset)
                logging.info("<DEBUG> tbot-py: get_updates with offset... OK")
            else:
                self.updates = self.bot.getUpdates(limit=5, timeout=60)
                logging.info("<DEBUG> tbot-py: get_updates... OK")
            return self.updates
        except:
            logging.info("<ERROR> tbot-py: get_updates")
            return False

    def get_last_update_id(self):
        """get the update IDs from the last updates of Telegram Bot"""
        update_ids = []
        self.get_updates()
        if self.updates != False:
            for update in self.updates:
                update_ids.append(int(update['update_id']))	
            return max(update_ids)
        else:
            return False

    def get_last_chat_id_and_text(self):
        """get last chat IDs and text of Telegram Bot"""
        self.get_updates()
        if self.updates != False:
            num_updates = len(self.updates)
            last_update = num_updates - 1
            text = updates[last_update]['message']['text']
            chat_id = updates[last_update]['message']['chat']['id']
            return (text, chat_id)
        else:
            return False
    
    def echo_all(self, updates):
        """let the Telegram Bot repeat all messages"""
        for update in updates:
            text = update['message']['text']
            chat = update['message']['chat']['id']
            self.send_message(text, chat)
    
    def send_message(self, text):
        """send text message to specific chat ID"""
        try:
            self.bot.sendChatAction(chat_id=self.chat_id, action=telegram.ChatAction.TYPING)
            self.bot.sendMessage(chat_id=self.chat_id, text=text)
            logging.info("<DEBUG> tbot.py: send_message... OK")
            return True
        except:
            logging.info("<ERROR> tbot-py: send_message")
            return False
            
    
    def send_photo(self, imagePath):
        """send photo to specific chat ID"""
        try:
            self.bot.sendChatAction(chat_id=self.chat_id, action=upload_photo)
            #self.bot.send_chat_action(chat_id=self.chat_id, action=telegram.ChatAction.UPLOAD_PHOTO)
            logging.info("<DEBUG> tbot.py: sendChatAction")
            t = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
            logging.info("<DEBUG> tbot.py: add timestring")
            photo = open(imagePath, 'rb')
            logging.info("<DEBUG> tbot.py: open photo")
            self.bot.sendPhoto(chat_id=self.chat_id, photo=photo, caption=t)
            logging.info("<DEBUG> tbot.py: sendPhoto")
            return True
        except:
            logging.info("<ERROR> tbot.py: sending photo failed")
            return False

    def send_pic(self, img):
        """send picture to telegram chat"""
        try:
            logging.info("<DEBUG> tbot.py: send_pic... Started")
            t = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
            logging.info("<DEBUG> tbot.py: timestamp %s" % t)
            self.bot.send_photo(chat_id=self.chat_id, photo=open(img, 'rb'), caption=t)
            logging.info("<DEBUG> tbot.py: send_photo... OK")
            return True
        except:
            logging.info("<ERROR> tbot.py: sending photo failed")
            return False

