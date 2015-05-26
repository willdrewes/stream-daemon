<?php

class Publisher extends ReliableQueue {
    
    private $redis;
    private $twitter_token;

    public function __construct() {
        $twitter_client = new TwitterClient();
        $this->twitter_token = $this->twitter_client->getToken();
    }

    /**
    * Checks if the process is running my comparing the process ids with the running process in the lock file.
    */
    private function isRunnng(){
        $lock_file = 'running.lock';
        $handle = fopen($lock_file, 'r');
        $data = fread($handle,filesize($lock_file));
       
        $str_output_array = array();
        $str_process_array = explode("\n", trim(`ps -ef`));
           
        for ($i = 0; $i < sizeof($str_process_array); $i++) {
            if ($i == 0){
                $str_field_nam_array = preg_split("#[[:space:]]+#", $str_process_array[$i]);
            }
            else {
                $str_field_value_array = preg_split("#[[:space:]]+#", $str_process_array[$i]);
                $str_output_array[$i - 1] = array();

                for ($j = 0; $j < sizeof($str_field_nam_array); $j++)
                    $str_output_array[$i - 1][$str_field_nam_array[$j]] = @$str_field_value_array[$j];
                }
            }

            foreach($str_output_array as $value){
                $unique = $value['PPID'].$value['TTY'];
                if($data == $unique){
                    $running = 1;
                }
            } 
       
            if($running){
                echo("\n\nPROCESS: ".$data." is running\n\n");
                return true;
            } else {
                echo("\n\nPROCESS: ".$data." is no longer running\n\n");
                return false;
            }
           
        } 
    }
    /**
    * Creates a lock file for the consumption process via the process ID
    */
    private function createCollectionLock(){

        if($this->isRunning() === true){
            echo "\n\ncreateCollectionLock, the process is already running\n\n";
            return false;
        }

        echo "\n\nStarting Collection Process...."."\n\n";
        //We will keep track of the process by storing it in a simple text file.
        $lock_file = 'running.lock';
        unlink($lock_file);
        $file_handle = fopen($lock_file, 'w') or die ('Cannot open file:  ' . $lock_file);
        $current_process_id = getmypid();
        $timestamp = date('g:iA', time());
        //Process ID's are not unique, therfore we need to add the TTY (time) to the PID string.
        $current_process_id = $data . $timestamp;
        echo("\n\nProccess ID: ".$current_process_id."\n\n");   
       
        //Save Process ID to file.
        fwrite($handle, $current_process_id);
        fclose($handle);

        return true;
    }

    /**
    * This is the collection (publish) process. Tweets are stored in a list to be picked up by the subscriber (consumption) process
    */
    public function collect() {
        if ($this->createCollectionLock() === false){
            return;
        }

        $fp = fsockopen(TWITTER_STREAM_API_ENDPOINT, 443, $errno, $errstr, 30);
        $query_data = array(
            'include_entities' => 'true',
            'track' => '%23Bengals,%23Seahawks,%23Steelers,%23Raiders,%23Cowboys,%23Patriots,%23Colts,%23Giants,%23Ravens,%23Dolphins,%23Saints,%23Eagles,%23Packers',
            'token' => $this->twitter_client->getToken(),
        );
                  
        if(!$fp){
            print "$errstr ($errno)\n";
            return;
        }

        $request = "GET /1/statuses/filter.json?" . http_build_query($query_data) . " HTTP/1.1\r\n";
        $request .= "Host: stream.twitter.com\r\n";
        fwrite($fp, $request);

        //Equivelent to while(1), as you can't ever get to the end of a socket 'file' unless the connection gets terminated somehow.
        while(!feof($fp)){  
            if ($this->shouldStop()){
                echo "\n\nBailing out\n\n";;
                break;  
            } 
            $json  = fgets($fp);
            $tweet = json_decode($json);           
            if(!$tweet){
                continue;
            }                            
            $this->redis->rpush("WorkingQueue", serialize($tweet));
        }
        close($fp);
        return;
    }
    
    /**
    * Returns true when the main loop should finally stop. This is controlled from the 'stop' function
    * called by a watchdog (cron calling the 'running' function)
    */
    public function shouldStop() {
        clearstatcache(); 
        if (file_exists('should_stop')) {
            unlink('should_stop'); // delete the file for next time
            return true;
        }
        return false;
    }
    /**
    * Creates a file named should_stop. This file signals for the collect process to stop, externally. 
    * This way we can exit the loop gracefully if we need to shut it off.
    */
    public function stop() {
        $file = 'should_stop';
        $handle = fopen($my_file, 'w') or die ('Cannot create file:  ' . $file); //implicitly creates file
        echo("\nProcess Stopped\n");
          
    }
       
}

