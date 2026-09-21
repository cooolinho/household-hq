#!/usr/bin/env bash
# supervisor.sh — Interactive Supervisor process manager for the laravel container.

set -euo pipefail

# ─── Configuration ────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/.env"
CONF_PATH="${SCRIPT_DIR}/docker/development/supervisord.conf"

CONTAINER="household-hq"

# ─── Colors ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

# ─── Helpers ──────────────────────────────────────────────────────────────────
header() {
    echo ""
    echo -e "${BOLD}${CYAN}╔══════════════════════════════════════╗${RESET}"
    echo -e "${BOLD}${CYAN}║     Supervisor Process Manager       ║${RESET}"
    echo -e "${BOLD}${CYAN}╚══════════════════════════════════════╝${RESET}"
    echo ""
}

info()    { echo -e "${CYAN}  →${RESET} $*"; }
success() { echo -e "${GREEN}  ✔${RESET} $*"; }
warn()    { echo -e "${YELLOW}  ⚠${RESET} $*"; }
error()   { echo -e "${RED}  ✘${RESET} $*"; }

supervisorctl_exec() {
    docker exec -it "$CONTAINER" supervisorctl "$@"
}

# ─── Read program names from supervisord.conf ─────────────────────────────────
read_programs() {
    grep '^\[program:' "$CONF_PATH" | sed 's/\[program:\(.*\)\]/\1/'
}

# Build process name as supervisord expects it (with _00 suffix if process_name uses process_num)
full_process_name() {
    local program="$1"
    # Check if this program block has a process_name containing process_num
    local has_num
    has_num=$(awk "/^\[program:${program}\]/{found=1} found && /^process_name=.*process_num/{print; exit} found && /^\[/{if(!/^\[program:${program}\]/) exit}" "$CONF_PATH")

    if [[ -n "$has_num" ]]; then
        echo "${program}:${program}_00"
    else
        echo "$program"
    fi
}

# ─── Action: status ──────────────────────────────────────────────────────────
action_status() {
    echo ""
    info "Fetching status of all processes..."
    echo ""
    supervisorctl_exec status
    echo ""
}

# ─── Action: select process ───────────────────────────────────────────────────
select_process() {
    local action="$1"
    local programs=()
    while IFS= read -r prog; do programs+=("$prog"); done <<< "$(read_programs)"

    echo ""
    echo -e "${BOLD}  Select a process to ${action}:${RESET}"
    echo ""
    echo -e "  ${YELLOW}0)${RESET} all"
    local i=1
    for prog in "${programs[@]}"; do
        echo -e "  ${YELLOW}${i})${RESET} ${prog}"
        ((i++))
    done
    echo ""

    local choice
    read -rp "  Enter number: " choice

    if [[ "$choice" == "0" ]]; then
        echo ""
        info "${action^} all processes..."
        supervisorctl_exec "$action" all
        success "Done."
        return
    fi

    local index=$(( choice - 1 ))
    if [[ "$index" -lt 0 || "$index" -ge "${#programs[@]}" ]]; then
        error "Invalid selection."
        return 1
    fi

    local selected="${programs[$index]}"
    local full_name
    full_name=$(full_process_name "$selected")

    echo ""
    info "${action^} ${BOLD}${selected}${RESET}..."
    supervisorctl_exec "$action" "$full_name"
    success "Done."
}

# ─── Action: reload config ───────────────────────────────────────────────────
action_reload() {
    echo ""
    info "Reloading supervisord configuration..."
    supervisorctl_exec reread
    supervisorctl_exec update
    success "Configuration reloaded."
    echo ""
}

# ─── Action: tail log ────────────────────────────────────────────────────────
read_supervisord_logfile() {
    grep '^logfile=' "$CONF_PATH" | head -1 | sed 's/logfile=//'
}

action_tail_log() {
    local programs=()
    while IFS= read -r prog; do programs+=("$prog"); done <<< "$(read_programs)"

    local supervisord_log
    supervisord_log=$(read_supervisord_logfile)

    echo ""
    echo -e "${BOLD}  Select a process to tail logs for:${RESET}"
    echo ""

    local i=1
    for prog in "${programs[@]}"; do
        echo -e "  ${YELLOW}${i})${RESET} ${prog}"
        ((i++))
    done
    echo -e "  ${YELLOW}${i})${RESET} supervisord  ${CYAN}(${supervisord_log})${RESET}"
    echo ""

    local choice
    read -rp "  Enter number: " choice

    # supervisord main log selected
    if [[ "$choice" -eq "$i" ]]; then
        info "Tailing ${supervisord_log} (Ctrl+C to exit)..."
        echo ""
        docker exec -it "$CONTAINER" tail -f "$supervisord_log"
        return
    fi

    local index=$(( choice - 1 ))
    if [[ "$index" -lt 0 || "$index" -ge "${#programs[@]}" ]]; then
        error "Invalid selection."
        return 1
    fi

    local selected="${programs[$index]}"

    echo ""
    echo -e "${BOLD}  Log type:${RESET}"
    echo -e "  ${YELLOW}1)${RESET} stdout"
    echo -e "  ${YELLOW}2)${RESET} stderr"
    echo ""
    read -rp "  Enter number [1]: " log_choice
    log_choice="${log_choice:-1}"

    local log_type="out"
    [[ "$log_choice" == "2" ]] && log_type="err"

    local log_path="/var/log/supervisor/${selected}.${log_type}.log"

    info "Tailing ${log_path} (Ctrl+C to exit)..."
    echo ""
    docker exec -it "$CONTAINER" tail -f "$log_path"
}

# ─── Main menu ───────────────────────────────────────────────────────────────
main() {
    header

    # Verify container is running
    if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
        error "Container '${CONTAINER}' is not running."
        exit 1
    fi

    echo -e "${BOLD}  What would you like to do?${RESET}"
    echo ""
    echo -e "  ${YELLOW}1)${RESET} Show status of all processes"
    echo -e "  ${YELLOW}2)${RESET} Restart a process"
    echo -e "  ${YELLOW}3)${RESET} Stop a process"
    echo -e "  ${YELLOW}4)${RESET} Start a process"
    echo -e "  ${YELLOW}5)${RESET} Reload configuration"
    echo -e "  ${YELLOW}6)${RESET} Tail process logs"
    echo -e "  ${YELLOW}0)${RESET} Exit"
    echo ""

    local action_choice
    read -rp "  Enter number: " action_choice

    case "$action_choice" in
        1) action_status ;;
        2) select_process "restart" ;;
        3) select_process "stop" ;;
        4) select_process "start" ;;
        5) action_reload ;;
        6) action_tail_log ;;
        0) echo ""; info "Bye."; echo ""; exit 0 ;;
        *) error "Invalid option."; exit 1 ;;
    esac
}

main
