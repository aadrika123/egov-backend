<?php

/**
 * | Created On-11-08-2022 
 * | Created By-Sandeep Bara Kumar
 * | For OBJECTION Master defining constants
 */

return [
    "OBJECTION"=>[
        "RanHarwesting" => "2",
        "RoadWidth"     => "3",
        "PropertyType"  => "4",
        "AreaOfPlot"    => "5",
        "MobileTower"   => "6",
        "HoardingBoard" => "7",
        "FloorDetail"   => "9"
    ],
    "PROPERTY-TYPE"=>[
        "SUPER STRUCTURE"                       => "1",
        "INDEPENDENT BUILDING"                  => "2",
        "FLATS / UNIT IN MULTI STORIED BUILDING"=> "3",
        "VACANT LAND"                           => "4",
        "OCCUPIED PROPERTY"                     => "5"
    ],
    "OWNERSHIP-TYPE"=>[
        "INDIVIDUAL"              => "1",
        "CO-OPERATIVE SOCIETY"    => "2",
        "RELIGIOUS TRUST"         => "3",
        "TRUST"                   => "4",
        "STATE GOVT"              => "5",
        "CENTRAL GOVT"            => "6",
        "STATE PSU"               => "7",
        "CENTRAL PSU"             => "8",
        "BOARD"                   => "9",
        "TCOMPANY PUBLIC LTD"     => "10",
        "INSTITUTE"               => "11",
        "OCCUPIER"                => "12",
        "COMPANY PRIVATE LTD"     => "15",
        "OTHER"                   => "13"

    ],
    "FLOOR-TYPE"=>[
        "PARKING"       =>  "1",
        "BASEMENT"      =>  "2",
        "Ground Floor"  =>  "3",
        "1st Floor"     =>  "4",
        "2nd Floor"     =>  "5",
        "3rd Floor"     =>  "6",
        "4th Floor"     =>  "7",
        "5th Floor"     =>  "8",
        "6th Floor"     =>  "9",
        "7th Floor"     =>  "10",
        "8th Floor"     =>  "11",
        "9th Floor"     =>  "12",
        "10th Floor"     =>  "13",
        "11th Floor"     =>  "14",
        "12th Floor"     =>  "15",
        "13th Floor"     =>  "16",
        "14th Floor"     =>  "17",
        "15th Floor"     =>  "18",
        "16th Floor"     =>  "19",
        "17th Floor"     =>  "20",
        "19th Floor"     =>  "21",
        "20th Floor"     =>  "22",
        "21th Floor"     =>  "23",
        "22th Floor"     =>  "24",
        "23th Floor"     =>  "25",
        "24th Floor"     =>  "26",
        "25th Floor"     =>  "27",

    ],
    "OCCUPENCY-TYPE"=>[
        "TENANTED"       =>"1",
        "SELF OCCUPIED"  =>"2",

    ],
    "USAGE-TYPE"=>[
        "1"=>["CODE"=>"A",
        "TYPE"=>"RESIDENTIAL"
        ],
        "6"=>["CODE"=>"G",
            "TYPE"=>"COMMERCIAL ESTABLISHMENTS AND UNDERTAKING OF STATE AND CENTRAL GOVERNMENT"
        ],
        "8"=>["CODE"=>"I",
            "TYPE"=>"STATE AND CENTRAL GOVERNMENT OFFICES OTHER THAN COMMERCIAL ESTABLISHMENT AND UNDERTAKINGS"
        ],
        "10"=>["CODE"=>"K",
            "TYPE"=>"RELIGIOUS AND SPIRITUAL PLACES"
        ],
        "12"=>["CODE"=>"B",
            "TYPE"=>"HOTEL"
        ],
        "13"=>["CODE"=>"B",
            "TYPE"=>"BARS"
        ],
        "14"=>["CODE"=>"B",
            "TYPE"=>"CLUBS"
        ],

        "15"=>["CODE"=>"B",
            "TYPE"=>"HEALTH CLUB"
        ],
        "16"=>["CODE"=>"B",
            "TYPE"=>"MARRIAGE HALLS"
        ],
        "17"=>["CODE"=>"C",
            "TYPE"=>"SHOP WITH LESS THAN 250 SQ. FEET"
        ],
        "18"=>["CODE"=>"D",
            "TYPE"=>"SHOW ROOM"
        ],
        "19"=>["CODE"=>"D",
            "TYPE"=>"SHOPPING MALLS"
        ],
        "20"=>["TYPE"=>"CINEMA HOUSES",
        "CODE"=>"D"
        ],
        "21"=>["CODE"=>"D",
        "TYPE"=>"MULTIPLEXES",

        ],
        "22"=>["CODE"=>"D",
        "TYPE"=>"DISPENSARIES",

        ],
        "23"=>["CODE"=>"D",
        "TYPE"=>"LABORATORIES",

        ],
        "24"=>["CODE"=>"D",
        "TYPE"=>"RESTURANTS",

        ],
        "25"=>["CODE"=>"D",
        "TYPE"=>"GUEST HOUSES",

        ],
        "26"=>["CODE"=>"E",
        "TYPE"=>"COMMERCIAL OFFICES",

        ],
        "27"=>["CODE"=>"E",
        "TYPE"=>"FINANCIAL INSTITUTIONS",

        ],
        "28"=>["CODE"=>"E",
        "TYPE"=>"BANKS",

        ],
        "29"=>["CODE"=>"E",
        "TYPE"=>"INSURANCE OFFICES",

        ],
        "30"=>["CODE"=>"E",
        "TYPE"=>"PRIVATE HOSPITALS",

        ],
        "31"=>["CODE"=>"E",
        "TYPE"=>"NURSING HOMES",

        ],
        "32"=>["CODE"=>"F",
        "TYPE"=>"INDUSTRIES",

        ],
        "33"=>["CODE"=>"F",
        "TYPE"=>"WORKSHOPS",

        ],
        "34"=>["CODE"=>"F",
        "TYPE"=>"STORAGE",

        ],
        "35"=>["CODE"=>"F",
        "TYPE"=>"GODOWNS",

        ],
        "36"=>["CODE"=>"F",
        "TYPE"=>"WARE HOUSES",

        ],
        "37"=>["CODE"=>"H",
        "TYPE"=>"COACHING CLASSES",

        ],
        "38"=>["CODE"=>"H",
        "TYPE"=>"GUIDANCE & TRAINING CENTRES & THEIR HOSTELS",

        ],
        "39"=>["CODE"=>"J",
        "TYPE"=>"PRIVATE SCHOOLS",

        ],
        "40"=>["CODE"=>"J",
        "TYPE"=>"PRIVATE COLLEGES",

        ],
        "41"=>["CODE"=>"J",
        "TYPE"=>"PRIVATE RESEARCH INSTITUTION AND OTHER PRIVATE EDUCATIONAL INSTITUTIONS AND THEIT HOSTELS",

        ],
        "42"=>["CODE"=>"L",
        "TYPE"=>"EDUCATIONAL & SOCIAL INSTITUTIONS RUN BY TRUST",

        ],
        "43"=>["CODE"=>"L",
        "TYPE"=>"NGOS ON NO-PROFIT",

        ],
        "44"=>["CODE"=>"L",
        "TYPE"=>"NO-LOSS BASIS",

        ],
        "45"=>["CODE"=>"M",
        "TYPE"=>"OTHERS",

        ],
    ],
    "CONSTRUCTION-TYPE"=>[
        "1"=>"Pucca with RCC Roof (RCC)",
        "2"=>"Pucca with Asbestos/Corrugated Sheet (ACC)",
        "3"=>"Kuttcha with Clay Roof (Other)",
    ]
];