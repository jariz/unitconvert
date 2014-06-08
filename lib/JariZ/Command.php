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
    public $input = "";
    public function getName() {
        return $this->name;
    }
    public function __construct() {

    }
    public function info($message) {
        echo "[INFO] ".$message."\n";
    }
    public function comment($message) {
        echo "[COMMENT] ".$message."\n";
    }
    public function error($message) {
        echo "[ERROR] >>>".$message;
    }
    public function fire() {
        //should be overridden
    }
} 