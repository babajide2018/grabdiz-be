# How to Fix MySQL/MariaDB and Run Migrations

## Quick Fix for XAMPP

### Option 1: Run mysql_upgrade (Recommended)

1. **Open Terminal**

2. **Run mysql_upgrade:**
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql_upgrade -u root
   ```
   
   If that doesn't work, try:
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql_upgrade -u root -p
   ```
   (Enter your MySQL root password, or just press Enter if no password)

3. **Restart MySQL in XAMPP:**
   - Open XAMPP Control Panel
   - Stop MySQL
   - Start MySQL

4. **Then run migrations:**
   ```bash
   npx prisma migrate dev --name init
   ```

### Option 2: Create Database Manually (If mysql_upgrade fails)

1. **Open phpMyAdmin:** http://localhost/phpmyadmin

2. **Create database:**
   - Click "New" 
   - Database name: `grabdiz_db`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Apply migration SQL:**
   - Select `grabdiz_db` database
   - Click "SQL" tab
   - Copy the SQL from: `prisma/migrations/20251111111200_init/migration.sql`
   - Paste and click "Go"

4. **Mark migration as applied:**
   ```bash
   npx prisma migrate resolve --applied 20251111111200_init
   ```

### Option 3: Use Prisma db push (Alternative)

If migrations still fail, you can use `db push` which doesn't require migrations:

```bash
npx prisma db push
```

This will create/update the database schema directly without migration files.

## After Fixing

Verify everything works:

```bash
# Generate Prisma Client
npx prisma generate

# Check migration status
npx prisma migrate status

# Open Prisma Studio to view database
npx prisma studio
```

## For Server

The deployment workflow will handle migrations automatically. Just make sure:
1. Database is created on server (via cPanel)
2. `.env` file exists on server with correct DATABASE_URL
3. Deployment runs (migrations execute automatically)

