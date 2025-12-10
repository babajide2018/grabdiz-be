#!/bin/bash

# Script to add ENGINE=InnoDB to all CREATE TABLE statements in Prisma migration files
# This ensures all tables are created with InnoDB instead of MyISAM

MIGRATION_FILE="$1"

if [ -z "$MIGRATION_FILE" ]; then
    echo "Usage: $0 <migration-file.sql>"
    echo "Example: $0 prisma/migrations/20240101120000_new_migration/migration.sql"
    exit 1
fi

if [ ! -f "$MIGRATION_FILE" ]; then
    echo "Error: Migration file not found: $MIGRATION_FILE"
    exit 1
fi

echo "🔧 Adding ENGINE=InnoDB to CREATE TABLE statements in: $MIGRATION_FILE"

# Create a backup
cp "$MIGRATION_FILE" "${MIGRATION_FILE}.bak"

# Add ENGINE=InnoDB to all CREATE TABLE statements
# This regex finds CREATE TABLE statements and adds ENGINE=InnoDB before the closing parenthesis
# It handles both single-line and multi-line CREATE TABLE statements

# Use sed to add ENGINE=InnoDB after the DEFAULT CHARACTER SET line
# Cross-platform compatible
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS - sed requires backup extension, but we use a temp file instead
    sed 's/) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/g' "$MIGRATION_FILE" > "${MIGRATION_FILE}.tmp" && mv "${MIGRATION_FILE}.tmp" "$MIGRATION_FILE"
else
    # Linux
    sed -i 's/) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/g' "$MIGRATION_FILE"
fi

# Verify changes
if grep -q "ENGINE=InnoDB" "$MIGRATION_FILE"; then
    echo "✅ Successfully added ENGINE=InnoDB to migration file"
    echo "📋 Modified CREATE TABLE statements:"
    grep -n "CREATE TABLE" "$MIGRATION_FILE" | head -5
    rm "${MIGRATION_FILE}.bak"
else
    echo "⚠️  Warning: No ENGINE=InnoDB found after modification. Restoring backup..."
    mv "${MIGRATION_FILE}.bak" "$MIGRATION_FILE"
    exit 1
fi

