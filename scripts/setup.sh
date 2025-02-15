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

# Install backend dependencies
echo "📦 Installing Laravel dependencies..."
cd backend
composer install
php artisan key:generate
cd ..

# Install frontend dependencies
echo "📦 Installing Quasar dependencies..."
cd frontend
yarn install
cd ..

# Start Docker
echo "🐳 Starting Docker containers..."
docker-compose -f docker/docker-compose.yml up -d --build

# Run Laravel migrations
echo "📌 Running Laravel migrations..."
docker exec -it quvel-app php artisan migrate --force

# Run storage linking
echo "🔗 Linking storage..."
docker exec -it quvel-app php artisan storage:link

echo "✅ Setup complete!"
