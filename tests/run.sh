#!/bin/bash

if [ $# == 0 ]; then
	sh ../vendor/bin/tester -s -c ./php.ini .
else
	sh ../vendor/bin/tester -s -c ./php.ini $1
fi
