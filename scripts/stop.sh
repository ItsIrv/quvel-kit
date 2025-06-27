#!/bin/bash

# QuVel Kit Stop Script
# Stops services based on deployment mode

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Stop Services"
    cat << EOF
Stops QuVel Kit services based on the current or specified deployment mode.

Usage: $0 [OPTIONS] [SERVICES...]

Options:
  --mode=MODE          Override deployment mode (traefik-only, minimal, docker, local)
  --all                Stop all Docker containers regardless of mode
  --help,-h           Show this help message

Services (optional, if specified only these will be stopped):
  traefik             Traefik reverse proxy
  app                 Laravel backend application
  frontend            Quasar frontend application
  mysql               MySQL database
  redis               Redis cache
  coverage            Coverage reporting service

Examples:
  $0                           # Stop all services for current mode
  $0 --mode=docker             # Stop all services for docker mode
  $0 traefik mysql             # Stop only traefik and mysql services
  $0 --all                     # Stop all Docker containers
  $0 --mode=minimal traefik    # Stop traefik in minimal mode

Deployment Modes:
  traefik-only    - Only Traefik in Docker (most minimal)
  minimal         - Traefik + MySQL + Redis in Docker, backend/frontend local
  docker          - All services in Docker containers
  local           - All services local (no Docker services to stop)

Current deployment mode: $(get_deployment_mode || echo "not set")
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "🛑 Stopping QuVel Kit services..."

# Navigate to project root
cd "$PROJECT_ROOT"

# Check for --all flag
STOP_ALL=false
for arg in "${REMAINING_ARGS[@]}"; do
    if [[ "$arg" == "--all" ]]; then
        STOP_ALL=true
        # Remove --all from remaining args
        REMAINING_ARGS=("${REMAINING_ARGS[@]/$arg}")
        break
    fi
done

# Determine deployment mode
if [[ -n "$MODE" ]]; then
    if ! validate_deployment_mode "$MODE"; then
        print_error "Invalid deployment mode: $MODE"
        print_error "Valid modes: traefik-only, minimal, docker, local"
        exit 1
    fi
    print_status "📋 Using specified mode: $MODE"
elif [[ "$STOP_ALL" != true ]]; then
    MODE=$(get_deployment_mode)
    if [[ -z "$MODE" ]]; then
        print_warning "No deployment mode found. Use --all to stop all containers or specify --mode=MODE"
        print_status "Attempting to stop all Docker containers..."
        STOP_ALL=true
    else
        print_status "📋 Using stored mode: $MODE"
    fi
fi

# Check if specific services were requested
REQUESTED_SERVICES=("${REMAINING_ARGS[@]}")

# Validate Docker Compose file exists
if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
    print_error "Docker Compose file not found: $DOCKER_COMPOSE_FILE"
    exit 1
fi

# Handle stopping all containers
if [[ "$STOP_ALL" == true ]]; then
    print_status "🐳 Stopping all Docker containers..."
    if docker-compose -f "$DOCKER_COMPOSE_FILE" down; then
        print_success "✅ All Docker containers stopped"
    else
        print_error "❌ Failed to stop Docker containers"
        exit 1
    fi
    exit 0
fi

# Determine which services to stop
if [[ ${#REQUESTED_SERVICES[@]} -gt 0 ]]; then
    # User specified specific services
    SERVICES_TO_STOP=("${REQUESTED_SERVICES[@]}")
    print_status "🎯 Stopping requested services: ${SERVICES_TO_STOP[*]}"
else
    # Stop all services for the deployment mode
    DOCKER_SERVICES=$(get_docker_services "$MODE")
    if [[ -n "$DOCKER_SERVICES" ]]; then
        read -ra SERVICES_TO_STOP <<< "$DOCKER_SERVICES"
        print_status "🐳 Stopping Docker services for $MODE mode: ${SERVICES_TO_STOP[*]}"
    else
        SERVICES_TO_STOP=()
        print_status "🏠 Local mode - no Docker services to stop"
    fi
fi

# Early exit for local mode with no Docker services
if [[ "$MODE" == "local" && ${#SERVICES_TO_STOP[@]} -eq 0 ]]; then
    print_status "💡 Local mode detected - no Docker services to stop"
    print_status "ℹ️  To stop local services, use your system's service manager (e.g., brew services stop)"
    exit 0
fi

# Stop the services
if [[ ${#SERVICES_TO_STOP[@]} -gt 0 ]]; then
    print_status "🐳 Stopping Docker services..."
    
    # Stop specific services
    for service in "${SERVICES_TO_STOP[@]}"; do
        container_name=$(get_service_container "$service")
        if [[ -n "$container_name" ]] && is_container_running "$container_name"; then
            print_status "Stopping $service ($container_name)..."
            if docker-compose -f "$DOCKER_COMPOSE_FILE" stop "$service"; then
                print_status "✓ Stopped $service"
            else
                print_warning "⚠ Failed to stop $service"
            fi
        else
            print_status "ℹ️  $service is not running"
        fi
    done
    
    print_success "✅ Requested Docker services stopped"
    
    # Show remaining running containers
    echo ""
    RUNNING_CONTAINERS=$(docker ps --format '{{.Names}}' | grep "^quvel-" || true)
    if [[ -n "$RUNNING_CONTAINERS" ]]; then
        print_status "📌 Still running:"
        echo "$RUNNING_CONTAINERS" | sed 's/^/  - /'
    else
        print_status "📌 No QuVel containers are running"
    fi
    
else
    print_status "ℹ️  No Docker services to stop for current configuration"
fi

# Show mode-specific information about local services
if [[ "$MODE" != "docker" && "$MODE" != "local" ]]; then
    echo ""
    print_header "💡 Local Services"
    LOCAL_SERVICES=$(get_local_services "$MODE")
    if [[ -n "$LOCAL_SERVICES" ]]; then
        print_status "The following services are running locally and need to be stopped manually:"
        for service in $LOCAL_SERVICES; do
            case "$service" in
                "mysql") echo "  - MySQL: brew services stop mysql" ;;
                "redis") echo "  - Redis: brew services stop redis" ;;
                "backend") echo "  - Laravel Backend: Stop the 'php artisan serve' process" ;;
                "frontend") echo "  - Quasar Frontend: Stop the 'npm run dev' process" ;;
                "traefik") echo "  - Traefik: Stop the local traefik process" ;;
            esac
        done
    fi
fi

print_success "🎉 QuVel Kit services stopped!"