#
# This script is used to prevent Cross Service Exploitation between Gamespy Service Providers
#

import host
import string
from bf2.BF2StatisticsConfig import http_backend_addr, http_backend_port
from bf2.stats.miniclient import miniclient, http_get
from bf2 import g_debug

def init():
	print "Player Verify module initialized"
	host.registerHandler('PlayerConnect', onPlayerConnect, 1)

	
def onPlayerConnect(aPlayer):
	
	# Ignore AI players
	if aPlayer.isAIPlayer():
		return
	
	#if g_debug: 
	player_nick = string.replace(aPlayer.getName(), ' ', '%20')
	asp_URI = '/ASP/verifyplayer.aspx?pid=%d&nick=%s' % ( int(aPlayer.getProfileId()), player_nick )
	
	# Run check
	print "Running CSE Verification Check on Player (%s)" % str(aPlayer.getName())
	if g_debug: print "URI: %s" % (asp_URI)
	data = http_get( http_backend_addr, http_backend_port, asp_URI )
	
	if data and data[0] == 'O':
		
		# Split the response into an array
		datalines = data.splitlines()
		
		# Parse result and message
		items = datalines[2].split('\t')
		result = str(items[1])
		message = str(items[2])
		
		#if g_debug: 
		print "Backend CSE Response on Player (%s): %s - %s " % (str(aPlayer.getName()), str(result), str(message))
		
		# Is player OK?
		if (result == "NOK"): host.rcon_invoke('admin.kickPlayer ' + str(aPlayer.index))
		
	else:
		print "Backend CSE Response on Player (%s) is INVALID, length %d" % int(len(data))
	