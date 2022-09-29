# ASP

[![github-actions](https://github.com/startersclan/ASP/workflows/ci-master-pr/badge.svg)](https://github.com/startersclan/ASP/actions)
[![github-release](https://img.shields.io/github/v/release/startersclan/ASP?style=flat-square)](https://github.com/startersclan/ASP/releases/)
[![docker-image-size](https://img.shields.io/docker/image-size/startersclan/ASP/latest)](https://hub.docker.com/r/startersclan/ASP)

The new BF2Statistics 3.0 ASP, currently in public Beta. The GameSpy server to match is over at https://github.com/BF2Statistics/BattleSpy

## Usage

See [docker-compose.example.yml](docker-compose.example.yml) example showing how to deploy BF2Statistics using `docker-compose`.

## Development

Requires `docker` and `docker-compose`.

```sh
# Start
docker-compose up --build
# Dashboard now available at http://localhost/ASP, username: admin, password admin. See ./src/ASP/system/config/config.php configuration file
# phpmyadmin available at http://localhost:8080. Username: root, password: ascent. See ./src/ASP/system/config/config.php configuration file

# Fix php xdebug not reaching host IDE
iptables -A INPUT -i br+ -j ACCEPT

# Test routes
docker-compose -f docker-compose.test.yml up

# Stop
docker-compose down

# Cleanup
docker volume rm asp_backups-volume
docker volume rm asp_cache-volume
docker volume rm asp_logs-volume
docker volume rm asp_snapshots-volume
docker volume rm asp_db-volume
```
