#!/bin/bash
#
# Agent Skills Interactive Wizard
# Usage: ./wizard.sh
#
# Interactive menu untuk memilih dan menjalankan skills.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILLS_DIR="$SCRIPT_DIR"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# Print colored text
print_header() {
    echo ""
    echo -e "${BLUE}${BOLD}╔════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}${BOLD}║       🧙 Agent Skills Wizard               ║${NC}"
    echo -e "${BLUE}${BOLD}╚════════════════════════════════════════════╝${NC}"
    echo ""
}

print_menu() {
    echo -e "${CYAN}Pilih kategori:${NC}"
    echo ""
    echo -e "  ${GREEN}1)${NC} 🆕 Buat Fitur Baru (CRUD)"
    echo -e "  ${GREEN}2)${NC} 🔧 Refactor Kode Existing"
    echo -e "  ${GREEN}3)${NC} 🗄️  Database (Migration/Seeder)"
    echo -e "  ${GREEN}4)${NC} 🧪 Testing"
    echo -e "  ${GREEN}5)${NC} 📖 Baca Dokumentasi"
    echo ""
    echo -e "  ${RED}0)${NC} Exit"
    echo ""
}

print_crud_menu() {
    echo ""
    echo -e "${CYAN}Pilih tipe CRUD:${NC}"
    echo ""
    echo -e "  ${GREEN}1)${NC} Simple CRUD (1 tabel, tanpa relasi)"
    echo -e "  ${GREEN}2)${NC} Complex CRUD (dengan FK, filter kompleks)"
    echo -e "  ${GREEN}3)${NC} Non-CRUD (dashboard, settings, dll)"
    echo ""
    echo -e "  ${YELLOW}b)${NC} Kembali"
    echo ""
}

print_refactor_menu() {
    echo ""
    echo -e "${CYAN}Pilih tipe refactor:${NC}"
    echo ""
    echo -e "  ${GREEN}1)${NC} Backend (Laravel/PHP)"
    echo -e "  ${GREEN}2)${NC} Frontend (React/TypeScript)"
    echo ""
    echo -e "  ${YELLOW}b)${NC} Kembali"
    echo ""
}

print_docs_menu() {
    echo ""
    echo -e "${CYAN}Pilih dokumentasi:${NC}"
    echo ""
    echo -e "  ${GREEN}1)${NC} DECISION.md (Matrix pemilihan skill)"
    echo -e "  ${GREEN}2)${NC} README.md (Overview skills)"
    echo -e "  ${GREEN}3)${NC} feature-crud-simple"
    echo -e "  ${GREEN}4)${NC} feature-crud-complex"
    echo -e "  ${GREEN}5)${NC} feature-non-crud"
    echo -e "  ${GREEN}6)${NC} refactor-backend"
    echo -e "  ${GREEN}7)${NC} refactor-frontend"
    echo -e "  ${GREEN}8)${NC} database-migration"
    echo -e "  ${GREEN}9)${NC} testing-strategy"
    echo ""
    echo -e "  ${YELLOW}b)${NC} Kembali"
    echo ""
}

ask_feature_name() {
    echo ""
    read -p "Masukkan nama fitur (PascalCase, contoh: Category): " FEATURE_NAME
    
    if [[ -z "$FEATURE_NAME" ]]; then
        echo -e "${RED}Nama fitur tidak boleh kosong!${NC}"
        return 1
    fi
    
    # Validate PascalCase
    if [[ ! "$FEATURE_NAME" =~ ^[A-Z][a-zA-Z]*$ ]]; then
        echo -e "${YELLOW}Warning: Nama harus PascalCase (contoh: Category, Product)${NC}"
    fi
    
    echo ""
    return 0
}

run_script() {
    local script="$1"
    local feature="$2"
    local action="$3"
    
    if [[ ! -f "$script" ]]; then
        echo -e "${RED}Script tidak ditemukan: $script${NC}"
        return 1
    fi
    
    echo ""
    echo -e "${CYAN}Running: bash $script $feature $action${NC}"
    echo ""
    bash "$script" "$feature" "$action"
}

handle_crud_simple() {
    if ! ask_feature_name; then return; fi
    
    echo -e "${CYAN}Pilih action:${NC}"
    echo -e "  ${GREEN}1)${NC} Scaffold (buat struktur folder)"
    echo -e "  ${GREEN}2)${NC} Generate (buat files dari template)"
    echo -e "  ${GREEN}3)${NC} Scaffold + Generate"
    echo ""
    read -p "Pilihan: " action
    
    case $action in
        1)
            run_script "$SKILLS_DIR/feature-crud-simple/scripts/scaffold.sh" "$FEATURE_NAME" ""
            ;;
        2)
            run_script "$SKILLS_DIR/feature-crud-simple/scripts/generate.sh" "$FEATURE_NAME" "--all"
            ;;
        3)
            run_script "$SKILLS_DIR/feature-crud-simple/scripts/scaffold.sh" "$FEATURE_NAME" ""
            run_script "$SKILLS_DIR/feature-crud-simple/scripts/generate.sh" "$FEATURE_NAME" "--all"
            ;;
        *)
            echo -e "${RED}Pilihan tidak valid${NC}"
            ;;
    esac
}

handle_crud_complex() {
    if ! ask_feature_name; then return; fi
    
    echo -e "${CYAN}Pilih action:${NC}"
    echo -e "  ${GREEN}1)${NC} Scaffold (buat struktur folder)"
    echo -e "  ${GREEN}2)${NC} Generate (buat files dari template)"
    echo -e "  ${GREEN}3)${NC} Scaffold + Generate"
    echo ""
    read -p "Pilihan: " action
    
    case $action in
        1)
            run_script "$SKILLS_DIR/feature-crud-complex/scripts/scaffold.sh" "$FEATURE_NAME" ""
            ;;
        2)
            run_script "$SKILLS_DIR/feature-crud-complex/scripts/generate.sh" "$FEATURE_NAME" "--all"
            ;;
        3)
            run_script "$SKILLS_DIR/feature-crud-complex/scripts/scaffold.sh" "$FEATURE_NAME" ""
            run_script "$SKILLS_DIR/feature-crud-complex/scripts/generate.sh" "$FEATURE_NAME" "--all"
            ;;
        *)
            echo -e "${RED}Pilihan tidak valid${NC}"
            ;;
    esac
}

handle_refactor_backend() {
    if ! ask_feature_name; then return; fi
    
    echo -e "${CYAN}Pilih action:${NC}"
    echo -e "  ${GREEN}1)${NC} Check architecture"
    echo -e "  ${GREEN}2)${NC} Generate refactor files"
    echo -e "  ${GREEN}3)${NC} Check + Generate"
    echo ""
    read -p "Pilihan: " action
    
    case $action in
        1)
            run_script "$SKILLS_DIR/refactor-backend/scripts/check-architecture.sh" "$FEATURE_NAME" ""
            ;;
        2)
            run_script "$SKILLS_DIR/refactor-backend/scripts/generate.sh" "$FEATURE_NAME" "--all"
            ;;
        3)
            run_script "$SKILLS_DIR/refactor-backend/scripts/check-architecture.sh" "$FEATURE_NAME" ""
            run_script "$SKILLS_DIR/refactor-backend/scripts/generate.sh" "$FEATURE_NAME" "--all"
            ;;
        *)
            echo -e "${RED}Pilihan tidak valid${NC}"
            ;;
    esac
}

show_doc() {
    local file="$1"
    if [[ -f "$file" ]]; then
        echo ""
        echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
        cat "$file"
        echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
        echo ""
        read -p "Tekan Enter untuk kembali..."
    else
        echo -e "${RED}File tidak ditemukan: $file${NC}"
    fi
}

# Main menu loop
main_menu() {
    while true; do
        print_header
        print_menu
        read -p "Pilihan: " choice
        
        case $choice in
            1) # CRUD
                while true; do
                    print_crud_menu
                    read -p "Pilihan: " crud_choice
                    case $crud_choice in
                        1) handle_crud_simple ;;
                        2) handle_crud_complex ;;
                        3) 
                            echo -e "${YELLOW}Untuk Non-CRUD, baca SKILL.md:${NC}"
                            show_doc "$SKILLS_DIR/feature-non-crud/SKILL.md"
                            ;;
                        b|B) break ;;
                        *) echo -e "${RED}Pilihan tidak valid${NC}" ;;
                    esac
                done
                ;;
            2) # Refactor
                while true; do
                    print_refactor_menu
                    read -p "Pilihan: " refactor_choice
                    case $refactor_choice in
                        1) handle_refactor_backend ;;
                        2) 
                            echo -e "${YELLOW}Untuk Frontend refactor, baca SKILL.md:${NC}"
                            show_doc "$SKILLS_DIR/refactor-frontend/SKILL.md"
                            ;;
                        b|B) break ;;
                        *) echo -e "${RED}Pilihan tidak valid${NC}" ;;
                    esac
                done
                ;;
            3) # Database
                echo -e "${YELLOW}Database Migration Skills:${NC}"
                show_doc "$SKILLS_DIR/database-migration/SKILL.md"
                ;;
            4) # Testing
                echo -e "${YELLOW}Testing Strategy:${NC}"
                show_doc "$SKILLS_DIR/testing-strategy/SKILL.md"
                ;;
            5) # Docs
                while true; do
                    print_docs_menu
                    read -p "Pilihan: " doc_choice
                    case $doc_choice in
                        1) show_doc "$SKILLS_DIR/DECISION.md" ;;
                        2) show_doc "$SKILLS_DIR/README.md" ;;
                        3) show_doc "$SKILLS_DIR/feature-crud-simple/SKILL.md" ;;
                        4) show_doc "$SKILLS_DIR/feature-crud-complex/SKILL.md" ;;
                        5) show_doc "$SKILLS_DIR/feature-non-crud/SKILL.md" ;;
                        6) show_doc "$SKILLS_DIR/refactor-backend/SKILL.md" ;;
                        7) show_doc "$SKILLS_DIR/refactor-frontend/SKILL.md" ;;
                        8) show_doc "$SKILLS_DIR/database-migration/SKILL.md" ;;
                        9) show_doc "$SKILLS_DIR/testing-strategy/SKILL.md" ;;
                        b|B) break ;;
                        *) echo -e "${RED}Pilihan tidak valid${NC}" ;;
                    esac
                done
                ;;
            0)
                echo -e "${GREEN}Goodbye! 👋${NC}"
                exit 0
                ;;
            *)
                echo -e "${RED}Pilihan tidak valid${NC}"
                ;;
        esac
    done
}

# Run main menu
main_menu
