#!/usr/bin/python3
#!/usr/bin/python3

import socket

UDP_IP="127.0.0.1"
UDP_PORT=5000
#MESSAGE="miniserver says: go to sleep"
MESSAGE="Snapshot: 2"

print "UDP target IP:", UDP_IP
print "UDP target port:", UDP_PORT
print "message: \"%s\"" % MESSAGE

sock = socket.socket( socket.AF_INET, socket.SOCK_DGRAM )
sock.sendto( MESSAGE, (UDP_IP, UDP_PORT) )
