#!/bin/bash

# API Base URL
BASE_URL="${BASE_URL:-http://localhost:8000}"

# Temporary file to store cookies
COOKIE_FILE="cookies.txt"

# Credentials for login
USERNAME="${USERNAME:-admin}"
PASSWORD="${PASSWORD:-admin}"

# Step 1: Fetch CSRF Token
echo "Fetching CSRF token..."
CSRF_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -c $COOKIE_FILE -X GET "$BASE_URL/sanctum/csrf-cookie")
if [[ "$CSRF_RESPONSE" -ne 204 ]]; then
  echo "Failed to fetch CSRF token. HTTP status: $CSRF_RESPONSE"
  exit 1
fi
echo "CSRF token fetched and stored in $COOKIE_FILE."

# Step 2: Log in
echo "Logging in..."
LOGIN_RESPONSE=$(curl -s -b $COOKIE_FILE -c $COOKIE_FILE -X POST "$BASE_URL/api/v1/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"$USERNAME\", \"password\":\"$PASSWORD\"}")

LOGIN_STATUS=$(echo "$LOGIN_RESPONSE" | jq -r '.status')
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token')
echo "Token: $TOKEN"
if [[ "$LOGIN_STATUS" != "success" ]]; then
  echo "Login failed. Response: $LOGIN_RESPONSE"
  exit 1
fi
echo "Login successful."

#echo "Cookies"
#cat $COOKIE_FILE
#CSRF_TOKEN=$(grep XSRF-TOKEN cookies.txt | awk '{print $7}')
CSRF_TOKEN=$(grep XSRF-TOKEN $COOKIE_FILE | awk '{print $7}' | sed 's/%/\\x/g' | xargs -0 printf)
echo "CSRF_TOKEN: $CSRF_TOKEN"

# Step 3: Access Protected Endpoint
#echo "Accessing protected endpoint with Cookies..."
#USER_RESPONSE=$(curl -s -b $COOKIE_FILE -X GET "$BASE_URL/api/v1/debug-csrf" \
#  -H "Accept: application/json" \
#  -H "Content-Type: application/json" \
#  -H "Referer: http://localhost:8000" \
#  -H "Content-Type: application/json")
#USER_RESPONSE=$(curl -s -b $COOKIE_FILE -X GET "$BASE_URL/api/v1/user" \
#  -H "Accept: application/json" \
#  -H "Content-Type: application/json" \
#  -H "Referer: http://localhost:8000" \
#  -H "X-XSRF-TOKEN: $CSRF_TOKEN")

echo "User response with Cookies: $USER_RESPONSE"

#echo "Accessing protected endpoint with Bearer Token..."
USER_RESPONSE=$(curl -s -X GET "$BASE_URL/api/v1/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")
echo "User response with Bearer Token: $USER_RESPONSE"

# Step 4: Log out
echo "Logging out..."
LOGOUT_RESPONSE=$(curl -s -b $COOKIE_FILE -X POST "$BASE_URL/api/v1/logout" \
  -H "Content-Type: application/json")

echo "Logout response: $LOGOUT_RESPONSE"

# Clean up
rm -f $COOKIE_FILE
echo "Cookies cleaned up. Done!"
