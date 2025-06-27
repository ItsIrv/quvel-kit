#!/bin/bash

# QuVel Kit SSL Certificate Generator
# Generates SSL certificates using mkcert for local development

# Source common utilities
source "$(dirname "$0")/lib/common.sh"
source "$(dirname "$0")/lib/config.sh"

# Parse arguments
parse_common_args "$@"

# Help function
show_help() {
    show_help_header "SSL Certificate Generator"
    cat << EOF
Generates SSL certificates for QuVel Kit development using mkcert.

Usage: $0 [OPTIONS]

Options:
  --mode=MODE           Generate certificates for specific deployment mode
                        (traefik-only, minimal, docker, local)
  --ip=IP              Custom LAN IP address (auto-detected if not provided)
  --non-interactive,-n  Run without interactive prompts
  --help,-h            Show this help message

Examples:
  $0                           # Interactive mode with auto-detection
  $0 --mode=docker             # Generate certificates for docker mode
  $0 --ip=192.168.1.100        # Use specific IP address
  $0 --non-interactive         # No prompts, use defaults

If no mode is specified, certificates will be generated for all domains.
EOF
}

# Show help if requested
if [[ "$SHOW_HELP" == true ]]; then
    show_help
    exit 0
fi

print_header "🔐 Setting up SSL Certificates with mkcert..."

# Navigate to project root
cd "$PROJECT_ROOT"

# Install mkcert if not installed
if ! command_exists mkcert; then
    print_status "📦 Installing mkcert via npm..."
    npm install -g mkcert
fi

# Ensure mkcert local CA is set up
if [ ! -f "$(mkcert -CAROOT)/rootCA.pem" ]; then
    print_status "🔧 Setting up mkcert certificate authority..."
    mkcert -install
fi

# Copy the CA to the certs folder
mkdir -p "$DOCKER_DIR/certs"
cp "$(mkcert -CAROOT)/rootCA.pem" "$DOCKER_DIR/certs/ca.pem"

# Get or detect deployment mode
if [[ -z "$MODE" ]]; then
    MODE=$(get_deployment_mode)
fi

# Get custom IP from arguments or auto-detect
CUSTOM_IP=""
for arg in "${REMAINING_ARGS[@]}"; do
    if [[ "$arg" =~ ^--ip=(.+)$ ]]; then
        CUSTOM_IP="${BASH_REMATCH[1]}"
    fi
done

# Auto-detect LAN IP if not provided
LAN_IP=""
if [[ -n "$CUSTOM_IP" ]]; then
    LAN_IP="$CUSTOM_IP"
    print_status "🔍 Using provided IP: $LAN_IP"
elif [[ "$NON_INTERACTIVE" == true ]]; then
    DETECTED_IP=$(detect_local_ip)
    if [[ -n "$DETECTED_IP" && "$DETECTED_IP" != "127.0.0.1" ]]; then
        LAN_IP="$DETECTED_IP"
        print_status "🔍 Auto-detected IP: $LAN_IP"
    fi
else
    echo ""
    DETECTED_IP=$(detect_local_ip)
    if [[ -n "$DETECTED_IP" && "$DETECTED_IP" != "127.0.0.1" ]]; then
        print_status "🔍 Auto-detected LAN IP: $DETECTED_IP"
        read -p "📡 Use detected IP ($DETECTED_IP) for LAN-based domains? [Y/n/custom]: " IP_CHOICE
        
        case "$IP_CHOICE" in
            [Nn]*)
                LAN_IP=""
                print_status "   Skipping LAN domains"
                ;;
            [Cc]*)
                read -p "   Enter custom LAN IP: " LAN_IP
                ;;
            *)
                LAN_IP="$DETECTED_IP"
                print_status "   Using detected IP: $LAN_IP"
                ;;
        esac
    else
        print_warning "⚠️  Could not auto-detect LAN IP"
        if [[ "$NON_INTERACTIVE" != true ]]; then
            read -p "📡 Enter your LAN IP (e.g., 192.168.X.X) or press Enter to skip: " LAN_IP
        fi
    fi
fi

# Define base domains (always included)
BASE_DOMAINS=(
    "quvel.127.0.0.1.nip.io"
    "api.quvel.127.0.0.1.nip.io"
    "coverage-api.quvel.127.0.0.1.nip.io"
    "coverage.quvel.127.0.0.1.nip.io"
    "cap-tenant.quvel.127.0.0.1.nip.io"
)

# Add mode-specific domains based on deployment mode
MODE_SPECIFIC_DOMAINS=()
if [[ -n "$MODE" ]]; then
    case "$MODE" in
        "docker"|"traefik-only"|"minimal")
            # These modes might use multi-tenant domains
            MODE_SPECIFIC_DOMAINS+=(
                "quvel-two.127.0.0.1.nip.io"
                "api.quvel-two.127.0.0.1.nip.io"
            )
            ;;
        "local")
            # Local mode typically uses single tenant
            ;;
    esac
else
    # No mode specified, include all possible domains
    MODE_SPECIFIC_DOMAINS+=(
        "quvel-two.127.0.0.1.nip.io"
        "api.quvel-two.127.0.0.1.nip.io"
    )
fi

# Combine base and mode-specific domains
DOMAINS=("${BASE_DOMAINS[@]}" "${MODE_SPECIFIC_DOMAINS[@]}")

# Add LAN IP domains if specified
if [[ -n "$LAN_IP" ]]; then
    LAN_DOMAINS=(
        "quvel.${LAN_IP}.nip.io"
        "api.quvel.${LAN_IP}.nip.io"
        "coverage-api.quvel.${LAN_IP}.nip.io"
        "coverage.quvel.${LAN_IP}.nip.io"
        "cap-tenant.quvel.${LAN_IP}.nip.io"
    )
    
    # Add mode-specific LAN domains
    if [[ -z "$MODE" ]] || [[ "$MODE" == "docker" ]] || [[ "$MODE" == "traefik-only" ]] || [[ "$MODE" == "minimal" ]]; then
        LAN_DOMAINS+=(
            "quvel-two.${LAN_IP}.nip.io"
            "api.quvel-two.${LAN_IP}.nip.io"
        )
    fi
    
    DOMAINS=("${DOMAINS[@]}" "${LAN_DOMAINS[@]}")
fi

print_status "🔍 Generating SSL certificates for:"
for domain in "${DOMAINS[@]}"; do
    echo "   - ${domain}"
done

if [[ -n "$MODE" ]]; then
    print_status "📋 Mode: $MODE"
fi

# Generate SSL certificates
print_status "🔐 Generating certificates..."
if mkcert -cert-file "$DOCKER_DIR/certs/selfsigned.crt" -key-file "$DOCKER_DIR/certs/selfsigned.key" "${DOMAINS[@]}"; then
    print_success "✅ SSL setup complete!"
    print_status "📜 Certificates saved to docker/certs/"
    
    # Show additional info based on mode
    if [[ "$MODE" == "local" ]]; then
        print_warning "Note: For local mode, make sure your local Traefik configuration uses these certificates."
    fi
else
    print_error "❌ Failed to generate SSL certificates"
    exit 1
fi
