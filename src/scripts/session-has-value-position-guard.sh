#!/usr/bin/env bash
# Reject session()->has() / $request->session()->has() used as a value (assignment,
# return operand, ?? RHS, etc.) rather than in a boolean condition.
set -euo pipefail

cd "$(dirname "$0")/.."
violations=0

while IFS= read -r line; do
    file="${line%%:*}"
    rest="${line#*:}"
    lineno="${rest%%:*}"
    content="${rest#*:}"

    # Allowed: if / elseif / while conditions (may span only single-line checks here).
    if echo "$content" | grep -qE '^\s*(if|elseif|while)\s*\('; then
        if echo "$content" | grep -qE 'session\(\)->has\(|->session\(\)->has\('; then
            continue
        fi
    fi

    # Allowed: continuation lines of a boolean condition.
    if echo "$content" | grep -qE '^\s*(&&|\|\|)'; then
        if echo "$content" | grep -qE 'session\(\)->has\(|->session\(\)->has\('; then
            continue
        fi
    fi

    # Allowed: boolean short-circuit chains inside conditions.
    if echo "$content" | grep -qE '([&|]{2}|!\s*)\s*(\$[a-zA-Z_][a-zA-Z0-9_]*->session\(\)|session\(\))->has\('; then
        continue
    fi

    # Allowed: elseif branches testing session keys.
    if echo "$content" | grep -qE '^\s*\}\s*elseif\s*\(|^\s*elseif\s*\('; then
        if echo "$content" | grep -qE 'session\(\)->has\(|->session\(\)->has\('; then
            continue
        fi
    fi

    # Allowed: ternary condition (has() before ?).
    if echo "$content" | grep -qE 'session\(\)->has\([^)]+\)\s*\?|->session\(\)->has\([^)]+\)\s*\?'; then
        continue
    fi

    echo "session()->has() in value position: ${file}:${lineno}: ${content}"
    violations=$((violations + 1))
done < <(rg -n '(\$[a-zA-Z_][a-zA-Z0-9_]*->session\(\)|session\(\))->has\(' app --glob '*.php' || true)

if [[ "$violations" -gt 0 ]]; then
    echo ""
    echo "Found ${violations} session()->has() value-position violation(s)."
    echo "Use session()->get() when reading a value; reserve has() for boolean conditions."
    exit 1
fi

echo "session()->has() value-position guard: OK"
