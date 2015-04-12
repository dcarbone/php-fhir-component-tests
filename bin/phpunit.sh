#!/usr/bin/env sh
BIN_TARGET="`pwd`/../phpunit/phpunit/phpunit"
"$BIN_TARGET" --configuration phpunit.dist.xml
