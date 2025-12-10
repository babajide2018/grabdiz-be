#!/usr/bin/env php
<?php
/**
 * Deploy .env.prod to server's /backend folder as .env
 *
 * Usage: php scripts/deploy-env.php (from /backend directory)
 */

// Configuration
$SERVER_HOST = 'server254.web-hosting.com';
$SERVER_PORT = 21098;
$SERVER_USER = 'scepgtce';
$SERVER_PATH = '~/demo.grabdiz.co.uk/backend';
$REMOTE_ENV_FILE = '.env';

// Find .env.prod file (should be in backend directory or project root)
$LOCAL_ENV_FILE = __DIR__ . '/../.env.prod';
if (!file_exists($LOCAL_ENV_FILE)) {
    // Try project root (two levels up from scripts)
    $LOCAL_ENV_FILE = __DIR__ . '/../../.env.prod';
    if (!file_exists($LOCAL_ENV_FILE)) {
        // Try current directory
        $LOCAL_ENV_FILE = getcwd() . '/.env.prod';
    }
}

// Colors for output
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[1;33m";
$NC = "\033[0m"; // No Color

echo "🚀 Deploying .env.prod to server...\n\n";

// Check if .env.prod exists
if (!file_exists($LOCAL_ENV_FILE)) {
    echo "{$RED}❌ Error: .env.prod file not found{$NC}\n";
    echo "   Searched in: " . __DIR__ . "/../../.env.prod\n";
    echo "   Please ensure .env.prod exists in the project root directory.\n";
    echo "   Expected location: " . dirname(dirname(__DIR__)) . "/.env.prod\n";
    exit(1);
}

echo "✅ Found .env.prod file at: {$LOCAL_ENV_FILE}\n";

// Read the .env.prod file
$envContent = file_get_contents($LOCAL_ENV_FILE);
if ($envContent === false) {
    echo "{$RED}❌ Error: Could not read .env.prod file{$NC}\n";
    exit(1);
}

echo "✅ Read .env.prod content (" . strlen($envContent) . " bytes)\n\n";

// Build SSH command to backup existing .env and write new one
$backupCommand = "cd {$SERVER_PATH} && " .
                 "if [ -f .env ]; then " .
                 "  cp .env .env.backup.$(date +%Y%m%d_%H%M%S) && " .
                 "  echo '✅ Backed up existing .env'; " .
                 "else " .
                 "  echo '⚠️  No existing .env to backup'; " .
                 "fi";

// Execute backup command
echo "💾 Backing up existing .env on server...\n";
$backupOutput = [];
$backupReturn = 0;
exec(
    "ssh -p {$SERVER_PORT} {$SERVER_USER}@{$SERVER_HOST} '{$backupCommand}' 2>&1",
    $backupOutput,
    $backupReturn
);

if ($backupReturn !== 0) {
    echo "{$YELLOW}⚠️  Warning: Backup command had issues (this is okay if .env doesn't exist){$NC}\n";
} else {
    echo implode("\n", $backupOutput) . "\n";
}

// Write file via SCP (more reliable than heredoc)
echo "\n📝 Writing .env.prod to server as .env...\n";

// Create temporary file
$tempFile = sys_get_temp_dir() . '/env_' . uniqid() . '.txt';
file_put_contents($tempFile, $envContent);

// Copy file via SCP
$scpCommand = "scp -P {$SERVER_PORT} {$tempFile} {$SERVER_USER}@{$SERVER_HOST}:{$SERVER_PATH}/.env.tmp";
exec($scpCommand . " 2>&1", $scpOutput, $scpReturn);

if ($scpReturn !== 0) {
    unlink($tempFile);
    echo "{$RED}❌ Error: Failed to copy file via SCP{$NC}\n";
    echo implode("\n", $scpOutput) . "\n";
    exit(1);
}

// Move and set permissions on server
$finalizeCommand = "cd {$SERVER_PATH} && " .
                  "mv .env.tmp {$REMOTE_ENV_FILE} && " .
                  "chmod 600 {$REMOTE_ENV_FILE} && " .
                  "echo '✅ .env file deployed and permissions set'";

exec(
    "ssh -p {$SERVER_PORT} {$SERVER_USER}@{$SERVER_HOST} '{$finalizeCommand}' 2>&1",
    $finalizeOutput,
    $finalizeReturn
);

unlink($tempFile);

if ($finalizeReturn !== 0) {
    echo "{$RED}❌ Error: Failed to finalize .env file on server{$NC}\n";
    echo implode("\n", $finalizeOutput) . "\n";
    exit(1);
}

echo implode("\n", $finalizeOutput) . "\n";

// Verify the file was created
echo "\n🔍 Verifying deployment...\n";
$verifyCommand = "cd {$SERVER_PATH} && " .
                 "if [ -f {$REMOTE_ENV_FILE} ]; then " .
                 "  echo '✅ .env file exists on server'; " .
                 "  FILE_SIZE=\$(stat -f%z {$REMOTE_ENV_FILE} 2>/dev/null || stat -c%s {$REMOTE_ENV_FILE} 2>/dev/null || echo 'unknown'); " .
                 "  echo \"📊 File size: \$FILE_SIZE bytes\"; " .
                 "  exit 0; " .
                 "else " .
                 "  echo '❌ .env file not found on server'; " .
                 "  exit 1; " .
                 "fi";

exec(
    "ssh -p {$SERVER_PORT} {$SERVER_USER}@{$SERVER_HOST} '{$verifyCommand}' 2>&1",
    $verifyOutput,
    $verifyReturn
);

echo implode("\n", $verifyOutput) . "\n";

if ($verifyReturn === 0) {
    echo "\n{$GREEN}✅ Successfully deployed .env.prod to server!{$NC}\n";
    echo "📍 Location: {$SERVER_PATH}/{$REMOTE_ENV_FILE}\n";
} else {
    echo "\n{$RED}❌ Verification failed{$NC}\n";
    exit(1);
}

