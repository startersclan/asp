# ASP

[![github-actions](https://github.com/startersclan/asp/workflows/ci-master-pr/badge.svg)](https://github.com/startersclan/asp/actions)
[![github-release](https://img.shields.io/github/v/release/startersclan/asp?style=flat-square)](https://github.com/startersclan/asp/releases/)
[![docker-image-size](https://img.shields.io/docker/image-size/startersclan/bf2stats/master?label=asp)](https://hub.docker.com/r/startersclan/asp)

The new BF2Statistics 3.0 ASP, currently in public Beta. The GameSpy server to match is over at https://github.com/BF2Statistics/BattleSpy

## Usage (docker)

```sh
docker run --rm -it -p 80:80 -e DB_HOST=db -e DB_HOST=db -e DB_PORT=3306 -e DB_NAME=bf2stats -e DB_USER=admin -e DB_PASS=admin startersclan/asp:3.3.0
```

See [this](docs/full-bf2-stack-example) example showing how to deploy [Battlefield 2 1.5 server](https://github.com/startersclan/docker-bf2), [PRMasterserver](https://github.com/startersclan/PRMasterServer) as the master server, and `ASP` as the stats web server, using `docker-compose`.

### Upgrading to v2.6.0 from prior versions

See here for [quick instructions](docs/upgrading-docker-images-to-2.6.md).

## Development

```sh
# Start
docker-compose up --build
# ASP available at http://localhost:8081/ASP. Username: admin, password admin. See ./config/ASP/config.php
# phpmyadmin available at http://localhost:8083. Username: root, password: ascent. See ./config/ASP/config.php config file

# Development - Install vscode extensions
# Once installed, set breakpoints in code, and press F5 to start debugging.
code --install-extension bmewburn.vscode-intelephense-client # PHP intellisense
code --install-extension xdebug.php-debug # PHP remote debugging via xdebug
code --install-extension ms-python.python # Python intellisense
# If xdebug is not working, iptables INPUT chain may be set to DROP on the docker bridge.
# Execute this to allow php to reach the host machine via the docker0 bridge
sudo iptables -A INPUT -i br+ -j ACCEPT

# asp - Exec into container
docker exec -it $( docker-compose ps -q asp ) sh
# asp - List backups
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/backups
# asp - List cache
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/cache
# asp - List logs
docker exec -it $( docker-compose ps -q asp ) ls -al /src/ASP/system/logs
# asp - List snapshots
docker exec -it $( docker-compose ps -q asp ) ls -alR /src/ASP/system/snapshots/

# Test routes
docker-compose -f docker-compose.test.yml --profile dev up

# Test production builds
(cd docs/full-bf2-stack-example && docker compose -f docker-compose.yml -f docker-compose.build.prod.yml up --build)
docker compose -f docker-compose.test.yml --profile prod up
docker compose -f docker-compose.test.yml --profile dns up

# Dump the DB
docker exec $( docker-compose ps | grep db | awk '{print $1}' ) mysqldump -uroot -pascent bf2stats | gzip > bf2stats.sql.gz

# Restore the DB
zcat bf2stats.sql.gz | docker exec -i $( docker-compose ps | grep db | awk '{print $1}' ) mysql -uroot -pascent bf2stats

# Stop
docker-compose down

# Cleanup
docker-compose down
docker volume rm asp_backups-volume
docker volume rm asp_cache-volume
docker volume rm asp_logs-volume
docker volume rm asp_snapshots-volume
docker volume rm asp_db-volume
```

## Release

```sh
./scripts/release.sh "3.x.x" "A comment to describe the release"
git add .
git commit -m "Chore: Release 3.x.x"
```

## FAQ

### Q: ASP dashboard shows `Parse error: syntax error, unexpected 'admin' (T_STRING) in /src/ASP/system/framework/View.php(346) : eval()'d code on line 153`

A: Grant `php`'s `www-data` user write permission for `config.php`.

```sh
chown 82:82 ./config/ASP/config.php
chmod 666 ./config/ASP/config.php
docker-compose restart php
```

### Q: `Xdebug: [Step Debug] Could not connect to debugging client. Tried: host.docker.internal:9000 (through xdebug.client_host/xdebug.client_port)` appears in the php logs

A: The debugger is not running. Press `F5` in `vscode` to start the `php` `xdebug` debugger. If you stopped the debugger, it is safe to ignore this message.
