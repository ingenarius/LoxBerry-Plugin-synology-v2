import os
import time
import urllib
from ConfigParser import ConfigParser
try:
	import telegram
except:
	return False

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
        # create file strings from os environment variables
        lbpconfig = os.environ['LBPCONFIG'] + "/REPLACELBPPLUGINDIR/plugin.cfg"
        # create config object to the the telegram bot details
        cfg = ConfigParser()
        cfg.read(lbpconfig)
        self.token = cfg.get("TELEGRAM", "TOKEN")
        self.chat_id = cfg.get("TELEGRAM", "CHAT_ID")
        self.bot = telegram.Bot(self.token)
        
    def get_me(self):
        """get information about the telegram bot"""
        try:
            return self.bot.getMe()
        except:
            return False

    def get_updates(self, offset=None):
        """get last updates of Telegram Bot"""
        try:
            if offset:
                self.updates = self.bot.getUpdates(limit=5, timeout=60, offset=offset)
            else:
                self.updates = self.bot.getUpdates(limit=5, timeout=60)
            return self.updates
        except:
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
            return True
        except:
            return False
            
    
    def send_photo(self, imagePath):
        """send photo to specific chat ID"""
        try:
            self.bot.sendChatAction(chat_id=self.chat_id, action=telegram.ChatAction.UPLOAD_PHOTO)
            t = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
            photo = open(imagePath, 'rb')
            self.bot.sendPhoto(chat_id=self.chat_id, photo=photo, caption=t)
            return True
        except:
            return False

