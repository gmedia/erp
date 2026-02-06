#!/bin/bash
#
# Generate Files for Simple CRUD Feature
# Usage: ./generate.sh FeatureName [options]
# Example: ./generate.sh Category --all
#
# This script generates actual files from templates, replacing placeholders.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILL_DIR="$(dirname "$SCRIPT_DIR")"
RESOURCES_DIR="$SKILL_DIR/resources"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../../.." && pwd)"

show_help() {
    echo "Usage: ./generate.sh <FeatureName> [options]"
    echo ""
    echo "Generates files for a simple CRUD feature from templates."
    echo ""
    echo "Arguments:"
    echo "  FeatureName     PascalCase name (e.g., Category, Brand)"
    echo ""
    echo "Options:"
    echo "  --help          Show this help message"
    echo "  --dry-run       Show what would be created without creating"
    echo "  --all           Generate all files"
    echo "  --action        Generate IndexAction only"
    echo "  --filter        Generate FilterService only"
    echo "  --routes        Generate routes file only"
    echo ""
    echo "Examples:"
    echo "  ./generate.sh Category --dry-run"
    echo "  ./generate.sh Category --all"
    echo "  ./generate.sh Category --action --filter"
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
GEN_ACTION=false
GEN_FILTER=false
GEN_ROUTES=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run) DRY_RUN=true ;;
        --all) GEN_ALL=true ;;
        --action) GEN_ACTION=true ;;
        --filter) GEN_FILTER=true ;;
        --routes) GEN_ROUTES=true ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
    shift
done

# If --all or no specific options, generate all
if [[ "$GEN_ALL" == true ]] || [[ "$GEN_ACTION" == false && "$GEN_FILTER" == false && "$GEN_ROUTES" == false ]]; then
    GEN_ACTION=true
    GEN_FILTER=true
    GEN_ROUTES=true
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

echo "🚀 Generating files for: $FEATURE"
echo ""

# Function to replace placeholders in template
generate_file() {
    local template="$1"
    local output="$2"
    local description="$3"
    
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

# Generate IndexAction
if [[ "$GEN_ACTION" == true ]]; then
    echo "📁 Actions:"
    generate_file \
        "$RESOURCES_DIR/IndexAction.php.template" \
        "app/Actions/${FEATURE_PLURAL}/Index${FEATURE_PLURAL}Action.php" \
        "Index Action"
fi

# Generate FilterService
if [[ "$GEN_FILTER" == true ]]; then
    echo ""
    echo "📁 Domain:"
    generate_file \
        "$RESOURCES_DIR/FilterService.php.template" \
        "app/Domain/${FEATURE_PLURAL}/${FEATURE}FilterService.php" \
        "Filter Service"
fi

# Generate Routes
if [[ "$GEN_ROUTES" == true ]]; then
    echo ""
    echo "📁 Routes:"
    generate_file \
        "$RESOURCES_DIR/routes.php.template" \
        "routes/${FEATURE_LOWER}.php" \
        "Routes"
fi

echo ""
if [[ "$DRY_RUN" == true ]]; then
    echo "🔍 Dry run complete. No files created."
else
    echo "✅ Generation complete!"
    echo ""
    echo "📝 Next steps:"
    echo "1. Create Model: ./vendor/bin/sail artisan make:model $FEATURE -mf"
    echo "2. Create Requests: Store, Update, Index, Export"
    echo "3. Create Resources: ${FEATURE}Resource, ${FEATURE}Collection"
    echo "4. Create Controller: ${FEATURE}Controller"
    echo "5. Include route in routes/web.php"
    echo "6. Create tests"
fi
