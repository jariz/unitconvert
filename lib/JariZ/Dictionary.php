<?php

namespace JariZ;

use PhpUnitsOfMeasure\PhysicalQuantity;
use \RedditApiClient\Comment;
use \RedditApiClient\Link;
use \RedditApiClient\Reddit;

/**
 * Class Dictionary
 * @package JariZ
 */
class Dictionary extends Command
{
    /**
     * @var Reddit
     */
    private $reddit;

    /*
     * IDs we already scanned
     */
    private $ids = array();

    public function __construct()
    {
        parent::__construct();
        $this->name = "dictionary";
    }

    public function fire()
    {
        if(!empty($this->input)) {
            $this->info("Unitconvert input mode, input string: ".$this->input);
            $this->info("   - Loading dictionary...");
            $this->loadDictionary();
            $this->info("   - Running algorithm...");
            var_dump($this->scan($this->input));
            $this->info("Buh-bye!");
            exit;
        }
        $this->info("Unitconvert dictionary mode booting...");
        $this->info("   - Logging in...");
        $this->reddit = new Reddit(BotConfig::$username, BotConfig::$password);
        $this->info("   - Loading dictionary...");
        $this->loadDictionary();

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
        global $baseDir;
        $this->dictionary = json_decode(file_get_contents($baseDir . "/res/DictionaryOfNumbers.db.json"));
        $this->info("Loaded " . count($this->dictionary) . " dictionary entries");

        $types = array();
        foreach ($this->dictionary as $dict)
            if (isset($dict->si_unit)) $types[$dict->si_unit] = $dict->si_unit;
        $this->info("Following quantities available in dictionary: " . implode(", ", $types));
    }


    public function Monitor()
    {
        while (true) {

            if(DictionaryConfig::$scanComments) {
                $start = microtime(true);
                $this->monitorComments();
                $this->wait($start);
            }

            if(DictionaryConfig::$scanLinks) {
                $start = microtime(true);
                $this->monitorLinks();
                $this->wait($start);
            }
        }
    }

    function wait($start) {
        if (BotConfig::$obeyRules) {
            $spend = 2000 - (microtime(true) - $start);
            if ($spend > 0) {
                $spend = ($spend * 1000);
                $this->comment("Sleeping {$spend} msecs, processed {$this->processed} in total.");
                usleep($spend);
            }
        }
    }

    function monitorComments() {
        $result = $this->reddit->getComments("r/all/comments", 100);
        foreach ($result as $comment)
            $this->scanComment($comment);
    }

    function monitorLinks() {
        $result = $this->reddit->getLinksBySubreddit("all/new", 50);
        foreach($result as $link)
            $this->scanLink($link);
    }

    private $processed = 0;

    function scanComment(Comment $comment) {
        if (isset($this->ids[$comment->getThingId()])) return;
        $this->ids[$comment->getThingId()] = "";

        //ignores
        if (in_array(strtolower($comment->offsetGet("subreddit")), BotConfig::$avoidSubs)) return;
        if(in_array(strtolower($comment->getAuthorName()), BotConfig::$avoidUsers)) return;
        if (strtolower($comment->getAuthorName()) == strtolower(BotConfig::$username)) return;

        //make it happen
        $sentences = $this->scan($comment->getBody());
        if(count($sentences) == 0) return;

        //build reply
        $sentences = implode(", ", $sentences);
        $OP = $comment->getAuthorName();
        $reply = "";
        eval("\$reply = \"" . DictionaryConfig::$templates["comment"] . "\";");

        $this->info("--- REPLYING @ COMMENT ---");
        $this->info($reply);
        $this->info("--------------------------");
        $this->info("By: " . $comment->getAuthorName());
        $this->info($comment->getBody());
        $this->info("--------------------------");
        if (!BotConfig::$dryRun) $comment->reply($reply);
        else $this->info("Running in dryrun-mode, didn't post.");
    }

    function scanLink(Link $link) {
        if (isset($this->ids[$link->getThingId()])) return;
        $this->ids[$link->getThingId()] = "";

        if (in_array(strtolower($link->offsetGet("subreddit")), BotConfig::$avoidSubs)) return;
        if(in_array(strtolower($link->getAuthorName()), BotConfig::$avoidUsers)) return;
        if (strtolower($link->getAuthorName()) == strtolower(BotConfig::$username)) return;

        //make it happen
        $sentences = $this->scan($link->getTitle());
        if(count($sentences) == 0) return;

        //build reply
        $sentences = implode(", ", $sentences);
        $OP = $link->getAuthorName();
        $reply = "";
        eval("\$reply = \"" . DictionaryConfig::$templates["comment"] . "\";");

        $this->info("----- REPLYING @ LINK ----");
        $this->info($reply);
        $this->info("--------------------------");
        $this->info("By: " . $link->getAuthorName());
        $this->info($link->getTitle());
        $this->info("--------------------------");
        if (!BotConfig::$dryRun) $link->reply($reply);
        else $this->info("Running in dryrun-mode, didn't post.");
    }

    /**
     * Scan the string for matches with the certified and patented unitconvert™  algorithm
     * @param $string string The input string
     * @returns string Returns an array with sentences (if any)
     */
    function scan($string)
    {
        $output = array();
        foreach (DictionaryConfig::$conversionClasses as $class => $properties) {
            $class = "\\PhpUnitsOfMeasure\\PhysicalQuantity\\" . $class;
            $quantity = new $class(null, null);
            /* @var $quantity PhysicalQuantity */
            $units = $quantity->getUnits();

            //conversion approach
            if (DictionaryConfig::$allowConversions) {
                $search = $this->pattern($string, implode("|", $units));
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
            preg_match_all($muney, $string, $matches, PREG_SET_ORDER);
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
            preg_match_all($ppl, $string, $matches, PREG_SET_ORDER);
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

        if (count($output) == 0) return array();

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

        return $sentences;

    }

    private $regex = "/\\b(\\d*([,.]\\d+)?)(?![0-9\\.])( )?(MATCHES)\\b/";

    function pattern($body, $matches)
    {
        $regex = str_replace("MATCHES", str_replace("^", "\\^", str_replace("/", "\\/", $matches)), $this->regex);
        $matches = array();
        preg_match_all($regex, $body, $matches, PREG_SET_ORDER);

        $ret = array();
        foreach ($matches as $match) {
            if (!empty($match[1]))
                $ret[] = array(doubleval(str_replace(",", "", $match[1])), $match[4]);
        }
        return $ret;
    }
} 