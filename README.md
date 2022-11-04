# ASP

[![github-actions](https://github.com/startersclan/ASP/workflows/ci-master-pr/badge.svg)](https://github.com/startersclan/ASP/actions)
[![github-release](https://img.shields.io/github/v/release/startersclan/ASP?style=flat-square)](https://github.com/startersclan/ASP/releases/)
[![docker-image-size](https://img.shields.io/docker/image-size/startersclan/asp/nginx)](https://hub.docker.com/r/startersclan/asp)

The new BF2Statistics 3.0 ASP, currently in public Beta. The GameSpy server to match is over at https://github.com/BF2Statistics/BattleSpy

## Usage

```
docker pull startersclan/asp:3.1.0-nginx
docker pull startersclan/asp:3.1.0-php
```

See [this](docs/full-bf2-stack-example) example showing how to deploy [Battlefield 2 1.5 server](https://github.com/startersclan/docker-bf2/), the [gamespy emulator](https://github.com/startersclan/PRMasterServer), and `bf2stats` using `docker-compose`.

## Development

- Install `docker` and `docker-compose`
- Install `vscode` for development. Install `vscode` extensions [`PHP Intelephense`](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client) for intellisense, and [xdebug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) and for `php` debugging.
- To start `php` debugging, press `F5` in `vscode`. Set breakpoints in code, and whenever a page is loaded, `vscode` shows hit breakpoints. To stop debugging, press `shift+F5`.

```sh
# Start
docker-compose up --build
# Dashboard now available at http://localhost:8081/ASP, username: admin, password admin. See ./config/ASP/config.php config file
# phpmyadmin available at http://localhost:8082. Username: root, password: ascent. See ./config/ASP/config.php config file

# If xdebug is not working, iptables INPUT chain may be set to DROP on the docker bridge.
# Execute this to allow php to reach the host machine via the docker0 bridge
sudo iptables -A INPUT -i br+ -j ACCEPT

# Test routes
docker-compose -f docker-compose.test.yml up

# Test production builds locally
docker build -t startersclan/asp:nginx -f Dockerfile.nginx.prod .
docker build -t startersclan/asp:php -f Dockerfile.php.prod .

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
