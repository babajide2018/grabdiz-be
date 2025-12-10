# Fix Database Connection Error

## Problem
```
Error: P1001: Can't reach database server at `localhost:3306`
```

This error occurs when Prisma can't connect to your MySQL database. On shared hosting, the database host might not be `localhost`.

## Solution: Check and Update DATABASE_URL

### Step 1: Find Your Database Hostname

1. **Log into cPanel**
2. Go to **MySQL Databases** (or **Databases** → **MySQL Databases**)
3. Look for your database information:
   - **Database Name**: Usually `username_dbname` (e.g., `scepgtce_grabdiz_db`)
   - **Database Host**: This is what you need! Common values:
     - `localhost`
     - `127.0.0.1`
     - A specific hostname like `mysql.server254.web-hosting.com`
     - Sometimes shown as "Remote MySQL" or "Hostname"

### Step 2: Update .env File on Server

SSH into your server:
```bash
ssh -p 21098 scepgtce@server254.web-hosting.com
cd ~/grabdiz.co.uk
nano .env
```

Update the `DATABASE_URL` with the correct host:

#### If host is `localhost`:
```env
DATABASE_URL="mysql://scepgtce_grbdz_admin:YOUR_PASSWORD@localhost:3306/scepgtce_grabdiz_db"
```

#### If host is `127.0.0.1`:
```env
DATABASE_URL="mysql://scepgtce_grbdz_admin:YOUR_PASSWORD@127.0.0.1:3306/scepgtce_grabdiz_db"
```

#### If host is a specific hostname (e.g., `mysql.server254.web-hosting.com`):
```env
DATABASE_URL="mysql://scepgtce_grbdz_admin:YOUR_PASSWORD@mysql.server254.web-hosting.com:3306/scepgtce_grabdiz_db"
```

#### If using Unix socket (some shared hosts):
```env
DATABASE_URL="mysql://scepgtce_grbdz_admin:YOUR_PASSWORD@localhost:3306/scepgtce_grabdiz_db?socket=/path/to/mysql.sock"
```

**Note**: Replace `YOUR_PASSWORD` with your actual database password.

### Step 3: Test Connection

After updating `.env`, test the connection:

```bash
# Load environment variables
source .env

# Test with Prisma
npx prisma db execute --stdin <<< "SELECT 1;"
```

If this works, you should see output. If it fails, check:
- Database username is correct
- Database password is correct
- Database name is correct
- Database host is correct

### Step 4: Alternative - Check via cPanel

Some hosting providers show the connection string in cPanel:

1. Go to **MySQL Databases**
2. Look for **Connection Strings** or **Remote MySQL**
3. You might see something like:
   ```
   mysql://username:password@hostname:3306/database
   ```
4. Use this format for your `DATABASE_URL`

### Step 5: Common Issues

#### Issue: "Access denied for user"
- **Solution**: Check username and password in `.env`
- Verify user has permissions in cPanel MySQL Databases

#### Issue: "Unknown database"
- **Solution**: Verify database name exists in cPanel
- Check database name matches exactly (case-sensitive)

#### Issue: "Can't reach database server"
- **Solution**: Try different hosts:
  - `localhost`
  - `127.0.0.1`
  - The specific hostname from cPanel
- Check if port is different (some hosts use non-standard ports)

#### Issue: "Connection timeout"
- **Solution**: Check if your hosting provider allows connections from your server IP
- Some hosts require adding your server IP to "Remote MySQL" in cPanel

### Step 6: Verify .env File Format

Make sure your `.env` file has the correct format:

```env
DATABASE_URL="mysql://username:password@host:port/database"
JWT_SECRET="your-secret-key"
NODE_ENV="production"
```

**Important**:
- No spaces around `=`
- Use quotes around values
- No trailing commas or semicolons
- Use forward slashes `/` in the URL

### Step 7: Test After Fix

Once you've updated the `.env` file:

1. **Test locally via SSH**:
   ```bash
   ssh -p 21098 scepgtce@server254.web-hosting.com
   cd ~/grabdiz.co.uk
   source .env
   npx prisma db execute --stdin <<< "SELECT 1;"
   ```

2. **Or run migration manually**:
   ```bash
   npx prisma migrate deploy
   ```

3. **If successful**, the next GitHub Actions deployment should work!

## Quick Reference: DATABASE_URL Format

```
mysql://[username]:[password]@[host]:[port]/[database]
```

Example:
```
mysql://scepgtce_grbdz_admin:MyPassword123@localhost:3306/scepgtce_grabdiz_db
```

## Still Having Issues?

1. **Contact your hosting provider** - Ask them for the correct MySQL connection details
2. **Check cPanel documentation** - Your hosting provider might have specific instructions
3. **Try different connection methods**:
   - TCP connection: `mysql://user:pass@host:port/db`
   - Socket connection: `mysql://user:pass@localhost/db?socket=/path/to/socket`

## Next Steps

After fixing the connection:
1. Test the connection manually
2. Run a test deployment
3. Monitor the GitHub Actions logs to confirm migrations run successfully

