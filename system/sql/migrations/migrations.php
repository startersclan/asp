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
        "comment" => "Removed a few un-needed columns from the map table.",
        "up" => null,
        "up_string" => "",
        "down" => "30000",
        "down_string" => "3.0.0",
    ]
];