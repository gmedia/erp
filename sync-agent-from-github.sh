#!/bin/bash

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

set -euo pipefail

# Source of truth for agent customization is .github
SOURCE_DIR=".github"

if [[ ! -d "$SOURCE_DIR" ]]; then
    echo "Error: $SOURCE_DIR directory not found."
    exit 1
fi

if [[ $# -eq 0 ]]; then
    TARGETS=(".claude" ".agent")
else
    TARGETS=("$@")
fi

copy_if_exists() {
    local src="$1"
    local dst="$2"

    if [[ -e "$src" ]]; then
        mkdir -p "$(dirname "$dst")"
        if [[ -d "$src" ]]; then
            rm -rf "$dst"
            cp -R "$src" "$dst"
        else
            cp "$src" "$dst"
        fi
    fi
}

for target in "${TARGETS[@]}"; do
    echo "Syncing to $target ..."

    rm -rf "$target"
    mkdir -p "$target"

    # Rules/instructions mapping
    copy_if_exists "$SOURCE_DIR/copilot-instructions.md" "$target/rules/agent-rules.md"

    # Skills mapping
    copy_if_exists "$SOURCE_DIR/skills" "$target/skills"

    # Prompt/workflow mapping
    if [[ -d "$SOURCE_DIR/prompts" ]]; then
        mkdir -p "$target/workflows"

        while IFS= read -r prompt_file; do
            prompt_name="$(basename "$prompt_file")"
            workflow_name="${prompt_name%.prompt.md}.md"
            cp "$prompt_file" "$target/workflows/$workflow_name"
        done < <(find "$SOURCE_DIR/prompts" -maxdepth 1 -type f -name "*.prompt.md" | sort)
    fi

    echo "Done: $target"
done

echo "Sync completed from $SOURCE_DIR"