#!/bin/bash

echo "🔐 Setting up SSL Certificates with mkcert..."

# Navigate to project root
cd "$(dirname "$0")/.."

# Install mkcert if not installed
if ! command -v mkcert &> /dev/null; then
  echo "📦 Installing mkcert via npm..."
  npm install -g mkcert
fi

# Ensure mkcert local CA is set up (run only if needed)
if [ ! -f "$(mkcert -CAROOT)/rootCA.pem" ]; then
  mkcert -install
fi

# Copy the CA to the certs folder
mkdir -p docker/certs
cp "$(mkcert -CAROOT)/rootCA.pem" docker/certs/ca.pem

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
    
    echo "$ip"
}

# Define base domains
BASE_DOMAINS=(
  "quvel.127.0.0.1.nip.io"
  "api.quvel.127.0.0.1.nip.io"
  "coverage-api.quvel.127.0.0.1.nip.io"
  "coverage.quvel.127.0.0.1.nip.io"
  "cap-tenant.quvel.127.0.0.1.nip.io"
  "quvel-two.127.0.0.1.nip.io"
  "api.quvel-two.127.0.0.1.nip.io"
)

# Auto-detect LAN IP
echo ""
DETECTED_IP=$(detect_local_ip)
if [[ -n "$DETECTED_IP" ]]; then
  echo "🔍 Auto-detected LAN IP: $DETECTED_IP"
  read -p "📡 Use detected IP ($DETECTED_IP) for LAN-based domains? [Y/n/custom]: " IP_CHOICE
  
  case "$IP_CHOICE" in
    [Nn]*)
      LAN_IP=""
      echo "   Skipping LAN domains"
      ;;
    [Cc]*)
      read -p "   Enter custom LAN IP: " LAN_IP
      ;;
    *)
      LAN_IP="$DETECTED_IP"
      echo "   Using detected IP: $LAN_IP"
      ;;
  esac
else
  echo "⚠️  Could not auto-detect LAN IP"
  read -p "📡 Enter your LAN IP (e.g., 192.168.X.X) if you want to add LAN-based domains, or press Enter to skip: " LAN_IP
fi

if [[ -n "$LAN_IP" ]]; then
  LAN_DOMAINS=(
    "quvel.${LAN_IP}.nip.io"
    "api.quvel.${LAN_IP}.nip.io"
    "coverage-api.quvel.${LAN_IP}.nip.io"
    "coverage.quvel.${LAN_IP}.nip.io"
    "cap-tenant.quvel.${LAN_IP}.nip.io"
    "quvel-two.${LAN_IP}.nip.io"
    "api.quvel-two.${LAN_IP}.nip.io"
  )
  DOMAINS=("${BASE_DOMAINS[@]}" "${LAN_DOMAINS[@]}")
else
  DOMAINS=("${BASE_DOMAINS[@]}")
fi

echo "🔍 Generating SSL certificates for:"
for domain in "${DOMAINS[@]}"; do
  echo "   - ${domain}"
done

# Generate SSL certificates
mkcert -cert-file docker/certs/selfsigned.crt -key-file docker/certs/selfsigned.key "${DOMAINS[@]}"

echo "✅ SSL setup complete!"
echo "📜 Certificates saved to docker/certs/"
