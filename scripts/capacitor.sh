#!/bin/bash

echo "⚡️ Updating Capacitor Config for iOS..."

# Navigate to project root (relative to this script's location)
cd "$(dirname "$0")/.."

CONFIG_PATH="frontend/src-capacitor/ios/App/App/capacitor.config.json"

# Prompt for LAN IP (aligning with ssl.sh)
echo ""
read -p "📡 Enter your LAN IP (e.g., 192.168.X.X): " LAN_IP

if [[ -z "$LAN_IP" ]]; then
  echo "❌ No IP provided. Exiting."
  exit 1
fi

# Construct new Capacitor JSON config
NEW_CONFIG=$(cat <<EOF
{
  "appId": "quvel.irv.codes",
  "appName": "quvel.irv.codes",
  "webDir": "www",
  "server": {
    "url": "https://cap-tenant.quvel.${LAN_IP}.nip.io",
    "cleartext": false
  },
  "packageClassList": [
    "AppPlugin"
  ]
}
EOF
)

# Ensure the config file exists
if [[ ! -f "$CONFIG_PATH" ]]; then
  echo "❌ capacitor.config.json not found. Make sure you've run 'yarn dev:ios' at least once."
  exit 1
fi

# Write the new JSON config
echo "$NEW_CONFIG" > "$CONFIG_PATH"

echo "🚀 Updated Capacitor config with server URL: https://cap-tenant.quvel.${LAN_IP}.nip.io"
