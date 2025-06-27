#!/bin/bash

# QuVel Kit Mode Switcher
# Safely switches between deployment modes

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "Switch Deployment Mode"
    cat << EOF
Safely switches between QuVel Kit deployment modes by stopping current services,
reconfiguring the system, and optionally starting services in the new mode.

Usage: $0 [OPTIONS] NEW_MODE

Arguments:
  NEW_MODE             Target deployment mode (traefik-only, minimal, docker, local)

Options:
  --no-stop            Don't stop current services before switching
  --no-start           Don't start services after switching (just reconfigure)
  --force              Skip confirmation prompts
  --help,-h           Show this help message

Deployment Modes:
  traefik-only    - Only Traefik in Docker (most minimal)
  minimal         - Traefik + MySQL + Redis in Docker, backend/frontend local
  docker          - All services in Docker containers
  local           - All services local (including Traefik)

Examples:
  $0 docker                    # Switch to full Docker mode
  $0 traefik-only              # Switch to traefik-only mode
  $0 --no-start minimal        # Switch to minimal but don't start services
  $0 --force local             # Switch to local mode without prompts

Current deployment mode: $(get_deployment_mode || echo "not set")

This script will:
1. Stop services in current mode (unless --no-stop)
2. Reconfigure Traefik for new mode
3. Update stored deployment mode
4. Start services in new mode (unless --no-start)
5. Show next steps for local services
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "🔄 QuVel Kit Mode Switcher"

# Navigate to project root
cd "$PROJECT_ROOT"

# Parse additional flags and target mode
NO_STOP=false
NO_START=false
FORCE=false
TARGET_MODE=""

for arg in "${REMAINING_ARGS[@]}"; do
    case "$arg" in
        --no-stop)
            NO_STOP=true
            ;;
        --no-start)
            NO_START=true
            ;;
        --force)
            FORCE=true
            ;;
        --*)
            print_warning "Unknown option: $arg"
            ;;
        *)
            if [[ -z "$TARGET_MODE" ]]; then
                TARGET_MODE="$arg"
            else
                print_error "Multiple target modes specified: $TARGET_MODE and $arg"
                exit 1
            fi
            ;;
    esac
done

# Validate target mode
if [[ -z "$TARGET_MODE" ]]; then
    print_error "Target deployment mode is required"
    echo ""
    echo "Available modes: traefik-only, minimal, docker, local"
    echo "Usage: $0 NEW_MODE"
    exit 1
fi

if ! validate_deployment_mode "$TARGET_MODE"; then
    print_error "Invalid target deployment mode: $TARGET_MODE"
    print_error "Valid modes: traefik-only, minimal, docker, local"
    exit 1
fi

# Get current mode
CURRENT_MODE=$(get_deployment_mode)
if [[ -z "$CURRENT_MODE" ]]; then
    print_status "📋 No current mode set - switching to: $TARGET_MODE"
    CURRENT_MODE="none"
else
    print_status "📋 Current mode: $CURRENT_MODE"
fi

print_status "📋 Target mode: $TARGET_MODE"

# Check if already in target mode
if [[ "$CURRENT_MODE" == "$TARGET_MODE" ]]; then
    print_warning "Already in $TARGET_MODE mode!"
    print_status "Use './scripts/status.sh' to check current status"
    exit 0
fi

echo ""

# Show mode differences
print_header "🔄 Mode Transition"
print_status "Switching from: $CURRENT_MODE → $TARGET_MODE"

if [[ "$CURRENT_MODE" != "none" ]]; then
    CURRENT_DOCKER=$(get_docker_services "$CURRENT_MODE")
    CURRENT_LOCAL=$(get_local_services "$CURRENT_MODE")
else
    CURRENT_DOCKER=""
    CURRENT_LOCAL=""
fi

TARGET_DOCKER=$(get_docker_services "$TARGET_MODE")
TARGET_LOCAL=$(get_local_services "$TARGET_MODE")

echo ""
print_status "Current Docker services: ${CURRENT_DOCKER:-none}"
print_status "Target Docker services:  ${TARGET_DOCKER:-none}"
echo ""
print_status "Current local services:  ${CURRENT_LOCAL:-none}"
print_status "Target local services:   ${TARGET_LOCAL:-none}"

# Confirmation prompt
if [[ "$FORCE" != true ]]; then
    echo ""
    print_warning "This will:"
    if [[ "$NO_STOP" != true && -n "$CURRENT_DOCKER" ]]; then
        echo "  - Stop current Docker services: $CURRENT_DOCKER"
    fi
    echo "  - Reconfigure Traefik for $TARGET_MODE mode"
    echo "  - Update deployment mode to: $TARGET_MODE"
    if [[ "$NO_START" != true && -n "$TARGET_DOCKER" ]]; then
        echo "  - Start new Docker services: $TARGET_DOCKER"
    fi
    echo ""
    read -p "Continue with mode switch? [y/N]: " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        print_status "Mode switch cancelled."
        exit 0
    fi
fi

echo ""

# Step 1: Stop current services
if [[ "$NO_STOP" != true && "$CURRENT_MODE" != "none" && "$CURRENT_MODE" != "local" ]]; then
    print_header "1️⃣ Stopping Current Services"
    print_status "Stopping services for $CURRENT_MODE mode..."
    
    if "$SCRIPTS_DIR/stop.sh" --mode="$CURRENT_MODE"; then
        print_success "✅ Current services stopped"
    else
        print_error "❌ Failed to stop current services"
        print_status "Continuing with mode switch..."
    fi
    echo ""
else
    print_header "1️⃣ Skipping Service Stop"
    if [[ "$NO_STOP" == true ]]; then
        print_status "Skipped stopping services (--no-stop specified)"
    elif [[ "$CURRENT_MODE" == "local" ]]; then
        print_status "Local mode - no Docker services to stop"
    else
        print_status "No current mode - no services to stop"
    fi
    echo ""
fi

# Step 2: Reconfigure for new mode
print_header "2️⃣ Reconfiguring System"
print_status "Configuring Traefik for $TARGET_MODE mode..."

if "$SCRIPTS_DIR/deploy-mode.sh" "$TARGET_MODE"; then
    print_success "✅ System reconfigured for $TARGET_MODE mode"
else
    print_error "❌ Failed to reconfigure system"
    exit 1
fi
echo ""

# Step 3: Update stored mode
print_header "3️⃣ Updating Deployment Mode"
save_deployment_mode "$TARGET_MODE"
print_success "✅ Deployment mode updated to: $TARGET_MODE"
echo ""

# Step 4: Start services in new mode
if [[ "$NO_START" != true && -n "$TARGET_DOCKER" ]]; then
    print_header "4️⃣ Starting New Services"
    print_status "Starting services for $TARGET_MODE mode..."
    
    if "$SCRIPTS_DIR/start.sh" --mode="$TARGET_MODE"; then
        print_success "✅ New services started"
    else
        print_error "❌ Failed to start new services"
        print_status "You can start them manually with: ./scripts/start.sh"
    fi
    echo ""
else
    print_header "4️⃣ Skipping Service Start"
    if [[ "$NO_START" == true ]]; then
        print_status "Skipped starting services (--no-start specified)"
        print_status "Start services manually with: ./scripts/start.sh"
    elif [[ "$TARGET_MODE" == "local" ]]; then
        print_status "Local mode - no Docker services to start"
    else
        print_status "No Docker services configured for $TARGET_MODE mode"
    fi
    echo ""
fi

# Step 5: Show next steps
print_header "🎉 Mode Switch Complete!"
print_success "Successfully switched to $TARGET_MODE mode"

echo ""
print_header "📋 Next Steps"
get_mode_instructions "$TARGET_MODE"

# Show status information
echo ""
print_header "📊 Quick Status"
print_status "Check full status with: ./scripts/status.sh"

case "$TARGET_MODE" in
    "docker")
        if [[ "$NO_START" != true ]]; then
            RUNNING_CONTAINERS=$(docker ps --format '{{.Names}}' | grep "^quvel-" | wc -l)
            print_status "Docker containers running: $RUNNING_CONTAINERS"
        fi
        ;;
    "traefik-only"|"minimal")
        if [[ "$NO_START" != true ]]; then
            RUNNING_CONTAINERS=$(docker ps --format '{{.Names}}' | grep "^quvel-" | wc -l)
            print_status "Docker containers running: $RUNNING_CONTAINERS"
        fi
        print_status "💡 Don't forget to start local services"
        ;;
    "local")
        print_status "💡 All services need to be started locally"
        ;;
esac

echo ""
print_header "🔗 Access URLs"
case "$TARGET_MODE" in
    "docker")
        if [[ "$NO_START" != true ]]; then
            echo "  Frontend: ${SERVICE_URLS[frontend_public]}"
            echo "  API: ${SERVICE_URLS[api_public]}"
            echo "  Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}"
        else
            echo "  Start services first with: ./scripts/start.sh"
        fi
        ;;
    "traefik-only"|"minimal")
        echo "  Frontend: ${SERVICE_URLS[frontend_public]} (after starting local services)"
        echo "  API: ${SERVICE_URLS[api_public]} (after starting local services)"
        echo "  Traefik Dashboard: ${SERVICE_URLS[traefik_dashboard]}"
        ;;
    "local")
        echo "  Frontend: ${SERVICE_URLS[frontend_dev]} (after starting locally)"
        echo "  API: ${SERVICE_URLS[backend_dev]} (after starting locally)"
        ;;
esac

print_success "🚀 Mode switch to $TARGET_MODE completed successfully!"