#!/bin/sh
set -eu

echo
echo "[test-routes]"
URLS="
http://asp/ 200
http://asp/.htaccess 401
http://asp/ASP/ 200
http://asp/ASP/aspx 401
http://asp/ASP/bf2statistics.php 403
http://asp/ASP/frontend 401
http://asp/ASP/frontend/css/bootstrap.min.css 200
http://asp/ASP/frontend/images/maps/foo.png 200
http://asp/ASP/frontend/images/ranks/foo.png 200
http://asp/ASP/frontend/images/armies/foo.png 200
http://asp/ASP/frontend/css/bootstrap.min.css 200
http://asp/ASP/createplayer.aspx 200
http://asp/ASP/getawardsinfo.aspx 200
http://asp/ASP/getbackendinfo.aspx 200
http://asp/ASP/getleaderboard.aspx 200
http://asp/ASP/getmapinfo.aspx 200
http://asp/ASP/getplayerid.aspx 200
http://asp/ASP/getplayerinfo.aspx 200
http://asp/ASP/getrankinfo.aspx 200
http://asp/ASP/getunlocksinfo.aspx 200
http://asp/ASP/ranknotification.aspx 200
http://asp/ASP/searchforplayers.aspx 200
http://asp/ASP/selectunlock.aspx 200
http://asp/ASP/verifyplayer.aspx 200
http://asp/ASP/index.php 200
http://asp/ASP/ranknotification.aspx 200
http://asp/ASP/searchforplayers.aspx 200
http://asp/ASP/selectunlock.aspx 200
http://asp/ASP/getplayerinfo.aspx 200
http://asp/ASP/system 401

http://phpmyadmin/ 200
"
echo "$URLS" | awk NF | while read -r i j; do
    if wget -q -SO- "$i" 2>&1 | grep "HTTP/1.1 $j " > /dev/null; then
        echo "PASS: $i"
    else
        echo "FAIL: $i"
        exit 1
    fi
done
