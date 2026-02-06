#!/bin/bash
#
# Generate Refactor Files for Backend Module
# Usage: ./generate.sh FeatureName [options]
# Example: ./generate.sh Employee --all
#
# This script generates proper architecture files from templates
# to help refactor existing modules.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILL_DIR="$(dirname "$SCRIPT_DIR")"
RESOURCES_DIR="$SKILL_DIR/resources"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../../.." && pwd)"

show_help() {
    echo "Usage: ./generate.sh <FeatureName> [options]"
    echo ""
    echo "Generates architecture files for refactoring a backend module."
    echo ""
    echo "Arguments:"
    echo "  FeatureName     PascalCase name (e.g., Employee, Department)"
    echo ""
    echo "Options:"
    echo "  --help          Show this help message"
    echo "  --dry-run       Show what would be created without creating"
    echo "  --all           Generate all files (Controller, Request, Resource)"
    echo "  --controller    Generate Controller template"
    echo "  --request       Generate FormRequest template"
    echo "  --resource      Generate Resource template"
    echo ""
    echo "⚠️  WARNING: This will OVERWRITE existing files!"
    echo "    Use --dry-run first to see what will be created."
    echo ""
    echo "Examples:"
    echo "  ./generate.sh Employee --dry-run"
    echo "  ./generate.sh Employee --request"
}

if [[ "$1" == "--help" || -z "$1" ]]; then
    show_help
    exit 0
fi

FEATURE="$1"
shift

# Parse options
DRY_RUN=false
GEN_ALL=false
GEN_CONTROLLER=false
GEN_REQUEST=false
GEN_RESOURCE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run) DRY_RUN=true ;;
        --all) GEN_ALL=true ;;
        --controller) GEN_CONTROLLER=true ;;
        --request) GEN_REQUEST=true ;;
        --resource) GEN_RESOURCE=true ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
    shift
done

# If --all or no specific options, generate all
if [[ "$GEN_ALL" == true ]] || [[ "$GEN_CONTROLLER" == false && "$GEN_REQUEST" == false && "$GEN_RESOURCE" == false ]]; then
    GEN_CONTROLLER=true
    GEN_REQUEST=true
    GEN_RESOURCE=true
fi

# Generate different case variations
FEATURE_LOWER=$(echo "$FEATURE" | tr '[:upper:]' '[:lower:]')

# Proper pluralization
if [[ "$FEATURE" =~ y$ ]]; then
    # Category -> Categories
    FEATURE_PLURAL="${FEATURE%y}ies"
    FEATURE_PLURAL_LOWER="${FEATURE_LOWER%y}ies"
else
    FEATURE_PLURAL="${FEATURE}s"
    FEATURE_PLURAL_LOWER="${FEATURE_LOWER}s"
fi

echo "🔧 Generating refactor files for: $FEATURE"
echo ""

# Function to replace placeholders in template
generate_file() {
    local template="$1"
    local output="$2"
    
    if [[ ! -f "$template" ]]; then
        echo "  ⚠️  Template not found: $template"
        return 1
    fi
    
    if [[ "$DRY_RUN" == true ]]; then
        echo "  📄 Would create: $output"
        return 0
    fi
    
    # Create directory if not exists
    mkdir -p "$(dirname "$output")"
    
    # Replace placeholders
    sed -e "s/{{Feature}}/$FEATURE/g" \
        -e "s/{{feature}}/$FEATURE_LOWER/g" \
        -e "s/{{Features}}/$FEATURE_PLURAL/g" \
        -e "s/{{features}}/$FEATURE_PLURAL_LOWER/g" \
        "$template" > "$output"
    
    echo "  ✅ Created: $output"
}

cd "$PROJECT_ROOT"

# Generate Controller
if [[ "$GEN_CONTROLLER" == true ]]; then
    echo "📁 Controller:"
    generate_file \
        "$RESOURCES_DIR/Controller.php.template" \
        "app/Http/Controllers/${FEATURE}Controller.php"
fi

# Generate FormRequest
if [[ "$GEN_REQUEST" == true ]]; then
    echo ""
    echo "📁 FormRequest:"
    generate_file \
        "$RESOURCES_DIR/FormRequest.php.template" \
        "app/Http/Requests/${FEATURE_PLURAL}/Store${FEATURE}Request.php"
fi

# Generate Resource
if [[ "$GEN_RESOURCE" == true ]]; then
    echo ""
    echo "📁 Resource:"
    generate_file \
        "$RESOURCES_DIR/Resource.php.template" \
        "app/Http/Resources/${FEATURE_PLURAL}/${FEATURE}Resource.php"
fi

echo ""
if [[ "$DRY_RUN" == true ]]; then
    echo "🔍 Dry run complete. No files created."
else
    echo "✅ Generation complete!"
    echo ""
    echo "⚠️  IMPORTANT: Review generated files and customize:"
    echo "   - Add specific fields to FormRequest rules"
    echo "   - Add all fields to Resource toArray()"
    echo "   - Adjust Controller imports and dependencies"
    echo ""
    echo "📝 Run architecture check:"
    echo "   bash .agent/skills/refactor-backend/scripts/check-architecture.sh $FEATURE"
fi
