#!/bin/bash

echo "🚀 Starting QuVel Kit..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose -f docker/docker-compose.yml up -d

# Show running containers
echo "📌 Running containers:"
docker ps

echo "✅ QuVel Kit is up and running!"
