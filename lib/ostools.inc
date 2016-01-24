<?php
include_once("tools.php");

Class OStools {

	function __construct(){
		$this->result["os"]=strtolower(php_uname("s"));
		$this->result["os_version"]=php_uname("v");
		if(is_dir("/usr/syno/synoman/")){
			$this->result["os"]="busybox";
			$this->result["os_version"]="Synology";
		}
		if($this->result["os"]=="windows nt"){
			$this->result["os"]="winnt";
		}
		$this->result["host_name"]=php_uname("n");
		$this->result["server_name"]=$_SERVER["SERVER_NAME"];
		$this->result["server_addr"]=$_SERVER["SERVER_ADDR"];
		$this->result["php_version"]=phpversion();
		trace(sprintf("OStools::__construct: detected OS [%s - %s]",$this->result["os"],$this->result["os_version"]));
		}

	//-------------------------------------------------------------
	function memusage(){
		switch($this->result["os"]){
		case "winnt":
			trace("OSSensor::memusage : Windows method - wmic");
			$total=$this->wmic("OS get TotalVisibleMemorySize","int",3600); // normally doesn't change
			$free =$this->wmic("OS get FreePhysicalMemory","int");
			$this->result["free"]=$free;
			$this->result["used"]=$total-$free;
			$this->result["total"]=$total;
			break;;
		case "darwin":
		case "linux":
		case "busybox":
			trace("OSSensor::memusage : linux method - free");
			$result=cmdline("free | grep Mem");
			$line=trim($result[0]);
			$line=preg_replace("#\s\s*#","\t",$line);
			list($mem,$total,$used,$free,$percent,$shared,$buffers)=explode("\t",$line);
			$this->result["free"]=$free;
			$this->result["used"]=$used;
			$this->result["total"]=$total;
			break;;
		default:
			trace("OSSensor::memusage : no source for data");
			$this->result["free"]=0;
			$this->result["used"]=0;
			$this->result["total"]=0;
		}
		trace("OSSensor::memusage -- done");
		trace($this->result);
		return $this->result;
	}

	//-------------------------------------------------------------
	function cpuload(){
		switch($this->result["os"]){
		case "winnt":
			trace("OSSensor::cpuload : Windows method - wmic:LoadPercentage");
			$load[0]=$this->wmic("cpu get LoadPercentage","int",0);
			sleep(1);
			$load[1]=$this->wmic("cpu  get LoadPercentage","int",1000);
			$load_avg=($load[0]+$load[1])/2;
			$load_min=min($load);
			$load_max=max($load);
			$this->result["1min"]=round($load_max,1);
			$this->result["5min"]=round($load_avg,1); // not really the same as load over 5min , but yeah, we can't have that
			$this->result["15min"]=round($load_min,1); // not really the same as load over 15min , but yeah, we can't have that
			break;;
		case "darwin":
		case "linux":
		case "busybox":
			trace("OSSensor::cpuload : Linux/MacOSX method - sys_getloadavg");
			$load=sys_getloadavg();
			$this->result["1min"]=round($load[0],3);
			$this->result["5min"]=round($load[1],3);
			$this->result["15min"]=round($load[2],3);
			break;;
		default:
			$this->result["1min"]=0;
			$this->result["5min"]=0;
			$this->result["15min"]=0;
		}
		trace("OSSensor::cpuload -- done");
		trace($this->result);
		return $this->result;
	}

	//-------------------------------------------------------------
	function cpucount(){
		$numCpus = 1;

		switch(true){
		case $this->result["os"]=="winnt":
			// Windows machines
			trace("OSSensor::cpucount : Windows method: wmic");
			$numCpus=$this->wmic("cpu get NumberOfCores","int",3600);
			break;;
		case $this->result["os"]=="darwin":
			trace("OSSensor::cpucount : MacOSX method: sysctl");
			$numCpus=$this->sysctl("hw.ncpu","int");
			break;;
		case is_readable('/proc/cpuinfo'):
			// Linux machines, if PHP permissions are ok for /proc
			trace("OSSensor::cpucount : Linux method: /proc/cpuinfo");
			$cpuinfo = file_get_contents('/proc/cpuinfo');
			preg_match_all('/^processor/m', $cpuinfo, $matches);
			$numCpus = count($matches[0]);
			break;;
		default:
			trace("OSSensor::cpucount: no source for this info - so just a guess");
			$numCpus=1;
		}
		trace("OSSensor::cpucount : $numCpus CPUs detected");
		return $numCpus;
	}

	//-------------------------------------------------------------
	function proccount($filter=false){
		switch($this->result["os"]){
		case "winnt":
			trace("OSSensor::proccount : Windows method - tasklist");
			$tasks=cmdline("tasklist");
			$total=count($tasks)-3; // remove 1st 3 lines,
			if($filter){
				$nb=0;
				foreach($tasks as $task){
					if(stristr($task,$filter))	$nb++;
				}
			} else {
				$nb=$total;
			}
			$this->result["total"]=$total;
			$this->result["filtered"]=$nb;
			break;;
		case "darwin":
		case "linux":
			trace("OSSensor::proccount : *nix method - ps -ax");
			$psall=cmdline("ps -ax | wc -l");
			$total=$psall[0]-3; // remove 1st line, and our own 'ps' and 'wc' process
			if($filter){
				$psfilter=cmdline("ps -ax | grep \"$filter\" | wc -l");
				$nb=$psfilter[0] - 1; // remove the 'grep' process we created ourselves
			} else {
				$nb=$total;
			}
			$this->result["total"]=$total;
			$this->result["filtered"]=$nb;
			break;;
		case "busybox":
			trace("OSSensor::proccount : *nix method - ps");
			$psall=cmdline("ps | wc -l"); // because Synology does not have options for 'ps'
			$total=$psall[0]-3; // remove 1st line, and our own 'ps' and 'wc' process
			if($filter){
				$psfilter=cmdline("ps | grep \"$filter\" | wc -l");
				$nb=$psfilter[0] - 1; // remove the 'grep' process we created ourselves
			} else {
				$nb=$total;
			}
			$this->result["total"]=$total;
			$this->result["filtered"]=$nb;
			break;;
		default:
			$this->result["total"]=0;
			$this->result["filtered"]=0;
		}
		trace($this->result);
		return $this->result;
	}

	//-------------------------------------------------------------
	function diskusage($path){
		switch($this->result["os"]){
		case "winnt":
			$disk=strtolower(substr($path,0,1).":");
			trace("OSSensor::proccount : Windows method - wmi for [$disk]");
			$lines=cmdline("wmic logicaldisk get size,freespace,caption");
			$free=0;
			$total=0;
			foreach($lines as $line){
				$line=strtolower(trim($line));
				if(!strstr($line," "))	continue;
				$line=preg_replace("#\s\s*#","\t",$line);
				list($dname,$dfree,$dtotal)=explode("\t",$line);
				if($dname==$disk){
					$free=$dfree;
					$total=$dtotal;
				}
			}
			$result=Array(
				"OS"	=> $this->result["os"],
				"total"	=> round($total/1000),
				"free"	=> round($free/1000),
				"used"	=> round(($total-$free)/1000),
				);
			break;;
		case "darwin":
		case "linux":
		default:
			// better use df -m because some path names can be so long they leave no space between name and #blocks
			$result=cmdline("df -m \"$path\"");
			$line=$result[1];
			$line=preg_replace("#\s\s*#","\t",$line);
			list($disk,$blocks,$used,$available,$percent,$mounted)=explode("\t",$line);
			$result=Array(
				"OS"	=> $this->result["os"],
				"total"	=> $blocks*1024,
				"used"	=> $used*1024,
				"free"	=> ($blocks-$used)*1024,
				);
		}
		return $result;
	}

	//-------------------------------------------------------------
	function foldersize($path,$recursive=false){
		$diskusage=$this->diskusage($path);
		$total=$diskusage["total"];
		switch($this->result["os"]){
		case "winnt":
			trace("OSSensor::proccount : Windows method - wmi for [$path]");
			$obj = new COM ( 'scripting.filesystemobject' );
			if ( is_object ( $obj ) )
			{
				$ref = $obj->getfolder($path);
				$size=$ref->size;
				$obj = null;
			}
			$result=Array(
				"OS"	=> $this->result["os"],
				"total"	=> $total,
				"size"	=> round($size/1000),
				);
			break;;
		case "darwin":
		case "linux":
		default:
			$result=cmdline("du -sk \"$path\"",false,3600);
			$line=$result[0];
			$line=preg_replace("#\s\s*#","\t",$line);
			list($size,$path)=explode("\t",$line);
			$result=Array(
				"OS"	=> $this->result["os"],
				"total"	=> $total,
				"size"	=> $size,
				);
		}
		//print_r($result);
		return $result;
	}

	//-------------------------------------------------------------
	function cpuinfo(){
		$numCpus = 1;

		$cpuinfo=Array(
			"bogomips" => 1000,
			"cores" => 1,
			"ghz" => 1,
			);
		switch(true){
		case $this->result["os"]=="winnt":
			// Windows machines
			trace("OSSensor::cpuinfo : Windows method: wmic");
			$cpuinfo["cores"]=$this->wmic("cpu get NumberOfCores","int",3600);
			$cpuinfo["ghz"]=round($this->wmic("cpu get MaxClockSpeed","int",3600)/1000,1);
			$cpuinfo["bogomips"]=$cpuinfo["cores"] * $cpuinfo["ghz"] * 1000;
			break;;
		case $this->result["os"]=="darwin":
			trace("OSSensor::cpuinfo : MacOSX method: sysctl");
			$cpuinfo["cores"]=$this->sysctl("hw.ncpu");
			$cpuinfo["ghz"]=round($this->sysctl("hw.cpufrequency")/1000000000,1);
			$cpuinfo["bogomips"]=$cpuinfo["cores"] * $cpuinfo["ghz"] * 1000;
			break;;
		case is_readable('/proc/cpuinfo'):
			// Linux machines
			// works on QNAP - but not on Synology (because of php permissions)
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
		default:
			trace("OSSensor::cpuinfo : no source for CPU info");
			$cpuinfo["cores"]=1;
			$cpuinfo["ghz"]=1200;
			$cpuinfo["bogomips"]=1200;
		}
		trace($cpuinfo);
		return $cpuinfo;
	}

	//-------------------------------------------------------------
	function uptime(){
		$uptime = 0;

		switch($this->result["os"]){
		case "winnt":
			// Windows machines
			// LastBootUpTime
			// 20150122133550.626398+060
			// 20150420094411.626398+120
			// 20151029154733.626398+060 => 2015-10-29 15:4733.626398+060
			trace("OSSensor::uptime : Windows method: wmic");
			$lastboot=$this->wmic("os get lastbootuptime","string",3600);
			$bdate=substr($lastboot,0,4)."-".substr($lastboot,4,2)."-".substr($lastboot,6,2)." ";
			$bdate.=substr($lastboot,8,2).":".substr($lastboot,10,2).":".substr($lastboot,12,2);
			trace("OSSensor::uptime: [$lastboot] => [$bdate]");
			$btime=strtotime($bdate);
			$since=time()-$btime;
			$sincedays=$since/(3600*24);
			switch(true){
			case $sincedays < 1:	$uptime=round($since/3600,1) . " hours";	break;;
			case $sincedays < 60:	$uptime=round($since/(3600*24),1) . " days";	break;;
			case $sincedays < 365:	$uptime=round($since/(3600*24*7),1) . " weeks";	break;;
			default:				$uptime=round($since/(3600*24*365),1) . " years";
			}
			break;;
		case "darwin":
		case "linux":
		case "busybox":
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

	//-------------------------------------------------------------
	function battery(){
		$result=Array();
		switch($this->result["os"]){
		case "winnt":
			// Windows machines
			trace("OSSensor::battery : Windows method: wmic");
			// to be implemented
			$result=false;
			break;;
		case "darwin":
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

		case "linux":
			// Linux machines
			$result["error"]="not yet implemented";
			break;;
		default:
			$result["error"]="not yet implemented";
			trace("OSSensor::uptime : unknown OS");
		}
		return $result;
	}

	//-------------------------------------------------------------
	private function wmic($command,$type="string",$cachesecs=30){
		if($this->result["os"]<>"winnt")	return false;
		$val=false;
		$return=cmdline("wmic $command",false,$cachesecs);

		if ($return){
			trace($return);
			switch($type){
			case "int":
			case "integer":
				$val = intval($return[1]);
				break;;
			default:
				$val = trim($return[1]);
			}
		}
		trace("OSTools::wmic: [$command] =>  [$val]");
		return $val;
	}

	private function winmgmts($command,$cachesecs=30){
		if($this->result["os"]<>"winnt")	return false;
		$val=false;

		if($cachesecs>0){
			$cc=New Cache;
			$result=$cc->get_arr($command,"winmgmts",$cachesecs);
			if($result)	return $result;
		}
		$wmi = new COM("Winmgmts://");
		trace("winmgmts: execute [$command]");
		$return = $wmi->execquery($command);
		if($cachesecs>0 AND $return){
			$cc=New Cache;
			$cc->set_arr($command,"winmgmts",$return);
		}
		//var_dump($return);
		return $return;
	}


	//-------------------------------------------------------------
	private function grepinfo($param,$default=false,$file="/proc/cpuinfo"){
		if($this->result["os"]=="winnt")	return false;
		if($this->result["os"]=="darwin")	return false;
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

	//-------------------------------------------------------------
	private function sysctl($key,$type="string"){
		if($this->result["os"]<>"darwin")	return false;
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

	//-------------------------------------------------------------
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

	//-------------------------------------------------------------
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

	//-------------------------------------------------------------
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
