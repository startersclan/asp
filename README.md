# ASP

[![github-actions](https://github.com/startersclan/ASP/workflows/ci-master-pr/badge.svg)](https://github.com/startersclan/ASP/actions)
[![github-release](https://img.shields.io/github/v/release/startersclan/ASP?style=flat-square)](https://github.com/startersclan/ASP/releases/)
[![docker-image-size](https://img.shields.io/docker/image-size/startersclan/ASP/latest)](https://hub.docker.com/r/startersclan/ASP)

The new BF2Statistics 3.0 ASP, currently in public Beta. The GameSpy server to match is over at https://github.com/BF2Statistics/BattleSpy

## Usage

See [docker-compose.example.yml](docker-compose.example.yml) example showing how to deploy BF2Statistics using `docker-compose`.

Notes:
- Mount the [`config.php`](./config/ASP/config.php) as read-only for the `php` service so that `ASP` doesn't mutate the config file. The `ASP`'s `System > Edit Configuration` should no longer be used, since we are managing the configuration file. `System > System Tests` will fail for `config.php` since `ASP` expects the file to be writeable, but this may be ignored.
- Seed the `db` service with `schema.sql` and `data.sql` so that the database is populated on the first run. The `ASP`'s `System > System Installation` doesn't need to be used.
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
