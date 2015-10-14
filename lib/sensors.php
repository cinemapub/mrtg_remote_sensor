<?php
include_once("tools.php");
include_once("ostools.inc");

Class Sensor{

	var $params=Array();
	
	function __construct(){
		$ss=New OStools();
		$urlparts=Array();
		$key=getparam("key","cpu");
		$nameparts[]=$key;
		$percent=getparam("percent");
		if($percent){
			$urlparts[]="percent=1";
			$nameparts[]="p";
			}
		$this->params["server"]=$_SERVER['SERVER_NAME'];
		$nameparts[]=$this->digest($_SERVER['SERVER_NAME'],2);
		$urlparts[]="key=$key";
		$param=getparam("param");
		if($param){
			$urlparts[]="param=$param";
			$nameparts[]=$this->digest($param);
			}
		$options=getparam("options");
		if($options){
			$urlparts[]="options=$options";
			$nameparts[]=$this->digest($options);
			}
		$this->params["mrtg_name"]=implode(".",$nameparts);
		$url=(isset($_SERVER["https"]) ? "https://" : "http://" ) . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
		$this->params["url"]=$url . "?" . implode("&",$urlparts);
		$urlparts[]="config=1";
		$this->params["cfgurl"]=$url . "?" . implode("&",$urlparts);
		$this->params["uptime"]=$ss->uptime();
		$this->params["mrtg_options"]="growright,nobanner";
		$this->params["mrtg_kmg"]=",k,M,G,T,P";
		}

	function mrtg_output($params,$withconfig=false){
		header("Content-Type: text/plain; charset=utf-8");
		echo trim($params["value1"]) . "\n";
		echo trim($params["value2"]) . "\n";
		echo trim($params["uptime"]) . "\n";
		echo trim($params["server"]) . "\n";
		if(!$withconfig){
			echo $params["cfgurl"] . "\n";
		} else {
			$name=$params["mrtg_name"];
			$name=str_replace("%","p",$name);
			echo "#### MRTG CONFIG $name ####\n";
			echo "Target[$name]: `curl -s \"$params[url]\"`\n";
			echo "Title[$name]: $params[description]\n";
			echo "PageTop[$name]: <h1>$params[description]</h1>\n";
			echo "LegendI[$name]: $params[name1]\n";
			echo "LegendO[$name]: $params[name2]\n";
			echo "YLegend[$name]: $params[mrtg_unit]\n";
			echo "PNGTitle[$name]: $name\n";
			echo "ShortLegend[$name]: $params[mrtg_unit]\n";
			echo "Options[$name]: $params[mrtg_options]\n";
			echo "MaxBytes[$name]: $params[mrtg_maxbytes]\n";
			echo "kMG[$name]: $params[mrtg_kmg]\n";
		}
	}

	function cpuusage($aspercent=false){
	
	// Ubuntu:
	// 22:52:42 up 9 days, 14:21,  2 users,  load average: 0.00, 0.01, 0.05

	// BusyBox
	// 22:52:54 up  3:38, load average: 2.52, 2.19, 2.01

	// MacOSX  Darwin
	// 20:57  up 9 days, 20:41, 2 users, load averages: 1.75 1.74 1.57

		//$result=cmdline("uptime");
		$server=strtolower($this->params["server"]);
		$ss=New OStools();
		$result=$ss->cpuload();
		$cpuinfo=$ss->cpuinfo();
		$nbcpu=$cpuinfo["cores"];
		if($result){
			$load1=$result["1min"];
			$load5=$result["5min"];
			$load15=$result["15min"];
			if($cpuinfo["cores"] == 1){
				$this->params["server"]="$cpuinfo[ghz] GHz (bogomips $cpuinfo[bogomips])";
			} else {
				$this->params["server"]="$cpuinfo[cores] cores x $cpuinfo[ghz] GHz (bogomips $cpuinfo[bogomips])";
			}
			if(!$aspercent){
				$this->params["value1"]=$load5*100;
				$this->params["value2"]=$load15*100;
				$this->params["name1"]="Avg load over 5 min";
				$this->params["name2"]="Avg load over 15 min";
				$this->params["description"]="$server: CPU (5/15 min - $nbcpu CPUs)";
				$this->params["mrtg_unit"]="load";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=500*$cpuinfo["cores"];
				// max load = 5.0 
			} else {
				$this->params["value1"]=round($load5*100/$nbcpu,2);
				$this->params["value2"]=round($load15*100/$nbcpu,2);
				$this->params["name1"]="% used - 5 min";
				$this->params["name2"]="% used - 15 min";
				$this->params["description"]="$server: CPU% (5/15 min - $nbcpu CPUs)";
				$this->params["mrtg_unit"]="%";
				$this->params["mrtg_options"].=",gauge,nopercent";
				$this->params["mrtg_maxbytes"]=500;
			}
			return $this->params;
		} else {
			return false;
		}

	}

	function memusage($aspercent=false){
	// >free
	//               total         used         free       shared      buffers
	//   Mem:       249184       214376        34808            0        47724
	//  Swap:      2097144       188224      1908920
	// Total:      2346328       402600      1943728
		$ss=New OStools();
		$server=strtolower($this->params["server"]);
		$result=$ss->memusage();
		if($result){
			if(!$aspercent){
				$this->params["value1"]=$result["used"];
				$this->params["value2"]=$result["total"];
				$this->params["name1"]="Used RAM";
				$this->params["name2"]="Total RAM";
				$this->params["description"]="$server: Mem (used/total)";
				$this->params["mrtg_unit"]="B";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=$result["total"];
				$this->params["mrtg_kmg"]="k,M,G,T,P";
			} else {
				$this->params["value1"]=round($result["used"]*100/$result["total"],2);
				$this->params["value2"]=100;
				$this->params["name1"]="% RAM used";
				$this->params["name2"]="100%";
				$this->params["description"]="$server: Mem %";
				$this->params["mrtg_unit"]="%";
				$this->params["mrtg_options"].=",gauge,nopercent";
				$this->params["mrtg_maxbytes"]=100;
			}
			return $this->params;
		} else {
			return false;
		}
	}

	function battery($type=""){
		/*
		Array
		(
		    [battery_mamp] =>  319
		    [battery_capacity] =>  6214
		    [battery_charge] =>  6151
		    [battery_charge_%] => 0.99
		    [battery_cycles] =>  22
		    [battery_health] =>  Normal
		    [battery_present] => 1
		    [battery_mvolt] => 12880
		    [charger_busy] => 1
		    [charger_done] => 0
		    [charger_present] => 1
		    [charger_watt] =>  85
		)
		*/
		$ss=New OStools();
		$server=strtolower($this->params["server"]);
		$result=$ss->battery();
		if($result){
			$line=trim($result[0]);
			$line=preg_replace("#\s\s*#","\t",$line);
			trace("battery: get data of type [$type]");
			switch($type){
				case "-":
					$this->params["value1"]=$result["battery_capacity"]-$result["battery_charge"];
					$this->params["value2"]=$result["battery_capacity"];
					$this->params["name1"]="Battery consumed Ah";
					$this->params["name2"]="Battery maximum Ah";
					$this->params["description"]="$server: Battery charge";
					$this->params["mrtg_unit"]="Ah";
					$this->params["mrtg_options"].=",gauge";
					$this->params["mrtg_maxbytes"]=$this->params["value2"];
					$this->params["mrtg_kmg"]=",k,M,G,T,P";
					break;;

				case "%":
					$this->params["value1"]=$result["battery_charge_%"];
					$this->params["value2"]=$result["charger_busy"]*100;
					$this->params["name1"]="Battery charge %";
					$this->params["name2"]="Charger active";
					$this->params["description"]="$server: Battery charge %";
					$this->params["mrtg_unit"]="%";
					$this->params["mrtg_options"].=",gauge";
					$this->params["mrtg_maxbytes"]=2;
					break;;

				case "V":
					$this->params["value1"]=$result["battery_mvolt"];
					$this->params["value2"]="";
					$this->params["name1"]="Battery voltage";
					$this->params["name2"]="";
					$this->params["description"]="$server: Battery voltage";
					$this->params["mrtg_unit"]="V";
					$this->params["mrtg_options"].=",gauge,noo";
					$this->params["mrtg_maxbytes"]=15000;
					$this->params["mrtg_kmg"]="m,,k,M,G,T";
				break;;

				case "A":
					$this->params["value1"]=$result["battery_mamp"];
					$this->params["value2"]="";
					$this->params["name1"]="Battery ampere";
					$this->params["name2"]="";
					$this->params["description"]="$server: Battery ampere";
					$this->params["mrtg_unit"]="V";
					$this->params["mrtg_options"].=",gauge,noo";
					$this->params["mrtg_maxbytes"]=15000;
					$this->params["mrtg_kmg"]=",k,M,G,T,P";
				break;;

				case "C":
					$this->params["value1"]=$result["battery_cycles"];
					$this->params["value2"]="";
					$this->params["name1"]="Battery cycles";
					$this->params["name2"]="";
					$this->params["description"]="$server: Battery cycles";
					$this->params["mrtg_unit"]="#";
					$this->params["mrtg_options"].=",gauge,noo";
					$this->params["mrtg_maxbytes"]=15000;
					$this->params["mrtg_kmg"]="m,,k,M,G,T";
				break;;

				default:
					$this->params["value1"]=$result["battery_charge"];
					$this->params["value2"]=$result["battery_capacity"];
					$this->params["name1"]="Battery available Ah";
					$this->params["name2"]="Battery maximum Ah";
					$this->params["description"]="$server: Battery charge";
					$this->params["mrtg_unit"]="Ah";
					$this->params["mrtg_options"].=",gauge";
					$this->params["mrtg_maxbytes"]=$this->params["value2"];
					$this->params["mrtg_kmg"]=",k,M,G,T,P";

			}
			$this->params["uptime"]="Health: $result[battery_health] after $result[battery_cycles] charging cycles";
			if(!$aspercent){
				if(!$inverse){
				} else {
				}
			} else {
			}
			return $this->params;
		} else {
			return false;
		}
	}

	function diskusage($path=false,$aspercent=false){
	// >df
	// Filesystem           1K-blocks      Used Available Use% Mounted on
	// /dev/sda3            1918213808  80720900 1837390508   4% /volume1
		if(!$path)	$path=".";
		if(!file_exists($path)){
			trace("diskusage: cannot find [$path]");
			//return false;
		}
		$ss=New OStools();
		$result=$ss->diskusage($path);
		$server=strtolower($this->params["server"]);
		//trace($result);
		if($result){
			if(!$aspercent){
				$this->params["value1"]=$result["used"];
				$this->params["value2"]=$result["total"];
				$this->params["name1"]="Used disk space";
				$this->params["name2"]="Total disk space";
				$this->params["description"]="$server: Disk (used/total) [$path]";
				$this->params["mrtg_unit"]="B";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=$result["total"];
				$this->params["mrtg_kmg"]="k,M,G,T,P";
			} else {
				$this->params["value1"]=round($result["used"]*100/$result["total"],2);
				$this->params["value2"]=100;
				$this->params["name1"]="Used disk %";
				$this->params["name2"]="100%";
				$this->params["description"]="$server: Disk usage % [$path]";
				$this->params["mrtg_unit"]="%";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=100;
			}
			return $this->params;
		} else {
			return false;
		}
	}

	function foldersize($folder,$options){
	// 1043015852032   /share/MASTER/MASTER/
		trace("foldersize:  checking [$folder]");
		if(!file_exists($folder)){
			trace("foldersize: cannot find [$folder]");
			return false;
		}
		$ss=New OStools();
		$result=$ss->foldersize($folder);
		if($result){
			$this->params["value1"]=$result["size"];
			$this->params["value2"]=$result["total"];
			$this->params["name1"]="Folder size";
			$this->params["name2"]="Total disk size";
			$this->params["description"]="Folder Size [$folder]";
			$this->params["mrtg_unit"]="B";
			$this->params["mrtg_options"].=",gauge,noo";
			$this->params["mrtg_maxbytes"]=$result["total"];
			$this->params["mrtg_kmg"]="k,M,G,T,P";
			return $this->params;
		} else {
			return false;
		}
	}

	function filecount($folder,$options){
		if(!file_exists($folder)){
			return false;
		}
		$findopt="";
		$params=$this->parse_options($options);
		if(!$params["recursive"])	$findopt.="-maxdepth 1 ";
		if($params["mtime"]) $findopt.="-mtime " . $params["mtime"] . " ";
		if($params["name"]) $findopt.="-name " . $params["name"] . " ";
		$findopt.="-type f ";
		$result=cmdline("find $folder $findopt | wc -l");
		if($result){
			$line=$result[0];
			$nb=(int)trim($line);
			$desc="File count [$folder]";
			if($options)	$desc.=" [$options]";
			$this->params["value1"]=$nb;
			$this->params["value2"]="0";
			$this->params["name1"]=$desc;
			$this->params["name2"]="";
			$this->params["description"]=$desc;
			$this->params["mrtg_unit"]="file(s)";
			$this->params["mrtg_options"].=",gauge,noo,nopercent";
			$this->params["mrtg_maxbytes"]=1000000;
			$this->params["mrtg_kmg"]=",k,M,G,T,P";
			return $this->params;
		} else {
			return false;
		}
	}
	
	function proccount($filter=false){
		$server=strtolower($this->params["server"]);
		$ss=New OStools();
		$result=$ss->proccount($filter);
		if($result){
			$server=strtolower($this->params["server"]);
			$desc="$server: server load";
			$descf=$desc;
			if($filter)	$descf.=" [$filter]";
			$filtered=$result["filtered"];
			$total=$result["total"];
			$this->params["value1"]=$filtered;
			$this->params["value2"]=$total;
			$this->params["name1"]=$descf;
			$this->params["name2"]=$desc;
			$this->params["description"]=$desc;
			$this->params["mrtg_unit"]="proc";
			$this->params["mrtg_options"].=",gauge,noo,nopercent";
			$this->params["mrtg_maxbytes"]=1000000;
			$this->params["mrtg_kmg"]=",k,M,G,T,P";
			return $this->params;
		} else {
			return false;
		}
	}

	function pingtime($address,$port=80){
		if(!$address)	return false;
		for($i=0;$i<4;$i++){
			$result[$i]=$this->tcpping($address,$port);
		}
                if($result){
                	//print_r($result);
					if($port==80){
						$desc="ping time to $address";
					} else {
						$desc="ping time to $address:$port";
					}
					$min=min($result);
					$max=max($result);
                    $this->params["value1"]=(int)$min;
                    $this->params["value2"]=(int)$max;
                    $this->params["name1"]="MIN $desc";
                    $this->params["name2"]="MAX $desc";
                    $this->params["description"]=$desc;
                    $this->params["mrtg_unit"]="sec";
                    $this->params["mrtg_options"].=",gauge,nopercent";
                    $this->params["mrtg_maxbytes"]=1000000000;
                    $this->params["mrtg_kmg"]="u,m,,k,M,G";
                    return $this->params;
                } else {
                        return false;
                }
	}

	function foldercount($folder,$options){
		if(!file_exists($folder)){
			return false;
		}
		$findopt="";
		$params=$this->parse_options($options);
		if(!$params["recursive"])	$findopt.="-maxdepth 1 ";
		if($params["mtime"]) $findopt.="-mtime " . $params["mtime"] . " ";
		if($params["name"]) $findopt.="-name " . $params["name"] . " ";
		$findopt.="-type d ";
		$result=cmdline("find $folder $findopt | wc -l");
		if($result){
			$line=$result[0];
			$nb=(int)trim($line);
			$desc="Folder count [$folder]";
			if($options)	$desc.=" [$options]";
			$this->params["value1"]=$nb;
			$this->params["value2"]="0";
			$this->params["name1"]=$desc;
			$this->params["name2"]="";
			$this->params["description"]=$desc;
			$this->params["mrtg_unit"]="folder(s)";
			$this->params["mrtg_options"].=",gauge,noo,nopercent";
			$this->params["mrtg_maxbytes"]=1000000;
			$this->params["mrtg_kmg"]=",k,M,G,T,P";
			return $this->params;
		} else {
			return false;
		}
	}

	// ---------- INTERNAL FUNCTIONS

	function digest($text,$length=4){
		return substr(sha1($text),0,$length);
	}
	function parse_options($options){
		// parse key1=val1,key2=val2,key3
		if(!$options)	return false;
		$results=Array();
		trace("parse_options: $options");
		$params=explode(",",$options);
		foreach($params as $param){
			$param=$this->sanitize($param);
			if(strstr($param,"=")){
				list($key,$val)=explode("=",$param,2);
			} else {
				$key=$param;
				$val=$param;
			}
			$result[$key]=$val;
			trace("Option: [$key] = [$val]");
		}
		return $result;	
	}
	
	function parse_cmd($cmd,$folder=false,$cachesecs=300,$linenr=1,$fieldnr=1){
		// to replace awk, tail, head, ...
		$result=cmdline($cmd,$folder,$cachesecs);
	}


	function sanitize($text){
		// remove all nasty stuff before passing to bash/sh
		$result=$text;
		$result=str_replace(Array(";",'"'),"",$result);
		return $result;
	}
	
	function tcpping($ip,$port){
		$timeout=4;
		$t1 = microtime(true); 
		$fP = fSockOpen($ip, $port, $errno, $errstr, $timeout); 
		if (!$fP) { 
			trace("tcpping: cannot connect to $ip:$port - $errstr");
			return 6666; 
			}
		$t2 = microtime(true); 
		//trace("[$ip:$port] - $t1 - $t2");
		return round((($t2 - $t1) * 1000000)); 
	}
}

?>
