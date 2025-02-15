#!/bin/bash

echo "🔄 Restarting QuVel Kit..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Stop services
./scripts/stop.sh

# Start services
./scripts/start.sh

echo "✅ QuVel Kit has been restarted!"
