#!/bin/bash
# Simple script for checking if the hosts are up and running

hosts=(
    "192.168.10.2"
    "192.168.10.3"
    "media.hahatay"
    "server.hahatay"
    # TODO Add remaining domains/IPs
)

for host in "${hosts[@]}"; do
    ping -c 1 $host -w 2 >/dev/null

    if [ $? -eq 0 ]; then
        printf "\xE2\x9C\x94 $host is up\n"
    else
        printf "\xE2\x9D\x8C $host is down\n"
    fi

done
