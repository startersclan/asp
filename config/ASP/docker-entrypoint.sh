#!/bin/sh
set -eu

setup() {
    local DIR="${1:?DIR is empty}"
    local ALL="${2:-}"
    mkdir -p "$DIR"
    chown www-data:www-data "$DIR"
    chmod 750 "$DIR"
    if [ -n "$ALL" ]; then
        chown -R www-data:www-data "$DIR"
        find "$DIR" -type d -exec chmod 750 {} \;
        find "$DIR" -type f -exec chmod 640 {} \;
    fi
}

echo "Setting permissions on backup volume"
setup /src/ASP/system/backups 1

echo "Setting permissions on cache volume"
setup /src/ASP/system/cache 1

echo "Setting up config file"
CONFIG_SAMPLE=/config.sample
CONFIG_FILE_SAMPLE="$CONFIG_SAMPLE/config.php"
CONFIG_FILE=/src/ASP/system/config/config.php
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Creating new config file: $CONFIG_FILE"
    cp -v "$CONFIG_FILE_SAMPLE" "$CONFIG_FILE"
fi
echo "Setting up other config files"
for f in $( ls "$CONFIG_SAMPLE" | grep -v '^config.php$' ); do
    cp -v "$CONFIG_SAMPLE/$f" "/src/ASP/system/config"
done

echo "Setting permissions on config volume"
setup /src/ASP/system/config 1

echo "Setting permissions on logs volume"
setup /src/ASP/system/logs 1

# There can be many snapshots, and recursively setting permissions may be very slow
echo "Setting permissions on snapshots volume"
setup /src/ASP/system/snapshots/
setup /src/ASP/system/snapshots/failed
setup /src/ASP/system/snapshots/processed
setup /src/ASP/system/snapshots/unauthorized
setup /src/ASP/system/snapshots/unprocessed

write_config() {
    # This function replaces config file values with env var values. E.g. DB_HOST=db sets $db_host = 'db' in config file
    local KEY="${1:-}"
    local TYPE="${2:-}"
    local CONFIG_FILE="${3:-}"
    if [ -z "$KEY" ]; then echo "KEY is empty"; exit 1; fi
    if [ -z "$TYPE" ]; then echo "TYPE is empty"; exit 1; fi
    if [ -z "$CONFIG_FILE" ]; then echo "CONFIG_FILE is empty"; exit 1; fi

    ENVVAR=$( echo "$KEY" | tr '[:lower:]' '[:upper:]' )    # E.g. 'db_host' to 'DB_HOST'
    VAL=$( printenv "$ENVVAR" || true )
    if [ -n "$VAL" ]; then
        echo "Writing key '$KEY' of type '$TYPE' to config file"
        if [ "$TYPE" == 'string' ]; then
            sed -i "s|^.$KEY =.*|\$$KEY = '$VAL';|" "$CONFIG_FILE"  # E.g. $db_host = '127.0.0.1';
        elif [ "$TYPE" == 'int' ]; then
            sed -i "s|^.$KEY =.*|\$$KEY = $VAL;|" "$CONFIG_FILE"  # E.g. $db_port = 3306;
        elif [ "$TYPE" == 'array' ]; then
            # Values are comma-separated. E.g. 127.0.0.1,192.168.2.0/24,localhost,192.168.1.102,192.168.1.110,0.0.0.0
            # We need to make it a PHP string array
            # set -x
            VAL=$( echo "$VAL" | rev | sed 's/^,*//' | rev )  # Trim any trailing commas
            VAL="array('$( echo "$VAL" | sed "s/,/','/g" )')"
            sed -i "s|^.$KEY.*|\$$KEY = $VAL;|" "$CONFIG_FILE"   # E.g. $admin_hosts = array('127.0.0.1','192.168.2.0/24','localhost','192.168.1.102','192.168.1.110','0.0.0.0');
        else
            echo "Unsupported TYPE: $TYPE"
            exit 1
        fi
    else
        echo "Not writing '$KEY' of type '$TYPE' to config file because env var '$ENVVAR' value is empty"
    fi
}

# Replace env var values in config file
CONFILE_FILE_CONTENT=$( cat "$CONFIG_FILE" )
echo "$CONFILE_FILE_CONTENT" | grep -E '^\$' | while read -r KEY EQUALS VALUE; do
    KEY=$( echo "$KEY" | sed 's/^\$//' )    # Strip the dollar sign. E.g. '$db_host' -> 'db_host'
    VALUE=$( echo "$VALUE" | rev | sed 's/^;//' | rev )    # Strip the trailing semicolon
    TYPE=
    if echo "$VALUE" | grep -E "^'" > /dev/null; then
        TYPE=string
    elif echo "$VALUE" | grep -E "^array\(" > /dev/null; then
        TYPE=array
    elif echo "$VALUE" | grep -E "^[0-9]+$" > /dev/null; then
        TYPE=int
    else
        echo "Unable to determine variable type of KEY '$KEY' in config file $CONFIG_FILE. Please check syntax."
        exit 1
    fi
    write_config "$KEY" "$TYPE" "$CONFIG_FILE"
done

echo "Checking syntax of config file: $CONFIG_FILE"
php "$CONFIG_FILE"

exec /usr/bin/supervisord -c /supervisor.conf --pidfile /run/supervisord.pid
