<?php
include_once("tools.php");

Class Sensor{

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
				return Array(
					"value1" => $load5, 
					"name1" => "Avg load over 5 min", 
					"value2" => $load15,
					"name2" => "Avg load over 15 min", 
					"unit" => "processes",
					"description" => "CPU usage (5/15 min)");
			} else {
				return Array(
					"value1" => round($load5*100/$nbcpu,2), 
					"name1" => "% used - 5 min", 
					"value2" => round($load15*100/$nbcpu,2), 
					"name2" => "% used - 15 min", 
					"unit" => "%",
					"description" => "CPU usage % (5/15 min)");
			}
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
				return Array(
					"value1" => $used, 
					"name1" => "Used bytes", 
					"value2" => $total,
					"name2" => "Total bytes", 
					"unit" => "B",
					"description" =>"Memory usage (used/total)");
			} else {
				return Array(
					"value1" => round($used*100/$total,2), 
					"name1" => "Used percentage", 
					"value2" => 100, 
					"unit" => "%",
					"description" => "Memory usage (%)");
			}
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
				return Array(
					"value1" => $used, 
					"name1" => "Used bytes", 
					"value2" => $blocks,
					"name2" => "Total bytes", 
					"unit" => "B",
					"description" =>"Disk Usage (used/total) [$disk]");
			} else {
				return Array(
					"value1" => round($used*100/$blocks,2), 
					"name1" => "Used percentage", 
					"value2" => 100, 
					"unit" => "%",
					"description" => "Disk Usage % [$disk]");
			}
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
		$result=cmdline("du -s -B 1 $folder",false,60*15);
		if($result){
			$line=$result[0];
			$line=preg_replace("#\s\s*#","\t",$line);
			list($size,$path)=explode("\t",$line);
			if(!$aspercent){
				return Array(
					"value1" => $size, 
					"name1" => "folder size", 
					"value2" => false,
					"name2" => "", 
					"unit" => "B",
					"description" =>"Folder Size [$folder]");
			} else {
				$usage=$this->diskusage($folder);
				$total=$usage["value2"];
				return Array(
					"value1" => round($size*100/$total,2), 
					"name1" => "Used percentage", 
					"value2" => 100, 
					"unit" => "%",
					"description" => "Folder Size [$folder]");
			}
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
}

?>