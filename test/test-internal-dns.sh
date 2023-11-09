#!/bin/sh
set -eu

echo
echo "[test-internal-dns]"
DNS="
battlefield2.available.gamespy.com
battlefield2.master.gamespy.com
battlefield2.ms14.gamespy.com
master.gamespy.com
motd.gamespy.com
gpsp.gamespy.com
gpcm.gamespy.com
gamespy.com
bf2web.gamespy.com
"
echo "$DNS" | awk NF | grep -v '127.0.0' | while read -r h; do
    if nslookup $h | grep -v '127.0.0' | grep -E '^Address: [0-9\.]+$'; then
        echo "PASS: $h"
    else
        echo "FAIL: $h"
        exit 1
    fi
done
