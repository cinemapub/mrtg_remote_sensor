<?php
include("lib/settings.inc");
$debug=getparam("debug");

$percent=getparam("percent");
$key=strtolower(getparam("key","cpu"));
if($percent)	$key.="%";

$param=getparam("param");
$config=getparam("config");
$options=getparam("options");

$s=New Sensor;

switch($key){
	case "cpu":		$values=$s->cpuusage();				break;
	case "cpu%":	$values=$s->cpuusage(true);			break;
		
	case "mem":		$values=$s->memusage();				break;
	case "mem%":	$values=$s->memusage(true);			break;
		
	case "disk":	$values=$s->diskusage($param);		break;
	case "disk%":	$values=$s->diskusage($param,true);	break;
		
	case "folder":
	case "foldersize":	$values=$s->foldersize($param,$options);	break;

	case "filecount":	$values=$s->filecount($param,$options);		break;

	case "foldercount":	$values=$s->foldercount($param,$options);	break;
		
	default:
}
if($config){
	$s->mrtg_output($values,true);
} else {
	$s->mrtg_output($values);
}

?>
