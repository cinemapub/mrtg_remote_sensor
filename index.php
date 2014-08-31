<?php
include("lib/settings.inc");
$debug=getparam("debug");

$key=strtolower(getparam("key","cpu"));

$param=getparam("param");
$config=getparam("config");
$options=getparam("options");

$s=New Sensor;

$values=false;
$debug=getparam("debug",false);
switch($key){
	case "cpu":	$values=$s->cpuusage();				break;
	case "cpu%":  	$values=$s->cpuusage(true);			break;
		
	case "mem":	$values=$s->memusage();				break;
	case "mem%":	$values=$s->memusage(true);			break;
		
	case "disk":	$values=$s->diskusage($param);		break;
	case "disk%":	$values=$s->diskusage($param,true);	break;
		
	case "proc":	$values=$s->proccount($param);        break;

	case "folder":
	case "foldersize":	$values=$s->foldersize($param,$options);	break;

	case "filecount":	$values=$s->filecount($param,$options);		break;

	case "foldercount":	$values=$s->foldercount($param,$options);	break;
	
	case "pingtime":	$values=$s->pingtime($param);	break;
		
	default: 	echo "Unknown key [$key]";
}
if($values){
	if($config){
		$s->mrtg_output($values,true);
	} else {
		$s->mrtg_output($values);
	}
} else {
	echo "Key [$key] has no results";
}

?>
