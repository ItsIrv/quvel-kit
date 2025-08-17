#!/bin/bash

# QuVel Kit Setup Script
# Sets up QuVel Kit with mode-aware configuration

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Setup QuVel Kit"
    cat << EOF
Sets up QuVel Kit development environment with the specified deployment mode.

Usage: $0 [OPTIONS]

Options:
  --mode=MODE          Deployment mode (traefik-only, minimal, docker, local)
  --non-interactive,-n Run without interactive prompts
  --help,-h           Show this help message

Available deployment modes:
  traefik-only    - Only Traefik in Docker, everything else local (most minimal)
  minimal         - Traefik + MySQL + Redis in Docker, services local
  docker          - All services in Docker containers
  local           - All services local (requires Traefik locally)

Examples:
  $0 --mode=docker             # Full Docker setup
  $0 --mode=traefik-only       # Minimal Docker setup
  $0 --non-interactive         # Use defaults without prompts

What this script does:
1. Creates .env files if they don't exist
2. Checks for required dependencies
3. Generates SSL certificates
4. Configures Traefik for the selected mode
5. Starts appropriate Docker services
6. Saves deployment mode for future script use
7. Shows next steps for local services

Current deployment mode: $(get_deployment_mode || echo "not set")
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "🚀 Setting up QuVel Kit..."

# Navigate to project root
cd "$PROJECT_ROOT"

# Default deployment mode
if [[ -z "$MODE" ]]; then
    CURRENT_MODE=$(get_deployment_mode)
    if [[ -n "$CURRENT_MODE" ]]; then
        if [[ "$NON_INTERACTIVE" == true ]]; then
            MODE="$CURRENT_MODE"
            print_status "📋 Using stored mode: $MODE"
        else
            echo ""
            print_status "Found existing deployment mode: $CURRENT_MODE"
            read -p "Use existing mode ($CURRENT_MODE)? [Y/n]: " USE_EXISTING
            if [[ "$USE_EXISTING" =~ ^[Nn]$ ]]; then
                echo ""
                echo "Available modes:"
                echo "  traefik-only - Only Traefik in Docker, everything else local (default)"
                echo "  minimal      - Traefik + MySQL + Redis in Docker, services local"
                echo "  docker       - All services in Docker"
                echo "  local        - All services local (requires Traefik locally)"
                echo ""
                read -p "Select deployment mode [traefik-only]: " MODE
                MODE=${MODE:-traefik-only}
            else
                MODE="$CURRENT_MODE"
            fi
        fi
    else
        if [[ "$NON_INTERACTIVE" == true ]]; then
            MODE="traefik-only"
            print_status "📋 Using default mode: $MODE"
        else
            echo ""
            echo "Available deployment modes:"
            echo "  traefik-only - Only Traefik in Docker, everything else local (default)"
            echo "  minimal      - Traefik + MySQL + Redis in Docker, services local"
            echo "  docker       - All services in Docker"
            echo "  local        - All services local (requires Traefik locally)"
            echo ""
            read -p "Select deployment mode [traefik-only]: " MODE
            MODE=${MODE:-traefik-only}
        fi
    fi
fi

# Validate deployment mode
if ! validate_deployment_mode "$MODE"; then
    print_error "Invalid deployment mode: $MODE"
    print_error "Valid modes: traefik-only, minimal, docker, local"
    exit 1
fi

print_status "📋 Deployment mode: $MODE"

# Save deployment mode early so other scripts can use it
save_deployment_mode "$MODE"

echo ""

# Check dependencies
print_header "🔍 Checking Dependencies"
if ! check_dependencies "$MODE"; then
    print_error "Missing required dependencies. Please install them and try again."
    exit 1
fi
print_success "✅ All required dependencies are available"

echo ""

# Ensure .env files exist
print_header "⚙️  Setting up Environment Files"

if [ ! -f "$BACKEND_DIR/.env" ]; then
    print_status "Creating Laravel .env file..."
    cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
    print_success "✅ Backend .env created"
else
    print_status "✓ Backend .env already exists"
fi

if [ ! -f "$FRONTEND_DIR/.env" ]; then
    print_status "Creating Quasar .env file..."
    cp "$FRONTEND_DIR/.env.example" "$FRONTEND_DIR/.env"
    print_success "✅ Frontend .env created"
else
    print_status "✓ Frontend .env already exists"
fi

echo ""

# Check npm availability
print_header "📦 Checking Node.js Environment"
if ! command_exists npm; then
    print_error "npm is not installed. Please install Node.js and npm before running this script."
    exit 1
fi
print_success "✅ Node.js and npm are available"

echo ""

# Run SSL setup
print_header "🔐 Setting up SSL Certificates"
SSL_ARGS=("--mode=$MODE")
if [[ "$NON_INTERACTIVE" == true ]]; then
    SSL_ARGS+=("--non-interactive")
fi

if "$SCRIPTS_DIR/ssl.sh" "${SSL_ARGS[@]}"; then
    print_success "✅ SSL certificates configured"
else
    print_error "❌ Failed to set up SSL certificates"
    exit 1
fi

echo ""

# Configure deployment mode
print_header "⚙️  Configuring Deployment Mode"
print_status "Configuring Traefik for $MODE mode..."
if "$SCRIPTS_DIR/deploy-mode.sh" "$MODE"; then
    print_success "✅ Deployment mode configured"
else
    print_error "❌ Failed to configure deployment mode"
    exit 1
fi

echo ""

# Validate Docker Compose file
if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
    print_error "Docker Compose file not found at $DOCKER_COMPOSE_FILE"
    exit 1
fi

# Copy php.ini for Docker builds
if [[ "$MODE" == "docker" ]]; then
    print_status "📄 Copying php.ini for Docker build..."
    cp "$DOCKER_DIR/php.ini" "$BACKEND_DIR/php.ini"
fi

# Start services based on deployment mode
print_header "🐳 Starting Services"
DOCKER_SERVICES=$(get_docker_services "$MODE")

if [[ -n "$DOCKER_SERVICES" ]]; then
    print_status "Starting Docker services for $MODE mode..."
    
    # Check Docker availability
    if ! check_docker; then
        exit 1
    fi
    
    read -ra SERVICES_TO_START <<< "$DOCKER_SERVICES"
    
    # Build Docker command
    DOCKER_CMD="docker-compose -f $DOCKER_COMPOSE_FILE up -d"
    
    # Add --build flag for modes that include app/frontend or full docker mode
    if [[ "$MODE" == "docker" ]] || [[ " ${SERVICES_TO_START[*]} " =~ " app " ]]; then
        DOCKER_CMD="$DOCKER_CMD --build"
    fi
    
    # Add specific services
    DOCKER_CMD="$DOCKER_CMD ${SERVICES_TO_START[*]}"
    
    print_status "Running: $DOCKER_CMD"
    if eval "$DOCKER_CMD"; then
        print_success "✅ Docker services started successfully"
    else
        print_error "❌ Failed to start Docker services"
        exit 1
    fi
    
    # Wait for critical services
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
    print_status "🏠 Local mode - no Docker services to start"
fi

# Clean up php.ini if it was copied
if [[ "$MODE" == "docker" && -f "$BACKEND_DIR/php.ini" ]]; then
    print_status "🧹 Cleaning up php.ini..."
    rm -f "$BACKEND_DIR/php.ini"
fi

echo ""

# Handle mode-specific setup tasks
print_header "🔧 Mode-Specific Setup"
case "$MODE" in
    "docker")
        print_status "Setting up full Docker environment..."
        
        # Wait for Laravel container and run setup commands
        container_name=$(get_service_container "app")
        if is_container_running "$container_name"; then
            print_status "🔑 Generating Laravel APP_KEY..."
            docker exec -it "$container_name" php artisan key:generate
            
            print_status "🔍 Verifying database connection..."
            if ! docker exec -it "$container_name" php -r "new PDO('mysql:host=mysql;dbname=quvel', 'quvel_user', 'quvel_password');"; then
                print_error "❌ Database connection failed! Check MySQL configuration."
                exit 1
            fi
            
            print_status "📌 Running Laravel migrations..."
            docker exec -it "$container_name" php artisan migrate:fresh --force --seed
            
            print_status "🔗 Linking storage..."
            docker exec -it "$container_name" php artisan storage:link
            
            print_status "📊 Generating initial PHPUnit coverage report..."
            docker exec -it "$container_name" php artisan test --coverage-html=storage/coverage
        fi
        ;;
    
    "minimal")
        print_status "Setting up minimal Docker environment..."
        container_name=$(get_service_container "mysql")
        if is_container_running "$container_name"; then
            wait_for_service "MySQL" "docker exec $container_name mysqladmin ping -h localhost --silent"
        fi
        ;;
    
    "traefik-only"|"local")
        print_status "Setup for $MODE mode complete"
        ;;
esac

echo ""

# Show completion message and next steps
print_header "✅ Setup Complete!"
print_success "QuVel Kit has been set up in $MODE mode"

echo ""
print_header "📋 Next Steps"
get_mode_instructions "$MODE"

echo ""
print_header "🌐 Access URLs"
case "$MODE" in
    "docker")
        echo "  Frontend: $(get_service_url frontend_public)"
        echo "  API: $(get_service_url api_public)"
        echo "  Coverage: $(get_service_url coverage_public)"
        echo "  Traefik Dashboard: $(get_service_url traefik_dashboard)"
        ;;
    "traefik-only"|"minimal")
        echo "  Frontend: $(get_service_url frontend_public) (after starting local services)"
        echo "  API: $(get_service_url api_public) (after starting local services)"
        echo "  Traefik Dashboard: $(get_service_url traefik_dashboard)"
        ;;
    "local")
        echo "  Frontend: $(get_service_url frontend_dev) (local development)"
        echo "  API: $(get_service_url backend_dev) (local development)"
        ;;
esac

echo ""
print_header "💡 Useful Commands"
echo "  Status:      ./scripts/status.sh"
echo "  Start:       ./scripts/start.sh"
echo "  Stop:        ./scripts/stop.sh" 
echo "  Logs:        ./scripts/log.sh"
echo "  Switch mode: ./scripts/switch-mode.sh NEW_MODE"

echo ""
print_success "🎉 QuVel Kit setup complete! Happy coding!"
