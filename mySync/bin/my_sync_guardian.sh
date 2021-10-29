#!/bin/bash
trap "pkill -P $$ --signal SIGHUP; pkill -9 -f \"sleep 86400\"; exit" SIGINT SIGHUP
# Start only when deamon is not running
while true; do
    PID=$(pgrep my_sync_de)
    if [ "${PID}" == "" ]; then
        break
    fi
    sleep 10 &
    wait $!
done

# Guard
while true; do
    PID=$(pgrep my_sync_de)
    if [ "${PID}" == "" ]; then
        ./my_sync_deamon.sh > /dev/null &
    fi
    sleep 86400 &
    wait $!
done
