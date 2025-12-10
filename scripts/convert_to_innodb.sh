#!/bin/bash

# Script to convert all database tables from MyISAM to InnoDB
# This ensures foreign key constraints work properly

echo "🔄 Converting database tables from MyISAM to InnoDB..."

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Extract database credentials from DATABASE_URL
# Format: mysql://user:password@host:port/database
if [ -z "$DATABASE_URL" ]; then
    echo "❌ Error: DATABASE_URL not found in .env file"
    exit 1
fi

# Parse DATABASE_URL (basic parsing, assumes standard format)
DB_URL=$DATABASE_URL
DB_URL=${DB_URL#mysql://}
DB_CREDS=${DB_URL%%@*}
DB_USER=${DB_CREDS%%:*}
DB_PASS=${DB_CREDS#*:}
DB_HOST_PORT=${DB_URL#*@}
DB_HOST=${DB_HOST_PORT%%:*}
DB_PORT=${DB_HOST_PORT#*:}
DB_PORT=${DB_PORT%%/*}
DB_NAME=${DB_HOST_PORT#*/}
DB_NAME=${DB_NAME%%\?*}

# If port is empty, use default 3306
if [ -z "$DB_PORT" ]; then
    DB_PORT=3306
fi

echo "📊 Database: $DB_NAME"
echo "🔗 Host: $DB_HOST:$DB_PORT"
echo ""

# Convert tables to InnoDB
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
ALTER TABLE \`categories\` ENGINE=InnoDB;
ALTER TABLE \`products\` ENGINE=InnoDB;
ALTER TABLE \`product_variants\` ENGINE=InnoDB;
ALTER TABLE \`product_images\` ENGINE=InnoDB;
ALTER TABLE \`users\` ENGINE=InnoDB;
ALTER TABLE \`cart\` ENGINE=InnoDB;
ALTER TABLE \`orders\` ENGINE=InnoDB;
ALTER TABLE \`order_items\` ENGINE=InnoDB;
ALTER TABLE \`sessions\` ENGINE=InnoDB;
EOF

if [ $? -eq 0 ]; then
    echo "✅ Successfully converted all tables to InnoDB!"
    echo ""
    echo "🔍 Verifying table engines..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$DB_NAME' AND TABLE_TYPE = 'BASE TABLE';"
else
    echo "❌ Error: Failed to convert tables to InnoDB"
    exit 1
fi

