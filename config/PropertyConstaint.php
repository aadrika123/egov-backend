<?php

/**
 * | Created On-11-08-2022 
 * | Created By-Sandeep Bara Kumar
 * | For OBJECTION Master defining constants
 */

return [
    "PARAM_RENTAL_RATE" => 144,
    "EFFECTIVE_DATE_RULE2" => "2016-04-01",
    "EFFECTIVE_DATE_RULE3" => "2022-04-01",
    "VACANT_PROPERTY_TYPE" => "4",

    // Mutation Reassessment and New Assessment Workflows
    "SAF_WORKFLOWS" => [3, 4, 5],

    "OBJECTION" => [
        "2"     => "RainHarvesting",
        "3"     => "RoadWidth",
        "4"     => "PropertyType",
        "5"     => "AreaOfPlot",
        "6"     => "MobileTower",
        "7"     => "HoardingBoard",
        "9"     => "FloorDetail"
    ],
    "PROPERTY-TYPE" => [
        "1"     => "SUPER STRUCTURE",
        "2"     => "INDEPENDENT BUILDING",
        "3"     => "FLATS / UNIT IN MULTI STORIED BUILDING",
        "4"     => "VACANT LAND",
        "5"     => "OCCUPIED PROPERTY"
    ],
    "OWNERSHIP-TYPE" => [
        "1"     => "INDIVIDUAL",
        "2"     => "CO-OPERATIVE SOCIETY",
        "3"     => "RELIGIOUS TRUST",
        "4"     => "TRUST",
        "5"     => "STATE GOVT",
        "6"     => "CENTRAL GOVT",
        "7"     => "STATE PSU",
        "8"     => "CENTRAL PSU",
        "9"     => "BOARD",
        "10"     => "TCOMPANY PUBLIC LTD",
        "11"     => "INSTITUTE",
        "12"     => "OCCUPIER",
        "15"     => "COMPANY PRIVATE LTD",
        "13"     => "OTHER"

    ],
    "FLOOR-TYPE" => [
        "1"   =>  "PARKING",
        "2"   =>  "BASEMENT",
        "3"   =>  "Ground Floor",
        "4"   =>  "1st Floor",
        "5"   =>  "2nd Floor",
        "6"   =>  "3rd Floor",
        "7"   =>  "4th Floor",
        "8"   =>  "5th Floor",
        "9"   =>  "6th Floor",
        "10"   =>  "7th Floor",
        "11"   =>  "8th Floor",
        "12"   =>  "9th Floor",
        "13"  => "10th Floor",
        "14"  => "11th Floor",
        "15"  => "12th Floor",
        "16"  => "13th Floor",
        "17"  => "14th Floor",
        "18"  => "15th Floor",
        "19"  => "16th Floor",
        "20"  => "17th Floor",
        "21"  => "19th Floor",
        "22"  => "20th Floor",
        "23"  => "21th Floor",
        "24"  => "22th Floor",
        "25"  => "23th Floor",
        "26"  => "24th Floor",
        "27"  => "25th Floor",

    ],
    "OCCUPANCY-TYPE" => [
        "1" =>  "TENANTED",
        "2" =>  "SELF OCCUPIED",

    ],
    "USAGE-TYPE" => [
        "1" => [
            "CODE" => "A",
            "TYPE" => "RESIDENTIAL"
        ],
        "6" => [
            "CODE" => "G",
            "TYPE" => "COMMERCIAL ESTABLISHMENTS AND UNDERTAKING OF STATE AND CENTRAL GOVERNMENT"
        ],
        "8" => [
            "CODE" => "I",
            "TYPE" => "STATE AND CENTRAL GOVERNMENT OFFICES OTHER THAN COMMERCIAL ESTABLISHMENT AND UNDERTAKINGS"
        ],
        "10" => [
            "CODE" => "K",
            "TYPE" => "RELIGIOUS AND SPIRITUAL PLACES"
        ],
        "12" => [
            "CODE" => "B",
            "TYPE" => "HOTEL"
        ],
        "13" => [
            "CODE" => "B",
            "TYPE" => "BARS"
        ],
        "14" => [
            "CODE" => "B",
            "TYPE" => "CLUBS"
        ],

        "15" => [
            "CODE" => "B",
            "TYPE" => "HEALTH CLUB"
        ],
        "16" => [
            "CODE" => "B",
            "TYPE" => "MARRIAGE HALLS"
        ],
        "17" => [
            "CODE" => "C",
            "TYPE" => "SHOP WITH LESS THAN 250 SQ. FEET"
        ],
        "18" => [
            "CODE" => "D",
            "TYPE" => "SHOW ROOM"
        ],
        "19" => [
            "CODE" => "D",
            "TYPE" => "SHOPPING MALLS"
        ],
        "20" => [
            "TYPE" => "CINEMA HOUSES",
            "CODE" => "D"
        ],
        "21" => [
            "CODE" => "D",
            "TYPE" => "MULTIPLEXES",

        ],
        "22" => [
            "CODE" => "D",
            "TYPE" => "DISPENSARIES",

        ],
        "23" => [
            "CODE" => "D",
            "TYPE" => "LABORATORIES",

        ],
        "24" => [
            "CODE" => "D",
            "TYPE" => "RESTURANTS",

        ],
        "25" => [
            "CODE" => "D",
            "TYPE" => "GUEST HOUSES",

        ],
        "26" => [
            "CODE" => "E",
            "TYPE" => "COMMERCIAL OFFICES",

        ],
        "27" => [
            "CODE" => "E",
            "TYPE" => "FINANCIAL INSTITUTIONS",

        ],
        "28" => [
            "CODE" => "E",
            "TYPE" => "BANKS",

        ],
        "29" => [
            "CODE" => "E",
            "TYPE" => "INSURANCE OFFICES",

        ],
        "30" => [
            "CODE" => "E",
            "TYPE" => "PRIVATE HOSPITALS",

        ],
        "31" => [
            "CODE" => "E",
            "TYPE" => "NURSING HOMES",

        ],
        "32" => [
            "CODE" => "F",
            "TYPE" => "INDUSTRIES",

        ],
        "33" => [
            "CODE" => "F",
            "TYPE" => "WORKSHOPS",

        ],
        "34" => [
            "CODE" => "F",
            "TYPE" => "STORAGE",

        ],
        "35" => [
            "CODE" => "F",
            "TYPE" => "GODOWNS",

        ],
        "36" => [
            "CODE" => "F",
            "TYPE" => "WARE HOUSES",

        ],
        "37" => [
            "CODE" => "H",
            "TYPE" => "COACHING CLASSES",

        ],
        "38" => [
            "CODE" => "H",
            "TYPE" => "GUIDANCE & TRAINING CENTRES & THEIR HOSTELS",

        ],
        "39" => [
            "CODE" => "J",
            "TYPE" => "PRIVATE SCHOOLS",

        ],
        "40" => [
            "CODE" => "J",
            "TYPE" => "PRIVATE COLLEGES",

        ],
        "41" => [
            "CODE" => "J",
            "TYPE" => "PRIVATE RESEARCH INSTITUTION AND OTHER PRIVATE EDUCATIONAL INSTITUTIONS AND THEIT HOSTELS",

        ],
        "42" => [
            "CODE" => "L",
            "TYPE" => "EDUCATIONAL & SOCIAL INSTITUTIONS RUN BY TRUST",

        ],
        "43" => [
            "CODE" => "L",
            "TYPE" => "NGOS ON NO-PROFIT",

        ],
        "44" => [
            "CODE" => "L",
            "TYPE" => "NO-LOSS BASIS",

        ],
        "45" => [
            "CODE" => "M",
            "TYPE" => "OTHERS",

        ],
    ],
    "CONSTRUCTION-TYPE" => [
        "1" => "Pucca with RCC Roof (RCC)",
        "2" => "Pucca with Asbestos/Corrugated Sheet (ACC)",
        "3" => "Kuttcha with Clay Roof (Other)",
    ],

    // Property Assessment Type
    "ASSESSMENT-TYPE" =>
    [
        "1" => "New Assessment",
        "2" => "Re Assessment",
        "3" => "Mutation"
    ],

    "ULB-TYPE-ID" => [
        "Municipal Carporation" => 1,
        "Nagar Parishad" => 2,
        "Nagar Panchayat" => 3
    ],
    "MATRIX-FACTOR" => [
        "2" => [
            "1" => 1,
            "2" => 1,
            "3" => 0.5,
        ],
        "3" => [
            "1" => 0.8,
            "2" => 0.8,
            "3" => 0.4
        ],
    ],
    "CIRCALE-RATE-ROAD" => [
        "1" => "_main",
        "2" => "_main",
        "3" => "_other",
        // RuleSet 3 Circle Rate Road
        "2022-04-01" => [
            "1" => "_main",
            "2" => "_other",
            "3" => "_other",
        ]
    ],
    "CIRCALE-RATE-PROP" => [
        "0" => "_apt",
        "1" => "_pakka",
        "2" => "_pakka",
        "3" => "_kuccha",

        // Ruleset3 Circle Rate Construction Type
        "2022-04-01" => [
            "0" => "_apt",
            "1" => "_pakka",
            "2" => "_kuccha",
            "3" => "_kuccha",
        ]
    ],
    "CIRCALE-RATE-USAGE" => [
        "1" => "res",
        "2" => "com",
    ],

    // Label Role ID
    "SAF-LABEL" => [
        "BO" => "11",
        "DA" => "6",
        "TC" => "5",
        "UTC" => "7",
        "SI" => "9",
        "EO" => "10"
    ],

    "VACANT_LAND"   => "4",
    // Saf Pending Status
    "SAF_PENDING_STATUS" => [
        "NOT_APPROVED" => 0,
        "APPROVED" => 1,
        "BACK_TO_CITIZEN" => 2,
        "LABEL_PENDING" => 3
    ],

    // Relative GeoTagging Path of Geo Tagging
    "GEOTAGGING_RELATIVE_PATH" => "public/Property/GeoTagging",

    // Rebates
    "REBATES" => [
        "CITIZEN" => [
            "ID" => 1,
            "PERC" => 5
        ],
        "JSK" => [
            "ID" => 2,
            "PERC" => 2.5
        ],
        "SPECIALLY_ABLED" => [
            "ID" => 3,
            "PERC" => 5
        ],
        "SERIOR_CITIZEN" => [
            "ID" => 4,
            "PERC" => 5
        ]
    ],

    // Penalties
    "PENALTIES" => [
        "RWH_PENALTY_ID" => 1,
        "LATE_ASSESSMENT_ID" => 2
    ],

];
