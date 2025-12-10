# Server .env File Configuration

## Create .env file on server

SSH into your server and run:

```bash
cd ~/grabdiz.co.uk
nano .env
```

## Paste this content:

```
DATABASE_URL="mysql://scepgtce_grbdz_admin:MY2F8ciK4!VG@localhost:3306/scepgtce_grabdiz_db"
JWT_SECRET="your-production-secret-key-change-this-to-random-string"
NODE_ENV="production"
```

## Save and exit:
- Press `Ctrl + X`
- Press `Y` to confirm
- Press `Enter` to save

## Set proper permissions:

```bash
chmod 600 .env
```

This ensures only you can read the .env file (contains sensitive credentials).

## Verify it was created:

```bash
cat .env
```

You should see your DATABASE_URL and other variables.

## Important Notes:

1. **JWT_SECRET**: Change `"your-production-secret-key-change-this-to-random-string"` to a strong random string
   - You can generate one: `openssl rand -base64 32`
   - Or use any long random string

2. **Security**: Never commit .env to git (it's already in .gitignore)

3. **Database**: Make sure the database `scepgtce_grabdiz_db` exists and the user `scepgtce_grbdz_admin` has proper permissions

