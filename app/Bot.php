<?php
namespace JariZ;

use Illuminate\Console\Command;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use \RedditApiClient\Comment;
use \RedditApiClient\Reddit;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Bot extends Command
{

    private $reddit;

    /*
     * IDs we already scanned
     */
    private $ids = array();

    public function __construct()
    {
        $this->name = "run";
        parent::__construct();
    }

    public function fire()
    {
        $this->info("Unitconvert booting...");
        $this->reddit = new Reddit(BotConfig::$username, BotConfig::$password);
        $this->initMatches();

        $this->Monitor();
//        $inputstring = "40km 43,34 km 435.65 kilometer 434.65cm 34465656675.3432454505000000mm holla holla get 44inch 54yard muney in my 50 feet basement you dirty ass mofo 44 feet!! 20 miles!";
//        $conversions = array();
//
//        //metric conversions
//        $results = $this->pattern($inputstring, $this->metric_matches);
//        foreach ($results as $result) {
//            $res = $this->unitconvert($result, Bot::UNITSYSTEM_METRIC);;
//            $keys = array_keys($res);
//            $conversions[$keys[0]] = $res[$keys[0]];
//        }
//
//        //imperial conversions
//        $results = $this->pattern($inputstring, $this->imperial_matches);
//        foreach ($results as $result) {
//            $res = $this->unitconvert($result, Bot::UNITSYSTEM_IMPERIAL);;
//            $keys = array_keys($res);
//            $conversions[$keys[0]] = $res[$keys[0]];
//        }
//
//        foreach ($conversions as $original => $conversion) {
//            $this->info("Yoyo {$original} = {$conversion}");
//        }
    }

    private $metric_matches;
    private $imperial_matches;

    function initMatches()
    {

        //load metric
        foreach (BotConfig::$units_metric as $class => $opposites_array) {
            foreach ($opposites_array as $key => $val) {
                foreach (explode(",", $key) as $match)
                    $this->metric_matches .= $match . "|";
            }
        }
        $this->metric_matches = substr($this->metric_matches, 0, strlen($this->metric_matches) - 1);
        $this->info("Loaded " . count(explode("|", $this->metric_matches)) . " metric matches");

        //load imperial
        foreach (BotConfig::$units_imperial as $class => $opposites_array) {
            foreach ($opposites_array as $key => $val) {
                foreach (explode(",", $key) as $match)
                    $this->imperial_matches .= $match . "|";
            }
        }
        $this->imperial_matches = substr($this->imperial_matches, 0, strlen($this->imperial_matches) - 1);
        $this->info("Loaded " . count(explode("|", $this->imperial_matches)) . " imperial matches");
    }


    public function Monitor()
    {
        $last = "";
        while (true) {
            $start = microtime(true);
            //this assumes there aren't more than 50 comments every 2 seconds, which seems reasonable imo
            $result = $this->reddit->getComments("r/all/comments", 50);
            foreach ($result as $comment) {
                $this->scan($comment);
            }

            //end of loop
            if (BotConfig::$obeyRules) {
                $spend = 2000 - (microtime(true) - $start);
                if ($spend > 0) {
                    $spend = ($spend * 1000);
                    $this->comment("Obeying tha rulez, Sleeping {$spend} msecs...");
                    usleep($spend);
                }
            }
        }
    }

    function scan(Comment $comment)
    {
        if (isset($this->ids[$comment->getThingId()]))
            return;

        $this->ids[$comment->getThingId()] = "";

        $conversions = array();
        //metric conversions
        $results = $this->pattern($comment->getBody(), $this->metric_matches);
        if (count($results) > 0)
            foreach ($results as $result) {
                $res = $this->unitconvert($result, Bot::UNITSYSTEM_METRIC);
                if($res != false) {
                    $keys = array_keys($res);
                    $conversions[$keys[0]] = $res[$keys[0]];
                }
            }

        //imperial conversions
        $results = $this->pattern($comment->getBody(), $this->imperial_matches);
        if (count($results) > 0)
            foreach ($results as $result) {
                $res = $this->unitconvert($result, Bot::UNITSYSTEM_IMPERIAL);;
                if($res != false) {
                    $keys = array_keys($res);
                    $conversions[$keys[0]] = $res[$keys[0]];
                }
            }

        if(count($results) > 0) {
            $conversions_comment = "";
            foreach($conversions as $original => $conversion)
                eval("\$conversions_comment .= \"".BotConfig::$templates["conversion"]."\";");
            $reply = "";
            eval("\$reply = \"".BotConfig::$templates["comment"]."\";");
            $this->info($reply);
        }
    }

    private $regex = "/(\\d*([,.]\\d+)?)(?![0-9\\.])( )?(MATCHES)/";

    function pattern($body, $matches)
    {
//        $this->info("DEBUG: Input string: " . $body);
        $regex = str_replace("MATCHES", $matches, $this->regex);
        $matches = array();
        preg_match_all($regex, $body, $matches, PREG_SET_ORDER);
        $ret = array();
        foreach ($matches as $match) {
            if(!empty($match[1]))
                $ret[] = array(doubleval(str_replace(",", ".", $match[1])), $match[4]);
        }
        return $ret;
    }

    function unitconvert($pattern, $unitsystem)
    {
        $unit = $pattern[1];
        $value = $pattern[0];

        switch ($unitsystem) {
            case Bot::UNITSYSTEM_IMPERIAL:
                $search = BotConfig::$units_imperial;
                break;
            case Bot::UNITSYSTEM_METRIC:
                $search = BotConfig::$units_metric;
                break;
            default:
                throw new \InvalidArgumentException("Invalid unit system");
        }

        $opposite = null;
        $class = "";
        foreach ($search as $clazz => $opposites_array) {
            foreach ($opposites_array as $key => $val) {
                foreach (explode(",", $key) as $match)
                    if ($unit == $match) {
                        $opposite = $val;
                        $class = $clazz;
                        break;
                    }
            }
        }
        if ($opposite == null) {
            $this->comment("WARN: Couldn't find opposite, even though the regex did match something a key (shouldn't be possible)");
            return false;
        }

        $this->info("DEBUG: Found the opposite of unit '{$unit}'! It's '{$opposite}' in class {$class}");

        //finally, time for the actual unit conversion
        $class = "\\PhpUnitsOfMeasure\\PhysicalQuantity\\" . $class;
        $class = new $class($value, $unit);
        /* @var $class \PhpUnitsOfMeasure\PhysicalQuantity */
        $opposite_value = $class->toUnit($opposite);
        $opposite_value = round($opposite_value);
        return array(
            "{$value} {$unit}" => "{$opposite_value} {$opposite}"
        );
    }

    const UNITSYSTEM_METRIC = 0;
    const UNITSYSTEM_IMPERIAL = 1;
} 