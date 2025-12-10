# Understanding Server Processes

## Why Processes Increase on Your Server

### 1. **Next.js Server Processes**
When Next.js runs in server mode, it can spawn multiple processes:
- **Main Process**: The primary Node.js process running your app
- **Worker Processes**: Next.js may spawn workers for handling requests
- **Child Processes**: For tasks like image optimization, API routes, etc.

**Typical Count**: 2-5 processes per Next.js instance

### 2. **PM2 Process Manager** (if using PM2)
If you're using PM2 to manage your Next.js server:
- **PM2 Daemon**: The main PM2 process
- **PM2 Workers**: One or more worker processes for your app
- **PM2 Watch**: File watching processes (if enabled)

**Typical Count**: 3-6 processes

### 3. **SSH Connections**
Each active SSH session creates a process:
- **Active SSH Sessions**: When you or GitHub Actions connect via SSH
- **SSH Agent**: Background SSH agent processes
- **Rsync Processes**: During deployments, rsync creates processes

**During Deployment**: Can create 5-10+ processes temporarily

### 4. **cPanel/Server Management Processes**
Your hosting provider runs background services:
- **cPanel Processes**: Web interface, file management
- **Cron Jobs**: Scheduled tasks (backups, maintenance)
- **Email Processing**: Mail server processes
- **Database Connections**: MySQL connection handlers

**Typical Count**: 20-50 processes (varies by hosting provider)

### 5. **PHP Processes** (if any PHP scripts exist)
- **PHP-FPM**: FastCGI Process Manager
- **PHP Workers**: Handling PHP requests

**Typical Count**: 5-20 processes

### 6. **GitHub Actions Deployment**
When GitHub Actions deploys your code:
- **SSH Connections**: Multiple SSH sessions
- **Rsync Processes**: File transfer processes
- **Command Execution**: Processes for running commands (npm, prisma, etc.)

**During Deployment**: Can temporarily create 10-20 processes

### 7. **Node.js Worker Threads**
Next.js may use worker threads for:
- **API Route Processing**: Parallel request handling
- **Image Processing**: If image optimization is enabled
- **Build Processes**: If building on the server

**Typical Count**: 2-4 worker threads

## How to Check What Processes Are Running

### Via SSH (Recommended)
```bash
# List all processes with details
ps aux | head -20

# Count processes by type
ps aux | grep node | wc -l    # Node.js processes
ps aux | grep pm2 | wc -l     # PM2 processes
ps aux | grep ssh | wc -l     # SSH processes
ps aux | grep php | wc -l     # PHP processes
ps aux | grep rsync | wc -l   # Rsync processes

# See process tree
pstree -p

# See processes sorted by CPU usage
ps aux --sort=-%cpu | head -20

# See processes sorted by memory usage
ps aux --sort=-%mem | head -20
```

### Via cPanel
1. Go to **Metrics** → **Process Manager**
2. View all running processes
3. Filter by process name (node, pm2, ssh, etc.)

## Common Causes of Process Accumulation

### 1. **Multiple Next.js Instances**
If the server is started multiple times without stopping the previous instance:
```bash
# Check for multiple Node.js processes
ps aux | grep "node.*next" | grep -v grep
```

**Solution**: Stop all instances and start only one:
```bash
# Stop all Node.js processes
pkill -f "node.*next"

# Or if using PM2
pm2 stop all
pm2 delete all
```

### 2. **PM2 Cluster Mode**
PM2 cluster mode creates multiple worker processes:
```bash
# Check PM2 status
pm2 list

# If using cluster mode, you'll see multiple processes
```

**Solution**: Use fork mode instead of cluster mode for single-server setups:
```bash
pm2 start npm --name grabdiz -- start --instances 1
```

### 3. **Stuck SSH Sessions**
SSH sessions that didn't close properly:
```bash
# Check active SSH sessions
who
ps aux | grep sshd
```

**Solution**: Close inactive sessions or wait for timeout

### 4. **Deployment Processes Not Cleaning Up**
GitHub Actions deployment processes that didn't terminate:
```bash
# Check for rsync or deployment processes
ps aux | grep rsync
ps aux | grep "npm\|prisma\|node"
```

**Solution**: These should auto-terminate, but if stuck:
```bash
# Kill specific stuck processes (use with caution)
pkill -f rsync
```

### 5. **Cron Jobs Running Too Frequently**
Check your cron jobs:
```bash
# View cron jobs
crontab -l

# Check cron process count
ps aux | grep cron | wc -l
```

**Solution**: Reduce frequency of cron jobs if possible

## Best Practices to Reduce Processes

### 1. **Use PM2 Fork Mode (Not Cluster)**
For a single server, use fork mode:
```bash
pm2 start npm --name grabdiz -- start --instances 1
```

### 2. **Set Process Limits in PM2**
Limit the number of instances:
```bash
pm2 start npm --name grabdiz -- start --max-memory-restart 500M --instances 1
```

### 3. **Monitor and Clean Up Regularly**
Create a cleanup script:
```bash
#!/bin/bash
# cleanup-processes.sh

# Kill stuck Node.js processes (be careful!)
# pkill -f "node.*next"  # Uncomment if needed

# Restart PM2 if using it
if command -v pm2 &> /dev/null; then
    pm2 restart grabdiz
fi

# Check process count
echo "Current process count: $(ps aux | wc -l)"
```

### 4. **Optimize Deployment**
- Use single rsync command instead of multiple
- Close SSH connections properly
- Add delays between deployment steps if needed

### 5. **Use Process Monitoring**
Set up alerts when processes exceed a threshold:
```bash
# Check process count
PROCESS_COUNT=$(ps aux | wc -l)
MAX_PROCESSES=180  # Alert at 90%

if [ $PROCESS_COUNT -gt $MAX_PROCESSES ]; then
    echo "Warning: Process count is $PROCESS_COUNT"
    # Send alert or take action
fi
```

## Normal Process Counts

### Typical Shared Hosting Server
- **System Processes**: 30-50 (OS, cPanel, services)
- **Next.js Server**: 2-5 processes
- **PM2**: 2-3 processes (if used)
- **SSH/Deployment**: 0-10 (during deployments)
- **PHP**: 5-20 (if PHP apps exist)
- **Other**: 10-20

**Total Normal Range**: 50-110 processes

### Your Current Status
- **141/200 (70.5%)**: This is **within normal range** but getting close to the limit
- **Safe Zone**: Below 160 processes (80%)
- **Warning Zone**: 160-180 processes (80-90%)
- **Critical Zone**: Above 180 processes (90%+)

## When to Worry

1. **Processes > 180 (90%)**: Risk of hitting the limit
2. **Rapid Increase**: Processes growing quickly without deployment
3. **Stuck Processes**: Processes that don't terminate
4. **High CPU/Memory**: Processes consuming excessive resources

## Quick Fixes

### Immediate Actions
```bash
# 1. Check what's using processes
ps aux --sort=-%cpu | head -20

# 2. Restart PM2 (if using)
pm2 restart all

# 3. Kill stuck Node.js processes (if any)
pkill -f "node.*next"

# 4. Check for multiple PM2 instances
pm2 list
pm2 delete all  # Then restart properly
```

### Long-term Solutions
1. **Optimize PM2 Configuration**: Use fork mode, limit instances
2. **Monitor Deployments**: Ensure processes clean up after deployment
3. **Regular Cleanup**: Schedule periodic process cleanup
4. **Contact Hosting Provider**: If consistently high, ask about process limits

## Summary

Your current **141/200 (70.5%)** is **normal** for a server running:
- Next.js application
- PM2 process manager
- cPanel services
- Background cron jobs
- Active SSH sessions

The processes are likely from:
1. **Next.js server** (2-5 processes)
2. **PM2** (2-3 processes)
3. **cPanel/system** (30-50 processes)
4. **SSH/deployment** (5-10 processes during/after deployment)
5. **Other services** (PHP, cron, etc.)

**Action**: Monitor the count. If it consistently stays above 160, investigate what's causing the increase.

