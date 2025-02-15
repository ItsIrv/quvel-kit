#!/bin/bash

echo "♻️  Resetting QuVel Kit..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Stop Docker containers and remove volumes
echo "🛑 Stopping and removing Docker containers & volumes..."
docker-compose -f docker/docker-compose.yml down --volumes --remove-orphans

# Remove old SSL certificates
echo "🗑 Removing old SSL certificates..."
rm -rf docker/certs/*

echo "✅ Reset complete! Run ./scripts/setup.sh to start fresh."
