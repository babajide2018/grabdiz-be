# Server SSH Key Setup

## Why This Is Needed

For GitHub Actions to deploy via SSH, your **public key** must be added to the server's `~/.ssh/authorized_keys` file.

## Your Public SSH Key

Here's your public key that needs to be on the server:

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICLO3ofz8r72ECi98R62/Pw6s84Z8eDdKSicjhBe5e5x ojobababajide2018@gmail.com
```

## Steps to Add Key to Server

### Option 1: Using SSH (if you can already connect)

If you can already SSH into the server, run:

```bash
# Connect to server
ssh -p 21098 scepgtce@server254.web-hosting.com

# Once connected, run these commands:
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICLO3ofz8r72ECi98R62/Pw6s84Z8eDdKSicjhBe5e5x ojobababajide2018@gmail.com" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
exit
```

### Option 2: Using cPanel File Manager

1. Log into cPanel
2. Go to **File Manager**
3. Navigate to your home directory
4. Create `.ssh` folder if it doesn't exist (make it hidden - starts with dot)
5. Create or edit `authorized_keys` file in `.ssh` folder
6. Paste your public key (one line):
   ```
   ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICLO3ofz8r72ECi98R62/Pw6s84ZeDdKSicjhBe5e5x ojobababajide2018@gmail.com
   ```
7. Set permissions:
   - `.ssh` folder: `700` (drwx------)
   - `authorized_keys` file: `600` (-rw-------)

### Option 3: Using Terminal (if you have password access)

```bash
# Connect with password
ssh -p 21098 scepgtce@server254.web-hosting.com

# Then run:
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys
# Paste the public key, save and exit (Ctrl+X, Y, Enter)
chmod 600 ~/.ssh/authorized_keys
```

## Verify It Works

After adding the key, test from your local machine:

```bash
ssh -p 21098 -i ~/.ssh/id_ed25519 scepgtce@server254.web-hosting.com "echo 'SSH key authentication works!'"
```

If this works without asking for a password, the key is set up correctly.

## Important Notes

1. **Public key only**: Only add the **public key** (`.pub` file), never the private key
2. **One key per line**: If you have multiple keys, add each on a new line
3. **Permissions matter**: 
   - `.ssh` folder must be `700` (drwx------)
   - `authorized_keys` must be `600` (-rw-------)
4. **Same key for GitHub**: The private key you add to GitHub Secrets is the matching private key for this public key

## Troubleshooting

### If SSH still asks for password:

1. Check permissions:
   ```bash
   ls -la ~/.ssh/
   # Should show:
   # drwx------ .ssh
   # -rw------- authorized_keys
   ```

2. Check if key is in authorized_keys:
   ```bash
   cat ~/.ssh/authorized_keys
   # Should show your public key
   ```

3. Check SSH logs on server:
   ```bash
   tail -f /var/log/auth.log
   # (or check your hosting provider's logs)
   ```

### If you can't access the server:

Contact your hosting provider and ask them to:
1. Add your SSH public key to `~/.ssh/authorized_keys`
2. Set proper permissions (700 for .ssh, 600 for authorized_keys)

