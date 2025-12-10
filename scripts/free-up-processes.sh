#!/bin/bash

# Script to free up processes on the server
# Run this via SSH: ssh -p 21098 scepgtce@server254.web-hosting.com "bash -s" < free-up-processes.sh

echo "🔍 Checking current processes..."
PROCESS_COUNT=$(ps aux | wc -l)
echo "Current process count: $PROCESS_COUNT"

echo ""
echo "📋 Top processes by CPU usage:"
ps aux --sort=-%cpu | head -11

echo ""
echo "🔍 Checking for Node.js/Next.js processes:"
NODE_PROCS=$(ps aux | grep -E 'node|next|npm' | grep -v grep)
if [ -z "$NODE_PROCS" ]; then
    echo "No Node.js processes found"
else
    echo "$NODE_PROCS"
fi

echo ""
echo "🔍 Checking for PM2 processes:"
if command -v pm2 &> /dev/null; then
    pm2 list
    echo ""
    echo "💡 To stop PM2 processes, run: pm2 stop all"
    echo "💡 To delete PM2 processes, run: pm2 delete all"
else
    echo "PM2 not installed or not in PATH"
fi

echo ""
echo "🔍 Checking for zombie processes:"
ZOMBIES=$(ps aux | grep -c ' Z ')
echo "Zombie processes: $ZOMBIES"

echo ""
echo "💡 To kill specific processes, use: kill <PID>"
echo "💡 To kill all Node.js processes: pkill -f node"
echo "💡 To see all your processes: ps aux | grep scepgtce"

