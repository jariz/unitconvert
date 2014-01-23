<?php

namespace JariZ;

use Illuminate\Console\Command;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity;
use PhpUnitsOfMeasure\UnitOfMeasure;
use \RedditApiClient\Comment;
use \RedditApiClient\Reddit;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Dictionary
 * @package JariZ
 */
class Dictionary extends Command
{

    private $reddit;

    /*
     * IDs we already scanned
     */
    private $ids = array();

    public function __construct()
    {
        $this->name = "dictionary";
        parent::__construct();
    }

    public function fire()
    {
        $this->info("Unitconvert dictionary mode booting...");
        $this->reddit = new Reddit(BotConfig::$username, BotConfig::$password);
//        $this->initMatches();
        $this->loadDictionary();

        $inputstring = "15grams";
//        $this->scan($inputstring);
        $this->Monitor();
    }

    //todo sql db or smth?
    function searchDictionary($unit, $value)
    {
        $tolerance = DictionaryConfig::tolerance($unit, $value);
        $min = $value > 0 ? $value * $tolerance : $value / $tolerance;
        $max = $value > 0 ? $value / $tolerance : $value * $tolerance;
        $results = array();

        foreach ($this->dictionary as $entry) {
            if ($entry->si_unit == $unit) {

                if ($entry->si_numeral <= $max && $entry->si_numeral >= $min)
                    $results[] = $entry;

//                if($entry->si_numeral == $value) {
//                    return $entry;
//                } else if((!is_null($entry->si_maximum) && !is_null($entry->si_minimum)) && ($value > $entry->si_numeral && $value < $entry->si_maximum)) {
//                    return $entry;
//                }
            }
        }

        return $results;
    }

    private $dictionary;

    function loadDictionary()
    {
        $this->dictionary = json_decode(file_get_contents(__DIR__ . "/../res/DictionaryOfNumbers.db.json"));
        $this->info("Loaded " . count($this->dictionary) . " dictionary entries");

        $types = array();
        foreach ($this->dictionary as $dict)
            if (isset($dict->si_unit)) $types[$dict->si_unit] = $dict->si_unit;
        $this->info("Following quantities available in dictionary: " . implode(", ", $types));
    }


    public function Monitor()
    {
        while (true) {
            $start = microtime(true);
            //this assumes there aren't more than 50 comments every 2 seconds, which seems reasonable imo
            $result = $this->reddit->getComments("r/all/comments", 100);
//            $result = $this->reddit->getComments("r/JariZ/comments", 100);
            foreach ($result as $comment)
                $this->scan($comment);

            //end of loop
            if (BotConfig::$obeyRules) {
                $spend = 2000 - (microtime(true) - $start);
                if ($spend > 0) {
                    $spend = ($spend * 1000);
                    $this->comment("Sleeping {$spend} msecs, processed {$this->processed} in total.");
                    usleep($spend);
                }
            }
        }
    }

    private $processed = 0;

    function scan(Comment $comment)
    {
        if (isset($this->ids[$comment->getThingId()])) return;

        $this->ids[$comment->getThingId()] = "";

        //ignores
        if (in_array(strtolower($comment->offsetGet("subreddit")), BotConfig::$avoidSubs)) return;
        if(in_array(strtolower($comment->getAuthorName()), BotConfig::$avoidUsers)) return;
        if (strtolower($comment->getAuthorName()) == strtolower(BotConfig::$username)) return;

        $output = array();
        foreach (DictionaryConfig::$conversionClasses as $class => $properties) {
            $class = "\\PhpUnitsOfMeasure\\PhysicalQuantity\\" . $class;
            $quantity = new $class(null, null);
            /* @var $quantity PhysicalQuantity */
            $units = $quantity->getUnits();
//            $this->info("Following units in class {$class}: array(\"" . implode("\", \"", $units) . "\")");


            //conversion approach
            if (DictionaryConfig::$allowConversions) {
                $search = $this->pattern($comment->getBody(), implode("|", $units));
                if (count($search) > 0) {
                    foreach ($search as $match) {
                        $quantity = new $class($match[0], $match[1]);
                        $base = $quantity->toUnit($properties["base"]);
                        if (!isset($properties["dictionary_unit"])) $properties["dictionary_unit"] = $properties["base"];
                        $entries = $this->searchDictionary($properties["dictionary_unit"], $base);
                        if ($entries != false) {
                            foreach ($entries as $entry) {
                                $output[] = array($entry, "{$match[0]} {$match[1]}");
                            }
                        }

                    }
                }
            }
        }

        //non-conversion approach #1
        if (DictionaryConfig::$allowMoney) {
            $matches = array();
            $muney = "/(\\$)\\d+([,.])?(\\d+)?/";
            preg_match_all($muney, $comment->getBody(), $matches, PREG_SET_ORDER);
            if (count($matches) > 0) {
                foreach ($matches as $match) {
                    $entries = $this->searchDictionary("$", floatval(substr(str_replace(",", "", $match[0]), 1)));

                    if ($entries != false)
                        foreach ($entries as $entry)
                            $output[] = array($entry, $match[0]);
                }
            }
        }

        //non-conversion approach #2
        if (DictionaryConfig::$allowPeople) {
            $matches = array();
            $ppl = "/\\d+([,.])?(\\d+)?( )?(people|humans|civilians|protesters)/";
            preg_match_all($ppl, $comment->getBody(), $matches, PREG_SET_ORDER);
            if (count($matches) > 0) {
                foreach ($matches as $match) {
                    $entries = $this->searchDictionary("people", floatval(str_replace(",", "", $match[0])));

                    if ($entries != false)
                        foreach ($entries as $entry)
                            $output[] = array($entry, $match[0]);
                }
            }
        }

        $this->processed++;

        if (count($output) == 0) return;

        //randomize order
        shuffle($output);

        //check for double sentences
        $new_output = array();
        $check = array();
        foreach ($output as $attrs) {
            $entry = $attrs[0];
            if (!isset($check[$attrs[1]])) $check[$attrs[1]] = array();
            if (!in_array($entry->human_readable, $check[$attrs[1]])) {
                if (count($check[$attrs[1]]) == DictionaryConfig::$sentencesPerUnit) continue;
                $check[$attrs[1]][] = $entry->human_readable;
                $new_output[] = $attrs;
            }
        }
        $output = $new_output;

        $sentences = array();
        foreach ($output as $attrs) {
            $entry = $attrs[0];
            $original_value = $attrs[1];
            $sentences[] = "{$original_value} ≈ " . (filter_var($entry->source, FILTER_VALIDATE_URL) ? "[{$entry->human_readable}](" . str_replace(array("(", ")"), array("%28", "%29"), $entry->source) . ")" : $entry->human_readable);
        }
        $sentences = implode(", ", $sentences);
        $OP = $comment->getAuthorName();
        $reply = "";
        eval("\$reply = \"" . DictionaryConfig::$templates["comment"] . "\";");

        $this->info("-------- REPLYING --------");
        $this->info($reply);
        $this->info("-------------------------");
        $this->info("By: " . $comment->getAuthorName());
        $this->info($comment->getBody());
        $this->info("-------------------------");
        if (!BotConfig::$dryRun) $comment->reply($reply);
        else $this->info("Running in dryrun-mode, didn't post.");
    }

    private $regex = "/\\b(\\d*([,.]\\d+)?)(?![0-9\\.])( )?(MATCHES)\\b/";

    function pattern($body, $matches)
    {
        $regex = str_replace("MATCHES", str_replace("^", "\\^", str_replace("/", "\\/", $matches)), $this->regex);
        $matches = array();
        preg_match_all($regex, $body, $matches, PREG_SET_ORDER);

//        $this->info($regex);
        $ret = array();
        foreach ($matches as $match) {
            if (!empty($match[1]))
                $ret[] = array(doubleval(str_replace(",", "", $match[1])), $match[4]);
        }
        return $ret;
    }
} 