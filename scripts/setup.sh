#!/bin/bash

echo "🚀 Setting up QuVel Kit..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Ensure backend `.env` file exists
if [ ! -f backend/.env ]; then
  echo "⚙️  Creating Laravel .env file..."
  cp backend/.env.example backend/.env
fi

# Ensure frontend `.env` file exists
if [ ! -f frontend/.env ]; then
  echo "⚙️  Creating Quasar .env file..."
  cp frontend/.env.example frontend/.env
fi

# Start Docker
echo "🐳 Starting Docker containers..."
docker-compose -f docker/docker-compose.yml up -d --build

# Wait for containers to be ready
echo "⏳ Waiting for containers to be ready..."
sleep 5  # Small delay to ensure services start

# Run Laravel migrations
echo "📌 Running Laravel migrations..."
docker exec -it quvel-app php artisan migrate --force

# Run storage linking
echo "🔗 Linking storage..."
docker exec -it quvel-app php artisan storage:link

echo "✅ Setup complete!"
