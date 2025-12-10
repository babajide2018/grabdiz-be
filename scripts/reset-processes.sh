#!/bin/bash

# Script to reset and clean up processes on the server
# Run this via SSH: ssh -p 21098 scepgtce@server254.web-hosting.com 'bash -s' < reset-processes.sh
# Or upload to server and run: bash reset-processes.sh

echo "🧹 Process Cleanup Script"
echo "=========================="
echo ""

# Show current process count
CURRENT_COUNT=$(ps aux | wc -l)
echo "📊 Current process count: $CURRENT_COUNT / 200 ($(( CURRENT_COUNT * 100 / 200 ))%)"
echo ""

# 1. Kill stuck rsync processes
echo "1️⃣  Cleaning up rsync processes..."
RSYNC_COUNT=$(ps aux | grep rsync | grep -v grep | wc -l)
if [ $RSYNC_COUNT -gt 0 ]; then
    echo "   Found $RSYNC_COUNT rsync process(es)"
    pkill -f rsync 2>/dev/null && echo "   ✅ Killed rsync processes" || echo "   ⚠️  Failed to kill rsync processes"
    sleep 1
else
    echo "   ✅ No rsync processes found"
fi
echo ""

# 2. Restart PM2 (if using)
echo "2️⃣  Restarting PM2 processes..."
if command -v pm2 &> /dev/null; then
    echo "   PM2 status before restart:"
    pm2 list
    echo ""
    echo "   Restarting all PM2 processes..."
    pm2 restart all 2>/dev/null && echo "   ✅ PM2 restarted successfully" || echo "   ⚠️  PM2 restart failed"
    sleep 2
    echo "   PM2 status after restart:"
    pm2 list
else
    echo "   ⚠️  PM2 is not installed or not in PATH"
fi
echo ""

# 3. Check for multiple Node.js processes
echo "3️⃣  Checking Node.js processes..."
NODE_PROCESSES=$(ps aux | grep -E "node.*next|node.*start" | grep -v grep)
NODE_COUNT=$(echo "$NODE_PROCESSES" | wc -l)
echo "   Found $NODE_COUNT Node.js process(es):"
echo "$NODE_PROCESSES" | awk '{printf "   PID: %s - %s\n", $2, $11}'
echo ""

# 4. Kill orphaned Node.js processes (if too many)
if [ $NODE_COUNT -gt 5 ]; then
    echo "   ⚠️  Too many Node.js processes detected ($NODE_COUNT)"
    echo "   Killing orphaned processes..."
    pkill -f "node.*next" 2>/dev/null && echo "   ✅ Killed orphaned Node.js processes" || echo "   ⚠️  Failed to kill processes"
    sleep 2
    
    # Restart PM2 if it exists
    if command -v pm2 &> /dev/null; then
        echo "   Restarting PM2 to restore server..."
        pm2 restart all 2>/dev/null && echo "   ✅ PM2 restarted" || echo "   ⚠️  PM2 restart failed"
    fi
else
    echo "   ✅ Node.js process count is normal"
fi
echo ""

# 5. Kill stuck SSH processes (be careful!)
echo "4️⃣  Checking for stuck processes..."
STUCK_PROCESSES=$(ps aux | grep -E "defunct|zombie" | grep -v grep | wc -l)
if [ $STUCK_PROCESSES -gt 0 ]; then
    echo "   Found $STUCK_PROCESSES defunct/zombie processes"
    echo "   (These are usually cleaned up automatically)"
else
    echo "   ✅ No stuck processes found"
fi
echo ""

# 6. Final process count
FINAL_COUNT=$(ps aux | wc -l)
echo "📊 Final process count: $FINAL_COUNT / 200 ($(( FINAL_COUNT * 100 / 200 ))%)"
echo ""

# 7. Show top processes
echo "📋 Top 10 processes by CPU usage:"
ps aux --sort=-%cpu | head -11 | tail -10 | awk '{printf "   %-8s %6s%% CPU - %s\n", $2, $3, $11}'
echo ""

# 8. Status summary
if [ $FINAL_COUNT -gt 180 ]; then
    echo "⚠️  WARNING: Process count is still high ($FINAL_COUNT/200)"
    echo "   Consider:"
    echo "   - Checking for other applications using processes"
    echo "   - Contacting hosting provider"
elif [ $FINAL_COUNT -gt 160 ]; then
    echo "⚠️  CAUTION: Process count is getting high ($FINAL_COUNT/200)"
    echo "   Monitor closely"
else
    echo "✅ Process count is within normal range"
fi

echo ""
echo "✅ Cleanup complete!"

