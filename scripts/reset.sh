#!/bin/bash

# QuVel Kit Reset Script
# Resets services and data based on deployment mode

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Reset Services"
    cat << EOF
Resets QuVel Kit services and data based on the current or specified deployment mode.

Usage: $0 [OPTIONS]

Options:
  --mode=MODE          Override deployment mode (traefik-only, minimal, docker, local)
  --all                Reset all Docker containers and volumes regardless of mode
  --volumes            Remove Docker volumes (destructive - removes all data)
  --certs              Remove SSL certificates
  --help,-h           Show this help message

Examples:
  $0                           # Reset services for current mode
  $0 --mode=docker             # Reset all services for docker mode
  $0 --all --volumes           # Reset everything including data volumes
  $0 --certs                   # Only remove SSL certificates
  $0 --mode=minimal --volumes  # Reset minimal mode services and volumes

Deployment Modes:
  traefik-only    - Only Traefik in Docker (resets only Traefik)
  minimal         - Traefik + MySQL + Redis in Docker (resets these services)
  docker          - All services in Docker containers (resets all)
  local           - All services local (only removes certs, no Docker reset)

Current deployment mode: $(get_deployment_mode || echo "not set")

WARNING: Using --volumes will permanently delete all data in Docker volumes!
This includes databases, uploaded files, and any other persistent data.
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "♻️  Resetting QuVel Kit..."

# Navigate to project root
cd "$PROJECT_ROOT"

# Parse additional flags
RESET_ALL=false
REMOVE_VOLUMES=false
REMOVE_CERTS=false

for arg in "${REMAINING_ARGS[@]}"; do
    case "$arg" in
        --all)
            RESET_ALL=true
            ;;
        --volumes)
            REMOVE_VOLUMES=true
            ;;
        --certs)
            REMOVE_CERTS=true
            ;;
        --*)
            print_warning "Unknown option: $arg"
            ;;
    esac
done

# Determine deployment mode
if [[ -n "$MODE" ]]; then
    if ! validate_deployment_mode "$MODE"; then
        print_error "Invalid deployment mode: $MODE"
        print_error "Valid modes: traefik-only, minimal, docker, local"
        exit 1
    fi
    print_status "📋 Using specified mode: $MODE"
elif [[ "$RESET_ALL" != true ]]; then
    MODE=$(get_deployment_mode)
    if [[ -z "$MODE" ]]; then
        print_warning "No deployment mode found. Use --all to reset all containers or specify --mode=MODE"
        print_status "Resetting all Docker containers..."
        RESET_ALL=true
    else
        print_status "📋 Using stored mode: $MODE"
    fi
fi

# Validate Docker Compose file exists for Docker operations
if [[ "$MODE" != "local" || "$RESET_ALL" == true ]] && [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
    print_error "Docker Compose file not found: $DOCKER_COMPOSE_FILE"
    exit 1
fi

# Warning for destructive operations
if [[ "$REMOVE_VOLUMES" == true ]]; then
    echo ""
    print_warning "⚠️  WARNING: This will permanently delete all data in Docker volumes!"
    print_warning "This includes databases, uploaded files, and any other persistent data."
    echo ""
    if [[ "$NON_INTERACTIVE" != true ]]; then
        read -p "Are you sure you want to continue? [y/N]: " CONFIRM
        if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
            print_status "Reset cancelled."
            exit 0
        fi
    fi
fi

# Handle resetting all containers
if [[ "$RESET_ALL" == true ]]; then
    print_status "🐳 Resetting all Docker containers..."
    
    DOCKER_DOWN_CMD="docker-compose -f $DOCKER_COMPOSE_FILE down --remove-orphans"
    
    if [[ "$REMOVE_VOLUMES" == true ]]; then
        DOCKER_DOWN_CMD="$DOCKER_DOWN_CMD --volumes"
        print_status "🗑️ Removing all volumes..."
    fi
    
    print_status "Running: $DOCKER_DOWN_CMD"
    if eval "$DOCKER_DOWN_CMD"; then
        print_success "✅ All Docker containers reset"
    else
        print_error "❌ Failed to reset Docker containers"
        exit 1
    fi
else
    # Reset only services for the current mode
    DOCKER_SERVICES=$(get_docker_services "$MODE")
    
    if [[ "$MODE" == "local" ]]; then
        print_status "🏠 Local mode - no Docker services to reset"
    elif [[ -n "$DOCKER_SERVICES" ]]; then
        read -ra SERVICES_TO_RESET <<< "$DOCKER_SERVICES"
        print_status "🐳 Resetting Docker services for $MODE mode: ${SERVICES_TO_RESET[*]}"
        
        # Stop and remove containers for specific services
        for service in "${SERVICES_TO_RESET[@]}"; do
            container_name=$(get_service_container "$service")
            if [[ -n "$container_name" ]] && is_container_running "$container_name"; then
                print_status "Stopping $service..."
                docker-compose -f "$DOCKER_COMPOSE_FILE" stop "$service"
            fi
            
            print_status "Removing $service container..."
            docker-compose -f "$DOCKER_COMPOSE_FILE" rm -f "$service"
        done
        
        # Remove volumes if requested
        if [[ "$REMOVE_VOLUMES" == true ]]; then
            print_status "🗑️ Removing volumes for specified services..."
            # For mode-specific resets, we need to be more careful about volumes
            # This is a simplified approach - in practice you might want service-specific volume handling
            print_warning "Note: Volume removal in mode-specific reset removes ALL volumes"
            docker-compose -f "$DOCKER_COMPOSE_FILE" down --volumes
        fi
        
        print_success "✅ Docker services for $MODE mode reset"
    else
        print_status "ℹ️  No Docker services to reset for $MODE mode"
    fi
fi

# Handle certificate removal
if [[ "$REMOVE_CERTS" == true ]] || [[ "$RESET_ALL" == true ]]; then
    print_status "🗑️ Removing SSL certificates..."
    if [[ -d "$DOCKER_DIR/certs" ]]; then
        # Keep the directory but remove contents
        rm -rf "$DOCKER_DIR/certs"/*
        print_success "✅ SSL certificates removed"
    else
        print_status "ℹ️  No SSL certificates found to remove"
    fi
fi

# Show cleanup status
echo ""
print_header "📋 Reset Summary"

if [[ "$RESET_ALL" == true ]]; then
    print_status "✓ All Docker containers and networks removed"
    if [[ "$REMOVE_VOLUMES" == true ]]; then
        print_status "✓ All Docker volumes removed (data deleted)"
    fi
else
    case "$MODE" in
        "docker")
            print_status "✓ All Docker services reset"
            ;;
        "traefik-only")
            print_status "✓ Traefik container reset"
            ;;
        "minimal")
            print_status "✓ Traefik, MySQL, Redis containers reset"
            ;;
        "local")
            print_status "✓ No Docker services to reset (local mode)"
            ;;
    esac
    
    if [[ "$REMOVE_VOLUMES" == true ]]; then
        print_status "✓ Docker volumes removed (data deleted)"
    fi
fi

if [[ "$REMOVE_CERTS" == true ]] || [[ "$RESET_ALL" == true ]]; then
    print_status "✓ SSL certificates removed"
fi

# Show next steps
echo ""
print_header "🚀 Next Steps"
print_status "To start fresh, run: ./scripts/setup.sh"
if [[ -n "$MODE" && "$MODE" != "unknown" ]]; then
    print_status "Or to use the same mode: ./scripts/setup.sh --mode=$MODE"
fi

# Show warnings about local services
if [[ "$MODE" != "docker" && "$MODE" != "local" ]]; then
    echo ""
    print_header "💡 Local Services"
    LOCAL_SERVICES=$(get_local_services "$MODE")
    if [[ -n "$LOCAL_SERVICES" ]]; then
        print_status "The following local services may still be running:"
        for service in $LOCAL_SERVICES; do
            case "$service" in
                "mysql") echo "  - MySQL (stop with: brew services stop mysql)" ;;
                "redis") echo "  - Redis (stop with: brew services stop redis)" ;;
                "backend") echo "  - Laravel Backend (stop the 'php artisan serve' process)" ;;
                "frontend") echo "  - Quasar Frontend (stop the 'npm run dev' process)" ;;
            esac
        done
    fi
fi

print_success "🎉 Reset complete!"