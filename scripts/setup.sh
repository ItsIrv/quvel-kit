#!/bin/bash

echo "🚀 Setting up QuVel Kit..."

# Navigate to the project root
cd "$(dirname "$0")/.."

# Default deployment mode
DEPLOY_MODE="traefik-only"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --mode=*)
      DEPLOY_MODE="${1#*=}"
      shift
      ;;
    --help|-h)
      echo "Usage: $0 [--mode=MODE]"
      echo ""
      echo "Available modes:"
      echo "  traefik-only - Only Traefik in Docker, everything else local (default)"
      echo "  minimal      - Traefik + MySQL + Redis in Docker, services local"
      echo "  docker       - All services in Docker"
      echo "  local        - All services local (requires Traefik locally)"
      echo ""
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      echo "Use --help for usage information"
      exit 1
      ;;
  esac
done

echo "📋 Deployment mode: $DEPLOY_MODE"

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

# Check if npm is installed
if ! command -v npm &> /dev/null; then
  echo "❌ npm is not installed. Please install Node.js and npm before running this script."
  exit 1
fi

# Run the SSL setup script
./scripts/ssl.sh

# Configure deployment mode
echo "⚙️  Configuring deployment mode: $DEPLOY_MODE"
./scripts/deploy-mode.sh "$DEPLOY_MODE"

# Define the correct Docker Compose file path
DOCKER_COMPOSE_FILE="docker/docker-compose.yml"

# Ensure the correct file exists
if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
  echo "❌ ERROR: Docker Compose file not found at $DOCKER_COMPOSE_FILE"
  exit 1
fi

# ✅ **Copy php.ini into backend before building**
echo "📄 Copying php.ini for build..."
cp docker/php.ini backend/php.ini

# Handle different deployment modes
case "$DEPLOY_MODE" in
  "traefik-only")
    echo "🐳 Starting Traefik only (most minimal setup)..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" down
    # Only start Traefik for traefik-only mode
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d traefik
    ;;
  "minimal")
    echo "🐳 Starting minimal Docker services (Traefik, MySQL, Redis)..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" down
    # Only start infrastructure services for minimal mode
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d traefik mysql redis coverage
    ;;
  "docker")
    echo "🐳 Starting all Docker services..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" down
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d --build
    ;;
  "local")
    echo "🏠 Local mode selected - skipping Docker services"
    echo "ℹ️  You'll need to start Traefik, MySQL, and other services locally"
    docker-compose -f "$DOCKER_COMPOSE_FILE" down
    # Don't start any Docker services for local mode
    ;;
  *)
    echo "❌ Unknown deployment mode: $DEPLOY_MODE"
    exit 1
    ;;
esac

# ✅ **Remove php.ini after build**
echo "🧹 Cleaning up php.ini..."
rm -f backend/php.ini

# Handle setup tasks based on deployment mode
if [ "$DEPLOY_MODE" = "docker" ]; then
  # Full Docker mode - Laravel runs in container
  echo "⏳ Waiting for Laravel container to be ready..."
  while ! docker exec -it quvel-app test -f /var/www/vendor/autoload.php; do
    echo "   🔄 Laravel is still booting... retrying in 3s"
    sleep 3
  done

  echo "🔑 Generating Laravel APP_KEY..."
  docker exec -it quvel-app php artisan key:generate

  echo "⏳ Waiting for MySQL to be ready..."
  until docker exec -it quvel-mysql mysqladmin ping -h"localhost" --silent; do
    echo "   🔄 MySQL is still starting... retrying in 3s"
    sleep 3
  done

  echo "🔍 Verifying database connection..."
  if ! docker exec -it quvel-app php -r "new PDO('mysql:host=mysql;dbname=quvel', 'quvel_user', 'quvel_password');"; then
    echo "❌ Database connection failed! Ensure MySQL is configured correctly."
    exit 1
  fi

  echo "📌 Running Laravel migrations..."
  docker exec -it quvel-app php artisan migrate:fresh --force --seed

  echo "🔗 Linking storage..."
  docker exec -it quvel-app php artisan storage:link

  echo "📊 Generating initial PHPUnit coverage report..."
  docker exec -it quvel-app php artisan test --coverage-html=storage/coverage

elif [ "$DEPLOY_MODE" = "traefik-only" ]; then
  # Traefik-only mode - everything else runs locally
  echo "ℹ️  Traefik is ready. You can now:"
  echo "  1. Start local MySQL: brew services start mysql"
  echo "  2. Start local Redis: brew services start redis"
  echo "  3. Install dependencies: cd backend && composer install"
  echo "  4. Generate APP_KEY: cd backend && php artisan key:generate"
  echo "  5. Run migrations: cd backend && php artisan migrate:fresh --seed"
  echo "  6. Start backend: cd backend && php artisan serve --host=0.0.0.0 --port=8000"
  echo "  7. Start frontend: cd frontend && quasar dev --port 3000"

elif [ "$DEPLOY_MODE" = "minimal" ]; then
  # Minimal mode - only wait for MySQL, Laravel runs locally
  echo "⏳ Waiting for MySQL to be ready..."
  until docker exec -it quvel-mysql mysqladmin ping -h"localhost" --silent; do
    echo "   🔄 MySQL is still starting... retrying in 3s"
    sleep 3
  done

  echo "ℹ️  Database is ready. You can now:"
  echo "  1. Install dependencies: cd backend && composer install"
  echo "  2. Generate APP_KEY: cd backend && php artisan key:generate"
  echo "  3. Run migrations: cd backend && php artisan migrate:fresh --seed"
  echo "  4. Start backend: cd backend && php artisan serve --host=0.0.0.0 --port=8000"
  echo "  5. Start frontend: cd frontend && quasar dev --port 3000"

elif [ "$DEPLOY_MODE" = "local" ]; then
  # Local mode - everything runs locally
  echo "ℹ️  Local mode setup complete. You'll need to:"
  echo "  1. Install and start local MySQL"
  echo "  2. Install and start local Traefik"
  echo "  3. Install dependencies: cd backend && composer install"
  echo "  4. Generate APP_KEY: cd backend && php artisan key:generate"
  echo "  5. Run migrations: cd backend && php artisan migrate:fresh --seed"
  echo "  6. Start backend: cd backend && php artisan serve --host=0.0.0.0 --port=8000"
  echo "  7. Start frontend: cd frontend && quasar dev --port 3000"
fi

# Completion message
echo ""
echo "✅ Setup complete!"

if [ "$DEPLOY_MODE" = "docker" ]; then
  echo "🌐 Access your app at:"
  echo "   Frontend: https://quvel.127.0.0.1.nip.io"
  echo "   API: https://api.quvel.127.0.0.1.nip.io"
  echo "   Backend Coverage: https://coverage-api.quvel.127.0.0.1.nip.io"
  echo "   Traefik Dashboard: http://localhost:8080"
elif [ "$DEPLOY_MODE" = "traefik-only" ]; then
  echo "🔧 Next steps for traefik-only mode:"
  echo "   Start: brew services start mysql && brew services start redis"
  echo "   Setup: cd backend && composer install && php artisan key:generate && php artisan migrate:fresh --seed"
  echo "   Run: php artisan serve --host=0.0.0.0 --port=8000"
  echo "   And: cd frontend && quasar dev --port 3000"
  echo ""
  echo "🌐 Once running, access your app at:"
  echo "   Frontend: https://quvel.127.0.0.1.nip.io"
  echo "   API: https://api.quvel.127.0.0.1.nip.io"
elif [ "$DEPLOY_MODE" = "minimal" ]; then
  echo "🔧 Next steps for minimal mode:"
  echo "   Run: cd backend && composer install && php artisan key:generate && php artisan migrate:fresh --seed"
  echo "   Then: php artisan serve --host=0.0.0.0 --port=8000"
  echo "   And: cd frontend && quasar dev --port 3000"
  echo ""
  echo "🌐 Once running, access your app at:"
  echo "   Frontend: https://quvel.127.0.0.1.nip.io"
  echo "   API: https://api.quvel.127.0.0.1.nip.io"
elif [ "$DEPLOY_MODE" = "local" ]; then
  echo "🔧 Follow the local setup instructions above to complete setup."
fi

echo ""
echo "💡 Switch deployment modes anytime with: ./scripts/deploy-mode.sh [traefik-only|minimal|docker|local]"
