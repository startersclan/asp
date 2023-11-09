#!/bin/sh
set -eu
while true; do
    echo "Tailing logs in /src/ASP/system/logs/*.log"
    tail -n0 -f /src/ASP/system/logs/*.log || sleep 10;
done
