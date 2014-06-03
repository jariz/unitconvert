<?php
/**
 * Created by IntelliJ IDEA.
 * User: JariZ
 * Date: 3-6-14
 * Time: 23:23
 */

namespace JariZ;
use Hoa\Console\Cursor;
class Command {
    protected $name = "";
    public function getName() {
        return $this->name;
    }
    public function __construct() {

    }
    public function info($message) {
        echo $message."\n";
    }
    public function comment($message) {
        echo $message."\n";
    }
    public function fire() {
        //should be overridden
    }
} 