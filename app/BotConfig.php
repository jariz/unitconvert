<?php
namespace JariZ;

class BotConfig
{
    public static $username = "unitconvert";
    public static $password = "";

    public static $obeyRules = true; //wait 2 secs in between requests
    public static $avoidSubs = array(
        //american sports
        "nfl", "redskins", "49ers", "cfb", "cfl", "fantasyfootball", "eurobowl", "arenafootball", "nflfandom", "madden", "chargers", "oaklandraiders", "kansascitychiefs", "denverbroncos", "tennesseetitans", "jaguars", "colts", "texans", "steelers", "browns", "bengals", "ravens", "nyjets", "patriots", "stlouisrams", "seahawks", "azcardinals", "saints", "panthers", "falcons", "buccaneers", "minnesotavikings", "greenbaypackers", "detriotlions", "chibears", "eagles", "nygiants", "cowboys",
        //american cities/states/whatever
        "columbus", "ohio", "akron", "cleveland", "osu", "cscc",
        //other (shit we're banned from)
        "explainlikeimfive", "fitness", "askreddit", "pathfinder_rpg"
    );
    public static $dryRun = true; //run, but don't actually comment

    //most important arrays in the app.
    //Class => array with all aliases and their opposites
    public static $units_metric = array(
        "Length" => array(
            "km,kilometer,kilometers,km" => "mile",
            "decimeter,decimeters,dm" => "foot",
            "centimeter,centimeters,cm" => "inch",
            "millimeter,millimeters,mm" => "inch",
            "meter,meters,m" => "foot"
        ),
        "Mass" => array(
            "kg,kilogram,kgs,kg's" => "pound",
            "kg,kilogram,kgs,kg's" => "pound",
        )
    );

    public static $units_imperial = array(
        "Length" => array(
            "mile,miles" => "kilometer",
            "yard,yards,yd,yds" => "meter",
            "feet,foot,ft,fts" => "meter",
            "inch,inches" => "centimeter",
        ),
        "Mass" => array(
            "t,ton,tonnes,tons,tonne" => "kilogram",
            "lbs,lb,pound,pounds" => "kilogram",
            "oz,ounce,ounces" => "gram"
        )
    );

    public static $plurals = array(
        "mile" => "miles",
        "foot" => "feet",
        "inch" => "inches",

        "kilometer" => "kilometers",
        "meter" => "meters",
        "centimeter" => "centimeters",
        "kilogram" => "kilograms",
        "gram" => "grams"
    );


    public static $templates = array(
        "comment" => "I've converted the units in this comment for you:
{\$conversions_comment}


_____

_^comment ^will ^be ^deleted ^if ^the ^ranking ^goes ^below ^0 ^OR ^if ^{\$OP} ^responses ^with ^'remove'_

[`More information`](/r/unitconvert)]",
        "conversion" => "
- {\$original} is __{\$conversion}__

",
        "removed" => "Hi {\$OP}, I removed [my post]({\$post}) on your request.

- Unitconvert",
        "removed_subject" => "My comment has been removed."
    );
} 