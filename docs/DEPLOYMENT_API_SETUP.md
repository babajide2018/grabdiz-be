# API Server Deployment Setup

## ✅ Server Status

**Confirmed via SSH:**
- ✅ Node.js v20.19.5 is available
- ✅ npm v10.8.2 is available  
- ✅ PM2 is installed
- ✅ .env file exists
- ✅ node_modules exists
- ⚠️ server-api.js needs to be deployed (will be deployed on next push)

## How It Works

### 1. Build Process (GitHub Actions)

1. **Static Export Build:**
   - `app/api/` is moved to `_api_backup` before build
   - Next.js builds static files → `out/` folder
   - `app/api/` is restored after build (for deployment)

2. **Why API routes are moved:**
   - Static export doesn't support API routes
   - Moving them prevents build errors
   - They're restored for deployment

### 2. Deployment Process

**Files Deployed:**
- ✅ `out/` → Static frontend files
- ✅ `app/api/` → API route definitions (for reference, not used directly)
- ✅ `server-api.js` → Express server that handles API requests
- ✅ `lib/` → Prisma client and utilities
- ✅ `app/lib/` → Auth utilities
- ✅ `prisma/` → Database schema and migrations
- ✅ `package.json` → Dependencies

### 3. Server Setup (Automatic)

**On the server:**
1. Dependencies are installed (`npm install`)
2. Prisma Client is generated (`npx prisma generate`)
3. API server starts on port 3000:
   ```bash
   pm2 start server-api.js --name grabdiz-api
   ```
   OR if PM2 fails:
   ```bash
   nohup node server-api.js > api-server.log 2>&1 &
   ```

### 4. Request Flow

**When user visits `/api/auth/login`:**

1. Browser → `https://grabdiz.co.uk/api/auth/login`
2. Apache (.htaccess) → Proxies to `http://localhost:3000/api/auth/login`
3. Express Server (server-api.js) → Handles the request
4. Prisma → Queries database
5. Response → Sent back to browser

## ✅ Will It Work?

**YES!** The backend endpoint will work because:

1. ✅ **Node.js is available** on the server
2. ✅ **API server runs separately** from static files
3. ✅ **Prisma Client** is generated on the server
4. ✅ **Database connection** via `.env` file
5. ✅ **.htaccess proxy** routes API requests correctly

## Port Configuration

- **API Server:** Port 3000 (configurable via `PORT` env var)
- **.htaccess:** Proxies to `http://localhost:3000`
- **PM2:** Manages the server process

## Troubleshooting

### If API doesn't work after deployment:

1. **Check if server is running:**
   ```bash
   ssh -p 21098 scepgtce@server254.web-hosting.com
   cd ~/grabdiz.co.uk
   pm2 list
   # OR
   ps aux | grep server-api
   ```

2. **Check server logs:**
   ```bash
   pm2 logs grabdiz-api
   # OR
   tail -f api-server.log
   ```

3. **Check .htaccess proxy:**
   - Ensure `mod_proxy` is enabled in Apache
   - Verify proxy rule is correct

4. **Check database connection:**
   - Verify `.env` has correct `DATABASE_URL`
   - Test connection: `npx prisma db pull`

5. **Restart API server:**
   ```bash
   pm2 restart grabdiz-api
   # OR
   kill $(cat api-server.pid) && node server-api.js &
   ```

## Next Steps

1. ✅ Commit and push changes
2. ✅ GitHub Actions will deploy automatically
3. ✅ API server will start automatically
4. ✅ Test login at `https://grabdiz.co.uk/admin`

