#################################################
#
# History:
#   11/24/05 v0.0.1 - ALPHA build
#   11/28/05 v0.0.2 - Removed killedByPlayer
#                   - Added kills
#   12/08/05 v0.0.3 - Added deaths
#   12/09/05 v0.0.4 - Removed tnv/tgm
#   12/10/05 v0.0.5 - Added prefix
#   12/14/05 v0.0.6 - Removed useless GS call
#                   - Removed tactical/zip/grappling kills
#                   - Added grappling deaths
#   12/25/05 v0.0.7 - Added v
#   01/03/06 v0.1 - BETA release
#   01/05/06 v0.1.1 - Added master db
#                   - Added socket timeout/error handling
#   01/25/06 v0.1.2 - Updated CDB IP
#   02/15/06 v0.1.3 - Updated CDB URL
#   06/17/06 v0.1.4 - Added EF army
#   02/01/06 v1.0 - Public Release
#
#   06/10/10 - Removed Combat scores, they now calculate in bf2statistics.php
#   06/10/10 - Added return
#   06/10/10 - Corrected Teamwork keys
#
#   3/10/18 v2.0 - Converted to snapshot to JSON format
#  
#################################################

#################################################
#     DO NOT EDIT ANYTHING BELOW THIS LINE!
#################################################

import host
import bf2.PlayerManager
import fpformat
from constants import *
from bf2 import g_debug
from bf2.stats.stats import getStatsMap, setStatsMap, getPlayerConnectionOrderIterator, setPlayerConnectionOrderIterator, roundArmies
from bf2.stats.medals import getMedalMap, setMedalMap

# ------------------------------------------------------------------------------
# omero 2006-03-31
# ------------------------------------------------------------------------------
from bf2.BF2StatisticsConfig import *
from bf2.stats.miniclient import miniclient, http_postSnapshot

# Added by Chump - for bf2statistics stats
from time import time, localtime, strftime

map_start = 0

def init():
	print "Snapshot module initialized"

	# Added by Chump - for bf2statistics stats
	host.registerGameStatusHandler(onChangeGameStatus)
	
	
# Added by Chump - for bf2statistics stats
def onChangeGameStatus(status):
	global map_start
	if status == bf2.GameStatus.Playing:
		map_start = time()

def invoke():

	# Added by Chump - for bf2statistics stats
	#host.pers_gamespyStatsNewGame()
	
	snapshot_start = time()
	
	if g_debug: print "Gathering SNAPSHOT Data"
	snapShot = getSnapShot()
	
	
	elapsedTime = time() - snapshot_start;
	ms = elapsedTime * 1000;
	print "SNAPSHOT Data generated in %d ms" % ms

	# Send snapshot to Backend Server
	print "Sending SNAPSHOT to backend: %s" % str(http_backend_addr)
	snapshot_sent = 0
	
	# -------------------------------------------------------------------
	# Attempt to send snapshot
	# -------------------------------------------------------------------
	try:
		backend_response = http_postSnapshot( http_backend_addr, http_backend_port, http_backend_asp, snapShot )
		if backend_response and backend_response[0] == 'O':
			print "SNAPSHOT Received: OK"
			snapshot_sent = 1
			if g_debug: sayAll("Game Stats Received OK")
			
		else:
			print "SNAPSHOT Received: ERROR"
			if backend_response and backend_response[0] == 'E':
				datalines = backend_response.splitlines()
				response = datalines[2].split("\t")
				print "Backend Response: %s" % str(response[-1:])
				if g_debug: sayAll("Game Stats Received in ERROR: %s" % str(response[-1:]))
				
			snapshot_sent = 0
		
	except Exception, e:
		snapshot_sent = 0
		print "An error occurred while sending SNAPSHOT to backend: %s" % str(e)
		if g_debug: sayAll("An error occurred while sending stats data to backend: %s" % str(e))
		
	
	# -------------------------------------------------------------------
	# If SNAPSHOT logging is enabled, or the snapshot failed to send, 
	# then log the snapshot
	# -------------------------------------------------------------------	
	if snapshot_logging == 1 or snapshot_sent == 0:
		snaplog_title = ""
		log_time = str(strftime("%Y%m%d_%H%M", localtime()))
	
		if snapshot_sent == 0:
			snaplog_title = snapshot_log_path_unsent + "/" + bf2.gameLogic.getMapName() + "_" + log_time + ".json"
			print "Logging snapshot for manual processing..."
		else:
			snaplog_title = snapshot_log_path_sent + "/" + bf2.gameLogic.getMapName() + "_" + log_time + ".json"
		
		print "SNAPSHOT log file: %s" % snaplog_title
		try:
			snap_log = file(snaplog_title, 'a')
			snap_log.write(snapShot)
			snap_log.close()
		
		except Exception, e:
			print "Cannot write to SNAPSHOT log file! Reason: %s" % str(e)
			print "Printing Snapshot as last resort manual processing: ", snapShot
			

	# Log snapshot timing
	elapsedTime = time() - snapshot_start;
	ms = elapsedTime * 1000;
	print "SNAPSHOT Processing Time: %d ms" % ms

# ------------------------------------------------------------------------------
# omero 2006-03-31
# ------------------------------------------------------------------------------
# always do the following at the end...
	repackStatsVectors()


def repackStatsVectors():

	# remove disconnected players
	cleanoutStatsVector()
	cleanoutMedalsVector()
	
	# repack stats and medal vector so there are no holes. gamespy doesnt like holes.
	medalMap = getMedalMap()
	statsMap = getStatsMap()
	playerOrderIt = getPlayerConnectionOrderIterator()

	newOrderIterator = 0
	newStatsMap = {}
	newMedalMap = {}

	highestId = 0
	for id, statsItem in statsMap.iteritems():

		newStatsMap[newOrderIterator] = statsItem
		if id in medalMap:
			newMedalMap[newOrderIterator] = medalMap[id]

		statsItem.connectionOrderNr = newOrderIterator
		newOrderIterator += 1
		
	print "Repacked stats map. Stats map size=%d. OrderIt changed from %d to %d" % (len(statsMap), playerOrderIt, newOrderIterator)

	setPlayerConnectionOrderIterator(newOrderIterator)
	setStatsMap(newStatsMap)
	setMedalMap(newMedalMap)
		
		

def cleanoutStatsVector():
	print "Cleaning out unconnected players from stats map"
	statsMap = getStatsMap()
	
	# remove disconnected players after snapshot was sent
	removeList = []
	for pid in statsMap:
		foundPlayer = False
		for p in bf2.playerManager.getPlayers():
			if p.stats == statsMap[pid]:
				foundPlayer = True
				break

		if not foundPlayer:
			removeList += [pid]

	for pid in removeList:
		print "Removed player %d from stats." % pid
		del statsMap[pid]		



def cleanoutMedalsVector():
	print "Cleaning out unconnected players from medal map"
	medalMap = getMedalMap()
	
	# remove disconnected players after snapshot was sent
	removeList = []
	for pid in medalMap:
		foundPlayer = False
		for p in bf2.playerManager.getPlayers():
			if p.medals == medalMap[pid]:
				foundPlayer = True
				break

		if not foundPlayer:
			removeList += [pid]

	for pid in removeList:
		if g_debug: print "Removed player %d from medals." % pid
		del medalMap[pid]

	
	
def getSnapShot():
	print "Assembling snapshot"
	global map_start
	statsMap = getStatsMap()
	
	# ----------------------------------------------------------------------------
	# Wilson212 2016-06-24
	# ----------------------------------------------------------------------------
	# Changed standardKeys['v'] from mod, to python version!
	# standardKeys['m'] is now for mod name
	#
	running_mod = str(host.sgl_getModDirectory())
	running_mod = running_mod.lower().replace("mods/", "")
	
	if g_debug: print 'Running MOD: %s' % running_mod
	
	standardKeys = [
		("authId",		str(stats_auth_id)),
		("authToken",	str(stats_auth_token)),
		("serverName",	str(bf2.serverSettings.getServerConfig('sv.serverName'))),
		("gamePort",	str(bf2.serverSettings.getServerConfig('sv.serverPort'))),
		("queryPort",	str(bf2.serverSettings.getServerConfig('sv.gameSpyPort'))),
		("mapId",		str(getMapId(bf2.serverSettings.getMapName()))),
		("mapName",		str(bf2.gameLogic.getMapName())),
		("mapStart",	str(map_start)),
		("mapEnd",		str(time())),
		("winner",		str(bf2.gameLogic.getWinner())),
		("gameMode",	str(getGameModeId(bf2.serverSettings.getGameMode()))),
		("mod",			str(running_mod)),
		("version",		"3.1"),
		("pc",			len(statsMap)),
	]
	
	if g_debug: print 'Finished Pre-Compile SNAPSHOT'

	# only send rwa key if there was a winner
	winner = bf2.gameLogic.getWinner()
	if winner != 0: 
		standardKeys += [("rwa", roundArmies[winner])]
	
	# get final ticket score
	if g_debug: print "Army 1 (%s) Score: %s" % (str(roundArmies[1]), str(bf2.gameLogic.getTickets(1)))
	if g_debug: print "Army 2 (%s) Score: %s" % (str(roundArmies[2]), str(bf2.gameLogic.getTickets(2)))
	standardKeys += [
		("ra1", str(roundArmies[1])),
		("rs1", str(bf2.gameLogic.getTickets(1))),
		("ra2", str(roundArmies[2])),
		("rs2", str(bf2.gameLogic.getTickets(2))),
    ]
	
	standardKeys += [("rst2", str(bf2.gameLogic.getTickets(2)))]
	
	stdKeyVals = []
	for k in standardKeys:
		stdKeyVals.append (":".join(( '"' + str(k[0]) + '"', '"' + str(k[1]) + '"')))

	snapShot = "{"
	snapShot += ",".join(stdKeyVals)

	if g_debug: print 'Snapshot Pre-processing complete: %s' % (str(snapShot))
	
	playerSnapShots = []
	if g_debug: print 'Num clients to base snap on: %d' % (len(statsMap))
	for sp in statsMap.itervalues():
		if g_debug: print 'Processing PID: %s' % (str(sp.profileId))
		playerSnap = getPlayerSnapshot(sp)
		if len(playerSnap) > 0:
			playerSnapShots.append (playerSnap)

	print "Doing Player SNAPSHOTS"
	snapShot += ',"players":[' + ",".join(playerSnapShots) + ']'
	
	# Add EOF marker for validation 
	snapShot += "}"
	
	return snapShot



def getPlayerSnapshot(playerStat):

	# The player didn't spawn in... 
	if playerStat.timePlayed == 0:
		return ""
		
	playerKeys = 	[

		# main keys 
		("pID", 	playerStat.profileId),
		("name",	playerStat.name),
		("t",		playerStat.team),
		("a",		playerStat.army),
		("ctime",	int(playerStat.timePlayed)),
		("c",		playerStat.complete),
		("ip",		playerStat.ipaddr),
		("ai",		playerStat.isAIPlayer),
		
		# score keys
		("rs",		playerStat.score),
		("cs",		playerStat.cmdScore),
		("ss", 		playerStat.skillScore),
		("ts",		playerStat.teamScore),
		("kills",	playerStat.kills),
		("deaths",	playerStat.deaths),
		("cpc",		playerStat.localScore.cpCaptures),
		("cpn",		playerStat.localScore.cpNeutralizes),
		("cpa",		playerStat.localScore.cpAssists),
		("cpna",	playerStat.localScore.cpNeutralizeAssists),
		("cpd",		playerStat.localScore.cpDefends),
		("ka",		playerStat.localScore.damageAssists),
		("he",		playerStat.localScore.heals),
		("rev",		playerStat.localScore.revives),
		("rsp",		playerStat.localScore.ammos),
		("rep",		playerStat.localScore.repairs),
		("tre",		playerStat.localScore.targetAssists),
		("drs",		playerStat.localScore.driverSpecials + playerStat.localScore.driverAssists),
		#("drs",	playerStat.localScore.driverSpecials),		// Processed in backend
		#("dra",	playerStat.localScore.driverAssists),		// Processed in backend
		#("pa",		playerStat.localScore.passengerAssists),	// Processed in backend
		
		# Additional player stats
		("tmkl",	playerStat.teamkills),
		("tmdg",	playerStat.localScore.teamDamages),
		("tmvd",	playerStat.localScore.teamVehicleDamages),
		("su",		playerStat.localScore.suicides),
		("ks",		playerStat.longestKillStreak),
		("ds",		playerStat.longestDeathStreak),
		("rank",	playerStat.rank),
		("ban",		playerStat.timesBanned),
		("kck",		playerStat.timesKicked),		
		
		# time keys
		("tco",		int(playerStat.timeAsCmd)),
		("tsl",		int(playerStat.timeAsSql)),
		("tsm",		int(playerStat.timeInSquad - playerStat.timeAsSql)),
		("tlw",		int(playerStat.timePlayed - playerStat.timeAsCmd - playerStat.timeInSquad)),
		("tvp", 	str(int(playerStat.vehicles[VEHICLE_TYPE_PARACHUTE].timeInObject)) )
		
	]
	
	# Combine current keys into player snapshot
	keyvals = []
	for k in playerKeys:
		keyvals.append (":".join(( '"' + str(k[0]) + '"', '"' + str(k[1]) + '"')))

	playerSnapShot = ",".join(keyvals)
	
	# army time
	keyvals = []
	for index in range(NUM_ARMIES):
		aTime = int(playerStat.timeAsArmy[index])
		if aTime > 0:
			keyvals.append ('{"id":' + str(index) + ',"time":' + str(aTime) + '}')
			
	armySnapShot = '"armyData":[' + ",".join(keyvals) + ']'
	
	# victims
	keyvals = []
	statsMap = getStatsMap()

	for p in playerStat.killedPlayer:
		if not p in statsMap:
			if g_debug: print "killedplayer_id victim connorder: ", playerStat.killedPlayer[p], " wasnt in statsmap!"
		else:
			keyvals.append ('{"id":' + str(statsMap[p].profileId) + ',"count":' + str(playerStat.killedPlayer[p]) + '}')
			
	victimSnapShot = '"victims":[' +",".join(keyvals) + ']'
	
	# medals
	medalsSnapShot = '"awards":['
	if playerStat.medals:
		if g_debug: print "Medals Found (%s), Processing Medals Snapshot" % (playerStat.profileId)
		medalsSnapShot += playerStat.medals.getSnapShot()
		
	medalsSnapShot = medalsSnapShot + ']'
	
	# vehicles - Backend does not care about VEHICLE_TYPE_[SOLDIER, NIGHTVISION, or GASMASK]
	# NOTE: VEHICLE_TYPE_NIGHTVISION and VEHICLE_TYPE_GASMASK do not register with onEnterVehicle()
	vehkeyvals = []
	for i in range(7):
		index = str(i)
		v = playerStat.vehicles[i]
		vTime = int(v.timeInObject)
		if vTime > 0:
			keyvals = []
			keyvals.append ('"id":' + str(i))
			keyvals.append ('"time":' + str(vTime))
			keyvals.append ('"score":' + str(v.score))
			keyvals.append ('"kills":' + str(v.kills))
			keyvals.append ('"deaths":' + str(v.deaths))
			keyvals.append ('"roadkills":' + str(v.roadKills))
			vehkeyvals.append ('{' +  ",".join(keyvals) + '}')

	vehicleSnapShot = '"vehicleData":[' + ",".join(vehkeyvals) + ']'
	
	# kits
	kitkeyvals = []
	for i in range(NUM_KIT_TYPES):
		index = str(i)
		k = playerStat.kits[i]
		kTime = int(k.timeInObject)
		if kTime > 0:
			keyvals = []
			keyvals.append ('"id":' + str(i))
			keyvals.append ('"time":' + str(kTime))
			keyvals.append ('"score":' + str(k.score))
			keyvals.append ('"kills":' + str(k.kills))
			keyvals.append ('"deaths":' + str(k.deaths))
			kitkeyvals.append ('{' +  ",".join(keyvals) + '}')

	kitSnapShot = '"kitData":[' + ",".join(kitkeyvals) + ']'
	
	# weapons
	# NOTE Backend does not care about WEAPON_TYPE_TARGETING, so skip it!
	i = 0
	weapkeyvals = []
	for j in range(NUM_WEAPON_TYPES):
	
		# Skip targetting
		if (j == WEAPON_TYPE_TARGETING):
			continue
			
		index = str(i)
		w = playerStat.weapons[i]
		wTime = int(w.timeInObject)
		if wTime > 0:
			keyvals = []
			keyvals.append ('"id":' + str(i))
			keyvals.append ('"time":' + str(wTime))
			keyvals.append ('"score":' + str(w.score))
			keyvals.append ('"kills":' + str(w.kills))
			keyvals.append ('"deaths":' + str(w.deaths))
			keyvals.append ('"fired":' + str(w.bulletsFired))
			keyvals.append ('"hits":' + str(w.bulletsHit))
			keyvals.append ('"deployed":' + str(w.deployed))
			weapkeyvals.append ('{' +  ",".join(keyvals) + '}')
		
		i += 1

	weaponSnapShot = '"weaponData":[' + ",".join(weapkeyvals) + ']'
	
	allSnapShots = [playerSnapShot, armySnapShot, kitSnapShot, vehicleSnapShot, weaponSnapShot, victimSnapShot, medalsSnapShot]
	playerSnapShot = ",".join(allSnapShots)
	return "{" + playerSnapShot + "}"


def sayAll(msg):
	host.rcon_invoke("game.sayAll \"" + str(msg) + "\"")

