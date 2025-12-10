#!/bin/bash

echo "=== Server Process Analysis ==="
echo ""

echo "1. Total Process Count:"
TOTAL=$(ps aux | wc -l)
echo "   $TOTAL processes"
echo ""

echo "2. Process Breakdown:"
echo "   Node.js processes: $(ps aux | grep -E 'node|next' | grep -v grep | wc -l)"
echo "   PM2 processes: $(ps aux | grep pm2 | grep -v grep | wc -l)"
echo "   SSH processes: $(ps aux | grep ssh | grep -v grep | wc -l)"
echo "   PHP processes: $(ps aux | grep php | grep -v grep | wc -l)"
echo "   Rsync processes: $(ps aux | grep rsync | grep -v grep | wc -l)"
echo ""

echo "3. Top 10 Processes by CPU:"
ps aux --sort=-%cpu | head -11 | tail -10
echo ""

echo "4. Top 10 Processes by Memory:"
ps aux --sort=-%mem | head -11 | tail -10
echo ""

echo "5. Node.js/Next.js Processes Details:"
ps aux | grep -E 'node|next' | grep -v grep
echo ""

echo "6. PM2 Status (if available):"
if command -v pm2 &> /dev/null; then
    pm2 list
else
    echo "   PM2 is not installed or not in PATH"
fi
echo ""

echo "=== Recommendations ==="
if [ $TOTAL -gt 180 ]; then
    echo "⚠️  WARNING: Process count is high ($TOTAL/200)"
    echo "   Consider:"
    echo "   - Restarting PM2: pm2 restart all"
    echo "   - Checking for stuck processes"
    echo "   - Contacting hosting provider"
elif [ $TOTAL -gt 160 ]; then
    echo "⚠️  CAUTION: Process count is getting high ($TOTAL/200)"
    echo "   Monitor closely"
else
    echo "✅ Process count is normal ($TOTAL/200)"
fi
