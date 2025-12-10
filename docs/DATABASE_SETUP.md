# Database Setup Guide

## Step 1: Create Database Locally (XAMPP)

Since you're using XAMPP, create the database:

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" to create a database
3. Database name: `grabdiz_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

## Step 2: Create .env File Locally

Create a `.env` file in the project root:

```bash
# Database Configuration
DATABASE_URL="mysql://root:@localhost:3306/grabdiz_db"

# JWT Secret
JWT_SECRET="your-secret-key-change-in-production"

# Node Environment
NODE_ENV="development"
```

**Note:** 
- Default XAMPP MySQL user is `root` with no password
- Adjust if you have a different MySQL setup

## Step 3: Test Migrations Locally

```bash
# Generate Prisma Client
npx prisma generate

# Create initial migration
npx prisma migrate dev --name init

# This will:
# 1. Create the migration files
# 2. Apply migrations to your database
# 3. Generate Prisma Client
```

## Step 4: Verify Database

```bash
# Open Prisma Studio to view your database
npx prisma studio
```

This opens a GUI at http://localhost:5555 to view/edit your database.

## Step 5: Server Database Setup

On your server, you'll need to:

1. **Create database via cPanel:**
   - Log into cPanel
   - Go to "MySQL Databases"
   - Create database: `grabdiz_db` (or your preferred name)
   - Create database user
   - Add user to database with ALL PRIVILEGES

2. **Create .env file on server:**
   ```bash
   # Connect to server
   ssh -p 21098 scepgtce@server254.web-hosting.com
   
   # Create .env file
   cd ~/grabdiz.co.uk
   nano .env
   ```
   
   Add:
   ```
   DATABASE_URL="mysql://DB_USER:DB_PASSWORD@localhost:3306/DB_NAME"
   JWT_SECRET="your-production-secret-key"
   NODE_ENV="production"
   ```

3. **Run migrations on server:**
   - The deployment workflow will run migrations automatically
   - Or manually: `npx prisma migrate deploy`

## Troubleshooting

### Migration fails locally:
- Check MySQL is running: `mysql -u root -p`
- Verify DATABASE_URL in .env is correct
- Check database exists: `SHOW DATABASES;`

### Migration fails on server:
- Verify .env file exists on server
- Check DATABASE_URL credentials are correct
- Ensure database user has proper permissions
- Check MySQL is accessible from your user account

