#!/bin/bash

# Wrapper script for Prisma migrations that automatically adds ENGINE=InnoDB
# Usage: ./scripts/create-migration.sh <migration-name>

MIGRATION_NAME="$1"

if [ -z "$MIGRATION_NAME" ]; then
    echo "Usage: $0 <migration-name>"
    echo "Example: $0 add_product_reviews"
    exit 1
fi

echo "📦 Creating new Prisma migration: $MIGRATION_NAME"
echo ""

# Step 1: Create migration (without applying)
npx prisma migrate dev --create-only --name "$MIGRATION_NAME"

if [ $? -ne 0 ]; then
    echo "❌ Failed to create migration"
    exit 1
fi

# Step 2: Find the latest migration file
LATEST_MIGRATION=$(find prisma/migrations -type d -name "*_${MIGRATION_NAME}" | sort | tail -1)

if [ -z "$LATEST_MIGRATION" ]; then
    echo "⚠️  Warning: Could not find migration directory for: $MIGRATION_NAME"
    echo "💡 You may need to manually add ENGINE=InnoDB to the migration SQL"
    exit 0
fi

MIGRATION_SQL="${LATEST_MIGRATION}/migration.sql"

if [ ! -f "$MIGRATION_SQL" ]; then
    echo "⚠️  Warning: Migration SQL file not found: $MIGRATION_SQL"
    exit 0
fi

echo ""
echo "🔧 Adding ENGINE=InnoDB to migration file..."

# Create backup
cp "$MIGRATION_SQL" "${MIGRATION_SQL}.bak"

# Add ENGINE=InnoDB to all CREATE TABLE statements
# Replace the DEFAULT CHARACTER SET line with one that includes ENGINE=InnoDB
# Use a temporary file for cross-platform compatibility
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS - sed requires backup extension, but we use a temp file instead
    sed 's/) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/g' "$MIGRATION_SQL" > "${MIGRATION_SQL}.tmp" && mv "${MIGRATION_SQL}.tmp" "$MIGRATION_SQL"
else
    # Linux
    sed -i 's/) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/g' "$MIGRATION_SQL"
fi

# Verify changes
if grep -q "ENGINE=InnoDB" "$MIGRATION_SQL"; then
    echo "✅ Successfully added ENGINE=InnoDB to all CREATE TABLE statements"
    echo ""
    echo "📋 Modified tables:"
    grep -n "CREATE TABLE" "$MIGRATION_SQL" | sed 's/^/   /'
    echo ""
    echo "✅ Migration created and fixed: $LATEST_MIGRATION"
    echo ""
    echo "📝 Next steps:"
    echo "   1. Review the migration: $MIGRATION_SQL"
    echo "   2. Apply the migration: npx prisma migrate dev"
    rm "${MIGRATION_SQL}.bak"
else
    echo "⚠️  Warning: Could not verify ENGINE=InnoDB was added"
    echo "💡 Restoring backup and checking manually..."
    mv "${MIGRATION_SQL}.bak" "$MIGRATION_SQL"
    echo "📝 Please manually add ENGINE=InnoDB to CREATE TABLE statements in:"
    echo "   $MIGRATION_SQL"
fi

