#!/bin/bash

# QuVel Kit Deployment Mode Switcher
# Generates traefik configurations from templates based on deployment mode

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration paths
TEMPLATE_DIR="docker/traefik/templates"
OUTPUT_DIR="docker/traefik/dynamic"
BACKEND_CONFIG="$OUTPUT_DIR/backend.yml"
FRONTEND_CONFIG="$OUTPUT_DIR/frontend.yml"
CERT_CONFIG="$OUTPUT_DIR/certificates.yaml"
TRAEFIK_CONFIG="docker/traefik/traefik.yml"

# Function to print colored output
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
    echo -e "${BLUE}[DEPLOY]${NC} $1"
}

# Function to detect local IP
detect_local_ip() {
    # Try to get local IP (excluding 127.0.0.1)
    local ip=""
    
    # macOS/Linux method
    if command -v ifconfig &> /dev/null; then
        ip=$(ifconfig | grep 'inet ' | grep -v '127.0.0.1' | head -1 | awk '{print $2}' | sed 's/addr://')
    fi
    
    # If ifconfig didn't work, try ip command (Linux)
    if [[ -z "$ip" ]] && command -v ip &> /dev/null; then
        ip=$(ip route get 8.8.8.8 | head -1 | awk '{print $7}')
    fi
    
    # Fallback to 127.0.0.1 if we can't detect
    if [[ -z "$ip" ]]; then
        ip="127.0.0.1"
    fi
    
    echo "$ip"
}

# Function to generate configuration from template
generate_config() {
    local template_file="$1"
    local output_file="$2"
    local local_ip="$3"
    local frontend_target="$4"
    local backend_target="$5"
    local cert_path="$6"
    
    if [[ ! -f "$template_file" ]]; then
        print_error "Template file not found: $template_file"
        exit 1
    fi
    
    print_status "Generating $output_file from template..."
    
    # Use environment variables for template substitution
    export LOCAL_IP="127.0.0.1"
    export FRONTEND_TARGET="$frontend_target"
    export FRONTEND_WS_TARGET="$frontend_target"
    export CAPACITOR_TARGET="$frontend_target"
    export BACKEND_TARGET="$backend_target"
    export CERT_PATH="$cert_path"
    export TENANT_DOMAINS=""
    export API_TENANT_DOMAINS=""
    export CAP_TENANT_DOMAINS=""
    
    # Generate additional domains for LAN IP if different from 127.0.0.1
    if [[ "$local_ip" != "127.0.0.1" ]]; then
        export TENANT_DOMAINS=" || Host(\`quvel.$local_ip.nip.io\`) || Host(\`quvel-two.$local_ip.nip.io\`)"
        export API_TENANT_DOMAINS=" || Host(\`api.quvel.$local_ip.nip.io\`) || Host(\`api.quvel-two.$local_ip.nip.io\`)"
        export CAP_TENANT_DOMAINS=" || Host(\`cap-tenant.quvel.$local_ip.nip.io\`)"
    fi
    
    # For Docker mode, use different ports for WS and Capacitor
    if [[ "$frontend_target" == "https://quvel-frontend:9000" ]]; then
        export FRONTEND_WS_TARGET="https://127.0.0.1.nip.io:3000"  # WS goes to local for Docker mode
        export CAPACITOR_TARGET="https://host.docker.internal:3002"  # Capacitor has different port
    elif [[ "$frontend_target" == "https://host.docker.internal:3000" ]]; then
        export CAPACITOR_TARGET="https://host.docker.internal:3002"
    fi
    
    # Use envsubst for template substitution (much more reliable than sed)
    if command -v envsubst &> /dev/null; then
        envsubst < "$template_file" > "$output_file"
    else
        # Fallback to simpler sed for basic replacements
        print_warning "envsubst not available, using basic sed replacement"
        sed -e "s|\${LOCAL_IP}|$LOCAL_IP|g" \
            -e "s|\${FRONTEND_TARGET}|$FRONTEND_TARGET|g" \
            -e "s|\${FRONTEND_WS_TARGET}|$FRONTEND_WS_TARGET|g" \
            -e "s|\${CAPACITOR_TARGET}|$CAPACITOR_TARGET|g" \
            -e "s|\${BACKEND_TARGET}|$BACKEND_TARGET|g" \
            -e "s|\${CERT_PATH}|$CERT_PATH|g" \
            -e "s|\${TENANT_DOMAINS}||g" \
            -e "s|\${API_TENANT_DOMAINS}||g" \
            -e "s|\${CAP_TENANT_DOMAINS}||g" \
            "$template_file" > "$output_file"
    fi
    
    # Clean up environment variables
    unset LOCAL_IP FRONTEND_TARGET FRONTEND_WS_TARGET CAPACITOR_TARGET BACKEND_TARGET CERT_PATH
    unset TENANT_DOMAINS API_TENANT_DOMAINS CAP_TENANT_DOMAINS
    
    # Basic validation - check if file was created and has content
    if [[ -f "$output_file" ]] && [[ -s "$output_file" ]]; then
        print_status "✓ Generated $output_file successfully"
    else
        print_error "✗ Failed to generate $output_file"
        return 1
    fi
}

# Function to generate traefik.yml configuration  
generate_traefik_config() {
    local mode="$1"
    local project_root=$(pwd)
    
    print_status "Generating traefik.yml for $mode mode..."
    
    # Set paths based on deployment mode
    case "$mode" in
        "local")
            # For local mode, Traefik runs locally and needs actual file paths
            export TRAEFIK_CERT_FILE="$project_root/docker/certs/selfsigned.crt"
            export TRAEFIK_KEY_FILE="$project_root/docker/certs/selfsigned.key"
            export TRAEFIK_DYNAMIC_DIR="$project_root/docker/traefik/dynamic"
            ;;
        *)
            # For Docker modes, use container paths
            export TRAEFIK_CERT_FILE="/certs/selfsigned.crt"
            export TRAEFIK_KEY_FILE="/certs/selfsigned.key"
            export TRAEFIK_DYNAMIC_DIR="/traefik"
            ;;
    esac
    
    # Generate traefik.yml from template
    if command -v envsubst &> /dev/null; then
        envsubst < "$TEMPLATE_DIR/traefik.yml.template" > "$TRAEFIK_CONFIG"
    else
        print_warning "envsubst not available, using basic sed replacement"
        sed -e "s|\${TRAEFIK_CERT_FILE}|$TRAEFIK_CERT_FILE|g" \
            -e "s|\${TRAEFIK_KEY_FILE}|$TRAEFIK_KEY_FILE|g" \
            -e "s|\${TRAEFIK_DYNAMIC_DIR}|$TRAEFIK_DYNAMIC_DIR|g" \
            "$TEMPLATE_DIR/traefik.yml.template" > "$TRAEFIK_CONFIG"
    fi
    
    # Clean up environment variables
    unset TRAEFIK_CERT_FILE TRAEFIK_KEY_FILE TRAEFIK_DYNAMIC_DIR
    
    # Validation
    if [[ -f "$TRAEFIK_CONFIG" ]] && [[ -s "$TRAEFIK_CONFIG" ]]; then
        print_status "✓ Generated $TRAEFIK_CONFIG successfully"
    else
        print_error "✗ Failed to generate $TRAEFIK_CONFIG"
        return 1
    fi
}

# Function to show current mode
show_current_mode() {
    print_header "Detecting current deployment mode..."
    
    if [[ ! -f "$BACKEND_CONFIG" ]] || [[ ! -f "$FRONTEND_CONFIG" ]]; then
        echo -e "Current mode: ${YELLOW}not configured${NC} (no configuration files found)"
        return
    fi
    
    # Check backend configuration
    if grep -q "host.docker.internal:8000" "$BACKEND_CONFIG"; then
        if grep -q "host.docker.internal:3000" "$FRONTEND_CONFIG"; then
            echo -e "Current mode: ${GREEN}traefik-only/minimal${NC} (Services local, Traefik in Docker)"
        fi
    elif grep -q "quvel-app:8000" "$BACKEND_CONFIG"; then
        if grep -q "quvel-frontend:9000" "$FRONTEND_CONFIG"; then
            echo -e "Current mode: ${GREEN}docker${NC} (All services in Docker)"
        fi
    elif grep -q "127.0.0.1:8000" "$BACKEND_CONFIG"; then
        if grep -q "127.0.0.1:3000" "$FRONTEND_CONFIG"; then
            echo -e "Current mode: ${GREEN}local${NC} (All services local)"
        fi
    else
        echo -e "Current mode: ${YELLOW}unknown${NC} (Custom or mixed configuration)"
    fi
}

# Function to configure traefik-only mode
configure_traefik_only() {
    local local_ip=$(detect_local_ip)
    
    print_status "Configuring for traefik-only mode (only Traefik in Docker, everything else local)..."
    if [[ "$local_ip" != "127.0.0.1" ]]; then
        print_status "Detected local IP: $local_ip"
    fi
    
    # Generate configurations
    generate_config "$TEMPLATE_DIR/frontend.yml.template" "$FRONTEND_CONFIG" "$local_ip" \
        "https://host.docker.internal:3000" \
        "http://host.docker.internal:8000" \
        "/certs"
    
    generate_config "$TEMPLATE_DIR/backend.yml.template" "$BACKEND_CONFIG" "$local_ip" \
        "https://host.docker.internal:3000" \
        "http://host.docker.internal:8000" \
        "/certs"
    
    generate_config "$TEMPLATE_DIR/certificates.yml.template" "$CERT_CONFIG" "$local_ip" \
        "https://host.docker.internal:3000" \
        "http://host.docker.internal:8000" \
        "/certs"
    
    # Generate traefik.yml for this mode
    generate_traefik_config "traefik-only"
    
    print_status "Configuration updated for traefik-only mode"
    print_warning "You'll need to start all services locally:"
    echo "  MySQL:    brew services start mysql"
    echo "  Redis:    brew services start redis"
    echo "  Backend:  cd backend && php artisan serve --host=0.0.0.0 --port=8000"
    echo "  Frontend: cd frontend && quasar dev --port 3000"
}

# Function to configure minimal mode
configure_minimal() {
    local local_ip=$(detect_local_ip)
    
    print_status "Configuring for minimal resource mode (Traefik + DB in Docker, services local)..."
    if [[ "$local_ip" != "127.0.0.1" ]]; then
        print_status "Detected local IP: $local_ip"
    fi
    
    # Same config as traefik-only since both have services running locally
    generate_config "$TEMPLATE_DIR/frontend.yml.template" "$FRONTEND_CONFIG" "$local_ip" \
        "https://host.docker.internal:3000" \
        "http://host.docker.internal:8000" \
        "/certs"
    
    generate_config "$TEMPLATE_DIR/backend.yml.template" "$BACKEND_CONFIG" "$local_ip" \
        "https://host.docker.internal:3000" \
        "http://host.docker.internal:8000" \
        "/certs"
    
    generate_config "$TEMPLATE_DIR/certificates.yml.template" "$CERT_CONFIG" "$local_ip" \
        "https://host.docker.internal:3000" \
        "http://host.docker.internal:8000" \
        "/certs"
    
    # Generate traefik.yml for this mode
    generate_traefik_config "minimal"
    
    print_status "Configuration updated for minimal mode"
    print_warning "You'll need to start backend and frontend services locally:"
    echo "  Backend:  cd backend && php artisan serve --host=0.0.0.0 --port=8000"
    echo "  Frontend: cd frontend && quasar dev --port 3000"
}

# Function to configure docker mode
configure_docker() {
    local local_ip=$(detect_local_ip)
    
    print_status "Configuring for full Docker mode (all services in Docker)..."
    if [[ "$local_ip" != "127.0.0.1" ]]; then
        print_status "Detected local IP: $local_ip"
    fi
    
    # Generate configurations for Docker mode
    generate_config "$TEMPLATE_DIR/frontend.yml.template" "$FRONTEND_CONFIG" "$local_ip" \
        "https://quvel-frontend:9000" \
        "http://quvel-app:8000" \
        "/certs"
    
    generate_config "$TEMPLATE_DIR/backend.yml.template" "$BACKEND_CONFIG" "$local_ip" \
        "https://quvel-frontend:9000" \
        "http://quvel-app:8000" \
        "/certs"
    
    generate_config "$TEMPLATE_DIR/certificates.yml.template" "$CERT_CONFIG" "$local_ip" \
        "https://quvel-frontend:9000" \
        "http://quvel-app:8000" \
        "/certs"
    
    # Generate traefik.yml for this mode
    generate_traefik_config "docker"
    
    print_status "Configuration updated for Docker mode"
    print_warning "Run './scripts/restart.sh' to start all services in Docker"
}

# Function to configure local mode
configure_local() {
    local local_ip=$(detect_local_ip)
    
    print_status "Configuring for fully local mode (all services local)..."
    if [[ "$local_ip" != "127.0.0.1" ]]; then
        print_status "Detected local IP: $local_ip"
    fi
    
    # Generate configurations for local mode
    generate_config "$TEMPLATE_DIR/frontend.yml.template" "$FRONTEND_CONFIG" "$local_ip" \
        "https://127.0.0.1:3000" \
        "http://127.0.0.1:8000" \
        "$(pwd)/docker/certs"
    
    generate_config "$TEMPLATE_DIR/backend.yml.template" "$BACKEND_CONFIG" "$local_ip" \
        "https://127.0.0.1:3000" \
        "http://127.0.0.1:8000" \
        "$(pwd)/docker/certs"
    
    generate_config "$TEMPLATE_DIR/certificates.yml.template" "$CERT_CONFIG" "$local_ip" \
        "https://127.0.0.1:3000" \
        "http://127.0.0.1:8000" \
        "$(pwd)/docker/certs"
    
    # Generate traefik.yml for this mode
    generate_traefik_config "local"
    
    print_status "Configuration updated for local mode"
    print_warning "You'll need to install and configure Traefik locally:"
    echo "  brew install traefik"
    echo "  traefik --configfile=docker/traefik/traefik.yml"
    print_warning "And start backend and frontend services locally:"
    echo "  Backend:  cd backend && php artisan serve --host=0.0.0.0 --port=8000"
    echo "  Frontend: cd frontend && quasar dev --port 3000"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [MODE]"
    echo ""
    echo "Available modes:"
    echo "  traefik-only - Only Traefik in Docker, everything else local (most minimal)"
    echo "  minimal      - Traefik + MySQL + Redis in Docker, backend/frontend local"
    echo "  docker       - All services in Docker containers"
    echo "  local        - All services local (including Traefik)"
    echo "  current      - Show current deployment mode"
    echo ""
    echo "Examples:"
    echo "  $0 traefik-only  # Only Traefik in Docker (maximum local setup)"
    echo "  $0 minimal       # Traefik + database services in Docker"
    echo "  $0 docker        # Full Docker mode"
    echo "  $0 local         # Fully local mode"
    echo "  $0 current       # Show current mode"
}

# Ensure output directory exists
mkdir -p "$OUTPUT_DIR"

# Main script logic
case "$1" in
    "traefik-only")
        configure_traefik_only
        ;;
    "minimal")
        configure_minimal
        ;;
    "docker")
        configure_docker
        ;;
    "local")
        configure_local
        ;;
    "current"|"")
        show_current_mode
        ;;
    *)
        print_error "Unknown mode: $1"
        show_usage
        exit 1
        ;;
esac

echo ""
print_status "Deployment mode configuration complete!"