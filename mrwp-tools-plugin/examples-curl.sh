#!/bin/bash

# Mr.WordPress Tools - API Examples with curl
# 
# Replace these variables with your actual values:
SITE_URL="https://your-site.com"
SITE_SECRET="your_site_secret_64_characters"

echo "=== Mr.WordPress Tools API Examples ==="
echo ""

# Function to calculate HMAC signature
calculate_signature() {
    local timestamp="$1"
    local body="$2"
    local message="${timestamp}\n${body}"
    echo -n "$message" | openssl dgst -sha256 -hmac "$SITE_SECRET" -binary | base64
}

echo "1. Testing public ping endpoint..."
echo "curl -X GET \"${SITE_URL}/wp-json/mrwp/v1/ping\""
curl -X GET "${SITE_URL}/wp-json/mrwp/v1/ping" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "2. Getting site status..."
timestamp=$(date +%s)
body='{}'
signature=$(calculate_signature "$timestamp" "$body")

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/status\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/status" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "3. Toggling maintenance mode..."
timestamp=$(date +%s)
body='{"action":"toggle_maintenance"}'
signature=$(calculate_signature "$timestamp" "$body")

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/action\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "4. Resetting bypass code..."
timestamp=$(date +%s)
body='{"action":"reset_bypass"}'
signature=$(calculate_signature "$timestamp" "$body")

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/action\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "5. Toggling debug mode..."
timestamp=$(date +%s)
body='{"action":"toggle_debug"}'
signature=$(calculate_signature "$timestamp" "$body")

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/action\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "6. Sending bypass email..."
timestamp=$(date +%s)
body='{"action":"send_bypass_email"}'
signature=$(calculate_signature "$timestamp" "$body")

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/action\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "7. Testing invalid action (should return 400)..."
timestamp=$(date +%s)
body='{"action":"invalid_action"}'
signature=$(calculate_signature "$timestamp" "$body")

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/action\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""
echo "----------------------------------------"
echo ""

echo "8. Testing invalid signature (should return 401)..."
timestamp=$(date +%s)
body='{"action":"toggle_maintenance"}'
invalid_signature="invalid_signature"

echo "curl -X POST \"${SITE_URL}/wp-json/mrwp/v1/action\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"x-mrwp-timestamp: ${timestamp}\" \\"
echo "  -H \"x-mrwp-signature: ${invalid_signature}\" \\"
echo "  -d '${body}'"

curl -X POST "${SITE_URL}/wp-json/mrwp/v1/action" \
  -H "Content-Type: application/json" \
  -H "x-mrwp-timestamp: ${timestamp}" \
  -H "x-mrwp-signature: ${invalid_signature}" \
  -d "${body}" \
  -w "\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  | jq '.' 2>/dev/null || cat
echo ""

echo "=== Examples Complete ==="
echo ""
echo "Usage:"
echo "1. Edit this script and replace SITE_URL and SITE_SECRET with your values"
echo "2. Make the script executable: chmod +x examples-curl.sh"
echo "3. Run the script: ./examples-curl.sh"
echo ""
echo "Requirements:"
echo "- curl command"
echo "- openssl command"
echo "- jq command (optional, for JSON formatting)"
echo ""
echo "To get your site secret:"
echo "- Log into WordPress admin"
echo "- Go to Settings > Mr.WordPress Tools"
echo "- Copy the site secret from the API Information section"