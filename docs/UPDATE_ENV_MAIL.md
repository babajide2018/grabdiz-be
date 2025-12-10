# Update .env File for Email Configuration

Add these lines to your `api/.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=grabdiz.co.uk
MAIL_PORT=465
MAIL_USERNAME=info@grabdiz.co.uk
MAIL_PASSWORD=X^MR78QYEwO#password.
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@grabdiz.co.uk
MAIL_FROM_NAME="Grabdiz"
ADMIN_EMAIL=info@grabdiz.co.uk
```

After adding these, run:
```bash
php artisan config:clear
```

Then test again with:
```bash
curl http://localhost:8000/api/test-email
```






