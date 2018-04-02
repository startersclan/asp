<?php
return [
    30000 => [
        "comment" => "Default Database Schema",
        "up" => "30010",
        "down" => null
    ],
    30010 => [
        "comment" => "Removed a few un-needed columns from the map table.",
        "up" => null,
        "down" => "30000"
    ]
];