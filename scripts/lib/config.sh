#!/bin/bash

# QuVel Kit Configuration
# Service definitions and deployment mode configurations

# Source common utilities if not already loaded
if [[ -z "$PROJECT_ROOT" ]]; then
    source "$(dirname "${BASH_SOURCE[0]}")/common.sh"
fi

# Service definitions
# Using functions instead of associative arrays for bash 3.x compatibility
get_service_name() {
    case "$1" in
        traefik) echo "quvel-traefik" ;;
        app) echo "quvel-app" ;;
        frontend) echo "quvel-frontend" ;;
        mysql) echo "quvel-mysql" ;;
        redis) echo "quvel-redis" ;;
        coverage) echo "quvel-coverage" ;;
        *) echo "" ;;
    esac
}

# Docker services per deployment mode
# Using functions instead of associative arrays for bash 3.x compatibility

# Local services per deployment mode

# Get Docker services for a deployment mode
get_docker_services() {
    local mode="$1"
    case "$mode" in
        traefik-only) echo "traefik" ;;
        minimal) echo "traefik mysql redis coverage" ;;
        docker) echo "traefik app frontend mysql redis coverage" ;;
        local) echo "" ;;
        *) echo "" ;;
    esac
}

# Get local services for a deployment mode
get_local_services() {
    local mode="$1"
    case "$mode" in
        traefik-only) echo "mysql redis backend frontend" ;;
        minimal) echo "backend frontend" ;;
        docker) echo "" ;;
        local) echo "traefik mysql redis backend frontend" ;;
        *) echo "" ;;
    esac
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
    get_service_name "$service"
}

# URLs and ports configuration
# Using functions instead of associative arrays for bash 3.x compatibility
get_service_url() {
    case "$1" in
        frontend_dev) echo "http://localhost:3000" ;;
        backend_dev) echo "http://localhost:8000" ;;
        traefik_dashboard) echo "http://localhost:8080" ;;
        frontend_public) echo "https://quvel.127.0.0.1.nip.io" ;;
        api_public) echo "https://api.quvel.127.0.0.1.nip.io" ;;
        coverage_public) echo "https://coverage-api.quvel.127.0.0.1.nip.io" ;;
        *) echo "" ;;
    esac
}

# Required local dependencies by mode

# Get required local dependencies for mode
get_required_deps() {
    local mode="$1"
    case "$mode" in
        traefik-only) echo "mysql php composer npm node redis-cli" ;;
        minimal) echo "php composer npm node" ;;
        docker) echo "" ;;
        local) echo "traefik mysql redis-cli php composer npm node" ;;
        *) echo "" ;;
    esac
}

# Check if all required dependencies are installed
check_dependencies() {
    local mode="$1"
    local deps=$(get_required_deps "$mode")
    local missing_deps=()
    
    for dep in $deps; do
        # Special handling for redis - check for redis-cli or redis-server
        if [[ "$dep" == "redis-cli" ]]; then
            if ! command_exists "redis-cli" && ! command_exists "redis-server"; then
                missing_deps+=("redis (redis-cli or redis-server)")
            fi
        else
            if ! command_exists "$dep"; then
                missing_deps+=("$dep")
            fi
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
  - Frontend: $(get_service_url frontend_public)
  - API: $(get_service_url api_public)
  - Traefik Dashboard: $(get_service_url traefik_dashboard)
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
