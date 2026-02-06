#!/bin/bash
#
# Scaffold Simple CRUD Feature
# Usage: ./scaffold.sh FeatureName
# Example: ./scaffold.sh Category
#
# This script creates the basic file structure for a simple CRUD feature.
# Run with --help for more information.
#

set -e

show_help() {
    echo "Usage: ./scaffold.sh <FeatureName> [options]"
    echo ""
    echo "Creates file structure for a simple CRUD feature."
    echo ""
    echo "Arguments:"
    echo "  FeatureName    PascalCase name (e.g., Category, Brand, Unit)"
    echo ""
    echo "Options:"
    echo "  --help         Show this help message"
    echo "  --dry-run      Show what would be created without creating"
    echo ""
    echo "Example:"
    echo "  ./scaffold.sh Category"
    echo "  ./scaffold.sh ProductType --dry-run"
}

if [[ "$1" == "--help" || -z "$1" ]]; then
    show_help
    exit 0
fi

FEATURE="$1"
DRY_RUN=false

if [[ "$2" == "--dry-run" ]]; then
    DRY_RUN=true
fi

# Convert to different cases
FEATURE_LOWER=$(echo "$FEATURE" | tr '[:upper:]' '[:lower:]')
FEATURE_PLURAL="${FEATURE}s"
FEATURE_PLURAL_LOWER="${FEATURE_LOWER}s"

echo "🚀 Scaffolding Simple CRUD: $FEATURE"
echo ""
echo "Directories to create:"
echo "  - app/Actions/${FEATURE_PLURAL}/"
echo "  - app/Domain/${FEATURE_PLURAL}/"
echo "  - app/Exports/${FEATURE_PLURAL}/"
echo "  - app/Http/Requests/${FEATURE_PLURAL}/"
echo "  - app/Http/Resources/${FEATURE_PLURAL}/"
echo "  - resources/js/pages/${FEATURE_PLURAL_LOWER}/"
echo "  - tests/Unit/Actions/${FEATURE_PLURAL}/"
echo "  - tests/Unit/Requests/${FEATURE_PLURAL}/"
echo "  - tests/Unit/Resources/${FEATURE_PLURAL}/"
echo "  - tests/e2e/${FEATURE_PLURAL_LOWER}/"
echo ""
echo "Files to create:"
echo "  - app/Models/${FEATURE}.php"
echo "  - app/Http/Controllers/${FEATURE}Controller.php"
echo "  - routes/${FEATURE_LOWER}.php"
echo "  - tests/Feature/${FEATURE}ControllerTest.php"
echo ""

if [[ "$DRY_RUN" == true ]]; then
    echo "🔍 Dry run complete. No files created."
    exit 0
fi

# Create directories
mkdir -p "app/Actions/${FEATURE_PLURAL}"
mkdir -p "app/Domain/${FEATURE_PLURAL}"
mkdir -p "app/Exports/${FEATURE_PLURAL}"
mkdir -p "app/Http/Requests/${FEATURE_PLURAL}"
mkdir -p "app/Http/Resources/${FEATURE_PLURAL}"
mkdir -p "resources/js/pages/${FEATURE_PLURAL_LOWER}"
mkdir -p "tests/Unit/Actions/${FEATURE_PLURAL}"
mkdir -p "tests/Unit/Requests/${FEATURE_PLURAL}"
mkdir -p "tests/Unit/Resources/${FEATURE_PLURAL}"
mkdir -p "tests/e2e/${FEATURE_PLURAL_LOWER}"

echo "✅ Directories created!"
echo ""
echo "📝 Next steps:"
echo "1. Create model:     ./vendor/bin/sail artisan make:model ${FEATURE} -m -f"
echo "2. Create migration content"
echo "3. Create Requests:  Index, Store, Update, Export"
echo "4. Create Resources: ${FEATURE}Resource, ${FEATURE}Collection"
echo "5. Create Actions:   Index${FEATURE_PLURAL}Action, Export${FEATURE_PLURAL}Action"
echo "6. Create FilterService with BaseFilterService trait"
echo "7. Create Controller"
echo "8. Create Routes and include in web.php"
echo "9. Create Frontend page"
echo "10. Create Tests"
