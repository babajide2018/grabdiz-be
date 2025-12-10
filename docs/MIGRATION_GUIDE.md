# Prisma Migration Guide - Ensuring InnoDB

## Problem

By default, Prisma migrations don't specify `ENGINE=InnoDB` in CREATE TABLE statements. On servers where MyISAM is the default storage engine, tables will be created as MyISAM, which doesn't support foreign keys (required by Prisma).

## Solution

We've created scripts and npm commands to automatically add `ENGINE=InnoDB` to all new migrations.

## Creating Migrations

### Recommended: Use the wrapper script

```bash
npm run migrate:create <migration-name>
```

Example:
```bash
npm run migrate:create add_product_reviews
```

This will:
1. Create the migration using Prisma
2. Automatically add `ENGINE=InnoDB` to all CREATE TABLE statements
3. Show you which tables were modified

### Alternative: Manual process

If you prefer to use Prisma directly:

```bash
# Step 1: Create migration
npx prisma migrate dev --create-only --name your_migration_name

# Step 2: Fix the engine (if needed)
npm run migrate:fix-engine prisma/migrations/TIMESTAMP_your_migration_name/migration.sql

# Step 3: Apply migration
npx prisma migrate dev
```

## Applying Migrations

### Development (with reset if needed)
```bash
npm run migrate:dev
```

### Production (deploy)
```bash
npm run migrate:deploy
```

## Verifying Table Engines

After running migrations, verify all tables use InnoDB:

```sql
SELECT TABLE_NAME, ENGINE 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'your_database_name' 
AND TABLE_TYPE = 'BASE TABLE';
```

All tables should show `ENGINE = InnoDB`.

## Manual Fix (if needed)

If you need to fix an existing migration file manually:

1. Open the migration SQL file: `prisma/migrations/TIMESTAMP_name/migration.sql`
2. Find all `CREATE TABLE` statements
3. Change:
   ```sql
   ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   To:
   ```sql
   ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

## Converting Existing MyISAM Tables

If you have existing MyISAM tables, convert them:

```sql
ALTER TABLE `table_name` ENGINE=InnoDB;
```

Or use the provided script:
```bash
bash convert_to_innodb.sh
```

## Best Practices

1. **Always use the wrapper script** for new migrations: `npm run migrate:create`
2. **Review migration files** before applying them
3. **Test migrations locally** before deploying to production
4. **Backup your database** before running migrations in production
5. **Verify table engines** after deployment

## Troubleshooting

### Migration fails with foreign key error

This usually means a table is still using MyISAM. Convert it to InnoDB:

```sql
ALTER TABLE `table_name` ENGINE=InnoDB;
```

### Script doesn't add ENGINE=InnoDB

Check the migration file format. The script looks for:
```sql
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

If your migration uses a different format, you may need to add `ENGINE=InnoDB` manually.

### Tables still created as MyISAM

1. Check if the migration file has `ENGINE=InnoDB`
2. Verify the server's MySQL default engine (shouldn't matter if ENGINE is specified)
3. Check for any custom MySQL configuration overriding the ENGINE

