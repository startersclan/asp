# Upgrading docker images from previous versions

In <= v3.2.0, `asp` had separate `nginx` and `php` images.

Since v3.3.0: `asp` image containing both `nginx` and `php`, with environment variable support, and entrypoint that sets the correct permissions.

Benefits:

- Easier to deploy / upgrade. No need to separate `nginx` and `php` containers
- Environment variable configuration means no more need to mount config into `asp` container
- Entrypoint script sets permissions on volumes. `init-container` should only need to set permissions for `db` volume

## Upgrade steps

These steps are demonstrated using Docker Compose.

1. Merge the networks and volumes of `asp-nginx` and `asp-php` into a single `asp` container, switch to a volume and env vars for `asp` configuration, and remove `depends_on`.

For instance, from this:

```yaml
  asp-nginx:
    image: startersclan/bf2stats:3.2.0-nginx
    volumes:
      - ./src:/src:ro
      - ./config/ASP/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
    networks:
      traefik-network:
      bf2-network:
        aliases:
          - bf2web.gamespy.com # Important! Spoof Gamespy DNS for the BF2 server to reach our ASP server IP over this network
    depends_on:
      - init-container
      - asp-php

  asp-php:
    image: startersclan/bf2stats:3.2.0-php
    volumes:
      - ./config/ASP/config.php:/src/ASP/system/config/config.php # Main config file. Must be writeable or else ASP will throw an exception. Customize only if needed
      - backups-volume:/src/ASP/system/backups # This volume is effectively unused since ASP doesn't allow DB backups for a remote DB, but mount it anyway to avoid errors.
      - cache-volume:/src/ASP/system/cache
      - logs-volume:/src/ASP/system/logs
      - snapshots-volume:/src/ASP/system/snapshots
    networks:
      - traefik-network
      - bf2-network
    depends_on:
      - init-container
```

To this:

```yaml
  asp:
    image: startersclan/bf2stats:3.3.0
    environment:
      # See ./src/ASP/system/config/config.php for all supported env vars
      - DB_HOST=db
      - DB_PORT=3306
      - DB_NAME=bf2stats
      - DB_USER=root
      - DB_PASS=ascent
    volumes:
      - backups-volume:/src/ASP/system/backups # This volume is effectively unused since ASP doesn't allow DB backups for a remote DB, but mount it anyway to avoid errors.
      - cache-volume:/src/ASP/system/cache
      - config-volume:/src/ASP/system/config # For a stateful config file
      - logs-volume:/src/ASP/system/logs
      - snapshots-volume:/src/ASP/system/snapshots
    networks:
      traefik-network:
      bf2-network:
        aliases:
          - bf2web.gamespy.com # Important! Spoof Gamespy DNS for the BF2 server to reach our ASP server IP over this network

volumes:
  config-volume:
  ...
```

2. If you have `init-container`, now it only needs to set permission for the `db` volume:

```yaml
  init-container:
    image: alpine:latest
    volumes:
      - db-volume:/var/lib/mysql
    entrypoint:
      - /bin/sh
    command:
      - -c
      - |
          set -eu

          echo "Granting db write permissions"
          chown -R 999:999 /var/lib/mysql
```

Done. Enjoy the simpler setup 😀
