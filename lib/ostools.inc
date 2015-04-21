<?php
include_once("tools.php");

Class OStools {

	function __construct(){
		$this->os=PHP_OS;
		$this->uname=php_uname("s");
		$this->version=php_uname("v");
		$this->host=php_uname("n");
		//print_r($this);
	}

	function cpuload(){
		switch($this->os){
		case "WINNT":
			trace("OSSensor::cpuload : Windows method - Win32_Processor");
			$wmi = new COM("Winmgmts://");
            $server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");
            $cpu_num = 0;
            $load_total = 0;
            $load_min=10000;
            foreach($server as $cpu){
                $cpu_num++;
                $load_total += $cpu->loadpercentage;
                $load_min=min($load_min,$cpu->loadpercentage);
            }
            $load = round($load_total/$cpu_num);
   			$result=Array(
				"OS"	=> $this->os,
				"1min"  => round($load,3),
				"5min"  => round(($load+$load_min)/2,3), // not really the same as load over 5min , but yeah, we can't have that
				"15min" => round(($load+2*$load_min)/3,3), // not really the same as load over 15min , but yeah, we can't have that
				);
			break;;
		case "Darwin":
		case "Linux":
			trace("OSSensor::cpuload : Linux/MacOSX method - sys_getloadavg");
			$load=sys_getloadavg();
			$result=Array(
				"OS"	=> $this->os,
				"1min"  => round($load[0],3),
				"5min"  => round($load[1],3),
				"15min" => round($load[2],3),
				);
			break;;
		default:
			$result=Array(
				"OS"	=> $this->os,
				"1min"  => 0,
				"5min"  => 0,
				"15min" => 0,
				);
		}
		trace("OSSensor::cpuload : load is " . $result["1min"]);
		return $result;
	}

	function cpucount(){
		$numCpus = 1;

		switch(true){
		case is_file('/proc/cpuinfo'):
			// Linux machines
			trace("OSSensor::cpucount : Linux method: /proc/cpuinfo");
			$cpuinfo = file_get_contents('/proc/cpuinfo');
			preg_match_all('/^processor/m', $cpuinfo, $matches);
			$numCpus = count($matches[0]);
			break;;
		case $this->os=="WINNT":
			// Windows machines
			trace("OSSensor::cpucount : Windows method: wmic");
			$numCpus=$this->wmic("cpu get NumberOfCores","int");
			break;;
		default:
			trace("OSSensor::cpucount : MacOSX method: sysctl");
			$numCpus=$this->sysctl("hw.ncpu","int");
		}
		trace("OSSensor::cpucount : $numCpus CPUs detected");
		return $numCpus;
	}

	function cpuinfo(){
		$numCpus = 1;

		$cpuinfo=Array(
			"bogomips" => 1000,
			"cores" => 1,
			"ghz" => 1,
			);
		switch(true){
		case is_file('/proc/cpuinfo'):
			// Linux machines
			trace("OSSensor::cpuinfo : Linux method: /proc/cpuinfo");
			/*
			Single Core ARM:
			Processor name  : Feroceon 88F6282 rev 1 (v5l) @ 1.6 GHz
			BogoMIPS        : 1589.24
			
			Dual Core Intel:
			vendor_id       : GenuineIntel
			cpu family      : 6
			model name      : Intel(R) Celeron(R) CPU  J1800  @ 2.41GHz
			cpu MHz         : 2408.000
			cpu cores       : 2
			bogomips        : 4833.43
			*/
			$cpulines = file_get_contents('/proc/cpuinfo');
			trace($cpulines);
			
			$bogomips=$this->grepinfo("bogomips",1000);
			$numcores=$this->grepinfo("cpu cores",1);
			$cpughz=$this->grepinfo("cpu MHz",$bogomips/$numcores);
			$cpughz=round($cpughz/1000,1);
			
			$cpuinfo["bogomips"]=$bogomips;
			$cpuinfo["ghz"]=$cpughz;
			$cpuinfo["cores"]=$numcores;
			break;;
		case $this->os=="WINNT":
			// Windows machines
			trace("OSSensor::cpuinfo : Windows method: wmic");
			$cpuinfo["cores"]=$this->wmic("cpu get NumberOfCores","int");
			$cpuinfo["ghz"]=round($this->wmic("cpu get MaxClockSpeed","int")/1000,1);
			$cpuinfo["bogomips"]=$cpuinfo["cpu cores"] * $cpuinfo["ghz"] * 1000;
			break;;
		default:
			trace("OSSensor::cpuinfo : MacOSX method: sysctl");
			$cpuinfo["cores"]=$this->sysctl("hw.ncpu");
			$cpuinfo["ghz"]=round($this->sysctl("hw.cpufrequency")/1000000000,1);
			$cpuinfo["bogomips"]=$cpuinfo["cores"] * $cpuinfo["ghz"] * 1000;
		}
		trace($cpuinfo);
		return $cpuinfo;
	}

	function uptime(){
		$uptime = 0;

		switch($this->os){
		case "WINNT":
			// Windows machines
			// LastBootUpTime
			// 20150122133550.626398+060
			trace("OSSensor::uptime : Windows method: wmic");
			$lastboot=$this->wmic("os get lastbootuptime");
			$bdate=midstr($lastboot,0,4)."-".midstr($lastboot,4,2)."-".midstr($lastboot,6,2)." ".midstr($lastboot,8,2).":".midstr($lastboot,10,2);
			$btime=strtotime($bdate);
			$since=time()-$btime;
			$sincedays=$since/(3600*24);
			switch(true){
			case $sincedays < 1:	$uptime=round($since/3600,1) . " hours";	break;;
			case $sincedays < 60:	$uptime=round($since/3600*24,1) . " days";	break;;
			case $sincedays < 365:	$uptime=round($since/3600*24*7,1) . " weeks";	break;;
			default:				$uptime=round($since/3600*24*365,1) . " years";
			}
		case "Darwin":
		case "Linux":
			// Linux machines
			trace("OSSensor::uptime : Linux method: /usr/bin/uptime");
			$output = cmdline('/usr/bin/uptime');
			trace($output);
			//  18:22  up 9 days, 18:06, 3 users, load averages: 1.78 1.50 1.52
			if($output){
				$output=str_replace(Array("up","load"),"|",$output[0]);
				list($now,$uptime,$rest)=explode("|",$output);
				$uptime=preg_replace("/([0-9]* users)/", "", $uptime);
				$uptime=str_replace(",", "", $uptime);
			}
			break;;
		default:
			trace("OSSensor::uptime : unknown OS");
		}
		trace("OSSensor::uptime : return $uptime");
		return $uptime;
	}

	function battery(){
		$result=Array();
		switch($this->os){
		case "WINNT":
			// Windows machines
			trace("OSSensor::battery : Windows method: wmic");
			// to be implemented
			$lastboot=$this->wmic("os get lastbootuptime");
			$result["error"]="not yet implemented";
		case "Darwin":
			if (file_exists('/usr/sbin/system_profiler')){
				trace("OSSensor::battery : MacOSX method: system_profiler");
				$output=cmdline("system_profiler SPPowerDataType");
				$this->parse_profiler($output);
			} elseif (file_exists('/usr/sbin/AppleSystemProfiler')) {
				trace("OSSensor::battery : MacOSX method: AppleSystemProfiler");
				$output=cmdline("AppleSystemProfiler SPPowerDataType");	
			}
			$parsed=$this->parse_profiler($output);
			// static
			$result["battery_capacity"]	=$this->find_val("Full Charge Capacity",$parsed);
			$result["charger_watt"]		=$this->find_val("AC Charger Information - Wattage",$parsed);

			// boolean
			$result["battery_present"]	=$this->find_bool("Battery Information - Battery Installed",$parsed);
			$result["charger_busy"]		=$this->find_bool("AC Charger Information - Charging",$parsed);
			$result["charger_present"]	=$this->find_bool("AC Charger Information - Connected",$parsed);
			$result["charger_done"]		=$this->find_bool("Fully Charged",$parsed);

			// dynamic
			$result["battery_health"]	=$this->find_val("Health Information - Condition",$parsed);
			$result["battery_cycles"]	=$this->find_val("Cycle Count",$parsed);

			$result["battery_mamp"]		=$this->find_val("Battery Information - Amperage",$parsed);
			$result["battery_mvolt"]	=$this->find_val("Battery Information - Voltage",$parsed);
			$result["battery_charge"]	=$this->find_val("Charge Remaining",$parsed);
			$result["battery_charge_%"]=round(100*$result["battery_charge"]/$result["battery_capacity"],3);
			ksort($result);

		case "Linux":
			// Linux machines
			$result["error"]="not yet implemented";
			break;;
		default:
			$result["error"]="not yet implemented";
			trace("OSSensor::uptime : unknown OS");
		}
		return $result;
	}

	private function wmic($command,$type="string"){
		if($this->os<>"WINNT")	return false;
		$val=false;
		$process = @popen('wmic $command', 'rb');
		if ($process){
			fgets($process); // skip^first line with field title
			switch($type){
			case "int":
			case "integer":
				$val = intval(fgets($process));
				break;;
			default:
				$val = trim(fgets($process));
			}
			pclose($process);
		}
		return $val;
	}

	private function grepinfo($param,$default=false,$file="/proc/cpuinfo"){
		if($this->os<>"Linux")	return false;
		$output=cmdline("grep -i \"$param\" $file");
		$val=$default;
		if($output){
			foreach($output as $line){
				list($key,$val)=explode(":",$line,2);
				if($val)	$val = trim($val);
			}
		}
		trace("grepinfo: found [$param] = [$val]");
		return $val;
	}

	private function sysctl($key,$type="string"){
		if($this->os<>"Darwin")	return false;
		$val=false;
		$output = cmdline("sysctl -a | grep '$key:'");
		trace($output);
		if ($output){
			$line=$output[0];
			list($key,$val)=explode(":",$line);
			switch($type){
			case "int":
			case "integer":
				$val=(int)$val;
				break;;
			default:
				$val=trim($val);
			}
			return $val;
		} else {
			trace("sysctl: nothing found for $key");
			return false;
		}
	}

	private function parse_profiler($lines){
		$previndent=-1;
		$parsed=Array();
		foreach($lines as $line){
			if(strlen(trim($line)) == 0) continue;
			$indent=strlen($line)-strlen(preg_replace("/^([\s\t]*)/","",$line));
			list($key,$val)=explode(":",trim($line),2);
			$val=trim($val);
			switch(true){
			case strlen($val) > 0:
				$tree=Array();
				foreach($titles as $level => $title){
					if($level < $indent AND strlen($title) > 0) $tree[]=$title;
				}
				$tree[]=$key;
				//$tree[]=$indent;
				$combined=implode(" - ",$tree);
				//$parsed[$combined]=$val;
				$parsed[]="$combined: $val";
				//trace("$combined :: $val");
			case $indent < $previndent:
				//$titles[$previndent]="";
				$titles[$indent]=$key;
				$previndent=$indent;
				break;
			case $indent > $previndent:
				$titles[$indent]=$key;
				break;
			}

		}
		return $parsed;
	}
	
	private function find_val($pattern,$subject){
		$results=preg_grep("/$pattern/", $subject);
		if(!$results){
			trace("find_val: cannot find [$pattern]");
			return false;
		}
		foreach($results as $result){
			trace($result);
			list($key,$val)=explode(":",$result,2);
		}
		$val=trim($val);
		return $val;
	}

	private function find_bool($pattern,$subject){
		$results=preg_grep("/$pattern/", $subject);
		if(!$results)	return false;
		foreach($results as $result){
			list($key,$val)=explode(":",$result,2);
		}
		$val=strtoupper(trim($val));
		switch($val){
			case 1:
			case "TRUE":
			case "YES":
			case "OUI":
				trace("find_bool:  found $val => 1");
				return 1;
				break;;
			case 0:
			case "NON":
			case "NO":
			case "FALSE":
				trace("find_bool:  found $val => 0");
				return 0;
				break;;
		}
		trace("find_bool:  found $val => ?");
		if(strtoupper(substr($val,0,1)) == "N")	return 0;
		return 1;
	}

}

?>
