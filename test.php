<?php
include("lib/settings.inc");
$s=New MRTGResult;
$s->prepare();
$debug=true;
trace("Running as:");
print_r(posix_getpwuid(posix_getuid()));

trace(getparam("test","ok"));

print_r(cmdline("whoami"));
print_r(Sensor::diskusage("."));
print_r(Sensor::cpuusage(true));
print_r(Sensor::memusage());
print_r(Sensor::foldersize("cache"));
print_r(Sensor::foldercount("."));
print_r(Sensor::filecount(".",true));
?>