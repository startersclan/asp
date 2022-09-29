# ASP

[![github-actions](https://github.com/startersclan/ASP/workflows/ci-master-pr/badge.svg)](https://github.com/startersclan/ASP/actions)
[![github-release](https://img.shields.io/github/v/release/startersclan/ASP?style=flat-square)](https://github.com/startersclan/ASP/releases/)
[![docker-image-size](https://img.shields.io/docker/image-size/startersclan/asp/master-nginx)](https://hub.docker.com/r/startersclan/asp)

The new BF2Statistics 3.0 ASP, currently in public Beta. The GameSpy server to match is over at https://github.com/BF2Statistics/BattleSpy

## Usage

See [docker-compose.example.yml](docker-compose.example.yml) example showing how to deploy BF2Statistics using `docker-compose`.

Notes:
- Mount the [`config.php`](./config/ASP/config.php) with write permissions, or else `ASP` dashboard will throw an error. Use `System > Edit Configuration` as reference to customize the config file.
- Optional: Mount your customized [`armyAbbreviationMap.php`](./config/ASP/armyAbbreviationMap.php), [`backendAwards.php`](./config/ASP/backendAwards.php), and [`ranks.php`](./config/ASP/ranks.php) config files if you are using a customized mod. Unlike `config.php`, they don't need write permissions.
- Seed the `db` service with `schema.sql` and `data.sql` so that the database is populated on the first run. The `System > System Installation` doesn't need to be used.
- [Backup the DB](#development) using `mysqldump` instead of the ASP. `System > Backup Stats Database` will not be allowed since the DB is on remote host. This means there is no need for provisioning a `backups-volume` volume.
- Optional: For better security, define `MARIADB_USER` and `MARIADB_PASSWORD` for the `db` service, so that a regular `mariadb` user is created on the first run, instead of using the `root` user. Note that this hasn't been tested, but it seems to work nicely, although it might break some modules in the `ASP` dashboard if they ruly on `root` privileges (any?).

## Development

Requires `docker` and `docker-compose`.

```sh
# Start
docker-compose up --build
# Dashboard now available at http://localhost/ASP, username: admin, password admin. See ./config/ASP/config.php config file
# phpmyadmin available at http://localhost:8080. Username: root, password: ascent. See ./config/ASP/config.php config file

# Fix php xdebug not reaching host IDE
iptables -A INPUT -i br+ -j ACCEPT

# Test routes
docker-compose -f docker-compose.test.yml up

# Test production builds locally
docker build -t startersclan/asp:nginx -f Dockerfile.nginx.prod .
docker build -t startersclan/asp:php -f Dockerfile.php.prod .
docker-compose -f docker-compose.example.yml up

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

Solution: Grant `php`'s `www-data` user write permission for `config.php`.

```sh
chown 33:33 ./config/ASP/config.php
chmod 666 ./config/ASP/config.php
docker-compose restart php
```
