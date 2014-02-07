<?php
include_once("tools.php");
include_once("mrtg.php");

Class MRTGResult{

	var $value1=false;
	var $value2=false;
	var $uptime=uptime;
	var $name=false;
	var $unit=false;

	function prepare(){
		header("Content-Type: text/plain; charset=utf-8");
	}


	function set($value1=false,$value2=false){
		$this->value1=$value1;
		$this->value2=$value2;
		$this->uptime="";
		$this->name="";
	}

	function output(){
		echo $this->value1 . "\n";
		echo $this->value1 . "\n";
		echo $this->uptime . "\n";
		echo $this->name . "\n";
	}
}

?>