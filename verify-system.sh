#!/bin/bash

# Quick Verification Script for Upload System
# This script checks if all components are properly set up

echo "🔍 Laravel Upload System Verification"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check counter
checks_passed=0
checks_failed=0

# Function to check directory
check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✅${NC} Directory exists: $1"
        ((checks_passed++))
    else
        echo -e "${RED}❌${NC} Directory missing: $1"
        ((checks_failed++))
    fi
}

# Function to check file
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅${NC} File exists: $1"
        ((checks_passed++))
    else
        echo -e "${RED}❌${NC} File missing: $1"
        ((checks_failed++))
    fi
}

echo "📁 Checking Storage Directories..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
check_dir "storage/app/temp_uploads"
check_dir "storage/app/uploads"
check_dir "storage/app/public/images"
check_dir "storage/app/private/temp"
check_dir "public/storage"
echo ""

echo "📄 Checking Sample Data..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
check_file "public/sample_products.csv"
check_dir "public/sample-images"
check_file "public/sample-images/product-widget-a.jpg"
check_file "public/sample-images/large-image-test.jpg"
echo ""

echo "🔧 Checking Key Files..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
check_file "app/Services/ChunkedUploadService.php"
check_file "app/Services/ImageProcessingService.php"
check_file "app/Services/ProductImportService.php"
check_file "app/Http/Controllers/ProductImportController.php"
check_file "app/Http/Controllers/UploadController.php"
echo ""

echo "🗄️  Checking Database..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if tables exist
tables=$(php artisan tinker --execute="echo json_encode(DB::select('SHOW TABLES'), JSON_PRETTY_PRINT);" 2>/dev/null)

if echo "$tables" | grep -q "products"; then
    echo -e "${GREEN}✅${NC} Table exists: products"
    ((checks_passed++))
else
    echo -e "${RED}❌${NC} Table missing: products"
    ((checks_failed++))
fi

if echo "$tables" | grep -q "uploads"; then
    echo -e "${GREEN}✅${NC} Table exists: uploads"
    ((checks_passed++))
else
    echo -e "${RED}❌${NC} Table missing: uploads"
    ((checks_failed++))
fi

if echo "$tables" | grep -q "images"; then
    echo -e "${GREEN}✅${NC} Table exists: images"
    ((checks_passed++))
else
    echo -e "${RED}❌${NC} Table missing: images"
    ((checks_failed++))
fi

if echo "$tables" | grep -q "upload_chunks"; then
    echo -e "${GREEN}✅${NC} Table exists: upload_chunks"
    ((checks_passed++))
else
    echo -e "${RED}❌${NC} Table missing: upload_chunks"
    ((checks_failed++))
fi
echo ""

echo "🌐 Checking Server..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if pgrep -f "php artisan serve" > /dev/null; then
    echo -e "${GREEN}✅${NC} Laravel server is running"
    ((checks_passed++))
else
    echo -e "${YELLOW}⚠️${NC}  Laravel server is NOT running"
    echo -e "   Run: ${YELLOW}php artisan serve${NC}"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 Results:"
echo -e "   ${GREEN}Passed:${NC} $checks_passed"
echo -e "   ${RED}Failed:${NC} $checks_failed"
echo ""

if [ $checks_failed -eq 0 ]; then
    echo -e "${GREEN}🎉 All checks passed! System is ready.${NC}"
    echo ""
    echo "🚀 Next Steps:"
    echo "   1. Make sure server is running: php artisan serve"
    echo "   2. Open test interface: http://127.0.0.1:8000/test"
    echo "   3. Upload sample CSV"
    echo "   4. Upload sample image"
    echo "   5. Attach image to product"
    echo ""
else
    echo -e "${YELLOW}⚠️  Some checks failed. Please review the issues above.${NC}"
    echo ""
fi

exit $checks_failed
