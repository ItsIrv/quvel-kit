#!/bin/bash

# QuVel Kit Configuration
# Service definitions and deployment mode configurations

# Source common utilities if not already loaded
if [[ -z "$PROJECT_ROOT" ]]; then
    source "$(dirname "${BASH_SOURCE[0]}")/common.sh"
fi

# Service definitions
declare -A SERVICES
SERVICES[traefik]="quvel-traefik"
SERVICES[app]="quvel-app"
SERVICES[frontend]="quvel-frontend"
SERVICES[mysql]="quvel-mysql"
SERVICES[redis]="quvel-redis"
SERVICES[coverage]="quvel-coverage"

# Docker services per deployment mode
declare -A DOCKER_SERVICES_BY_MODE

# traefik-only: Only Traefik runs in Docker
DOCKER_SERVICES_BY_MODE[traefik-only]="traefik"

# minimal: Traefik + MySQL + Redis + Coverage run in Docker
DOCKER_SERVICES_BY_MODE[minimal]="traefik mysql redis coverage"

# docker: All services run in Docker
DOCKER_SERVICES_BY_MODE[docker]="traefik app frontend mysql redis coverage"

# local: No services run in Docker (all local)
DOCKER_SERVICES_BY_MODE[local]=""

# Local services per deployment mode
declare -A LOCAL_SERVICES_BY_MODE

# traefik-only: App and Frontend run locally
LOCAL_SERVICES_BY_MODE[traefik-only]="mysql redis backend frontend"

# minimal: App and Frontend run locally
LOCAL_SERVICES_BY_MODE[minimal]="backend frontend"

# docker: No services run locally
LOCAL_SERVICES_BY_MODE[docker]=""

# local: All services run locally including Traefik
LOCAL_SERVICES_BY_MODE[local]="traefik mysql redis backend frontend"

# Get Docker services for a deployment mode
get_docker_services() {
    local mode="$1"
    echo "${DOCKER_SERVICES_BY_MODE[$mode]:-}"
}

# Get local services for a deployment mode
get_local_services() {
    local mode="$1"
    echo "${LOCAL_SERVICES_BY_MODE[$mode]:-}"
}

# Check if service runs in Docker for given mode
service_runs_in_docker() {
    local service="$1"
    local mode="$2"
    local docker_services=$(get_docker_services "$mode")
    [[ " $docker_services " =~ " $service " ]]
}

# Check if service runs locally for given mode
service_runs_locally() {
    local service="$1"
    local mode="$2"
    local local_services=$(get_local_services "$mode")
    [[ " $local_services " =~ " $service " ]]
}

# Get container name for service
get_service_container() {
    local service="$1"
    echo "${SERVICES[$service]:-}"
}

# URLs and ports configuration
declare -A SERVICE_URLS
SERVICE_URLS[frontend_dev]="http://localhost:3000"
SERVICE_URLS[backend_dev]="http://localhost:8000"
SERVICE_URLS[traefik_dashboard]="http://localhost:8080"
SERVICE_URLS[frontend_public]="https://quvel.127.0.0.1.nip.io"
SERVICE_URLS[api_public]="https://api.quvel.127.0.0.1.nip.io"
SERVICE_URLS[coverage_public]="https://coverage-api.quvel.127.0.0.1.nip.io"

# Required local dependencies by mode
declare -A REQUIRED_LOCAL_DEPS

REQUIRED_LOCAL_DEPS[traefik-only]="mysql redis php composer npm node"
REQUIRED_LOCAL_DEPS[minimal]="php composer npm node"
REQUIRED_LOCAL_DEPS[docker]=""
REQUIRED_LOCAL_DEPS[local]="traefik mysql redis php composer npm node"

# Get required local dependencies for mode
get_required_deps() {
    local mode="$1"
    echo "${REQUIRED_LOCAL_DEPS[$mode]:-}"
}

# Check if all required dependencies are installed
check_dependencies() {
    local mode="$1"
    local deps=$(get_required_deps "$mode")
    local missing_deps=()
    
    for dep in $deps; do
        if ! command_exists "$dep"; then
            missing_deps+=("$dep")
        fi
    done
    
    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        print_error "Missing required dependencies for $mode mode:"
        for dep in "${missing_deps[@]}"; do
            echo "  - $dep"
        done
        return 1
    fi
    
    return 0
}

# Instructions for each mode
get_mode_instructions() {
    local mode="$1"
    
    case "$mode" in
        "traefik-only")
            cat << EOF
Next steps for traefik-only mode:
  1. Start local services: brew services start mysql && brew services start redis
  2. Install dependencies: cd backend && composer install
  3. Generate APP_KEY: php artisan key:generate
  4. Run migrations: php artisan migrate:fresh --seed
  5. Start backend: php artisan serve --host=0.0.0.0 --port=8000
  6. Start frontend: cd ../frontend && npm run dev
EOF
            ;;
        "minimal")
            cat << EOF
Next steps for minimal mode:
  1. Install dependencies: cd backend && composer install
  2. Generate APP_KEY: php artisan key:generate
  3. Run migrations: php artisan migrate:fresh --seed
  4. Start backend: php artisan serve --host=0.0.0.0 --port=8000
  5. Start frontend: cd ../frontend && npm run dev
EOF
            ;;
        "docker")
            cat << EOF
Docker mode is fully automated.
All services are running in containers.
Access your app at:
  - Frontend: ${SERVICE_URLS[frontend_public]}
  - API: ${SERVICE_URLS[api_public]}
  - Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}
EOF
            ;;
        "local")
            cat << EOF
Next steps for local mode:
  1. Install and start local services (Traefik, MySQL, Redis)
  2. Install dependencies: cd backend && composer install
  3. Generate APP_KEY: php artisan key:generate
  4. Run migrations: php artisan migrate:fresh --seed
  5. Start Traefik: traefik --configfile=docker/traefik/traefik.yml
  6. Start backend: php artisan serve --host=0.0.0.0 --port=8000
  7. Start frontend: cd ../frontend && npm run dev
EOF
            ;;
    esac
}