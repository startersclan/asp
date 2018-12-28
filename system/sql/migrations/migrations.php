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
        "up" => null,
        "up_string" => "",
        "down" => "30000",
        "down_string" => "3.0.0",
    ]
];