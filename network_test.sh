#!/bin/bash
# Simple script for checking if the hosts are up and running

hosts=("127.0.0.3", "123.123.123.123", "127.0.0.1")

for host in "${hosts[@]}"; do
    ping -c 1 $host -w 2 > /dev/null

    if [ $? -eq 0 ]; then
        printf "\xE2\x9C\x94 $host is up\n"
    else
        printf "\xE2\x9D\x8C $host is down\n"
    fi

done

# TODO Try also to ping the domains
