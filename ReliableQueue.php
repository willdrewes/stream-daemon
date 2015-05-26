<?php

require_once("Predis/Autoloader.php");
require_once("Twitter/TwitterClient.php");
 
Predis\Autoloader::register();

class ReliableQueue extends DataStore {

    private $redis;

    public function __construct() {
        parent::__construct();
        $this->redis = $this->getRedisClient();
    }

    private function getRedisClient(){
        $redis_confog = array(
            "scheme" => REDIS_SCHEME,
            "host"   => REDIS_HOST,
            "port"   => REDIS_PORT,
        );
        try {
            $redis = new Predis\Client($redis_confog);
            echo "Successfully connected to Redis\n\n";
            return $redis;
        }
        catch (Exception $e) {
            die("Couldn't connected to Redis " . $e->getMessage() . "\n\n";
        }

    }

}