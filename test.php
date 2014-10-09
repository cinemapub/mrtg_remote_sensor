<?php
include("lib/settings.inc");
$debug=true;
//trace("Running as:");
//print_r(posix_getpwuid(posix_getuid()));
$s=New Sensor();
$ss=New OSSensor();
//trace(getparam("test","ok"));

//print_r(cmdline("whoami"));
trace($ss->battery());
echo "---------------- DISK";
print_r($s->diskusage("."));
echo "---------------- CPU";
print_r($s->cpuusage(true));
echo "---------------- MEM";
print_r($s->memusage());
print_r($s->foldersize("cache"));
print_r($s->foldercount("."));
print_r($s->filecount(".",true));


?>
