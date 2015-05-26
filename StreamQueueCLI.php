#!/usr/bin/php -q
<?php

//CLI for stream collection daemion. This can also be called from a cron.

require_once("Publisher.php");

echo "\n\Welcome to the Stream Ingestion CLI.\n\n";

$proccess = new  Publisher;
  
switch($argv[1]){
            
    case 'running':
        $proccess->isRunning();  //Check if Collection process is running
        break;    
    case 'stop':
        $proccess->stop();       //Stops Collection process
        break;    
    case 'collect':
        $proccess->collect();   //Start Collection 
        break;
                  
    default:
        echo("\nYou need to specify a valid agument:\ncollect: start collection\nstop: stop collection\nrunning: check if process is running\n");
        break;
}   

?>