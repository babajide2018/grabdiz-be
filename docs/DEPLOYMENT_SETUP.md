# Deployment Setup Guide

## ✅ Repository Setup

Your GitHub repository: `git@github.com:babajide2018/Grabdiz.git`

## 🔑 GitHub Secrets Configuration

Before the workflow can run, you need to add your SSH private key to GitHub:

1. Go to: https://github.com/babajide2018/Grabdiz/settings/secrets/actions
2. Click **"New repository secret"**
3. Name: `SSH_PRIVATE_KEY`
4. Value: Your SSH private key (the same one you use for the other project)
5. Click **"Add secret"**

### How to get your SSH private key:

If you already have it from the other project, use that. Otherwise:

```bash
# If you have the key file locally
cat ~/.ssh/id_rsa

# Or if it's named differently
cat ~/.ssh/your_key_name
```

**Note:** Make sure to copy the **entire** key including:
- `-----BEGIN OPENSSH PRIVATE KEY-----` (or `-----BEGIN RSA PRIVATE KEY-----`)
- All the key content
- `-----END OPENSSH PRIVATE KEY-----` (or `-----END RSA PRIVATE KEY-----`)

## 🚀 Deployment Workflow

The workflow (`.github/workflows/deploy.yml`) is configured to:

- **Trigger:** Automatically on push to `main` branch, or manually via GitHub UI
- **Server:** `server254.web-hosting.com:21098`
- **User:** `scepgtce`
- **Deploy Path:** `~/grabdiz.co.uk/`

### What the workflow does:

1. ✅ Checks out code
2. ✅ Sets up Node.js 20
3. ✅ Installs dependencies
4. ✅ Builds Next.js as static export
5. ✅ Copies `.htaccess` to output
6. ✅ **Checks Node.js availability on server** (important!)
7. ✅ Deploys files via rsync
8. ✅ Sets proper permissions


## 📝 First Deployment Steps

1. **Add SSH secret to GitHub** (see above)

2. **Commit and push your code:**
   ```bash
   git add .
   git commit -m "Initial setup with deployment workflow"
   git push -u origin main
   ```

3. **Monitor the deployment:**
   - Go to: https://github.com/babajide2018/Grabdiz/actions
   - Click on the running workflow
   - Watch the "Check Node.js availability" step output

4. **After deployment:**
   - Check the workflow output for Node.js availability
   - Based on the result, we'll decide on the backend approach

## 🔍 What to Look For

After the first deployment, check the workflow logs for:

```
🔍 Checking Node.js availability on server...
Node.js version: v20.x.x  (or NOT_FOUND)
npm version: 10.x.x  (or NOT_FOUND)
```

### If Node.js is available:
- ✅ We can use Next.js server mode
- ✅ API routes will work
- ✅ Prisma will work
- We'll update the workflow to deploy the full Next.js app

### If Node.js is NOT available:
- ⚠️ Keep static export for frontend
- ⚠️ Need separate backend (PHP API or separate Node.js server)
- ⚠️ Use `mysql2` directly instead of Prisma

## 🛠️ Manual Deployment (Optional)

If you want to test locally first:

```bash
# Build the static export
npm run build

# Test locally (optional)
npx serve out

# Manual rsync (if needed)
rsync -avz --delete \
  -e "ssh -p 21098" \
  out/ scepgtce@server254.web-hosting.com:~/grabdiz.co.uk/
```

## 📋 Next Steps After First Deployment

1. ✅ Verify deployment worked
2. ✅ Check Node.js availability result
3. ✅ Decide on backend approach (Prisma vs mysql2 vs PHP)
4. ✅ Update configuration accordingly
5. ✅ Test the deployed site

---

**Ready to deploy?** Just add the SSH secret and push to `main`! 🚀

