#!/bin/bash

echo "📜 Fetching QuVel Kit logs..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Show logs for all running containers
docker-compose -f docker/docker-compose.yml logs -f --tail=100
