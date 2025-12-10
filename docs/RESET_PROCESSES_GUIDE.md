# How to Reset Processes on Server

## Quick Commands

### Option 1: Run Script Directly (Recommended)

Upload the `reset-processes.sh` script to your server, then run:

```bash
# Via SSH
ssh -p 21098 scepgtce@server254.web-hosting.com
cd ~/grabdiz.co.uk
bash reset-processes.sh
```

Or run it directly without uploading:

```bash
# From your local machine
ssh -p 21098 scepgtce@server254.web-hosting.com 'bash -s' < reset-processes.sh
```

### Option 2: Manual Commands

Connect to server:
```bash
ssh -p 21098 scepgtce@server254.web-hosting.com
cd ~/grabdiz.co.uk
```

Then run these commands one by one:

#### 1. Check Current Process Count
```bash
ps aux | wc -l
```

#### 2. Kill Stuck Rsync Processes
```bash
# Check for rsync processes
ps aux | grep rsync | grep -v grep

# Kill them
pkill -f rsync
```

#### 3. Restart PM2 (if using)
```bash
# Check PM2 status
pm2 list

# Restart all PM2 processes
pm2 restart all

# Or if you need to stop and start fresh
pm2 stop all
pm2 delete all
pm2 start npm --name grabdiz -- start
```

#### 4. Kill Orphaned Node.js Processes
```bash
# See all Node.js processes
ps aux | grep node | grep -v grep

# Kill all Node.js processes (use carefully!)
pkill -f "node.*next"

# Then restart PM2
pm2 restart all
```

#### 5. Check Process Count Again
```bash
ps aux | wc -l
```

#### 6. See Top Processes
```bash
# Top 10 by CPU
ps aux --sort=-%cpu | head -11

# Top 10 by Memory
ps aux --sort=-%mem | head -11
```

## Quick One-Liners

### Reset Everything (PM2 + Cleanup)
```bash
pm2 restart all && pkill -f rsync && echo "Process count: $(ps aux | wc -l)"
```

### Just Restart PM2
```bash
pm2 restart all
```

### Kill All Rsync Processes
```bash
pkill -f rsync
```

### Check Process Count
```bash
echo "Processes: $(ps aux | wc -l) / 200"
```

### See What's Using Processes
```bash
ps aux --sort=-%cpu | head -20
```

## Safety Notes

⚠️ **Be Careful When Killing Processes:**

1. **Don't kill system processes** - Only kill processes you recognize (node, rsync, pm2)
2. **PM2 restart is safe** - It will restart your application
3. **pkill -f "node.*next"** - Only kills Next.js processes, not all node processes
4. **Always restart PM2 after killing Node processes** - Otherwise your app won't be running

## When to Use

Use these commands when:
- Process count is above 160 (80%)
- Deployment fails due to process limit
- You see stuck processes in cPanel
- After a failed deployment

## What the Script Does

1. ✅ Shows current process count
2. ✅ Kills stuck rsync processes (from deployments)
3. ✅ Restarts PM2 (clean state)
4. ✅ Checks for multiple Node.js processes
5. ✅ Kills orphaned Node.js processes (if too many)
6. ✅ Shows final process count
7. ✅ Displays top processes by CPU

## Expected Results

After running the script:
- Process count should drop to 130-150 (normal range)
- PM2 should show your app running
- No stuck rsync processes
- Only 2-5 Node.js processes (PM2 + workers)

## Troubleshooting

### "Command not found: pm2"
PM2 might not be in PATH. Try:
```bash
# Load nvm first
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# Then try pm2
pm2 list
```

### "Permission denied"
Make sure you're running as the correct user (scepgtce):
```bash
whoami
```

### Processes keep coming back
Check for:
- Cron jobs running frequently
- Multiple PM2 instances
- Other applications on the server

## After Cleanup

1. **Verify your app is running:**
   ```bash
   pm2 list
   curl http://localhost:3000
   ```

2. **Check process count:**
   ```bash
   ps aux | wc -l
   ```

3. **Monitor for a few minutes** to ensure processes don't spike again

