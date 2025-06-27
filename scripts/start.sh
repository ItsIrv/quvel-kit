#!/bin/bash

# QuVel Kit Start Script
# Starts services based on deployment mode

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Start Services"
    cat << EOF
Starts QuVel Kit services based on the current or specified deployment mode.

Usage: $0 [OPTIONS] [SERVICES...]

Options:
  --mode=MODE          Override deployment mode (traefik-only, minimal, docker, local)
  --help,-h           Show this help message

Services (optional, if specified only these will be started):
  traefik             Traefik reverse proxy
  app                 Laravel backend application
  frontend            Quasar frontend application
  mysql               MySQL database
  redis               Redis cache
  coverage            Coverage reporting service

Examples:
  $0                           # Start all services for current mode
  $0 --mode=docker             # Start all services for docker mode
  $0 traefik mysql             # Start only traefik and mysql services
  $0 --mode=minimal traefik    # Start traefik in minimal mode

Deployment Modes:
  traefik-only    - Only Traefik in Docker (most minimal)
  minimal         - Traefik + MySQL + Redis in Docker, backend/frontend local
  docker          - All services in Docker containers
  local           - All services local (no Docker services started)

Current deployment mode: $(get_deployment_mode || echo "not set")
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "🚀 Starting QuVel Kit services..."

# Navigate to project root
cd "$PROJECT_ROOT"

# Determine deployment mode
if [[ -n "$MODE" ]]; then
    if ! validate_deployment_mode "$MODE"; then
        print_error "Invalid deployment mode: $MODE"
        print_error "Valid modes: traefik-only, minimal, docker, local"
        exit 1
    fi
    print_status "📋 Using specified mode: $MODE"
else
    MODE=$(get_deployment_mode)
    if [[ -z "$MODE" ]]; then
        print_error "No deployment mode specified and none found in $MODE_FILE"
        print_error "Please run: ./scripts/setup.sh --mode=MODE or specify --mode=MODE"
        exit 1
    fi
    print_status "📋 Using stored mode: $MODE"
fi

# Check if specific services were requested
REQUESTED_SERVICES=("${REMAINING_ARGS[@]}")

# Determine which services to start
if [[ ${#REQUESTED_SERVICES[@]} -gt 0 ]]; then
    # User specified specific services
    SERVICES_TO_START=("${REQUESTED_SERVICES[@]}")
    print_status "🎯 Starting requested services: ${SERVICES_TO_START[*]}"
else
    # Start all services for the deployment mode
    DOCKER_SERVICES=$(get_docker_services "$MODE")
    if [[ -n "$DOCKER_SERVICES" ]]; then
        read -ra SERVICES_TO_START <<< "$DOCKER_SERVICES"
        print_status "🐳 Starting Docker services for $MODE mode: ${SERVICES_TO_START[*]}"
    else
        SERVICES_TO_START=()
        print_status "🏠 Local mode - no Docker services to start"
    fi
fi

# Early exit for local mode with no Docker services
if [[ "$MODE" == "local" && ${#SERVICES_TO_START[@]} -eq 0 ]]; then
    print_status "💡 Local mode detected - all services run locally"
    echo ""
    get_mode_instructions "$MODE"
    exit 0
fi

# Check Docker availability for Docker services
if [[ ${#SERVICES_TO_START[@]} -gt 0 ]]; then
    if ! check_docker; then
        exit 1
    fi
fi

# Validate Docker Compose file exists
if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
    print_error "Docker Compose file not found: $DOCKER_COMPOSE_FILE"
    exit 1
fi

# Start the services
if [[ ${#SERVICES_TO_START[@]} -gt 0 ]]; then
    print_status "🐳 Starting Docker services..."
    
    # Build command with specific services
    DOCKER_CMD="docker-compose -f $DOCKER_COMPOSE_FILE up -d"
    
    # Add --build flag for full docker mode or if app/frontend are being started
    if [[ "$MODE" == "docker" ]] || [[ " ${SERVICES_TO_START[*]} " =~ " app " ]] || [[ " ${SERVICES_TO_START[*]} " =~ " frontend " ]]; then
        DOCKER_CMD="$DOCKER_CMD --build"
    fi
    
    # Add specific services to command
    DOCKER_CMD="$DOCKER_CMD ${SERVICES_TO_START[*]}"
    
    print_status "Running: $DOCKER_CMD"
    if eval "$DOCKER_CMD"; then
        print_success "✅ Docker services started successfully"
        
        # Show running containers
        echo ""
        print_status "📌 Running containers:"
        docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(NAMES|quvel-)"
        
        # Wait for critical services to be ready
        for service in "${SERVICES_TO_START[@]}"; do
            case "$service" in
                "mysql")
                    container_name=$(get_service_container "mysql")
                    wait_for_service "MySQL" "docker exec $container_name mysqladmin ping -h localhost --silent"
                    ;;
                "app")
                    container_name=$(get_service_container "app")
                    wait_for_service "Laravel" "docker exec $container_name test -f /var/www/vendor/autoload.php"
                    ;;
            esac
        done
        
    else
        print_error "❌ Failed to start Docker services"
        exit 1
    fi
else
    print_status "ℹ️  No Docker services to start for current configuration"
fi

# Show mode-specific instructions
echo ""
print_header "📋 Next Steps"
get_mode_instructions "$MODE"

# Show access URLs
echo ""
print_header "🌐 Access URLs"
case "$MODE" in
    "docker")
        echo "  Frontend: ${SERVICE_URLS[frontend_public]}"
        echo "  API: ${SERVICE_URLS[api_public]}"
        echo "  Coverage: ${SERVICE_URLS[coverage_public]}"
        echo "  Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}"
        ;;
    "traefik-only"|"minimal")
        echo "  Frontend: ${SERVICE_URLS[frontend_public]} (after starting local services)"
        echo "  API: ${SERVICE_URLS[api_public]} (after starting local services)"
        echo "  Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}"
        ;;
    "local")
        echo "  Frontend: ${SERVICE_URLS[frontend_dev]} (local development)"
        echo "  API: ${SERVICE_URLS[backend_dev]} (local development)"
        ;;
esac

print_success "🎉 QuVel Kit startup complete!"