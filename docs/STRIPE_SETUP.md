# Stripe Payment Integration Setup

## Environment Variables

### Backend (Laravel) - `api/.env`

Add these variables to your `api/.env` file:

**For Test Mode (Development):**
```env
STRIPE_MODE=test
STRIPE_KEY_TEST=pk_test_your_test_publishable_key_here
STRIPE_SECRET_TEST=sk_test_your_test_secret_key_here
STRIPE_WEBHOOK_SECRET_TEST=whsec_your_test_webhook_secret_here
```

**For Live Mode (Production):**
```env
STRIPE_MODE=live
STRIPE_KEY_LIVE=pk_live_your_live_publishable_key_here
STRIPE_SECRET_LIVE=sk_live_your_live_secret_key_here
STRIPE_WEBHOOK_SECRET_LIVE=whsec_your_live_webhook_secret_here
```

**Legacy Support (for backward compatibility):**
If you only set `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET`, they will be used for the mode specified in `STRIPE_MODE`.

**To switch between modes:**
Simply change `STRIPE_MODE=test` to `STRIPE_MODE=live` (or vice versa) and restart your Laravel server.

**How to get your Stripe keys:**
1. Go to https://dashboard.stripe.com
2. Sign up or log in
3. Go to **Developers** → **API keys**
4. Copy your **Publishable key** (starts with `pk_test_`) → `STRIPE_KEY`
5. Copy your **Secret key** (starts with `sk_test_`) → `STRIPE_SECRET`

**For webhook secret:**
1. Go to **Developers** → **Webhooks**
2. Click **Add endpoint**
3. Set endpoint URL: `https://yourdomain.com/api/webhooks/stripe` (or `http://localhost:8000/api/webhooks/stripe` for local)
4. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
5. Copy the **Signing secret** (starts with `whsec_`) → `STRIPE_WEBHOOK_SECRET`

### Frontend (Next.js) - `.env.local`

Create or update `.env.local` in the project root:

**For Test Mode (Development):**
```env
NEXT_PUBLIC_STRIPE_MODE=test
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY_TEST=pk_test_your_test_publishable_key_here
```

**For Live Mode (Production):**
```env
NEXT_PUBLIC_STRIPE_MODE=live
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY_LIVE=pk_live_your_live_publishable_key_here
```

**Legacy Support (for backward compatibility):**
If you only set `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY`, it will be used for the mode specified in `NEXT_PUBLIC_STRIPE_MODE`.

**To switch between modes:**
Simply change `NEXT_PUBLIC_STRIPE_MODE=test` to `NEXT_PUBLIC_STRIPE_MODE=live` (or vice versa) and rebuild your Next.js application.

## Database Migration

Run the migrations to create orders and order_items tables:

```bash
cd api
php artisan migrate
```

## Testing

### Test Cards

Use these test card numbers in Stripe test mode:

- **Success**: `4242 4242 4242 4242`
- **Decline**: `4000 0000 0000 0002`
- **3D Secure**: `4000 0025 0000 3155`

For all test cards:
- **Expiry**: Any future date (e.g., 12/25)
- **CVC**: Any 3 digits (e.g., 123)
- **ZIP**: Any 5 digits (e.g., 12345)

### Testing Flow

1. Add items to cart
2. Go to checkout page
3. Fill in billing details
4. Click "Continue to Payment"
5. Enter test card details
6. Complete payment
7. Check order status in admin panel

## Production Setup

When ready for production:

1. Switch to **Live mode** in Stripe dashboard
2. Get your **live** API keys
3. Update environment variables with live keys
4. Update webhook endpoint to production URL
5. Test with real card (use small amount first)

## Webhook Testing (Local)

For local webhook testing, use Stripe CLI:

```bash
# Install Stripe CLI
# macOS: brew install stripe/stripe-cli/stripe
# Or download from: https://stripe.com/docs/stripe-cli

# Login
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/webhooks/stripe
```

This will give you a webhook signing secret to use in your `.env` file.

## Troubleshooting

### Payment not processing
- Check Stripe keys are correct
- Verify webhook endpoint is accessible
- Check browser console for errors
- Check Laravel logs: `api/storage/logs/laravel.log`

### Webhook not receiving events
- Verify webhook secret is correct
- Check webhook endpoint is publicly accessible
- Use Stripe CLI for local testing
- Check Stripe dashboard → Webhooks for event logs

### Order created but payment failed
- Check payment intent status in Stripe dashboard
- Verify webhook handler is working
- Check order payment_status in database

