#!/bin/sh
set -eu

SCRIPT_DIR=$( cd "$( dirname "$0" )" && pwd )
cd "$SCRIPT_DIR"

echo
echo "[test-snapshots]"
apk add --no-cache curl
for i in ./snapshots/*; do
    RES=$( set -x; curl -s -A GameSpyHTTP/1.0 -H 'Content-Type: application/json' --data "@$i" http://asp/ASP/bf2statistics.php )
    echo "$RES"; echo
    if [ "$( echo "$RES" | head -c1 )" = 'O' ]; then
        echo "PASS: $i"
    else
        echo "FAIL: $i"
        exit 1
    fi
done
