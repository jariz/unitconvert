<?php
/**
 * JARIZ.PRO
 * Date: 22/01/14
 * Time: 17:05
 * Author: JariZ
 */

namespace JariZ;


class DictionaryConfig
{

    /**
     * NOTE THAT (part of) BotConfig ALSO APPLIES
     */

    /**
     * The classes we use for conversion, and their extra 'properties'
     * @var array
     */
    public static $conversionClasses = array(
        "Area" => array(
            "base" => "m^2", //SI base unit
            "dictionary_unit" => "m^2" //how the unit is called in the dictionary (optional)
        ),
        "Length" => array(
            "base" => "m"
        ),
        "Mass" => array(
            "base" => "kg",
        ),
        "ElectricCurrent" => array(
            "base" => "A"
        ),
        "Velocity" => array(
            "base" => "m/s"
        ),
        "Acceleration" => array(
            "base" => "m/s^2"
        ),
        "Temperature" => array(
            "base" => "K"
        ),
        "Volume" => array(
            "base" => "l"
        )
    );

    /**
     * Number of dictionary entries we show per matched unit.
     * @var int
     */
    public static $sentencesPerUnit = 1;

    /**
     * Allow the bot to look for the units mentioned above, convert them to their base units, and look up their directory entries.
     * @var bool
     */
    public static $allowConversions = true;
    /**
     * Allow the bot to search for dollar amounts
     * @var bool
     */
    public static $allowMoney = true;
    /**
     * Allow the bot to search for people
     * @var bool
     */
    public static $allowPeople = true;

    /**
     * How exact we are when it comes to the range of dictionary items
     * @param $x float Value
     * @param $unit string Unit name in dict
     * @return float
     */
    public static function tolerance ($x, $unit) {
        switch($unit) {
            case 'K':
                return 0.99;
            default:
                return 0.9;
        }
    }

    /**
     * Message templates
     * @var array
     */
    public static $templates = array(
        "comment" => "{\$sentences}

_____

I convert numbers ito a more human-readable format | [data](http://dictionaryofnumbers.com) | [about](/r/unitconvert/wiki/index) | [botmaster](/u/MoederPoeder)

_^comment ^will ^be ^deleted ^if ^the ^ranking ^goes ^below ^0 ^OR ^if ^{\$OP} ^responses ^with ^'remove'_"

    );
}