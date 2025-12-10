# Migration Plan: Next.js API → PHP Backend

## Why This Migration Makes Sense Now

**Current Problem:**
- ❌ Regular CageFS resets needed (hosting provider intervention)
- ❌ Persistent Node.js server conflicts with CloudLinux/CageFS
- ❌ Process management issues despite PM2 configuration
- ❌ Operational burden (contacting support regularly)

**PHP Solution Benefits:**
- ✅ No persistent processes (runs per-request)
- ✅ Native shared hosting support
- ✅ No CageFS conflicts
- ✅ No process limits
- ✅ Simpler deployment (just upload files)

## Migration Strategy

### Phase 1: Convert Next.js to Static Export
- Remove API routes from Next.js
- Build static site
- Update frontend to call PHP APIs

### Phase 2: Create PHP API Endpoints
- Minimal PHP files for each endpoint
- Use PDO for database access
- Keep same API structure (`/api/*`)

### Phase 3: Update Frontend
- Change API calls from `/api/*` to `/api/*.php`
- Test all functionality

## Implementation Plan

### Step 1: Update Next.js Config (Static Export)

```typescript
// next.config.ts
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: 'export', // Enable static export
  images: {
    unoptimized: true,
  },
  // Remove API routes from build
  distDir: '.next',
};

export default nextConfig;
```

### Step 2: Create PHP API Structure

```
/api/
  auth/
    login.php
  admin/
    products.php
    products-id.php (for /api/admin/products/[id])
    categories.php
    categories-id.php
    upload.php
  categories.php
```

### Step 3: Create PHP Database Helper

```php
// /api/config/db.php
<?php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}
?>
```

### Step 4: Create PHP Authentication Helper

```php
// /api/config/auth.php
<?php
require_once __DIR__ . '/db.php';

function verifyAdmin() {
    // Get token from cookie
    $token = $_COOKIE['auth_token'] ?? null;
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - No token provided']);
        exit;
    }
    
    // Decode JWT (use firebase/php-jwt library)
    $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
    // ... JWT verification logic
    
    return $decoded;
}
?>
```

## API Endpoints to Create

### 1. `/api/auth/login.php`
- POST request
- Verify email/password
- Generate JWT token
- Set cookie
- Return user data

### 2. `/api/admin/products.php`
- GET: List all products with relations
- POST: Create new product

### 3. `/api/admin/products-id.php`
- GET: Get single product
- PUT: Update product
- DELETE: Delete product

### 4. `/api/admin/categories.php`
- GET: List categories
- POST: Create category

### 5. `/api/admin/categories-id.php`
- GET: Get single category
- PUT: Update category
- DELETE: Delete category

### 6. `/api/admin/upload.php`
- POST: Handle file uploads
- Validate files
- Save to `public/uploads/`
- Return file URLs

### 7. `/api/categories.php`
- GET: Public categories list

## Estimated Timeline

- **Day 1-2**: Set up PHP structure, database helper, auth helper
- **Day 3-4**: Implement authentication (`login.php`)
- **Day 5-7**: Implement products API
- **Day 8-9**: Implement categories API
- **Day 10**: Implement upload API
- **Day 11-12**: Update Next.js frontend to call PHP APIs
- **Day 13-14**: Testing and bug fixes

**Total: ~2 weeks**

## Files to Create

### PHP Files (New):
- `/api/config/db.php` - Database connection
- `/api/config/auth.php` - Authentication helpers
- `/api/auth/login.php` - Login endpoint
- `/api/admin/products.php` - Products CRUD
- `/api/admin/products-id.php` - Single product operations
- `/api/admin/categories.php` - Categories CRUD
- `/api/admin/categories-id.php` - Single category operations
- `/api/admin/upload.php` - File uploads
- `/api/categories.php` - Public categories

### Files to Modify:
- `next.config.ts` - Enable static export
- `app/components/admin/*.tsx` - Update API calls
- `app/admin/**/*.tsx` - Update API calls
- `.htaccess` - Remove reverse proxy (no longer needed)
- `.github/workflows/deploy.yml` - Remove PM2/Node.js server steps

### Files to Delete:
- `app/api/**/*.ts` - All Next.js API routes
- `lib/prisma.ts` - No longer needed (or keep for migrations only)

## Deployment Changes

### Before (Current):
1. Build Next.js
2. Deploy files
3. Run Prisma migrations
4. Start Node.js server with PM2
5. Clean up processes

### After (PHP):
1. Build Next.js (static export)
2. Deploy files
3. Run Prisma migrations (one-time, or use PHP for future migrations)
4. Done! (No server to start)

## Prisma Considerations

**Option 1: Keep Prisma for Migrations Only**
- Use Prisma CLI locally for schema changes
- Generate migrations
- Run migrations via PHP script or manually

**Option 2: Migrate to PHP Migrations**
- Use a PHP migration tool (Phinx, Doctrine Migrations)
- Or write raw SQL migrations

**Option 3: Manual SQL**
- Write SQL migrations manually
- Run via phpMyAdmin or command line

## Testing Checklist

- [ ] Login works
- [ ] Admin authentication works
- [ ] Products list loads
- [ ] Create product works
- [ ] Edit product works
- [ ] Delete product works
- [ ] Categories list loads
- [ ] Create category works
- [ ] Edit category works
- [ ] Delete category works
- [ ] File uploads work
- [ ] Images display correctly
- [ ] Public categories API works
- [ ] No console errors
- [ ] No network errors

## Risk Mitigation

1. **Keep Next.js API routes as backup** (comment out, don't delete)
2. **Test PHP APIs locally first** (XAMPP)
3. **Deploy to staging first** (if available)
4. **Keep database backups** before migration
5. **Gradual rollout** - migrate one endpoint at a time

## Next Steps

1. **Decision**: Confirm you want to proceed with PHP migration
2. **Start with**: Database helper and authentication
3. **Test locally**: Use XAMPP to test PHP APIs
4. **Migrate incrementally**: One endpoint at a time
5. **Update frontend**: As each endpoint is ready

## Alternative: Hybrid Approach

If full migration is too much work, consider:

1. **Keep critical APIs in PHP** (login, upload)
2. **Keep simple APIs in Next.js** (if they don't cause issues)
3. **Gradually migrate** as needed

This reduces initial effort but maintains some complexity.

