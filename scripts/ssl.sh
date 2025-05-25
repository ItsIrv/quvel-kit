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

# Define base domains
BASE_DOMAINS=(
  "quvel.127.0.0.1.nip.io"
  "api.quvel.127.0.0.1.nip.io"
  "coverage-api.quvel.127.0.0.1.nip.io"
  "coverage.quvel.127.0.0.1.nip.io"
  "cap-tenant.quvel.127.0.0.1.nip.io"
)

# Ask for optional LAN IP
echo ""
read -p "📡 Enter your LAN IP (e.g., 192.168.X.X) if you want to add LAN-based domains, or press Enter to skip: " LAN_IP

if [[ -n "$LAN_IP" ]]; then
  LAN_DOMAINS=(
    "quvel.${LAN_IP}.nip.io"
    "api.quvel.${LAN_IP}.nip.io"
    "coverage-api.quvel.${LAN_IP}.nip.io"
    "coverage.quvel.${LAN_IP}.nip.io"
    "cap-tenant.quvel.${LAN_IP}.nip.io"
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
