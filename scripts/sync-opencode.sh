#!/usr/bin/env bash
# sync-opencode.sh — Generate opencode.json from .github/ source
# Run this after changing .github/copilot-instructions.md, prompts, or skills.
# Output: opencode.json (gitignored)

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "Generating opencode.json from .github/ source..."

# Collect instruction files
INSTRUCTIONS_JSON=$(cat <<'EOF'
[
  ".github/copilot-instructions.md",
  "docs/development-patterns.md",
  "docs/module-registry.md"
]
EOF
)

# Build commands from prompts
COMMANDS_JSON="{"
first_cmd=true
for source in .github/prompts/*.prompt.md; do
  cmd_name="$(basename "$source" .prompt.md)"
  # Extract description from frontmatter, strip surrounding quotes
  desc=$(sed -n 's/^description:[[:space:]]*//p' "$source" | head -1 | sed 's/^"//;s/"$//')
  [ -z "$desc" ] && desc="$cmd_name workflow"
  # Escape any remaining double quotes in description
  desc=$(echo "$desc" | sed 's/"/\\"/g')

  if [ "$first_cmd" = true ]; then
    first_cmd=false
  else
    COMMANDS_JSON+=","
  fi

  COMMANDS_JSON+=$(cat <<EOF

    "$cmd_name": {
      "template": "{file:./.github/prompts/${cmd_name}.prompt.md}",
      "description": "$desc"
    }
EOF
  )
done
COMMANDS_JSON+="
  }"

# Write opencode.json
cat > opencode.json <<EOF
{
  "\$schema": "https://opencode.ai/config.json",
  "instructions": $INSTRUCTIONS_JSON,
  "agent": {
    "code": {
      "mode": "primary",
      "prompt": "You are an expert AI coding assistant for this ERP repository. Follow .github/copilot-instructions.md as the primary rule source. Keep architecture as Laravel API + React SPA (no Inertia). Use ./vendor/bin/sail for all runtime commands. Use MCP tools when available.",
      "tools": {
        "write": true,
        "edit": true,
        "bash": true
      }
    },
    "plan": {
      "mode": "primary",
      "prompt": "You are a planning agent for this ERP repository. Analyze requirements, propose architecture, and create implementation plans. Do NOT edit files directly.",
      "tools": {
        "write": false,
        "edit": false,
        "bash": false
      }
    },
    "safe-refactor": {
      "description": "Refactor aman dengan blast radius analysis via Depwire MCP",
      "mode": "subagent",
      "prompt": "{file:./.github/agents/refactor-safe.agent.md}",
      "tools": {
        "write": true,
        "edit": true,
        "bash": true
      }
    },
    "context7-research": {
      "description": "Lookup docs package/framework/SDK/API/CLI terbaru via Context7 MCP",
      "mode": "subagent",
      "prompt": "{file:./.github/agents/context7-research.agent.md}",
      "tools": {
        "write": false,
        "edit": false,
        "bash": false
      }
    }
  },
  "command": $COMMANDS_JSON,
  "mcp": {
    "context7": {
      "type": "local",
      "command": ["npx", "-y", "@upstash/context7-mcp@latest"],
      "environment": {
        "DEFAULT_MINIMUM_TOKENS": "10000"
      }
    },
    "laravel-boost": {
      "type": "local",
      "command": ["./vendor/bin/sail", "php", "/var/www/html/artisan", "boost:mcp"],
      "timeout": 15000
    },
    "depwire": {
      "type": "local",
      "command": ["npx", "-y", "depwire@latest", "mcp"],
      "environment": {
        "DEPWIRE_ROOT": "$ROOT_DIR"
      }
    },
    "filesystem": {
      "type": "local",
      "command": ["npx", "-y", "@modelcontextprotocol/server-filesystem", "$ROOT_DIR"]
    }
  }
}
EOF

echo "✅ opencode.json generated successfully"
echo "   Instructions: $(echo "$INSTRUCTIONS_JSON" | grep -c '"' || true) references"
echo "   Commands: $(echo "$COMMANDS_JSON" | grep -c 'template' || true) prompts"
echo "   MCP servers: context7, laravel-boost, depwire, filesystem"
echo "   Agents: code (primary), plan (primary), safe-refactor (sub), context7-research (sub)"
