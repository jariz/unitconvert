<?php

namespace JariZ;


use RedditApiClient\Reddit;
use RedditApiClient\Comment;

/**
 * Class DownvoteMonitor
 * @package JariZ
 * Downvotemonitor watches unitconvert's own comments, once they reach below 0, it'll remove them.
 */
class DownvoteMonitor extends Command
{
    public function __construct()
    {
        $this->name = "downvote";
        parent::__construct();
    }

    private $reddit;

    public function fire()
    {
        $this->info("Unitconvert booting in downvote monitor mode...");
        $this->reddit = new Reddit(BotConfig::$username, BotConfig::$password);

        $last = "";
        while (true) {
            $start = microtime(true);
            $comments = $this->reddit->getComments("user/unitconvert/comments", 100, $last);
            if (count($comments) == 0) { //we've reached the end, start from top again
                $last = "";
                $this->info("Restarted from top!");
            }
            foreach ($comments as $comment) {
                /* @var $comment Comment */
                $score = ($comment->getUpvotes() - $comment->getDownvotes());
                if ($score < 0) {
                    $x = $this->reddit->sendRequest("POST", "http://www.reddit.com/api/del", array("id" => $comment->getThingId(), "uh" => $this->reddit->modHash));
                    if (count($x) == 0) $this->info("Removed {$comment->getThingId()} because it's score is {$score} :(");
                    else $this->error("Failed removing {$comment->getThingId()}");
                }
                $last = $comment->getThingId();
            }

            //read messages at end of loop
            $messages = $this->reddit->getComments("message/unread", 100);
            foreach ($messages as $message) {
                /* @var $message Comment */
                if ((strtolower($message->getBody()) == "remove" || strtolower($message->getBody()) == "delete") &&
                    $message->offsetGet("subject") == "comment reply"
                ) {
                    $p = explode("/", $message->offsetGet("context"));
                    //grab parent of comment i made
                    $parent = $this->reddit->getComments("r/{$message->offsetGet("subreddit")}/comments/{$p[4]}/-/" . substr($message->offsetGet("parent_id"), 3), 25, "", "", "", "", 1);
                    if (!isset($parent[0])) continue;
                    $parent = $parent[0];
                    if ($message->getAuthorName() == $parent->getAuthorName()) {
                        $replies = $parent->getReplies();

                        //remove comment
                        $x = $this->reddit->sendRequest("POST", "http://www.reddit.com/api/del", array("id" => $replies[0]->getThingId(), "uh" => $this->reddit->modHash));
                        if (count($x) == 0)  {
                            $this->info("Removed {$comment->getThingId()} on request of a user.");

                            //notify OP
                            $OP = $message->getAuthorName();
                            $post = "/r/{$message->offsetGet("subreddit")}/comments/{$p[4]}/-/" . $parent->getId();
                            $template = "";
                            eval("\$template = \"".BotConfig::$templates["removed"]."\";");
                            $this->reddit->sendRequest("POST", "http://www.reddit.com/api/compose", array(
                                "api_type" => "json",
                                "subject" => BotConfig::$templates["removed_subject"],
                                "text" => $template,
                                "to" => $OP,
                                "uh" => $this->reddit->modHash
                            ));

                            $this->info("------- PM -------");
                            $this->info($template);
                            $this->info("------------------");
                        }
                        else
                            $this->error("Failed removing {$comment->getThingId()} on request of a user");


                    } else {
                        $this->info("Somebody tried to tell me to remove a post but he's not the OP of the post, what a dick.");
                    }
                }
                $read = $this->reddit->sendRequest("POST", "http://www.reddit.com/api/read_message", array("id" => $message->getThingId(), "uh" => $this->reddit->modHash));
                if (count($read) == 0) $this->info("Marked PM {$message->getThingId()} as read");
                else $this->info("Failed marking PM {$message->getThingId()} as read!");
            }

            //end of loop
            if (BotConfig::$obeyRules) {
                $spend = 2000 - (microtime(true) - $start);
                if ($spend > 0) {
                    $spend = ($spend * 1000);
                    $this->comment("Sleeping {$spend} msecs");
                    usleep($spend);
                }
            }
        }
    }
} 