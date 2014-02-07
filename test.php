<?php
include("lib/settings.inc");
$s=New MRTGResult;
$s->prepare();
$debug=true;
echo "Running as:\n";
print_r(posix_getpwuid(posix_getuid()));
print_r(cmdline("whoami"));
print_r(cmdline("pwd"));
print_r(cmdline("df"));
print_r(cmdline("free"));

?>