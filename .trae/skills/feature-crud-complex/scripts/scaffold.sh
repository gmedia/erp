#!/bin/bash
#
# Scaffold Complex CRUD Feature
# Usage: ./scaffold.sh FeatureName
# Example: ./scaffold.sh Product
#
# This script creates the full file structure for a complex CRUD feature
# including DTOs and React components.
# Run with --help for more information.
#

set -e

show_help() {
    echo "Usage: ./scaffold.sh <FeatureName> [options]"
    echo ""
    echo "Creates file structure for a complex CRUD feature with:"
    echo "  - DTOs for data transformation"
    echo "  - React components (Form, Filters, Columns, ViewModal)"
    echo "  - Extended FilterService"
    echo ""
    echo "Arguments:"
    echo "  FeatureName    PascalCase name (e.g., Product, Order, Invoice)"
    echo ""
    echo "Options:"
    echo "  --help         Show this help message"
    echo "  --dry-run      Show what would be created without creating"
    echo ""
    echo "Example:"
    echo "  ./scaffold.sh Product"
    echo "  ./scaffold.sh Order --dry-run"
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

echo "🚀 Scaffolding Complex CRUD: $FEATURE"
echo ""
echo "Directories to create:"
echo "  - app/Actions/${FEATURE_PLURAL}/"
echo "  - app/Domain/${FEATURE_PLURAL}/"
echo "  - app/DTOs/${FEATURE_PLURAL}/"
echo "  - app/Exports/${FEATURE_PLURAL}/"
echo "  - app/Http/Requests/${FEATURE_PLURAL}/"
echo "  - app/Http/Resources/${FEATURE_PLURAL}/"
echo "  - resources/js/pages/${FEATURE_PLURAL_LOWER}/"
echo "  - resources/js/components/${FEATURE_PLURAL_LOWER}/"
echo "  - tests/Unit/Actions/${FEATURE_PLURAL}/"
echo "  - tests/Unit/Requests/${FEATURE_PLURAL}/"
echo "  - tests/Unit/Resources/${FEATURE_PLURAL}/"
echo "  - tests/e2e/${FEATURE_PLURAL_LOWER}/"
echo ""
echo "Files to create:"
echo "  - app/Models/${FEATURE}.php"
echo "  - app/Http/Controllers/${FEATURE}Controller.php"
echo "  - app/DTOs/${FEATURE_PLURAL}/Update${FEATURE}Data.php"
echo "  - routes/${FEATURE_LOWER}.php"
echo "  - tests/Feature/${FEATURE}ControllerTest.php"
echo ""
echo "React Components:"
echo "  - resources/js/components/${FEATURE_PLURAL_LOWER}/${FEATURE}Form.tsx"
echo "  - resources/js/components/${FEATURE_PLURAL_LOWER}/${FEATURE}Filters.tsx"
echo "  - resources/js/components/${FEATURE_PLURAL_LOWER}/${FEATURE}Columns.tsx"
echo "  - resources/js/components/${FEATURE_PLURAL_LOWER}/${FEATURE}ViewModal.tsx"
echo ""

if [[ "$DRY_RUN" == true ]]; then
    echo "🔍 Dry run complete. No files created."
    exit 0
fi

# Create directories
mkdir -p "app/Actions/${FEATURE_PLURAL}"
mkdir -p "app/Domain/${FEATURE_PLURAL}"
mkdir -p "app/DTOs/${FEATURE_PLURAL}"
mkdir -p "app/Exports/${FEATURE_PLURAL}"
mkdir -p "app/Http/Requests/${FEATURE_PLURAL}"
mkdir -p "app/Http/Resources/${FEATURE_PLURAL}"
mkdir -p "resources/js/pages/${FEATURE_PLURAL_LOWER}"
mkdir -p "resources/js/components/${FEATURE_PLURAL_LOWER}"
mkdir -p "tests/Unit/Actions/${FEATURE_PLURAL}"
mkdir -p "tests/Unit/Requests/${FEATURE_PLURAL}"
mkdir -p "tests/Unit/Resources/${FEATURE_PLURAL}"
mkdir -p "tests/e2e/${FEATURE_PLURAL_LOWER}"

echo "✅ Directories created!"
echo ""
echo "📝 Next steps:"
echo "1. Create model with relations: ./vendor/bin/sail artisan make:model ${FEATURE} -m -f"
echo "2. Define foreign keys in migration"
echo "3. Add belongsTo relations in model"
echo "4. Create DTO: Update${FEATURE}Data"
echo "5. Create Requests with all validation rules"
echo "6. Create Resources with relation loading"
echo "7. Create Extended FilterService with applyAdvancedFilters()"
echo "8. Create Actions (Index, Export, custom)"
echo "9. Create Controller"
echo "10. Create Routes and include in web.php"
echo "11. Create React Components (Form, Filters, Columns, ViewModal)"
echo "12. Create Frontend page"
echo "13. Create Tests"
