#!/bin/bash
#
# Generate Files for Complex CRUD Feature
# Usage: ./generate.sh FeatureName [options]
# Example: ./generate.sh Product --all
#
# This script generates actual files from templates, replacing placeholders.
# For complex CRUD: includes DTOs, Extended FilterService, and React components.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILL_DIR="$(dirname "$SCRIPT_DIR")"
RESOURCES_DIR="$SKILL_DIR/resources"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../../.." && pwd)"

show_help() {
    echo "Usage: ./generate.sh <FeatureName> [options]"
    echo ""
    echo "Generates files for a complex CRUD feature from templates."
    echo ""
    echo "Arguments:"
    echo "  FeatureName     PascalCase name (e.g., Product, Order)"
    echo ""
    echo "Options:"
    echo "  --help          Show this help message"
    echo "  --dry-run       Show what would be created without creating"
    echo "  --all           Generate all files"
    echo "  --filter        Generate Extended FilterService"
    echo "  --dto           Generate DTO"
    echo "  --columns       Generate React Columns component"
    echo ""
    echo "Examples:"
    echo "  ./generate.sh Product --dry-run"
    echo "  ./generate.sh Product --all"
    echo "  ./generate.sh Product --filter --dto"
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
GEN_FILTER=false
GEN_DTO=false
GEN_COLUMNS=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run) DRY_RUN=true ;;
        --all) GEN_ALL=true ;;
        --filter) GEN_FILTER=true ;;
        --dto) GEN_DTO=true ;;
        --columns) GEN_COLUMNS=true ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
    shift
done

# If --all or no specific options, generate all
if [[ "$GEN_ALL" == true ]] || [[ "$GEN_FILTER" == false && "$GEN_DTO" == false && "$GEN_COLUMNS" == false ]]; then
    GEN_FILTER=true
    GEN_DTO=true
    GEN_COLUMNS=true
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

echo "🚀 Generating Complex CRUD files for: $FEATURE"
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

# Generate Extended FilterService
if [[ "$GEN_FILTER" == true ]]; then
    echo "📁 Domain:"
    generate_file \
        "$RESOURCES_DIR/ExtendedFilterService.php.template" \
        "app/Domain/${FEATURE_PLURAL}/${FEATURE}FilterService.php"
fi

# Generate DTO
if [[ "$GEN_DTO" == true ]]; then
    echo ""
    echo "📁 DTOs:"
    generate_file \
        "$RESOURCES_DIR/UpdateData.php.template" \
        "app/DTOs/${FEATURE_PLURAL}/Update${FEATURE}Data.php"
fi

# Generate React Columns
if [[ "$GEN_COLUMNS" == true ]]; then
    echo ""
    echo "📁 Components:"
    generate_file \
        "$RESOURCES_DIR/Columns.tsx.template" \
        "resources/js/components/${FEATURE_PLURAL_LOWER}/${FEATURE}Columns.tsx"
fi

echo ""
if [[ "$DRY_RUN" == true ]]; then
    echo "🔍 Dry run complete. No files created."
else
    echo "✅ Generation complete!"
    echo ""
    echo "📝 Next steps:"
    echo "1. Create Model with relations: ./vendor/bin/sail artisan make:model $FEATURE -mf"
    echo "2. Add foreign keys to migration"
    echo "3. Add belongsTo relations to Model"
    echo "4. Create all Requests and Resources"
    echo "5. Create Actions (Index, Export, custom)"
    echo "6. Create Controller"
    echo "7. Create remaining React components (Form, Filters, ViewModal)"
    echo "8. Create tests"
fi
