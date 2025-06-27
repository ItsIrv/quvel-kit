#!/bin/bash

# QuVel Kit Log Viewer
# Shows logs for Docker services based on deployment mode

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "View Service Logs"
    cat << EOF
Shows logs for QuVel Kit Docker services based on the current or specified deployment mode.

Usage: $0 [OPTIONS] [SERVICES...]

Options:
  --mode=MODE          Override deployment mode (traefik-only, minimal, docker, local)
  --follow,-f          Follow log output (like tail -f)
  --tail=N            Number of lines to show from end of logs (default: 100)
  --all               Show logs for all containers regardless of mode
  --help,-h           Show this help message

Services (optional, if specified only these logs will be shown):
  traefik             Traefik reverse proxy
  app                 Laravel backend application
  frontend            Quasar frontend application
  mysql               MySQL database
  redis               Redis cache
  coverage            Coverage reporting service

Examples:
  $0                           # Show logs for all services in current mode
  $0 --mode=docker             # Show logs for all services in docker mode
  $0 app mysql                 # Show logs for only app and mysql services
  $0 --follow                  # Follow logs in real-time
  $0 --tail=50 traefik         # Show last 50 lines of traefik logs
  $0 --all                     # Show logs for all containers

Deployment Modes:
  traefik-only    - Only Traefik in Docker (most minimal)
  minimal         - Traefik + MySQL + Redis in Docker, backend/frontend local
  docker          - All services in Docker containers
  local           - All services local (no Docker logs to show)

Current deployment mode: $(get_deployment_mode || echo "not set")

Note: This only shows Docker container logs. For local services, check your system logs.
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "📜 QuVel Kit Service Logs"

# Navigate to project root
cd "$PROJECT_ROOT"

# Parse additional flags
FOLLOW_LOGS=false
TAIL_LINES=100
SHOW_ALL=false
REQUESTED_SERVICES=()

for arg in "${REMAINING_ARGS[@]}"; do
    case "$arg" in
        --follow|-f)
            FOLLOW_LOGS=true
            ;;
        --tail=*)
            TAIL_LINES="${arg#*=}"
            ;;
        --all)
            SHOW_ALL=true
            ;;
        --*)
            print_warning "Unknown option: $arg"
            ;;
        *)
            REQUESTED_SERVICES+=("$arg")
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
elif [[ "$SHOW_ALL" != true ]]; then
    MODE=$(get_deployment_mode)
    if [[ -z "$MODE" ]]; then
        print_warning "No deployment mode found. Use --all to show all container logs or specify --mode=MODE"
        print_status "Showing logs for all Docker containers..."
        SHOW_ALL=true
    else
        print_status "📋 Using stored mode: $MODE"
    fi
fi

# Validate Docker Compose file exists
if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
    print_error "Docker Compose file not found: $DOCKER_COMPOSE_FILE"
    exit 1
fi

# Handle showing all containers
if [[ "$SHOW_ALL" == true ]]; then
    print_status "🐳 Showing logs for all Docker containers..."
    
    # Build docker-compose logs command
    DOCKER_LOGS_CMD="docker-compose -f $DOCKER_COMPOSE_FILE logs"
    
    if [[ "$FOLLOW_LOGS" == true ]]; then
        DOCKER_LOGS_CMD="$DOCKER_LOGS_CMD --follow"
    fi
    
    DOCKER_LOGS_CMD="$DOCKER_LOGS_CMD --tail=$TAIL_LINES"
    
    # Add specific services if requested
    if [[ ${#REQUESTED_SERVICES[@]} -gt 0 ]]; then
        DOCKER_LOGS_CMD="$DOCKER_LOGS_CMD ${REQUESTED_SERVICES[*]}"
        print_status "📋 Services: ${REQUESTED_SERVICES[*]}"
    fi
    
    print_status "Running: $DOCKER_LOGS_CMD"
    eval "$DOCKER_LOGS_CMD"
    exit $?
fi

# Determine which services to show logs for
if [[ ${#REQUESTED_SERVICES[@]} -gt 0 ]]; then
    # User specified specific services
    SERVICES_TO_LOG=("${REQUESTED_SERVICES[@]}")
    print_status "🎯 Showing logs for requested services: ${SERVICES_TO_LOG[*]}"
else
    # Show logs for all services for the deployment mode
    DOCKER_SERVICES=$(get_docker_services "$MODE")
    if [[ -n "$DOCKER_SERVICES" ]]; then
        read -ra SERVICES_TO_LOG <<< "$DOCKER_SERVICES"
        print_status "🐳 Showing logs for Docker services in $MODE mode: ${SERVICES_TO_LOG[*]}"
    else
        SERVICES_TO_LOG=()
        print_status "🏠 Local mode - no Docker services to show logs for"
    fi
fi

# Early exit for local mode with no Docker services
if [[ "$MODE" == "local" && ${#SERVICES_TO_LOG[@]} -eq 0 ]]; then
    print_status "💡 Local mode detected - no Docker logs to show"
    echo ""
    print_header "📋 Local Service Logs"
    LOCAL_SERVICES=$(get_local_services "$MODE")
    if [[ -n "$LOCAL_SERVICES" ]]; then
        print_status "For local services, check these locations:"
        for service in $LOCAL_SERVICES; do
            case "$service" in
                "mysql") 
                    echo "  - MySQL: /usr/local/var/log/mysql/ (brew) or system logs"
                    ;;
                "redis") 
                    echo "  - Redis: /usr/local/var/log/redis.log (brew) or system logs"
                    ;;
                "backend") 
                    echo "  - Laravel Backend: Check console output or storage/logs/"
                    ;;
                "frontend") 
                    echo "  - Quasar Frontend: Check console output from 'npm run dev'"
                    ;;
                "traefik") 
                    echo "  - Traefik: Check console output or configured log files"
                    ;;
            esac
        done
    fi
    exit 0
fi

# Show logs for the services
if [[ ${#SERVICES_TO_LOG[@]} -gt 0 ]]; then
    # Check which services are actually running
    RUNNING_SERVICES=()
    for service in "${SERVICES_TO_LOG[@]}"; do
        container_name=$(get_service_container "$service")
        if [[ -n "$container_name" ]] && is_container_running "$container_name"; then
            RUNNING_SERVICES+=("$service")
        else
            print_warning "⚠ $service is not running"
        fi
    done
    
    if [[ ${#RUNNING_SERVICES[@]} -eq 0 ]]; then
        print_error "❌ No services are currently running"
        exit 1
    fi
    
    print_status "📋 Showing logs for running services: ${RUNNING_SERVICES[*]}"
    
    # Build docker-compose logs command
    DOCKER_LOGS_CMD="docker-compose -f $DOCKER_COMPOSE_FILE logs"
    
    if [[ "$FOLLOW_LOGS" == true ]]; then
        DOCKER_LOGS_CMD="$DOCKER_LOGS_CMD --follow"
        print_status "👀 Following logs (press Ctrl+C to stop)..."
    fi
    
    DOCKER_LOGS_CMD="$DOCKER_LOGS_CMD --tail=$TAIL_LINES ${RUNNING_SERVICES[*]}"
    
    echo ""
    print_status "Running: $DOCKER_LOGS_CMD"
    echo ""
    
    # Execute the command
    eval "$DOCKER_LOGS_CMD"
    
else
    print_status "ℹ️  No Docker services to show logs for in current configuration"
fi

# Show additional information about local services
if [[ "$MODE" != "docker" && "$MODE" != "local" ]]; then
    echo ""
    print_header "💡 Local Service Logs"
    LOCAL_SERVICES=$(get_local_services "$MODE")
    if [[ -n "$LOCAL_SERVICES" ]]; then
        print_status "For local services running in $MODE mode, check:"
        for service in $LOCAL_SERVICES; do
            case "$service" in
                "backend") echo "  - Laravel Backend: Console output or storage/logs/" ;;
                "frontend") echo "  - Quasar Frontend: Console output from 'npm run dev'" ;;
            esac
        done
    fi
fi