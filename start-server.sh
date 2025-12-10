#!/bin/bash

# Laravel Development Server Starter Script
# This script starts the Laravel development server

echo "🚀 Starting Laravel Development Server..."
echo ""
echo "Server will be available at: http://127.0.0.1:8000"
echo "Test interface: http://127.0.0.1:8000/test"
echo ""
echo "Press Ctrl+C to stop the server"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

php artisan serve
