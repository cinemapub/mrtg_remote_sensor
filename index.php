<?php
include("lib/settings.inc");
$debug=getparam("debug");

$percent=getparam("percent");
$key=strtolower(getparam("key","cpu"));
if($percent)	$key.="%";

$param=getparam("param");
$config=getparam("config");

$s=New MRTGResult;
$s->prepare();

switch($key){
case "cpu":
	$values=Sensor::cpuusage();
	$s->set($values["value1"],$values["value2"],$values["description"]);
	break;
case "cpu%":
	$values=Sensor::cpuusage(true);
	$s->set($values["value1"],$values["value2"],$values["description"]);
	break;
	
case "mem":
	$values=Sensor::memusage();
	$s->set($values["value1"],$values["value2"],$values["description"]);
	break;
case "mem%":
	$values=Sensor::memusage(true);
	$s->set($values["value1"],$values["value2"],$values["description"]);
	break;
	
case "disk":
	$values=Sensor::diskusage($param);
	$s->set($values["value1"],$values["value2"],$values["description"] );
	break;
case "disk%":
	$values=Sensor::diskusage($param,true);
	$s->set($values["value1"],$values["value2"],$values["description"] );
	break;
	
case "folder":
	$values=Sensor::foldersize($param);
	$s->set($values["value1"],$values["value2"],$values["description"] );
	break;
	
default:
	$s->set(0,0);
}
if($config){
	$s->output(true);
} else {
	$s->output();
}

?>