#!/bin/bash

echo "🔍 Checking CageFS Status"
echo "========================"
echo ""

# Check if CageFS commands are available
echo "1. Checking for CageFS commands..."
if command -v cagefsctl &> /dev/null; then
    echo "   ✅ cagefsctl found"
    cagefsctl --status 2>/dev/null || echo "   ⚠️  Cannot run cagefsctl (requires admin)"
else
    echo "   ⚠️  cagefsctl not found in PATH"
fi
echo ""

# Check if in CageFS environment
echo "2. Checking CageFS mount..."
if mount | grep -q cagefs; then
    echo "   ✅ CageFS is mounted"
    mount | grep cagefs | head -3
else
    echo "   ℹ️  CageFS mount not visible (might be normal)"
fi
echo ""

# Check home directory
echo "3. Your environment:"
echo "   Home: $HOME"
echo "   Current: $(pwd)"
echo "   User: $(whoami)"
echo ""

# Check for CloudLinux
echo "4. Checking for CloudLinux..."
if [ -f /etc/cloudlinux-release ]; then
    echo "   ✅ CloudLinux detected"
    cat /etc/cloudlinux-release
else
    echo "   ℹ️  CloudLinux not detected (or file not accessible)"
fi
echo ""

# Check CageFS directory (if accessible)
echo "5. Checking CageFS directories..."
if [ -d /cagefs ]; then
    echo "   ✅ /cagefs directory exists"
    ls -la /cagefs 2>/dev/null | head -5 || echo "   ⚠️  Cannot list /cagefs"
else
    echo "   ℹ️  /cagefs not accessible (normal for regular users)"
fi
echo ""

echo "📋 Summary:"
echo "   - CageFS reset requires admin/root access"
echo "   - Contact your hosting provider for CageFS management"
echo "   - For application cleanup, use: pm2 restart all"
