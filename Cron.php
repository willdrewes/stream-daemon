<?php

require_once("Publisher.php");

//Called by crontab on a specified interval. 

$subscriber_process = new Subscriber;
$subscriber_process->processWorkingQueue();


?>