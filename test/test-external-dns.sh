#!/bin/sh
set -eu

echo
echo "[test-external-dns]"
DNS="
192.168.1.100 battlefield2.available.gamespy.com
192.168.1.100 battlefield2.master.gamespy.com
192.168.1.100 battlefield2.ms14.gamespy.com
192.168.1.100 master.gamespy.com
192.168.1.100 motd.gamespy.com
192.168.1.100 gpsp.gamespy.com
192.168.1.100 gpcm.gamespy.com
192.168.1.100 gamespy.com
192.168.1.100 bf2web.gamespy.com
192.168.1.100 gamestats.gamespy.com
192.168.1.100 eapusher.dice.se
"
echo "$DNS" | awk NF | while read -r ip h; do
    if nslookup $h coredns | grep -E "^Address: $ip" > /dev/null; then
        echo "PASS: $h"
    else
        echo "FAIL: $h"
        exit 1
    fi
done
