<?php

class Subscriber extends ReliableQueue {
    
    public function __construct() {
        parent::__construct();
    }
    /**
    * Removes the last elements (tail) of the list stored at working, and pushes the elements at the first element (head) 
    * of busy queue for processing, then add items to perminant storage.
    */
    public function processWorkingQueue(){
        $length_of_working_queue = $this->redis->llen("WorkingQueue");
            
        //Cap it at 500 at a time. This way, the busy queue will never get flooded.
        if($length_of_working_queue > 500) {                
            $length_of_working_queue = 500;
        }
          
        //Get all the stuff currently in the Queue, then place it into a processing Queue,
        //See: reliable queue pattern: http://redis.io/commands/rpoplpush
        for($i=0; $i < $length_of_working_queue; $i++){
            $contents[] = $redis->rpoplpush("WorkingQueue", "busyQueue"); 
        }
        
        $this->permanentStore($contents);

    }
    /**
    * Stores the items from the busy queue into 
    * @param array $contents an array of items to be written to DB from queue.
    */
    private function permanentStore($contents){
        //Perform bulk MySQL inserts in chunks of 100.
        $tweets_to_write = array_chunk($contents, 100);
        foreach ($tweets_to_write as $chunk) {
            $tweet_values_string = implode(',', $chunk);
            $sql = "INSERT INTO tweets (`tweet_body`) VALUES (?)";
            $insert_result = $this->data->insert($sql, array($tweet_values_string));
            if($insert_result === true){
                $this->clearBusyQueue($chunk);
            }
        }
    }
    /**
    * Clears the items that have already been processed from the busy queue.
    * @param array $completed an array of items to be removed from the busy queue.
    */
    private function clearBusyQueue($completed){
        foreach ($completed as $queue_item {
            $redis->lrem("busyQueue", $queue_item);
        }

    }

       
}




