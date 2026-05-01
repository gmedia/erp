#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Mirror Copilot prompts to Kilo workflows.
mkdir -p .kilo/commands
for source in .github/prompts/*.prompt.md; do
  target_name="$(basename "$source" .prompt.md).md"
  target_path=".kilo/commands/$target_name"
  cp "$source" "$target_path"

  # Ensure workflows run with the constrained ERP agent by default.
  if ! grep -q '^agent:' "$target_path"; then
    awk '{
      print
      if ($0 ~ /^description:/ && inserted == 0) {
        print "agent: erp-copilot"
        inserted = 1
      }
    }' "$target_path" > "$target_path.tmp"
    mv "$target_path.tmp" "$target_path"
  fi
done

# Mirror Copilot custom agents to Kilo local agents directory.
mkdir -p .kilo/agents
cp -a .github/agents/. .kilo/agents/

# Convert Copilot-style tool arrays in custom agents to the Kilo tools record format.
for target_path in .kilo/agents/*.agent.md; do
  if grep -q '^tools: \[' "$target_path"; then
    awk '
      function trim(value) {
        sub(/^[[:space:]]+/, "", value)
        sub(/[[:space:]]+$/, "", value)
        return value
      }

      /^tools: \[/ {
        line = $0
        sub(/^tools: \[/, "", line)
        sub(/\]$/, "", line)

        print "tools:"

        count = split(line, items, /,[[:space:]]*/)
        for (item_index = 1; item_index <= count; item_index++) {
          tool = trim(items[item_index])
          if (length(tool) > 0) {
            print "  \"" tool "\": true"
          }
        }

        next
      }

      { print }
    ' "$target_path" > "$target_path.tmp"
    mv "$target_path.tmp" "$target_path"
  fi
done

# Mirror Copilot skills into local Kilo skills directory.
mkdir -p .kilo/skills
cp -a .github/skills/. .kilo/skills/

echo "Kilo sync complete: commands, agents, and skills now mirror .github sources for the local Kilo workspace."
