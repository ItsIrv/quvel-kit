#!/bin/bash

# QuVel Kit Common Utilities
# Shared functions and utilities for all scripts

# Colors for output
export RED='\033[0;31m'
export GREEN='\033[0;32m'
export YELLOW='\033[1;33m'
export BLUE='\033[0;34m'
export MAGENTA='\033[0;35m'
export CYAN='\033[0;36m'
export NC='\033[0m' # No Color

# Project paths
export PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
export SCRIPTS_DIR="$PROJECT_ROOT/scripts"
export DOCKER_DIR="$PROJECT_ROOT/docker"
export BACKEND_DIR="$PROJECT_ROOT/backend"
export FRONTEND_DIR="$PROJECT_ROOT/frontend"
export MODE_FILE="$PROJECT_ROOT/.quvel-mode"
export DOCKER_COMPOSE_FILE="$DOCKER_DIR/docker-compose.yml"

# Output functions
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}==>${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅${NC} $1"
}

print_task() {
    echo -e "${CYAN}[TASK]${NC} $1"
}

# IP Detection function
detect_local_ip() {
    local ip=""
    
    # macOS/Linux method
    if command -v ifconfig &> /dev/null; then
        ip=$(ifconfig | grep 'inet ' | grep -v '127.0.0.1' | head -1 | awk '{print $2}' | sed 's/addr://')
    fi
    
    # If ifconfig didn't work, try ip command (Linux)
    if [[ -z "$ip" ]] && command -v ip &> /dev/null; then
        ip=$(ip route get 8.8.8.8 | head -1 | awk '{print $7}')
    fi
    
    # If we still don't have an IP, try hostname -I (Linux)
    if [[ -z "$ip" ]] && command -v hostname &> /dev/null; then
        ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi
    
    echo "$ip"
}

# Get stored deployment mode
get_deployment_mode() {
    if [[ -f "$MODE_FILE" ]]; then
        cat "$MODE_FILE"
    else
        echo ""
    fi
}

# Save deployment mode
save_deployment_mode() {
    local mode="$1"
    echo "$mode" > "$MODE_FILE"
    print_status "Saved deployment mode: $mode"
}

# Validate deployment mode
validate_deployment_mode() {
    local mode="$1"
    case "$mode" in
        "traefik-only"|"minimal"|"docker"|"local")
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# Check if Docker is running
check_docker() {
    if ! docker info &> /dev/null; then
        print_error "Docker is not running. Please start Docker Desktop."
        return 1
    fi
    return 0
}

# Check if a command exists
command_exists() {
    command -v "$1" &> /dev/null
}

# Wait for a service to be ready
wait_for_service() {
    local service_name="$1"
    local check_command="$2"
    local max_attempts="${3:-30}"
    local attempt=1
    
    print_status "Waiting for $service_name to be ready..."
    
    while [ $attempt -le $max_attempts ]; do
        if eval "$check_command" &> /dev/null; then
            print_success "$service_name is ready!"
            return 0
        fi
        
        echo -n "."
        sleep 2
        ((attempt++))
    done
    
    echo ""
    print_error "$service_name failed to start after $max_attempts attempts"
    return 1
}

# Get container name based on service
get_container_name() {
    local service="$1"
    case "$service" in
        "traefik") echo "quvel-traefik" ;;
        "app"|"backend") echo "quvel-app" ;;
        "frontend") echo "quvel-frontend" ;;
        "mysql") echo "quvel-mysql" ;;
        "redis") echo "quvel-redis" ;;
        "coverage") echo "quvel-coverage" ;;
        *) echo "" ;;
    esac
}

# Check if container is running
is_container_running() {
    local container_name="$1"
    docker ps --format '{{.Names}}' | grep -q "^${container_name}$"
}

# Common help header
show_help_header() {
    local script_name="$1"
    echo "QuVel Kit - $script_name"
    echo "================================="
    echo ""
}

# Parse common arguments
parse_common_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --mode=*)
                MODE="${1#*=}"
                shift
                ;;
            --mode)
                MODE="$2"
                shift 2
                ;;
            --help|-h)
                SHOW_HELP=true
                shift
                ;;
            --non-interactive|-n)
                NON_INTERACTIVE=true
                shift
                ;;
            *)
                # Unknown option, let calling script handle it
                REMAINING_ARGS+=("$1")
                shift
                ;;
        esac
    done
}

# Initialize common variables
MODE=""
SHOW_HELP=false
NON_INTERACTIVE=false
REMAINING_ARGS=()