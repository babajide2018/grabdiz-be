# SSH Authentication Troubleshooting

## Issue: Permission Denied

The deployment is failing with:
```
Permission denied (publickey,gssapi-keyex,gssapi-with-mic,password).
```

This means the SSH key isn't being properly authenticated.

## ✅ Solution Steps

### Step 1: Verify Your SSH Key

First, let's get your SSH private key again:

```bash
cat ~/.ssh/id_ed25519
```

Copy the **ENTIRE** output, including:
- `-----BEGIN OPENSSH PRIVATE KEY-----`
- All the key content (multiple lines)
- `-----END OPENSSH PRIVATE KEY-----`

### Step 2: Add/Update GitHub Secret

1. Go to: https://github.com/babajide2018/Grabdiz/settings/secrets/actions
2. Check if `SSH_PRIVATE_KEY` exists:
   - If it exists: Click on it → "Update" → Paste the full key → "Update secret"
   - If it doesn't exist: Click "New repository secret" → Name: `SSH_PRIVATE_KEY` → Paste the full key → "Add secret"

### Step 3: Verify Key Format

The key should look like this:
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACAizt6H8/K+9hAovfEetvz8OrPOGfHg3SkonI4QXuXucQAAAKCz4+aAs+Pm
...
-----END OPENSSH PRIVATE KEY-----
```

**Important:**
- ✅ Include the BEGIN and END lines
- ✅ Include ALL lines (even if they're long)
- ✅ No extra spaces or characters
- ✅ Copy the entire key from your terminal

### Step 4: Test SSH Connection Locally

Test if your key works locally:

```bash
ssh -p 21098 -i ~/.ssh/id_ed25519 scepgtce@server254.web-hosting.com "echo 'Connection successful'"
```

If this works locally but fails in GitHub Actions, the secret is likely incorrect.

### Step 5: Alternative - Use Password Authentication (Not Recommended)

If SSH key continues to fail, you might need to:
1. Check if your hosting provider allows SSH key authentication
2. Verify the key is added to the server's `~/.ssh/authorized_keys`
3. Contact your hosting provider for SSH setup help

## 🔍 Common Issues

### Issue 1: Key has extra whitespace
- **Fix:** Copy the key exactly as it appears, no leading/trailing spaces

### Issue 2: Key is missing BEGIN/END lines
- **Fix:** Include the entire key including BEGIN and END markers

### Issue 3: Wrong key format
- **Fix:** Make sure you're using the private key (`id_ed25519`), not the public key (`id_ed25519.pub`)

### Issue 4: Key doesn't match server
- **Fix:** Verify the key is the same one used for your other project that works

## 📝 Next Steps

After fixing the SSH key:
1. Update the GitHub secret
2. Re-run the workflow (Actions tab → "Deploy to Production" → "Re-run jobs")
3. Monitor the deployment

## 🆘 Still Having Issues?

If SSH authentication still fails:
1. Verify the key works locally (Step 4 above)
2. Check if the server accepts this key for the `scepgtce` user
3. Consider using a different deployment method (FTP, cPanel, etc.)

