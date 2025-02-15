#!/bin/bash

echo "🚀 Setting up QuVel Kit..."

# Navigate to the project root
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

# Install mkcert using npm globally if not already installed
if ! command -v mkcert &> /dev/null; then
  echo "📦 Installing mkcert via npm..."
  npm install -g mkcert
fi

# Ensure mkcert local CA is set up
mkcert -install

# Generate SSL certificates using mkcert
mkdir -p "$(dirname "$0")/../docker/certs"
if [ ! -f docker/certs/selfsigned.crt ] || [ ! -f docker/certs/selfsigned.key ]; then
  echo "🔐 Generating mkcert SSL certificates..."
  mkcert -cert-file docker/certs/selfsigned.crt -key-file docker/certs/selfsigned.key quvel.127.0.0.1.nip.io api.quvel.127.0.0.1.nip.io
fi

# Ensure certificates.yaml exists for Traefik
if [ ! -f docker/certs/certificates.yaml ]; then
  echo "📄 Creating certificates.yaml..."
  cat <<EOF > docker/certs/certificates.yaml
tls:
  certificates:
    - certFile: "/certs/selfsigned.crt"
      keyFile: "/certs/selfsigned.key"
      stores:
        - default
      domains:
        - main: "quvel.127.0.0.1.nip.io"
          sans:
            - "api.quvel.127.0.0.1.nip.io"
EOF
fi

# Define the correct Docker Compose file path
DOCKER_COMPOSE_FILE="docker/docker-compose.yml"

# Ensure the correct file exists
if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
  echo "❌ ERROR: Docker Compose file not found at $DOCKER_COMPOSE_FILE"
  exit 1
fi

# Stop any existing containers
echo "🐳 Stopping existing Docker containers..."
docker-compose -f "$DOCKER_COMPOSE_FILE" down

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose -f "$DOCKER_COMPOSE_FILE" up -d --build

# Run Laravel migrations
echo "📌 Running Laravel migrations..."
docker exec -it quvel-app php artisan migrate --force

# Run storage linking
echo "🔗 Linking storage..."
docker exec -it quvel-app php artisan storage:link

# Completion message
echo "✅ Setup complete! Access your app at:"
echo "   🌐 Frontend: https://quvel.127.0.0.1.nip.io"
echo "   ⚙️  API: https://api.quvel.127.0.0.1.nip.io"
echo "   📊 Traefik Dashboard: http://localhost:8080"
