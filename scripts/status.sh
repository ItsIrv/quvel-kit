#!/bin/bash

# QuVel Kit Status Script
# Shows current deployment mode and service status

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Service Status"
    cat << EOF
Shows the current deployment mode and status of all QuVel Kit services.

Usage: $0 [OPTIONS]

Options:
  --mode=MODE          Override deployment mode for status check
  --detailed,-d        Show detailed container information
  --help,-h           Show this help message

Examples:
  $0                           # Show status for current mode
  $0 --mode=docker             # Show status for docker mode
  $0 --detailed                # Show detailed container information

This script shows:
  - Current deployment mode
  - Docker container status
  - Local service suggestions
  - Access URLs
  - Quick health checks
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "📊 QuVel Kit Status"

# Navigate to project root
cd "$PROJECT_ROOT"

# Parse additional flags
DETAILED=false
for arg in "${REMAINING_ARGS[@]}"; do
    case "$arg" in
        --detailed|-d)
            DETAILED=true
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
    print_status "📋 Checking status for specified mode: $MODE"
else
    MODE=$(get_deployment_mode)
    if [[ -z "$MODE" ]]; then
        print_warning "❓ No deployment mode configured"
        print_status "Run './scripts/setup.sh --mode=MODE' to set up a deployment mode"
        MODE="unknown"
    else
        print_status "📋 Current deployment mode: $MODE"
    fi
fi

echo ""

# Show mode configuration
print_header "🔧 Mode Configuration"
case "$MODE" in
    "traefik-only")
        print_status "Mode: Traefik Only (most minimal)"
        print_status "Docker services: Traefik"
        print_status "Local services: MySQL, Redis, Backend, Frontend"
        ;;
    "minimal")
        print_status "Mode: Minimal Resources"
        print_status "Docker services: Traefik, MySQL, Redis, Coverage"
        print_status "Local services: Backend, Frontend"
        ;;
    "docker")
        print_status "Mode: Full Docker"
        print_status "Docker services: All (Traefik, Backend, Frontend, MySQL, Redis, Coverage)"
        print_status "Local services: None"
        ;;
    "local")
        print_status "Mode: Fully Local"
        print_status "Docker services: None"
        print_status "Local services: All (Traefik, MySQL, Redis, Backend, Frontend)"
        ;;
    "unknown")
        print_warning "Mode: Not configured"
        print_status "Run setup to configure a deployment mode"
        ;;
esac

echo ""

# Docker Services Status
if [[ "$MODE" != "local" && "$MODE" != "unknown" ]]; then
    print_header "🐳 Docker Services Status"
    
    DOCKER_SERVICES=$(get_docker_services "$MODE")
    if [[ -n "$DOCKER_SERVICES" ]]; then
        read -ra EXPECTED_SERVICES <<< "$DOCKER_SERVICES"
        
        # Check if Docker is running
        if ! check_docker; then
            print_error "❌ Docker is not running"
        else
            # Check each expected service
            RUNNING_COUNT=0
            TOTAL_COUNT=${#EXPECTED_SERVICES[@]}
            
            for service in "${EXPECTED_SERVICES[@]}"; do
                container_name=$(get_service_container "$service")
                if [[ -n "$container_name" ]] && is_container_running "$container_name"; then
                    print_success "✅ $service ($container_name) - Running"
                    ((RUNNING_COUNT++))
                else
                    print_error "❌ $service ($container_name) - Not running"
                fi
            done
            
            echo ""
            print_status "📊 Docker Services: $RUNNING_COUNT/$TOTAL_COUNT running"
            
            # Show detailed container info if requested
            if [[ "$DETAILED" == true && "$RUNNING_COUNT" -gt 0 ]]; then
                echo ""
                print_header "📋 Detailed Container Information"
                docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(NAMES|quvel-)" || print_status "No QuVel containers running"
            fi
        fi
    else
        print_status "ℹ️  No Docker services expected for $MODE mode"
    fi
elif [[ "$MODE" == "local" ]]; then
    print_status "🏠 Local mode - no Docker services expected"
fi

echo ""

# Local Services Status
if [[ "$MODE" != "docker" && "$MODE" != "unknown" ]]; then
    print_header "🏠 Local Services Status"
    
    LOCAL_SERVICES=$(get_local_services "$MODE")
    if [[ -n "$LOCAL_SERVICES" ]]; then
        print_status "Expected local services for $MODE mode:"
        
        for service in $LOCAL_SERVICES; do
            case "$service" in
                "mysql")
                    if command_exists mysql && brew services list | grep -q "mysql.*started"; then
                        print_success "✅ MySQL - Running (brew services)"
                    elif pgrep mysqld > /dev/null; then
                        print_success "✅ MySQL - Running (process detected)"
                    else
                        print_warning "⚠️  MySQL - Not detected (run: brew services start mysql)"
                    fi
                    ;;
                "redis")
                    if command_exists redis-cli && brew services list | grep -q "redis.*started"; then
                        print_success "✅ Redis - Running (brew services)"
                    elif pgrep redis-server > /dev/null; then
                        print_success "✅ Redis - Running (process detected)"
                    else
                        print_warning "⚠️  Redis - Not detected (run: brew services start redis)"
                    fi
                    ;;
                "backend")
                    if pgrep -f "php artisan serve" > /dev/null; then
                        print_success "✅ Laravel Backend - Running"
                    else
                        print_warning "⚠️  Laravel Backend - Not detected (run: cd backend && php artisan serve)"
                    fi
                    ;;
                "frontend")
                    if pgrep -f "quasar dev\|npm.*dev\|yarn.*dev" > /dev/null; then
                        print_success "✅ Frontend - Running"
                    else
                        print_warning "⚠️  Frontend - Not detected (run: cd frontend && npm run dev)"
                    fi
                    ;;
                "traefik")
                    if pgrep traefik > /dev/null; then
                        print_success "✅ Traefik - Running"
                    else
                        print_warning "⚠️  Traefik - Not detected (run: traefik --configfile=docker/traefik/traefik.yml)"
                    fi
                    ;;
            esac
        done
    else
        print_status "ℹ️  No local services expected for $MODE mode"
    fi
fi

echo ""

# Network Connectivity Checks
print_header "🌐 Network Connectivity"

# Check if ports are available/in use
case "$MODE" in
    "docker"|"traefik-only"|"minimal")
        # Check Traefik ports
        if lsof -i :80 > /dev/null 2>&1; then
            print_success "✅ Port 80 - In use (HTTP)"
        else
            print_warning "⚠️  Port 80 - Not in use"
        fi
        
        if lsof -i :443 > /dev/null 2>&1; then
            print_success "✅ Port 443 - In use (HTTPS)"
        else
            print_warning "⚠️  Port 443 - Not in use"
        fi
        
        if lsof -i :8080 > /dev/null 2>&1; then
            print_success "✅ Port 8080 - In use (Traefik Dashboard)"
        else
            print_warning "⚠️  Port 8080 - Not in use"
        fi
        ;;
    "local")
        # Check local service ports
        if lsof -i :3000 > /dev/null 2>&1; then
            print_success "✅ Port 3000 - In use (Frontend)"
        else
            print_warning "⚠️  Port 3000 - Not in use"
        fi
        
        if lsof -i :8000 > /dev/null 2>&1; then
            print_success "✅ Port 8000 - In use (Backend)"
        else
            print_warning "⚠️  Port 8000 - Not in use"
        fi
        ;;
esac

echo ""

# Access URLs
print_header "🔗 Access URLs"
case "$MODE" in
    "docker")
        echo "  Frontend: ${SERVICE_URLS[frontend_public]}"
        echo "  API: ${SERVICE_URLS[api_public]}"
        echo "  Coverage: ${SERVICE_URLS[coverage_public]}"
        echo "  Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}"
        ;;
    "traefik-only"|"minimal")
        echo "  Frontend: ${SERVICE_URLS[frontend_public]} (when local services running)"
        echo "  API: ${SERVICE_URLS[api_public]} (when local services running)"
        echo "  Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}"
        echo ""
        print_status "💡 Start local services to access frontend/API URLs"
        ;;
    "local")
        echo "  Frontend: ${SERVICE_URLS[frontend_dev]} (local development)"
        echo "  API: ${SERVICE_URLS[backend_dev]} (local development)"
        ;;
    "unknown")
        print_warning "Configure deployment mode first with: ./scripts/setup.sh --mode=MODE"
        ;;
esac

echo ""

# Quick Actions
print_header "⚡ Quick Actions"
case "$MODE" in
    "unknown")
        echo "  Setup: ./scripts/setup.sh --mode=MODE"
        echo "  Help:  ./scripts/setup.sh --help"
        ;;
    *)
        echo "  Start:   ./scripts/start.sh"
        echo "  Stop:    ./scripts/stop.sh"
        echo "  Restart: ./scripts/restart.sh"
        echo "  Logs:    ./scripts/log.sh"
        echo "  Reset:   ./scripts/reset.sh"
        echo "  Switch:  ./scripts/switch-mode.sh NEW_MODE"
        ;;
esac

echo ""

# Overall Status Summary
print_header "📈 Overall Status"
if [[ "$MODE" == "unknown" ]]; then
    print_warning "⚠️  System not configured - run setup first"
elif [[ "$MODE" == "docker" ]]; then
    DOCKER_SERVICES=$(get_docker_services "$MODE")
    read -ra EXPECTED_SERVICES <<< "$DOCKER_SERVICES"
    RUNNING_COUNT=0
    for service in "${EXPECTED_SERVICES[@]}"; do
        container_name=$(get_service_container "$service")
        if [[ -n "$container_name" ]] && is_container_running "$container_name"; then
            ((RUNNING_COUNT++))
        fi
    done
    
    if [[ "$RUNNING_COUNT" -eq "${#EXPECTED_SERVICES[@]}" ]]; then
        print_success "🟢 All services running - System healthy"
    elif [[ "$RUNNING_COUNT" -gt 0 ]]; then
        print_warning "🟡 Some services running - Check individual service status"
    else
        print_error "🔴 No services running - Run './scripts/start.sh' to start"
    fi
else
    print_status "🔵 Hybrid mode ($MODE) - Check individual service status above"
    print_status "💡 Some services run in Docker, others locally"
fi

echo ""
print_status "Status check complete! Use --detailed for more information."