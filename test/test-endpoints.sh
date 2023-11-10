#!/bin/sh
set -eu

echo
echo "[test-endpoints]"
ENDPOINTS="
asp.example.com/ 200
asp.example.com/.htaccess 401
asp.example.com/ASP/ 200
asp.example.com/ASP/aspx 401
asp.example.com/ASP/bf2statistics.php 403
asp.example.com/ASP/frontend 401
asp.example.com/ASP/frontend/css/bootstrap.min.css 200
asp.example.com/ASP/frontend/images/maps/foo.png 200
asp.example.com/ASP/frontend/images/ranks/foo.png 200
asp.example.com/ASP/frontend/images/armies/foo.png 200
asp.example.com/ASP/frontend/css/bootstrap.min.css 200
asp.example.com/ASP/createplayer.aspx 200
asp.example.com/ASP/getawardsinfo.aspx 200
asp.example.com/ASP/getbackendinfo.aspx 200
asp.example.com/ASP/getleaderboard.aspx 200
asp.example.com/ASP/getmapinfo.aspx 200
asp.example.com/ASP/getplayerid.aspx 200
asp.example.com/ASP/getplayerinfo.aspx 200
asp.example.com/ASP/getrankinfo.aspx 200
asp.example.com/ASP/getunlocksinfo.aspx 200
asp.example.com/ASP/ranknotification.aspx 200
asp.example.com/ASP/searchforplayers.aspx 200
asp.example.com/ASP/selectunlock.aspx 200
asp.example.com/ASP/verifyplayer.aspx 200
asp.example.com/ASP/index.php 200
asp.example.com/ASP/ranknotification.aspx 200
asp.example.com/ASP/searchforplayers.aspx 200
asp.example.com/ASP/selectunlock.aspx 200
asp.example.com/ASP/getplayerinfo.aspx 200
asp.example.com/ASP/system 401
"
command -v curl || apk add --no-cache curl
echo "$ENDPOINTS" | awk NF | while read -r i j; do
    d=$( echo "$i" | cut -d '/' -f1 )
    if curl --head -skL http://$i --resolve $d:80:127.0.0.1 --resolve $d:443:127.0.0.1 2>&1 | grep -E "^HTTP/(1.1|2) $j " > /dev/null; then
        echo "PASS: $i"
    else
        echo "FAIL: $i"
        exit 1
    fi
done
