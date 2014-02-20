<?php
include_once("tools.php");

Class Sensor{

	var $params=Array();
	
	function __construct(){
		$urlparts=Array();
		$nameparts[]=$_SERVER['SERVER_NAME'];
		$this->params["server"]=$_SERVER['SERVER_NAME'];
		$key=getparam("key","cpu");
		$nameparts[]=$key;
		$urlparts[]="key=$key";
		$param=getparam("param");
		if($param){
			$urlparts[]="param=$param";
			$nameparts[]=basename($param);
			}
		$percent=getparam("percent");
		if($percent){
			$urlparts[]="percent=1";
			$nameparts[]="p";
			}
		$this->params["mrtg_name"]=implode(".",$nameparts);
		$url=($_SERVER["https"] ? "https://" : "http://" ) . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
		$this->params["url"]=$url . "?" . implode("&",$urlparts);
		$urlparts[]="config=1";
		$this->params["cfgurl"]=$url . "?" . implode("&",$urlparts);
		$this->params["uptime"]=$this->uptime();
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

		$result=cmdline("uptime");
		trace($result);
		$cpus=cmdline('grep cpu /proc/stat | grep -v "cpu "',false,3600*24);
		//$cpus=cmdline('grep "model name" /proc/cpuinfo',false,3600*24);
		trace($cpus);
		$nbcpu=count($cpus);
		if(!$nbcpu)	$nbcpu=1;
		$cpumodel=$cpus[0];
		if($result){
			$line=$result[0];
			list($uptime,$loads)=explode("load average:",$line,2);
			if(!$loads)	return false;
			list($load1,$load5,$load15)=explode(",",$loads);
			if(!$aspercent){
				$this->params["value1"]=$load5*1000/$nbcpu;
				$this->params["value2"]=$load15*1000/$nbcpu;
				$this->params["name1"]="Avg load over 5 min";
				$this->params["name2"]="Avg load over 15 min";
				$this->params["description"]="CPU usage (5/15 min)";
				$this->params["mrtg_unit"]="load";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]="1000";
			} else {
				$this->params["value1"]=round($load5*100/$nbcpu,2);
				$this->params["value2"]=round($load15*100/$nbcpu,2);
				$this->params["name1"]="% used - 5 min";
				$this->params["name2"]="% used - 15 min";
				$this->params["description"]="CPU usage % (5/15 min)";
				$this->params["mrtg_unit"]="%";
				$this->params["mrtg_options"].=",gauge,nopercent";
				$this->params["mrtg_maxbytes"]="100";
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
		$result=cmdline("free | grep Mem");
		if($result[0]){
			$line=trim($result[0]);
			$line=preg_replace("#\s\s*#","\t",$line);
			list($mem,$total,$used,$free,$percent,$shared,$buffers)=explode("\t",$line);
			if(!$aspercent){
				$this->params["value1"]=$used;
				$this->params["value2"]=$total;
				$this->params["name1"]="Used RAM";
				$this->params["name2"]="Total RAM";
				$this->params["description"]="Memory usage (used/total)";
				$this->params["mrtg_unit"]="B";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=$total;
				$this->params["mrtg_kmg"]="k,M,G,T,P";
			} else {
				$this->params["value1"]=round($used*100/$total,2);
				$this->params["value2"]=100;
				$this->params["name1"]="% RAM used";
				$this->params["name2"]="100%";
				$this->params["description"]="Memory usage (%)";
				$this->params["mrtg_unit"]="%";
				$this->params["mrtg_options"].=",gauge,nopercent";
				$this->params["mrtg_maxbytes"]=100;
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
			return false;
		}
		$result=cmdline("df -k $path");
		trace($result);
		if($result[1]){
			$line=$result[1];
			$line=preg_replace("#\s\s*#","\t",$line);
			list($disk,$blocks,$used,$available,$percent,$mounted)=explode("\t",$line);
			if(!$aspercent){
				$this->params["value1"]=$used;
				$this->params["value2"]=$blocks;
				$this->params["name1"]="Used disk space";
				$this->params["name2"]="Total disk space";
				$this->params["description"]="Disk Usage (used/total) [$disk]";
				$this->params["mrtg_unit"]="B";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=$blocks;
				$this->params["mrtg_kmg"]="k,M,G,T,P";
			} else {
				$this->params["value1"]=round($used*100/$blocks,2);
				$this->params["value2"]=100;
				$this->params["name1"]="Used disk %";
				$this->params["name2"]="100%";
				$this->params["description"]="Disk Usage (%) [$disk]";
				$this->params["mrtg_unit"]="%";
				$this->params["mrtg_options"].=",gauge";
				$this->params["mrtg_maxbytes"]=100;
			}
			return $this->params;
		} else {
			return false;
		}
	}

	function foldersize($folder){
	// 1043015852032   /share/MASTER/MASTER/
		if(!file_exists($folder)){
			trace("foldersize: cannot find [$path]");
			return false;
		}
		$result=cmdline("du -s -k $folder",false,60*15);
		if($result){
			$line=$result[0];
			$line=preg_replace("#\s\s*#","\t",$line);
			list($size,$path)=explode("\t",$line);
			if(!$aspercent){
				$this->params["value1"]=$size;
				$this->params["value2"]="0";
				$this->params["name1"]="Folder size";
				$this->params["name2"]="";
				$this->params["description"]="Folder Size [$folder]";
				$this->params["mrtg_unit"]="B";
				$this->params["mrtg_options"].=",gauge,noo,nopercent";
				$this->params["mrtg_maxbytes"]=1000000000;
				$this->params["mrtg_kmg"]="k,M,G,T,P";
			} else {
				$this->params["value1"]=$size;
				$this->params["value2"]="0";
				$this->params["name1"]="Folder size";
				$this->params["name2"]="";
				$this->params["description"]="Folder Size [$folder]";
				$this->params["mrtg_unit"]="B";
				$this->params["mrtg_options"].=",gauge,noo,nopercent";
				$this->params["mrtg_maxbytes"]=1000000000;
				$this->params["mrtg_kmg"]="k,M,G,T,P";
			}
			return $this->params;
		} else {
			return false;
		}
	}

	function filecount($folder,$recursive=false){
		if(!file_exists($folder)){
			return false;
		}
		$options="";
		if(!$recursive)	$options.="-maxdepth 1 ";
		$options.="-type f ";
		$result=cmdline("find $folder $options | wc -l");
		if($result){
			$line=$result[0];
			$nb=(int)trim($line);
			return Array(
				"value1" => $nb, 
				"name1" => "number of files", 
				"value2" => false,
				"name2" => "", 
				"unit" => "files",
				"description" =>"file count [$folder]");
		} else {
			return false;
		}
	}

	function foldercount($folder){
		if(!file_exists($folder)){
			return false;
		}
		$options="";
		if(!$recursive)	$options.="-maxdepth 1 ";
		$options.="-type d ";
		$result=cmdline("find $folder $options | wc -l");
		if($result){
			$line=$result[0];
			$nb=(int)trim($line);
			return Array(
				"value1" => $nb, 
				"name1" => "number of subfolders", 
				"value2" => false,
				"name2" => "", 
				"unit" => "folders",
				"description" =>"folder count [$folder]");
		} else {
			return false;
		}
	}
	
	function uptime(){
		$result=cmdline("</proc/uptime awk '{print $1}'");
		$nbsecs=$result[0];
		if($nbsecs > 60*60*24){
			return round($nbsecs/(60*60*24),1) . " days";
		} else {
			return round($nbsecs/(60*60),1) . " hours";
		}
	}
	
	function parse_cmd($cmd,$folder=false,$cachesecs=300,$linenr=1,$fieldnr=1){
	// to replace awk, tail, head, ...
	$result=cmdline($cmd,$folder,$cachesecs);
	
	}
}

?>