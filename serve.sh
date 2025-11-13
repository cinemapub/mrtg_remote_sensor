#!/usr/bin/env bash
(
sleep 1
open http://localhost:8080/overview.html
) &
php -S localhost:8080 -t public