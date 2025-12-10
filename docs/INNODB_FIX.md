# Fix: Convert Database Tables from MyISAM to InnoDB

## Problem

The database tables on the server are using **MyISAM** storage engine instead of **InnoDB**. This is a problem because:

1. **MyISAM doesn't support foreign keys** - Prisma requires foreign keys for relations
2. **MyISAM doesn't support transactions** - Critical for data integrity
3. **MyISAM uses table-level locking** - Poor performance under concurrent load
4. **MyISAM has no crash recovery** - Data loss risk on server crashes

## Why This Happened

Prisma migrations don't explicitly specify `ENGINE=InnoDB` in the generated SQL. When no engine is specified, MySQL uses the server's default storage engine, which on many shared hosting servers is still MyISAM.

## Solution

### Option 1: Run the Conversion Script on Server (Recommended)

SSH into your server and run:

```bash
cd ~/grabdiz.co.uk
bash convert_to_innodb.sh
```

Or manually run the SQL:

```bash
mysql -u scepgtce_grbdz_admin -p scepgtce_grabdiz_db < prisma/migrations/convert_to_innodb.sql
```

### Option 2: Run via MySQL Client

Connect to your database and run:

```sql
ALTER TABLE `categories` ENGINE=InnoDB;
ALTER TABLE `products` ENGINE=InnoDB;
ALTER TABLE `product_variants` ENGINE=InnoDB;
ALTER TABLE `product_images` ENGINE=InnoDB;
ALTER TABLE `users` ENGINE=InnoDB;
ALTER TABLE `cart` ENGINE=InnoDB;
ALTER TABLE `orders` ENGINE=InnoDB;
ALTER TABLE `order_items` ENGINE=InnoDB;
ALTER TABLE `sessions` ENGINE=InnoDB;
```

### Verify Conversion

After running the conversion, verify all tables are using InnoDB:

```sql
SELECT TABLE_NAME, ENGINE 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'scepgtce_grabdiz_db' 
AND TABLE_TYPE = 'BASE TABLE';
```

All tables should show `ENGINE = InnoDB`.

## Preventing Future Issues

### For Future Migrations

When creating new migrations, you can manually edit the migration SQL file to add `ENGINE=InnoDB` to each `CREATE TABLE` statement:

```sql
CREATE TABLE `new_table` (
    ...
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Alternative: Post-Migration Script

You can add a post-migration step in your deployment workflow that automatically converts any new MyISAM tables to InnoDB.

## Important Notes

- **Backup first**: Always backup your database before running migrations
- **Downtime**: Converting large tables may cause brief locks
- **Foreign Keys**: After conversion, verify foreign keys are working correctly
- **Performance**: InnoDB generally performs better for most workloads

## Database Credentials (Server)

- **User**: `scepgtce_grbdz_admin`
- **Database**: `scepgtce_grabdiz_db`
- **Password**: `MY2F8ciK4!VG`

