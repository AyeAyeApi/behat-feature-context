#!/usr/bin/env bash

PID_DIR="/tmp/mock-api"
PID_FILE="${PID_DIR}/pid"

setup ()
{
    mkdir -p ${PID_DIR}
    chmod -R u+rw ${PID_DIR}
}

start ()
{
    if [ "started" == `status` ]
    then
        echo "Already started"
    else
        php -S localhost:8000 -t . index.php > /dev/null &
        echo $! > ${PID_FILE}
        status
    fi
}

stop ()
{
    if [ "stopped" == `status` ]
    then
        echo "Already stopped"
    else
        kill `cat ${PID_FILE}`
        status
    fi
}

status ()
{
    if ps -p `cat ${PID_FILE} 2> /dev/null` > /dev/null 2>&1
    then
        echo "started"
    else
        echo "stopped"
        rm ${PID_FILE} > /dev/null 2>&1
    fi
}

restart ()
{
    stop
    start
}


# ENTRY POINT

setup

case "$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    status)
        status
        ;;
    restart)
        stop
        start
        ;;
    *)
        echo $"Usage: $0 {start|stop|restart|status}"
        exit 1
esac
