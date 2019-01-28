<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2019, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

/**
 * Provides a mapping of army abbreviations from a parsed server responses to
 * it's full name representation, and flag ID. Modded content will need to add
 * abbreviations to display correctly.
 *
 * This mapping is used by the Server module and view method (/ASP/servers/view/[id])
 */
return array(
    'us' => [
        'flag' => 0,
        'name' => 'United States Marine Corps'
    ],
    'usa' => [
        'flag' => 0,
        'name' => 'United States Marine Corps'
    ],
    'usmc' => [
        'flag' => 0,
        'name' => 'United States Marine Corps'
    ],
    'seal' => [
        'flag' => 0,
        'name' => 'United States Navy Seals'
    ],
    'mec' => [
        'flag' => 1,
        'name' => 'Middle Eastern Coalition'
    ],
    'mecsf' => [
        'flag' => 1,
        'name' => 'Middle Eastern Coalition Special Forces'
    ],
    'ch' => [
        'flag' => 2,
        'name' => 'People\'s Liberation Army'
    ],
    'sas' => [
        'flag' => 4,
        'name' => 'British Special Air Service'
    ],
    'spetz' => [
        'flag' => 5,
        'name' => 'Russian Spetsnaz'
    ],
    'chinsurgent' => [
        'flag' => 7,
        'name' => 'Rebels'
    ],
    'rebels' => [
        'flag' => 7,
        'name' => 'Rebels'
    ],
    'meinsurgent' => [
        'flag' => 8,
        'name' => 'Insurgents'
    ],
    'insurgents' => [
        'flag' => 8,
        'name' => 'Insurgents'
    ],
    'eu' => [
        'flag' => 9,
        'name' => 'European Union'
    ],
    'ger' => [
        'flag' => 10,
        'name' => 'German Forces'
    ],
    'ukr' => [
        'flag' => 12,
        'name' => 'Ukrainian Forces'
    ],
    'un' => [
        'flag' => 13,
        'name' => 'United Nations'
    ],
);