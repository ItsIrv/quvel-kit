#!/bin/bash

echo "🛑 Stopping QuVel Kit..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Stop Docker containers
docker-compose -f docker/docker-compose.yml down

echo "✅ QuVel Kit has been stopped."
