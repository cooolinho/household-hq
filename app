#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${ENV_FILE:-${SCRIPT_DIR}/.env}"
LARAVEL_ENV_FILE="${LARAVEL_ENV_FILE:-${SCRIPT_DIR}/laravel/.env}"
DOCKER_BIN="${DOCKER_BIN:-docker}"
DEFAULT_CONTAINER_NAME="personal-home-portal"
CONTAINER_WORKDIR="/var/www/html"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
RESET='\033[0m'

COMMANDS=(
    "sail|yarn build|false"
    "sail|php artisan migrate|false"
    "sail|php artisan test|false"
    "sail|php artisan optimize:clear|false"
    "sail|php artisan horizon|false"
    "sail|php artisan tinker|false"
    "sail|php artisan migrate:fresh --seed|true"
    "sail|php artisan|false"
    "sail|bash|false"
)

declare -A SHORTCUTS=(
    [migrate]="sail|php artisan migrate"
    [test]="sail|php artisan test"
    [tinker]="sail|php artisan tinker"
    [artisan]="sail|php artisan"
    [fix-permissions]="root|chmod -R 777 /var/www/html && chown -R sail:sail /var/www/html"
    [bash]="sail|bash"
    [bash-root]="root|bash"
)

read_env_value() {
    local key="$1"
    local file="$2"
    local line

    [[ -f "$file" ]] || return 1

    line="$(grep -E "^[[:space:]]*${key}=" "$file" | tail -n 1 || true)"
    [[ -n "$line" ]] || return 1

    line="${line#*=}"
    line="${line%$'\r'}"
    line="${line%\"}"
    line="${line#\"}"
    line="${line%\'}"
    line="${line#\'}"

    printf '%s\n' "$line"
}

APP_CONTAINER_NAME="$(read_env_value APP_CONTAINER_NAME "$ENV_FILE" || true)"
APP_CONTAINER_NAME="${APP_CONTAINER_NAME:-$DEFAULT_CONTAINER_NAME}"

APP_ENV_VALUE="$(read_env_value APP_ENV "$LARAVEL_ENV_FILE" || true)"
APP_ENV_VALUE="${APP_ENV_VALUE:-production}"

APP_URL="$(read_env_value APP_URL "$LARAVEL_ENV_FILE" || true)"
APP_URL="${APP_URL:-http://localhost}"

show_environment_banner() {
    if [[ "$APP_ENV_VALUE" == "production" ]]; then
        echo ""
        echo -e "${BOLD}${RED}╔══════════════════════════════════════════════════════╗${RESET}"
        echo -e "${BOLD}${RED}║   ⚠  PRODUKTIONSUMGEBUNG (APP_ENV=production)  ⚠     ║${RESET}"
        echo -e "${BOLD}${RED}║   Befehle wirken auf echte, produktive Daten!        ║${RESET}"
        echo -e "${BOLD}${RED}║   URL: ${APP_URL}${RESET}"
        echo -e "${BOLD}${RED}╚══════════════════════════════════════════════════════╝${RESET}"
        echo ""
    else
        echo ""
        echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${RESET}"
        echo -e "${BOLD}${GREEN}║   Entwicklungsumgebung (APP_ENV=${APP_ENV_VALUE})${RESET}"
        echo -e "${BOLD}${GREEN}║   URL: ${APP_URL}${RESET}"
        echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${RESET}"
        echo ""
    fi
}

usage() {
    cat <<EOF
Usage:
  $(basename "$0") <command...>
  $(basename "$0")                 # interactive menu

Examples:
  $(basename "$0") php artisan migrate
  $(basename "$0") custom "php artisan cache:clear"

Environment:
  ENV_FILE             Path to .env file (default: ${ENV_FILE:-${SCRIPT_DIR}/.env})
  DOCKER_BIN           Docker executable (default: docker)
  APP_CONTAINER_NAME   Read from .env, fallback: ${DEFAULT_CONTAINER_NAME}

EOF

    local shortcut
    local spec
    local execution_user
    local command

    printf 'Shortcuts:\n'
    while IFS= read -r shortcut; do
        spec="${SHORTCUTS[$shortcut]}"
        execution_user="${spec%%|*}"
        command="${spec#*|}"
        printf '  %s %s -> %s (%s)\n' "$(basename "$0")" "$shortcut" "$command" "$execution_user"
    done < <(printf '%s\n' "${!SHORTCUTS[@]}" | LC_ALL=C sort)
}

ensure_container_running() {
    if ! "$DOCKER_BIN" ps --format '{{.Names}}' | grep -qx "$APP_CONTAINER_NAME"; then
        echo "Container '$APP_CONTAINER_NAME' is not running." >&2
        exit 1
    fi
}

run_exec_as_sail() {
    ensure_container_running
    "$DOCKER_BIN" exec -it -u sail -w "$CONTAINER_WORKDIR" "$APP_CONTAINER_NAME" "$@"
}

run_exec_as_root() {
    ensure_container_running
    "$DOCKER_BIN" exec -it -u root -w "$CONTAINER_WORKDIR" "$APP_CONTAINER_NAME" "$@"
}

run_shell_command() {
    local command="$1"
    ensure_container_running
    "$DOCKER_BIN" exec -it -u sail -w "$CONTAINER_WORKDIR" "$APP_CONTAINER_NAME" bash -lc "$command"
}

run_shell_command_as_root() {
    local command="$1"
    ensure_container_running
    "$DOCKER_BIN" exec -it -u root -w "$CONTAINER_WORKDIR" "$APP_CONTAINER_NAME" bash -lc "$command"
}

confirm_critical_command() {
    local command="$1"
    local answer

    if [[ "$APP_ENV_VALUE" == "production" ]]; then
        echo ""
        echo -e "${BOLD}${RED}╔══════════════════════════════════════════════════════╗${RESET}"
        echo -e "${BOLD}${RED}║   ⚠  PRODUKTIONSUMGEBUNG - KRITISCHER BEFEHL  ⚠      ║${RESET}"
        echo -e "${BOLD}${RED}║   URL: ${APP_URL}${RESET}"
        echo -e "${BOLD}${RED}╚══════════════════════════════════════════════════════╝${RESET}"
        echo ""
    fi
    echo -e "${YELLOW}Kritischer Befehl:${RESET} ${command}"
    read -rp "Wirklich ausführen? [y/N]: " answer
    [[ "$answer" =~ ^[Yy]$ ]]
}

run_command_spec() {
    local spec="$1"
    shift

    if [[ "$spec" != *"|"* ]]; then
        echo "Invalid command specification: '$spec'." >&2
        return 1
    fi

    local execution_user="${spec%%|*}"
    local rest="${spec#*|}"
    local command
    local requires_confirmation="false"
    local argument

    if [[ "$rest" == *"|"* ]]; then
        command="${rest%|*}"
        requires_confirmation="${rest##*|}"
    else
        command="$rest"
    fi

    if [[ -z "$command" ]]; then
        echo "Command specification has no command: '$spec'." >&2
        return 1
    fi

    for argument in "$@"; do
        printf -v command '%s %q' "$command" "$argument"
    done

    if [[ "$requires_confirmation" == "true" ]]; then
        if ! confirm_critical_command "$command"; then
            echo "Abgebrochen."
            return 1
        fi
    fi

    if [[ "$command" == "bash" && $# -eq 0 ]]; then
        case "$execution_user" in
            sail) run_exec_as_sail bash ;;
            root) run_exec_as_root bash ;;
            *) echo "Unsupported execution user: '$execution_user'." >&2; return 1 ;;
        esac
        return
    fi

    case "$execution_user" in
        sail) run_shell_command "$command" ;;
        root) run_shell_command_as_root "$command" ;;
        *) echo "Unsupported execution user: '$execution_user'." >&2; return 1 ;;
    esac
}

prompt_custom_command() {
    local command

    read -rp "Enter command to run in container: " command
    if [[ -z "$command" ]]; then
        echo "No command entered." >&2
        exit 1
    fi

    run_shell_command "$command"
}

show_menu() {
    cat <<EOF
Available commands for container: ${APP_CONTAINER_NAME}

EOF

    local choice=1
    local spec
    local rest
    local command
    for spec in "${COMMANDS[@]}"; do
        rest="${spec#*|}"
        command="${rest%|*}"
        printf '  %d) %s\n' "$choice" "$command"
        choice=$((choice + 1))
    done

    cat <<EOF
  ${choice}) bash
  $((choice + 1))) Custom command
  0) Exit
EOF
}

run_menu_selection() {
    local choice="${1:-}"
    local command_count="${#COMMANDS[@]}"
    local bash_menu_choice=$((command_count + 1))
    local custom_menu_choice=$((bash_menu_choice + 1))

    if [[ -z "$choice" ]]; then
        read -rp "Choose [0]: " choice
    fi

    if [[ "$choice" =~ ^[0-9]+$ ]] && (( choice >= 1 && choice <= command_count )); then
        run_command_spec "${COMMANDS[$((choice - 1))]}"
        return
    fi

    case "$choice" in
        "$bash_menu_choice") run_command_spec "${SHORTCUTS[bash]}" ;;
        "$custom_menu_choice") prompt_custom_command ;;
        0|"") exit 0 ;;
        *) echo "Invalid selection." >&2; exit 1 ;;
    esac
}

main() {
    local command="${1:-}"

    show_environment_banner

    if [[ "$command" =~ ^[a-z0-9-]+$ ]] && [[ -n "${SHORTCUTS[$command]+set}" ]]; then
        shift
        run_command_spec "${SHORTCUTS[$command]}" "$@"
        return
    fi

    case "$command" in
        ""|menu)
            show_menu
            run_menu_selection "${2:-}"
            ;;
        -h|--help|help)
            usage
            ;;
        custom)
            shift
            if [[ $# -gt 0 ]]; then
                run_shell_command "$*"
            else
                prompt_custom_command
            fi
            ;;
        *)
            run_exec_as_sail "$@"
            ;;
    esac
}

main "$@"
