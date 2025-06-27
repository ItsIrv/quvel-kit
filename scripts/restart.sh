#!/bin/bash

# QuVel Kit Restart Script
# Restarts services based on deployment mode

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Restart Services"
    cat << EOF
Restarts QuVel Kit services based on the current or specified deployment mode.

Usage: $0 [OPTIONS] [SERVICES...]

Options:
  --mode=MODE          Override deployment mode (traefik-only, minimal, docker, local)
  --help,-h           Show this help message

Services (optional, if specified only these will be restarted):
  traefik             Traefik reverse proxy
  app                 Laravel backend application
  frontend            Quasar frontend application
  mysql               MySQL database
  redis               Redis cache
  coverage            Coverage reporting service

Examples:
  $0                           # Restart all services for current mode
  $0 --mode=docker             # Restart all services for docker mode
  $0 traefik mysql             # Restart only traefik and mysql services
  $0 --mode=minimal            # Restart services for minimal mode

Deployment Modes:
  traefik-only    - Only Traefik in Docker (most minimal)
  minimal         - Traefik + MySQL + Redis in Docker, backend/frontend local
  docker          - All services in Docker containers
  local           - All services local (no Docker services to restart)

Current deployment mode: $(get_deployment_mode || echo "not set")

Note: This script will stop and then start the specified services.
For local services, you'll need to restart them manually.
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "🔄 Restarting QuVel Kit services..."

# Determine deployment mode early for display
DISPLAY_MODE=""
if [[ -n "$MODE" ]]; then
    DISPLAY_MODE="$MODE"
else
    DISPLAY_MODE=$(get_deployment_mode)
fi

print_status "📋 Mode: ${DISPLAY_MODE:-not set}"

# Prepare arguments for stop and start scripts
SCRIPT_ARGS=()
if [[ -n "$MODE" ]]; then
    SCRIPT_ARGS+=("--mode=$MODE")
fi

# Add any remaining service arguments
SCRIPT_ARGS+=("${REMAINING_ARGS[@]}")

print_status "🛑 Stopping services..."
if "$SCRIPTS_DIR/stop.sh" "${SCRIPT_ARGS[@]}"; then
    print_success "✅ Services stopped successfully"
else
    print_error "❌ Failed to stop services"
    exit 1
fi

echo ""
print_status "🚀 Starting services..."
if "$SCRIPTS_DIR/start.sh" "${SCRIPT_ARGS[@]}"; then
    print_success "✅ Services started successfully"
else
    print_error "❌ Failed to start services"
    exit 1
fi

echo ""
print_success "🎉 QuVel Kit services restarted successfully!"

# Show quick status if no specific services were requested
if [[ ${#REMAINING_ARGS[@]} -eq 0 ]]; then
    echo ""
    print_header "📊 Quick Status"
    
    case "$DISPLAY_MODE" in
        "docker")
            RUNNING_CONTAINERS=$(docker ps --format '{{.Names}}' | grep "^quvel-" | wc -l)
            print_status "Docker containers running: $RUNNING_CONTAINERS"
            ;;
        "traefik-only"|"minimal")
            RUNNING_CONTAINERS=$(docker ps --format '{{.Names}}' | grep "^quvel-" | wc -l)
            print_status "Docker containers running: $RUNNING_CONTAINERS"
            print_status "💡 Don't forget to start local services (backend/frontend)"
            ;;
        "local")
            print_status "💡 All services run locally - restart them using your system's service manager"
            ;;
        *)
            print_warning "Unknown deployment mode - check service status manually"
            ;;
    esac
fi