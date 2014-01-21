<?php
namespace JariZ;

class BotConfig
{
    public static $username = "unitconvert";
    public static $password = "";

    public static $obeyRules = true;

    //most important arrays in the app.
    //Class => array with all aliases and their opposites

    public static $units_metric = array(
        "Length" => array(
            "km,kilometer,kilometers,km" => "mile",
            "decimeter,decimeters,dm" => "foot",
            "centimeter,centimeters,cm" => "inch",
            "millimeter,millimeters,mm" => "inch",
            "meter,meters,m" => "foot"
        )
    );

    public static $units_imperial = array(
        "Length" => array(
            "mile,miles" => "kilometer",
            "yard,yards,yd" => "meter",
            "feet,foot,ft" => "meter",
            "inch,inches,in" => "centimeter",
        )
    );


    public static $templates = array(
        "comment" => "I've converted the units in this comment for you:
{\$conversions_comment}
####[[`I am opensource`](http://github.com/jariz/unitconvert)] [[`Author`](/u/MoederPoeder)]",
        "conversion" => "
- __{\$original}__ is __{\$conversion}__

"
    );
} 