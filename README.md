# ASP

[![github-actions](https://github.com/startersclan/asp/workflows/ci-master-pr/badge.svg)](https://github.com/startersclan/asp/actions)
[![github-release](https://img.shields.io/github/v/release/startersclan/asp?style=flat-square)](https://github.com/startersclan/asp/releases/)
[![docker-image-size](https://img.shields.io/docker/image-size/startersclan/asp/master?label=asp)](https://hub.docker.com/r/startersclan/asp)

The new BF2Statistics 3.0 ASP, currently in public Beta. The GameSpy server to match is over at https://github.com/BF2Statistics/BattleSpy

## Usage (docker)

```sh
docker run --rm -it -p 80:80 -e DB_HOST=db -e DB_PORT=3306 -e DB_NAME=bf2stats -e DB_USER=root -e DB_PASS=ascent startersclan/asp:3.2.0
```

See [this](docs/full-bf2-stack-example) example showing how to deploy [Battlefield 2 1.5 server](https://github.com/startersclan/docker-bf2), [PRMasterserver](https://github.com/startersclan/PRMasterServer) as the master server, and `ASP` as the stats web server, using `docker-compose`.

### Upgrading

See instructions to upgrade to v3.3.0 [here](docs/upgrading-docker-images-to-3.3.md).

## Development

To use Docker Compose v2, use `docker compose` instead of `docker-compose`.

```sh
# 1. Start
docker-compose up --build
# ASP available at http://localhost:8081/ASP. Username: admin, password admin. See ./config/ASP/config.php
# phpmyadmin available at http://localhost:8083. Username: root, password: ascent. See ./config/ASP/config.php config file

# 2. Once everything has started, restart the BF2 server to begin recording stats
docker-compose restart bf2

# 3. Before launching the BF2 client, spoof gamespy DNS by adding these entries in C:\Windows\system32\drivers\etc\hosts. This is needed for the BF2 client to work correctly.
# Replace '192.168.1.100' with your development machine's IP address
192.168.1.100 battlefield2.available.gamespy.com
192.168.1.100 battlefield2.master.gamespy.com
192.168.1.100 battlefield2.ms14.gamespy.com
192.168.1.100 master.gamespy.com
192.168.1.100 motd.gamespy.com
192.168.1.100 gpsp.gamespy.com
192.168.1.100 gpcm.gamespy.com
192.168.1.100 gamespy.com
192.168.1.100 bf2web.gamespy.com
192.168.1.100 gamestats.gamespy.com
192.168.1.100 eapusher.dice.se

# 4. Launch BF2 client and connect to the BF2 server
# - To use BF2Hub as the Gamespy server, launch BF2.exe, and login to your BF2Hub account, and connect to the BF2 server using MULTIPLAYER > CONNECT TO IP
# - To use PRMasterserver in docker-compose as the Gamespy server, if you have previously patched BF2.exe using the BF2Hub patcher, you must unpatch BF2.exe. Then launch BF2.exe (do not use BF2Hub), create a new Online account, login, and connect to the BF2 server using MULTIPLAYER > CONNECT TO IP.
# At the end of a round, the BF2 server will send a stats snapshot to the ASP. View stats in ASP.

# Development - Install vscode extensions
# Once installed, set breakpoints in code, and press F5 to start debugging.
code --install-extension bmewburn.vscode-intelephense-client # PHP intellisense
code --install-extension xdebug.php-debug # PHP remote debugging via xdebug
code --install-extension ms-python.python # Python intellisense
# If xdebug is not working, iptables INPUT chain may be set to DROP on the docker bridge.
# Execute this to allow php to reach the host machine via the docker0 bridge
sudo iptables -A INPUT -i br+ -j ACCEPT

# BF2 server - Restart server
docker-compose restart bf2
# BF2 server - Attach to the bf2 server console
docker attach $( docker-compose ps -q bf2 )
# BF2 server - Exec into container
docker exec -it $( docker-compose ps -q bf2) bash
# BF2 server - Read python logs
docker exec -it $( docker-compose ps -q bf2 ) bash -c 'cat python/bf2/logs/bf2game_*'
# BF2 server - List snapshots
docker exec -it $( docker-compose ps -q bf2 ) bash -c 'ls -al python/bf2/logs/snapshots/sent'
docker exec -it $( docker-compose ps -q bf2 ) bash -c 'ls -al python/bf2/logs/snapshots/unsent'

# asp - Exec into container
docker exec -it $( docker-compose ps -q asp ) sh
# asp - List backups
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/backups
# asp - List cache
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/cache
# asp - List config
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/config
# asp - List logs
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/logs
# asp - List snapshots
docker exec -it $( docker-compose ps -q asp ) ls -alR /src/ASP/system/snapshots/

# Test
./test/test.sh dev 1

# Test production builds
./test/test.sh prod 1 1

# Dump the DB
docker exec $( docker-compose ps -q db ) mysqldump -uroot -pascent bf2stats | gzip > bf2stats.sql.gz

# Restore the DB
zcat bf2stats.sql.gz | docker exec -i $( docker-compose ps -q db ) mysql -uroot -pascent bf2stats

# Stop
docker-compose down

# Cleanup
docker-compose down --remove-orphans
docker volume rm asp_prmasterserver-volume
docker volume rm asp_backups-volume
docker volume rm asp_cache-volume
docker volume rm asp_config-volume
docker volume rm asp_logs-volume
docker volume rm asp_snapshots-volume
docker volume rm asp_db-volume
```

## Release

```sh
./release.sh 3.x.x "A comment to describe the release"
git add .
git commit -m "Chore: Release 3.x.x"
```

## FAQ

### Q: ASP dashboard shows `Parse error: syntax error, unexpected 'admin' (T_STRING) in /src/ASP/system/framework/View.php(346) : eval()'d code on line 153`

A: Grant the PHP user write permission for `./src/ASP/system/config/config.php`.

### Q: Database cannot be backed up using the ASP when the database is not on the same host!!!

A: If you are seeing this in docker, it is expected, since `ASP` and `db` containers are running on different hosts.

It is better to backup the DB on a `cron` schedule using `mysqldump` from another container linked to the `db` container:

```sh
# Dump a DB at host `db`, user `root`, password `ascent`, database `bf2stats`
mysqldump -hdb -uroot -pascent bf2stats
```

### Q: `Xdebug: [Step Debug] Could not connect to debugging client. Tried: host.docker.internal:9000 (through xdebug.client_host/xdebug.client_port)` appears in PHP logs on `docker-compose up`

A: If you are seeing this in development, the PHP debugger is not running. Press `F5` in `vscode` to start the PHP debugger. If you don't need debugging, set `XDEBUG_MODE=off` in `docker-compose.yml` to disable XDebug.
