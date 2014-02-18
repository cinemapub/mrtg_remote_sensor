<?php
include_once("tools.php");
include_once("mrtg.php");

Class MRTGResult{

	var $value1=false;
	var $value2=false;
	var $uptime=uptime;
	var $name=false;
	var $unit=false;
	var $cfgurl=false;

	function prepare(){
		header("Content-Type: text/plain; charset=utf-8");
		$params=Array();
		$key=getparam("key","cpu");
		if($key)	$params[]="key=$key";
		$param=getparam("param");
		if($param)	$params[]="param=$param";
		$url=($_SERVER["https"] ? "https://" : "http://" ) . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
		$this->url=$url . "?" . implode("&",$params);
		$params[]="config=1";
		$this->cfgurl=$url . "?" . implode("&",$params);
		$this->uptime=Sensor::uptime();
	}


	function set($value1=false,$value2=false,$description=false){
		$this->value1=$value1;
		$this->value2=$value2;
		$this->name=$description;
	}

	function output($withconfig=false){
		echo trim($this->value1) . "\n";
		echo trim($this->value2) . "\n";
		echo trim($this->uptime) . "\n";
		echo trim($this->name) . "\n";
		if(!$withconfig){
			echo trim($this->cfgurl) . "\n";
		} else {
			echo "----- MRTG CONFIG ------\n";
			$key=getparam("key","cpu");
			$name=getparam("name",$key);
			echo "Target[$name]=`curl -s \"$this->url\"`\n";
			echo "Title[$name]=$this->name\n";
			echo "PageTop[$name]=<h1>$this->name</h1>\n";
			echo "Legend[$name]=\n";
			echo "ShortLegend[$name]=\n";
			echo "Options[$name]=growright,gauge\n";
			echo "MaxBytes[$name]=100000000\n";
		}
	}
}

?>