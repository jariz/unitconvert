<?php
namespace JariZ;

class BotConfig
{
    public static $username = "unitconvert";
    public static $password = "";

    public static $obeyRules = true;
    public static $avoidSubs = array("nfl", "redskins", "columbus");

    //most important arrays in the app.
    //Class => array with all aliases and their opposites

    public static $units_metric = array(
        "Length" => array(
            "km,kilometer,kilometers,km" => "miles",
            "decimeter,decimeters,dm" => "feet",
            "centimeter,centimeters,cm" => "inch",
            "millimeter,millimeters,mm" => "inch",
            "meter,meters,m" => "feet"
        )
    );

    public static $units_imperial = array(
        "Length" => array(
            "mile,miles" => "kilometer",
            "yard,yards,yd" => "meter",
            "feet,foot,ft" => "meter",
            "inch,inches" => "centimeter",
        )
    );


    public static $templates = array(
        "comment" => "I've converted the units in this comment for you:
{\$conversions_comment}
####[`Still in a experimental phase, PM author if you have complains`][[`I am opensource`](http://github.com/jariz/unitconvert)] [[`Author`](/u/MoederPoeder)] [[`More information`](/r/unitconvert)]",
        "conversion" => "
- __{\$original}__ is __{\$conversion}__

"
    );
} 