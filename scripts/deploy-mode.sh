#!/bin/bash

# QuVel Kit Deployment Mode Switcher
# Switches between different deployment configurations

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration paths
BACKEND_CONFIG="docker/traefik/dynamic/backend.yml"
FRONTEND_CONFIG="docker/traefik/dynamic/frontend.yml"

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[DEPLOY]${NC} $1"
}

# Function to show current mode
show_current_mode() {
    print_header "Detecting current deployment mode..."
    
    # Check backend configuration
    if grep -q "host.docker.internal:8000" "$BACKEND_CONFIG" && ! grep -q "^#.*host.docker.internal:8000" "$BACKEND_CONFIG"; then
        if grep -q "host.docker.internal:3000" "$FRONTEND_CONFIG" && ! grep -q "^#.*host.docker.internal:3000" "$FRONTEND_CONFIG"; then
            echo -e "Current mode: ${GREEN}minimal${NC} (Traefik in Docker, services local)"
        fi
    elif grep -q "quvel-app:8000" "$BACKEND_CONFIG" && ! grep -q "^#.*quvel-app:8000" "$BACKEND_CONFIG"; then
        if grep -q "quvel-frontend:9000" "$FRONTEND_CONFIG" && ! grep -q "^#.*quvel-frontend:9000" "$FRONTEND_CONFIG"; then
            echo -e "Current mode: ${GREEN}docker${NC} (All services in Docker)"
        fi
    elif grep -q "127.0.0.1:8000" "$BACKEND_CONFIG" && ! grep -q "^#.*127.0.0.1:8000" "$BACKEND_CONFIG"; then
        if grep -q "127.0.0.1:3000" "$FRONTEND_CONFIG" && ! grep -q "^#.*127.0.0.1:3000" "$FRONTEND_CONFIG"; then
            echo -e "Current mode: ${GREEN}local${NC} (All services local)"
        fi
    else
        echo -e "Current mode: ${YELLOW}unknown${NC} (Mixed or custom configuration)"
    fi
}

# Function to update traefik configuration for traefik-only mode
configure_traefik_only() {
    print_status "Configuring for traefik-only mode (only Traefik in Docker, everything else local)..."
    
    # Backend configuration - same as minimal since both use host.docker.internal
    sed -i '' 's/^#.*- url.*host\.docker\.internal:8000.*/          - url: '\''http:\/\/host.docker.internal:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^          - url.*quvel-app:8000.*/          # - url: '\''http:\/\/quvel-app:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^          - url.*127\.0\.0\.1:8000.*/          # - url: '\''http:\/\/127.0.0.1:8000'\''/' "$BACKEND_CONFIG"
    
    # Frontend configuration - same as minimal since both use host.docker.internal
    sed -i '' 's/^#.*- url.*host\.docker\.internal:3000.*/          - url: '\''https:\/\/host.docker.internal:3000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^          - url.*quvel-frontend:9000.*/          # - url: '\''https:\/\/quvel-frontend:9000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^          - url.*127\.0\.0\.1:3000.*/          # - url: '\''https:\/\/127.0.0.1:3000'\''/' "$FRONTEND_CONFIG"
    
    print_status "Configuration updated for traefik-only mode"
    print_warning "You'll need to start all services locally:"
    echo "  MySQL:    brew services start mysql"
    echo "  Redis:    brew services start redis"
    echo "  Backend:  cd backend && php artisan serve --host=0.0.0.0 --port=8000"
    echo "  Frontend: cd frontend && quasar dev --port 3000"
}

# Function to update traefik configuration for minimal mode
configure_minimal() {
    print_status "Configuring for minimal resource mode (Traefik + DB in Docker, services local)..."
    
    # Backend configuration
    sed -i '' 's/^#.*- url.*host\.docker\.internal:8000.*/          - url: '\''http:\/\/host.docker.internal:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^          - url.*quvel-app:8000.*/          # - url: '\''http:\/\/quvel-app:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^          - url.*127\.0\.0\.1:8000.*/          # - url: '\''http:\/\/127.0.0.1:8000'\''/' "$BACKEND_CONFIG"
    
    # Frontend configuration
    sed -i '' 's/^#.*- url.*host\.docker\.internal:3000.*/          - url: '\''https:\/\/host.docker.internal:3000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^          - url.*quvel-frontend:9000.*/          # - url: '\''https:\/\/quvel-frontend:9000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^          - url.*127\.0\.0\.1:3000.*/          # - url: '\''https:\/\/127.0.0.1:3000'\''/' "$FRONTEND_CONFIG"
    
    print_status "Configuration updated for minimal mode"
    print_warning "You'll need to start backend and frontend services locally:"
    echo "  Backend:  cd backend && php artisan serve --host=0.0.0.0 --port=8000"
    echo "  Frontend: cd frontend && quasar dev --port 3000"
}

# Function to update traefik configuration for docker mode
configure_docker() {
    print_status "Configuring for full Docker mode (all services in Docker)..."
    
    # Backend configuration
    sed -i '' 's/^          - url.*host\.docker\.internal:8000.*/          # - url: '\''http:\/\/host.docker.internal:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^#.*- url.*quvel-app:8000.*/          - url: '\''http:\/\/quvel-app:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^          - url.*127\.0\.0\.1:8000.*/          # - url: '\''http:\/\/127.0.0.1:8000'\''/' "$BACKEND_CONFIG"
    
    # Frontend configuration
    sed -i '' 's/^          - url.*host\.docker\.internal:3000.*/          # - url: '\''https:\/\/host.docker.internal:3000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^#.*- url.*quvel-frontend:9000.*/          - url: '\''https:\/\/quvel-frontend:9000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^          - url.*127\.0\.0\.1:3000.*/          # - url: '\''https:\/\/127.0.0.1:3000'\''/' "$FRONTEND_CONFIG"
    
    print_status "Configuration updated for Docker mode"
    print_warning "Run './scripts/restart.sh' to start all services in Docker"
}

# Function to update traefik configuration for local mode
configure_local() {
    print_status "Configuring for fully local mode (all services local)..."
    
    # Backend configuration
    sed -i '' 's/^          - url.*host\.docker\.internal:8000.*/          # - url: '\''http:\/\/host.docker.internal:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^          - url.*quvel-app:8000.*/          # - url: '\''http:\/\/quvel-app:8000'\''/' "$BACKEND_CONFIG"
    sed -i '' 's/^#.*- url.*127\.0\.0\.1:8000.*/          - url: '\''http:\/\/127.0.0.1:8000'\''/' "$BACKEND_CONFIG"
    
    # Frontend configuration
    sed -i '' 's/^          - url.*host\.docker\.internal:3000.*/          # - url: '\''https:\/\/host.docker.internal:3000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^          - url.*quvel-frontend:9000.*/          # - url: '\''https:\/\/quvel-frontend:9000'\''/' "$FRONTEND_CONFIG"
    sed -i '' 's/^#.*- url.*127\.0\.0\.1:3000.*/          - url: '\''https:\/\/127.0.0.1:3000'\''/' "$FRONTEND_CONFIG"
    
    print_status "Configuration updated for local mode"
    print_warning "You'll need to install and configure Traefik locally:"
    echo "  brew install traefik"
    echo "  traefik --configfile=docker/traefik/traefik.yml"
    print_warning "And start backend and frontend services locally:"
    echo "  Backend:  cd backend && php artisan serve --host=0.0.0.0 --port=8000"
    echo "  Frontend: cd frontend && quasar dev --port 3000"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [MODE]"
    echo ""
    echo "Available modes:"
    echo "  traefik-only - Only Traefik in Docker, everything else local (most minimal)"
    echo "  minimal      - Traefik + MySQL + Redis in Docker, backend/frontend local"
    echo "  docker       - All services in Docker containers"
    echo "  local        - All services local (including Traefik)"
    echo "  current      - Show current deployment mode"
    echo ""
    echo "Examples:"
    echo "  $0 traefik-only  # Only Traefik in Docker (maximum local setup)"
    echo "  $0 minimal       # Traefik + database services in Docker"
    echo "  $0 docker        # Full Docker mode"
    echo "  $0 local         # Fully local mode"
    echo "  $0 current       # Show current mode"
}

# Main script logic
case "$1" in
    "traefik-only")
        configure_traefik_only
        ;;
    "minimal")
        configure_minimal
        ;;
    "docker")
        configure_docker
        ;;
    "local")
        configure_local
        ;;
    "current"|"")
        show_current_mode
        ;;
    *)
        print_error "Unknown mode: $1"
        show_usage
        exit 1
        ;;
esac

echo ""
print_status "Deployment mode configuration complete!"