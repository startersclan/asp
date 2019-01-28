<?php
return [
    "3.0.0" => [
        "comment" => "Default Database Schema",
        "up" => "30010",
        "up_string" => "3.0.1",
        "down" => null,
        "down_string" => "",
    ],
    "3.0.1" => [
        "comment" => "Added the failed snapshots table.",
        "up" => "30020",
        "up_string" => "3.0.2",
        "down" => "30000",
        "down_string" => "3.0.0",
    ],
    "3.0.2" => [
        "comment" => "Added additional columns to the player_map table.",
        "up" => "30030",
        "up_string" => "3.0.3",
        "down" => "30010",
        "down_string" => "3.0.1",
    ],
    "3.0.3" => [
        "comment" => "Added additional views for the Statistics module.",
        "up" => "30040",
        "up_string" => "3.0.4",
        "down" => "30020",
        "down_string" => "3.0.2",
    ],
    "3.0.4" => [
        "comment" => "Added lastseen column to server, and lastupdate to stats_provider tables.",
        "up" => null,
        "up_string" => "",
        "down" => "30030",
        "down_string" => "3.0.3",
    ]
];