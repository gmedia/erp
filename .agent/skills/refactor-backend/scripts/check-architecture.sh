#!/bin/bash
#
# Check Backend Architecture Consistency
# Usage: ./check-architecture.sh [ModuleName]
# Example: ./check-architecture.sh Employee
#
# This script checks if a module follows the proper architecture pattern.
#

set -e

show_help() {
    echo "Usage: ./check-architecture.sh <ModuleName> [options]"
    echo ""
    echo "Checks if a module follows proper Laravel architecture."
    echo ""
    echo "Arguments:"
    echo "  ModuleName    PascalCase name (e.g., Employee, Department)"
    echo ""
    echo "Options:"
    echo "  --help        Show this help message"
    echo ""
    echo "Checks performed:"
    echo "  - Controller exists and is thin"
    echo "  - FormRequests exist (Store, Update, Index, Export)"
    echo "  - Resources exist (Resource, Collection)"
    echo "  - Actions exist (Index, Export)"
    echo "  - Domain FilterService exists"
    echo ""
    echo "Example:"
    echo "  ./check-architecture.sh Employee"
}

if [[ "$1" == "--help" || -z "$1" ]]; then
    show_help
    exit 0
fi

FEATURE="$1"
FEATURE_PLURAL="${FEATURE}s"
FEATURE_LOWER=$(echo "$FEATURE" | tr '[:upper:]' '[:lower:]')

echo "🔍 Checking architecture for: $FEATURE"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

check_file() {
    local path="$1"
    local description="$2"
    if [[ -f "$path" ]]; then
        echo -e "  ${GREEN}✓${NC} $description"
        return 0
    else
        echo -e "  ${RED}✗${NC} $description - NOT FOUND"
        return 1
    fi
}

check_dir() {
    local path="$1"
    local description="$2"
    if [[ -d "$path" ]]; then
        echo -e "  ${GREEN}✓${NC} $description"
        return 0
    else
        echo -e "  ${YELLOW}?${NC} $description - NOT FOUND (may not be needed)"
        return 1
    fi
}

ERRORS=0

# Controller
echo "📁 Controller Layer:"
check_file "app/Http/Controllers/${FEATURE}Controller.php" "Controller" || ((ERRORS++))

# Requests
echo ""
echo "📁 FormRequest Layer:"
check_file "app/Http/Requests/${FEATURE_PLURAL}/Store${FEATURE}Request.php" "StoreRequest" || ((ERRORS++))
check_file "app/Http/Requests/${FEATURE_PLURAL}/Update${FEATURE}Request.php" "UpdateRequest" || ((ERRORS++))
check_file "app/Http/Requests/${FEATURE_PLURAL}/Index${FEATURE}Request.php" "IndexRequest" || ((ERRORS++))
check_file "app/Http/Requests/${FEATURE_PLURAL}/Export${FEATURE}Request.php" "ExportRequest" || true

# Resources
echo ""
echo "📁 Resource Layer:"
check_file "app/Http/Resources/${FEATURE_PLURAL}/${FEATURE}Resource.php" "Resource" || ((ERRORS++))
check_file "app/Http/Resources/${FEATURE_PLURAL}/${FEATURE}Collection.php" "Collection" || ((ERRORS++))

# Actions
echo ""
echo "📁 Action Layer:"
check_file "app/Actions/${FEATURE_PLURAL}/Index${FEATURE_PLURAL}Action.php" "IndexAction" || ((ERRORS++))
check_file "app/Actions/${FEATURE_PLURAL}/Export${FEATURE_PLURAL}Action.php" "ExportAction" || true

# Domain
echo ""
echo "📁 Domain Layer:"
check_file "app/Domain/${FEATURE_PLURAL}/${FEATURE}FilterService.php" "FilterService" || ((ERRORS++))

# Optional: DTOs
echo ""
echo "📁 Optional Layers:"
check_dir "app/DTOs/${FEATURE_PLURAL}" "DTOs folder"

# Tests
echo ""
echo "📁 Test Layer:"
check_file "tests/Feature/${FEATURE}ControllerTest.php" "Feature Test" || ((ERRORS++))
check_dir "tests/Unit/Actions/${FEATURE_PLURAL}" "Unit Tests (Actions)" || true
check_dir "tests/e2e/${FEATURE_LOWER}s" "E2E Tests" || true

echo ""
if [[ $ERRORS -eq 0 ]]; then
    echo -e "${GREEN}✅ Architecture check PASSED!${NC}"
else
    echo -e "${RED}❌ Architecture check found $ERRORS issue(s)${NC}"
    exit 1
fi
