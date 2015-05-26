# stream-daemon

stream-daemon is a reliable queue system for collecting data from a stream, for example an  Internet or Unix domain socket connection.

In this repository, we are using fictional twitter stream which provides access to streamed data from twitter for tweets using the names of NFL football teams. 

The use-case for this stream daemon could be that you are consuming from a stream of data with high reliability, and suffer minimal data loss if any of the processes in the system fail by using a simple messaging queue. 

This project is experimental and has never been used in a real production environment, and was started as a rough proof of concept for building a stream collection process using the reliable queue design pattern.


##Sub-processes and functions

###Producer (collector) process
The collector process connects to the stream and reads from it until the process is halted. This process is designed to run indefinitely, and should be monitored using a watchdog via server cron or some other external process that can restart the system a “no-longer-running” event is detected. The only job for this processes is to put data into a redis queue as fast as possible, such that any suddens spikes in the stream volume will not flood the system. 
 
###Subscriber process

The subscriber process ingests the data items that are stored in the holding pen which is populated by the collector process. This process allows the high volumes of data to be processed in bite-sized pieces, from the redis queue to be stored in a permanent data store, such as MySQL.This process should be run on a long interval which runs just often enough so that the data does not become backlogged.

This processes is where the “reliability” comes into play. Once an item is popped off of the working queue, a backup of the item is pushed into a busy queue. Once confirmation has been made that the permanent storage was successful, the item is then purged from the busy queue. This ensures that even if the subscriber process crashes during an import, the item is still persistent in the busy queue to be picked up at a later time. 

###CLI
The CLI is a command line interface for starting, stopping, or monitoring the collection process. 
The following commands are available:
####collect: 
	start collection
####stop: 
  	stop collection
####running: 
  	check if process is running

This CLI can be run from the command line on the server, or via a server CRON. 

###Cron
This process should be called by a cron, and triggers the subscriber process. It should be called on some interval appropriate for the volume of data in the stream.

## Contributing

Anyone and everyone is welcome to contribute

## License

### Major components:

* PHP: GNU General Public License (GPL),
* Predis: MIT License
* Redis: BSD Licence

### Everything else:

The Unlicensed (aka: public domain)
