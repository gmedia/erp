#!/usr/bin/env bash
# sync-opencode.sh — Generate opencode.json from the shared agent config.
#
# Source of truth (shared by OpenCode and Claude CLI):
#   AGENTS.md                 rules (OpenCode loads it natively)
#   .claude/commands/*.md     slash commands
#   .claude/agents/*.md       subagents
#   .claude/skills/*/SKILL.md skills (registered via skills.paths)
#
# Run after adding/removing a command or agent. Output: opencode.json (gitignored).
# MCP servers are intentionally not configured.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

command -v jq >/dev/null 2>&1 || { echo "jq is required" >&2; exit 1; }

echo "Generating opencode.json from AGENTS.md + .claude/ ..."

# Description from YAML frontmatter (first `description:` line, quotes stripped).
frontmatter_description() {
  sed -n '1,/^---$/{/^description:/{s/^description:[[:space:]]*//;s/^"//;s/"$//;p;q}}' "$1"
}

commands='{}'
for source in .claude/commands/*.md; do
  name="$(basename "$source" .md)"
  desc="$(frontmatter_description "$source")"
  [ -z "$desc" ] && desc="$name workflow"
  commands="$(jq --arg n "$name" --arg d "$desc" --arg t "{file:./$source}" \
    '. + {($n): {template: $t, description: $d}}' <<<"$commands")"
done

agents="$(jq -n '{
  code: {
    mode: "primary",
    prompt: "You are an expert AI coding assistant for this ERP repository. Follow AGENTS.md as the primary rule source. Keep architecture as Laravel API + React SPA (no Inertia). Use ./vendor/bin/sail for all runtime commands. MCP servers are not used; use the CLI tooling listed in AGENTS.md.",
    tools: {write: true, edit: true, bash: true}
  },
  plan: {
    mode: "primary",
    prompt: "You are a planning agent for this ERP repository. Follow AGENTS.md. Analyze requirements, propose architecture, and create implementation plans. Do NOT edit files directly.",
    tools: {write: false, edit: false, bash: false}
  }
}')"
for source in .claude/agents/*.md; do
  name="$(basename "$source" .md)"
  desc="$(frontmatter_description "$source")"
  agents="$(jq --arg n "$name" --arg d "$desc" --arg p "{file:./$source}" \
    '. + {($n): {description: $d, mode: "subagent", prompt: $p, tools: {write: true, edit: true, bash: true}}}' <<<"$agents")"
done

jq -n \
  --argjson commands "$commands" \
  --argjson agents "$agents" \
  '{
    "$schema": "https://opencode.ai/config.json",
    instructions: ["docs/development-patterns.md", "docs/module-registry.md"],
    skills: {paths: [".claude/skills"]},
    agent: $agents,
    command: $commands
  }' > opencode.json

echo "✅ opencode.json generated"
echo "   Commands: $(jq '.command | length' opencode.json)"
echo "   Agents:   $(jq -r '.agent | keys | join(", ")' opencode.json)"
echo "   Skills:   $(find .claude/skills -mindepth 2 -maxdepth 2 -name SKILL.md | wc -l) (from .claude/skills)"
