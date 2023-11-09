#!/bin/sh
set -eu
echo "Tailing logs in /src/ASP/system/logs/*.log"
tail -n0 -F \
    /src/ASP/system/logs/asp_debug.log \
    /src/ASP/system/logs/php_errors.log \
    /src/ASP/system/logs/stats_debug.log \
    2>/dev/null
