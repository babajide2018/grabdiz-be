# Fix MySQL/MariaDB Version Issue

## Issue
Your XAMPP MySQL/MariaDB has a version mismatch that's preventing Prisma from running migrations.

## Solution Options

### Option 1: Fix MySQL (Recommended)

1. **Stop MySQL in XAMPP**
   - Open XAMPP Control Panel
   - Stop MySQL

2. **Run mysql_upgrade**
   ```bash
   # On macOS, XAMPP MySQL is usually at:
   /Applications/XAMPP/xamppfiles/bin/mysql_upgrade -u root
   ```

3. **Restart MySQL**
   - Start MySQL in XAMPP Control Panel

4. **Then run migrations:**
   ```bash
   npx prisma migrate dev --name init
   ```

### Option 2: Create Database Manually (Quick Fix)

1. **Open phpMyAdmin:** http://localhost/phpmyadmin

2. **Create database:**
   - Click "New"
   - Database name: `grabdiz_db`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Run migrations:**
   ```bash
   npx prisma migrate dev --name init
   ```

### Option 3: Use SQL Directly

If migrations still fail, you can apply the SQL directly:

1. **Get the SQL from Prisma:**
   ```bash
   npx prisma migrate diff --from-empty --to-schema-datamodel prisma/schema.prisma --script
   ```

2. **Copy the output SQL**

3. **Run it in phpMyAdmin:**
   - Select `grabdiz_db` database
   - Click "SQL" tab
   - Paste the SQL
   - Click "Go"

## After Fixing

Once the database is set up, verify:

```bash
# Generate Prisma Client
npx prisma generate

# Check migration status
npx prisma migrate status

# Open Prisma Studio to view database
npx prisma studio
```

## For Server Deployment

The deployment workflow will handle migrations automatically once:
1. Database is created on server (via cPanel)
2. `.env` file is created on server with correct DATABASE_URL
3. Deployment runs (migrations execute automatically)

