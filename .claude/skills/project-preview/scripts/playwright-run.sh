#!/usr/bin/env bash
# Runs one of the skill's Python scripts inside a temporary Playwright container (docker run --rm).
#
#   playwright-run.sh --workdir DIR [--network NET] [--add-host HOST:IP] [--env NAME] [--openai] \
#                     [--project DIR] [--image IMAGE] SCRIPT [ARGS...]
#
# Mounts:  <project> -> /project   <skill> -> /skill (ro)   <workdir> -> /work
# SCRIPT is resolved relative to <skill>/scripts. Python deps (playwright, pillow, openai) are installed once per
# Playwright version into <workdir>/.pydeps-<version>, so the playwright package always matches the image's browsers.
set -euo pipefail

SKILL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
DEFAULT_IMAGE="mcr.microsoft.com/playwright/python:v1.62.0-noble"
IMAGE="${PLAYWRIGHT_IMAGE:-}"
WORKDIR=""
DOCKER_ARGS=()
WITH_OPENAI=0

die() { echo "playwright-run: $*" >&2; exit 2; }

while [[ $# -gt 0 ]]; do
    case "$1" in
        --workdir)  WORKDIR="$2"; shift 2 ;;
        --project)  PROJECT_DIR="$2"; shift 2 ;;
        --network)  DOCKER_ARGS+=(--network "$2"); shift 2 ;;
        --add-host) DOCKER_ARGS+=(--add-host "$2"); shift 2 ;;
        --env)      DOCKER_ARGS+=(-e "$2"); shift 2 ;;  # name only, the value is taken from this environment
        --image)    IMAGE="$2"; shift 2 ;;
        --openai)   WITH_OPENAI=1; shift ;;
        --)         shift; break ;;
        -*)         die "unknown option: $1" ;;
        *)          break ;;
    esac
done

[[ $# -ge 1 ]] || die "missing script name"
[[ -n "$WORKDIR" ]] || die "--workdir is required"
SCRIPT="$1"; shift
[[ -f "$SKILL_DIR/scripts/$SCRIPT" ]] || die "unknown script: $SCRIPT"
mkdir -p "$WORKDIR"
WORKDIR="$(cd "$WORKDIR" && pwd)"
PROJECT_DIR="$(cd "$PROJECT_DIR" && pwd)"

# Prefer the newest local mcr.microsoft.com/playwright/python image, pull the default otherwise.
if [[ -z "$IMAGE" ]]; then
    IMAGE="$(docker images --format '{{.Repository}}:{{.Tag}}' mcr.microsoft.com/playwright/python 2>/dev/null \
        | grep -E ':v[0-9]+\.[0-9]+\.[0-9]+' | sort -t: -k2 -V | tail -n1 || true)"
    if [[ -z "$IMAGE" ]]; then
        IMAGE="$DEFAULT_IMAGE"
        docker pull -q "$IMAGE" >/dev/null
    fi
fi
PW_VERSION="$(sed -nE 's/.*:v([0-9]+\.[0-9]+\.[0-9]+).*/\1/p' <<<"$IMAGE")"
[[ -n "$PW_VERSION" ]] || die "cannot derive the Playwright version from image tag: $IMAGE"

# Pass the login of the demo user through by name only, if it is set.
for name in PREVIEW_LOGIN_EMAIL PREVIEW_LOGIN_PASSWORD; do
    if [[ -n "${!name:-}" ]]; then DOCKER_ARGS+=(-e "$name"); fi
done

# The OpenAI key is only resolved on request and never printed. Order: environment, then
# .claude/settings.local.json (env.OPENAI_API_KEY, then environment.variables.OPENAI_API_KEY).
if [[ "$WITH_OPENAI" == 1 ]]; then
    source_label="environment"
    if [[ -z "${OPENAI_API_KEY:-}" ]]; then
        source_label=""
        settings="$PROJECT_DIR/.claude/settings.local.json"
        if [[ -f "$settings" ]]; then
            if ! git -C "$PROJECT_DIR" check-ignore -q "$settings" 2>/dev/null; then
                echo "WARNING: .claude/settings.local.json is NOT git-ignored - the OpenAI key could be committed." >&2
            fi
            OPENAI_API_KEY="$(python3 - "$settings" <<'PY'
import json, sys
try:
    data = json.load(open(sys.argv[1]))
except Exception:
    sys.exit(0)
for path in (("env", "OPENAI_API_KEY"), ("environment", "variables", "OPENAI_API_KEY")):
    node = data
    for key in path:
        node = node.get(key) if isinstance(node, dict) else None
    if isinstance(node, str) and node.strip():
        print(node.strip())
        break
PY
)"
            if [[ -n "$OPENAI_API_KEY" ]]; then source_label=".claude/settings.local.json"; fi
        fi
    fi
    if [[ -n "${OPENAI_API_KEY:-}" ]]; then
        export OPENAI_API_KEY
        DOCKER_ARGS+=(-e OPENAI_API_KEY)
        if [[ -n "${OPENAI_IMAGE_MODEL:-}" ]]; then DOCKER_ARGS+=(-e OPENAI_IMAGE_MODEL); fi
        echo "OPENAI key: found ($source_label)" >&2
    else
        echo "OPENAI key: not found" >&2
    fi
fi

exec docker run --rm --init --ipc=host \
    --user "$(id -u):$(id -g)" \
    -e HOME=/tmp \
    -e PW_VERSION="$PW_VERSION" \
    -e PYTHONDONTWRITEBYTECODE=1 \
    -v "$PROJECT_DIR:/project" \
    -v "$SKILL_DIR:/skill:ro" \
    -v "$WORKDIR:/work" \
    -w /work \
    "${DOCKER_ARGS[@]}" \
    "$IMAGE" \
    sh -c '
        set -e
        DEPS="/work/.pydeps-$PW_VERSION"
        if ! PYTHONPATH="$DEPS" python3 -c "import playwright, PIL, openai" 2>/dev/null; then
            echo "installing playwright==$PW_VERSION pillow openai ..." >&2
            pip install -q --disable-pip-version-check --target "$DEPS" "playwright==$PW_VERSION" pillow openai >&2
        fi
        export PYTHONPATH="$DEPS"
        script="$1"; shift
        exec python3 "/skill/scripts/$script" "$@"
    ' sh "$SCRIPT" "$@"
