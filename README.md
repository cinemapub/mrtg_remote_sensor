# MRTG REMOTE SENSOR

## Why?

because I lose too much time figuring out how to use complex `snmpwalk/snmpget` SNMP to get vital data like CPU usage, disk usage and other stuff in my MRTG reporting. I want something easy to install, easy to use, easy to understand. Like a URL with easy, readable parameters.


## What

The MRTG sensor can be used to monitor a number of different variables. The PHP process will execute a PHP own command, or will run a bash command line to get the data. All these sensors are READ-ONLY. Never can a root-level (`sudo`) command be required to get the data.

* cpu usage (uses the `top` command)
* memory usage (uses the `free` command)
* disk usage (uses the `df` command)
* folder size in MB/GB(uses the `du` command)
* file count/folder count (uses the `ls`command)
* (more to be added)

## When?

* the server you want to monitor should have a webserver that can run PHP
* the web service can be called over a LAN ( `192.168.1.___/mrtg_sensor/` ) or over the internet ( `www.example.com/mrtg_sensor/` )
* the web service can obviously be called locally too ( `localhost:8080/mrtg_sensor/` )

## How?

* install this folder on the server you want to monitor (eg server01)
* in the MRTG config of your MRTG client (standard name: `mrtg.cfg` ), use `curl` to get the output of the script

An example:

	Target[server01_disk1]: $(curl -s "http://server01/mrtg_sensor/?line1=diskfree&line2=disksize&path=/share")
	Title[server01_disk1]: SERVER01: Disk Usage
	PageTop[server01_disk1]: < h1 >SERVER01: Disk 1 Usage< /h1 >
	LegendI[server01_disk1]: SERVER01: Disk 1 Usage
	ShortLegend[server01_disk1]: B  

the script will return with the standard 4 text lines that MRTG requires:

1. value I
2. value O (optional)
3. uptime
4. hostname

and will add some more lines (which are ignored by MRTG):

5. the unit of the result (MB, file/sec)
6. the URL for configuring

Example:
	
	85
	62
	1d, 12h 35 m
	server01
	B
	http://server01/mrtg_sensor/config.php?line1=diskfree&line2=disksize&path=/share
