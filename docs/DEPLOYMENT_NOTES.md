# Deployment Notes

## ⚠️ Important: Static Export vs Server Mode

This project is currently configured for **static export** (`output: 'export'` in `next.config.ts`).

### Current Setup (Static Export)
- ✅ Works on any shared hosting (no Node.js required)
- ✅ Fast deployment via rsync
- ❌ **API routes will NOT work** (`app/api/*` routes are excluded)
- ❌ No server-side rendering

### If Node.js is Available on Server

If the deployment check shows Node.js is available, you can:

1. **Remove static export** from `next.config.ts`:
   ```typescript
   const nextConfig: NextConfig = {
     // Remove: output: 'export',
     images: {
       unoptimized: true, // Keep this if image optimization issues
     },
   };
   ```

2. **Update GitHub Actions workflow** to deploy the full Next.js app:
   - Deploy `.next/` folder instead of `out/`
   - Run `npm start` on server (or use PM2)
   - API routes will work

3. **Update deployment script** to:
   ```yaml
   - name: Deploy Next.js application
     run: |
       rsync -avz --delete \
         -e "ssh -p 21098" \
         .next/ package.json package-lock.json \
         scepgtce@server254.web-hosting.com:~/grabdiz.co.uk/
       
       ssh -p 21098 scepgtce@server254.web-hosting.com \
         "cd ~/grabdiz.co.uk && npm install --production && pm2 restart grabdiz || pm2 start npm --name grabdiz -- start"
   ```

## Backend Options

### Option 1: Separate API Server (Recommended if no Node.js)
- Deploy API to a separate Node.js server (VPS, Railway, Render, etc.)
- Update frontend API calls to point to the external API
- Use CORS for cross-origin requests

### Option 2: PHP Backend (If no Node.js)
- Create PHP API endpoints
- Use same MySQL database
- Update frontend to call PHP endpoints

### Option 3: Next.js Server Mode (If Node.js available)
- Use Next.js API routes
- Deploy full Next.js app
- Use PM2 or similar to keep it running

## Database Setup

Regardless of backend choice:
1. Create MySQL database on shared hosting
2. Run Prisma migrations (if using Prisma) or SQL schema
3. Update `.env` with database credentials
4. Ensure database is accessible from your backend

## Next Steps After Deployment Check

1. Run the GitHub Actions workflow
2. Check the "Check Node.js availability" step output
3. Decide on backend approach based on Node.js availability
4. Update configuration accordingly

