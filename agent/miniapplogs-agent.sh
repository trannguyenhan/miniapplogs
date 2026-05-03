#!/usr/bin/env bash
# =============================================================================
# MiniAppLogs Agent — Pure Bash HTTP Server
# =============================================================================
# Chỉ cần: bash 4+, socat, tail, find, stat (coreutils)
# Cài socat: apt install socat  |  yum install socat
#
# Usage:
#   ./miniapplogs-agent.sh [--port 9876] [--bind 0.0.0.0] [--token SECRET]
#
# Endpoints:
#   GET /health                        → ping
#   GET /logs?path=...&lines=1000      → N dòng cuối của file log
#   GET /list?path=/var/log            → danh sách file/thư mục
#   GET /info?path=/var/log/app.log    → thông tin file
# =============================================================================

VERSION="1.1.0"

# Đọc từ env var (EnvironmentFile của systemd) hoặc CLI args
PORT="${AGENT_PORT:-9876}"
BIND="${AGENT_BIND:-0.0.0.0}"
TOKEN="${AGENT_TOKEN:-}"

# =============================================================================
# HANDLER MODE: socat fork gọi lại script này với --handle
# Stdin/stdout là HTTP stream của connection đó
# =============================================================================
if [[ "${1:-}" == "--handle" ]]; then

handle() {
    # ── URL decode ────────────────────────────────────────────────────────────
    urldecode() {
        local s="${1//+/ }"
        printf '%b' "${s//%/\\x}"
    }

    # ── JSON escape một string (log lines có thể chứa ký tự đặc biệt) ────────
    json_str() {
        local s="$1"
        s="${s//\\/\\\\}"
        s="${s//\"/\\\"}"
        s="${s//$'\r'/}"
        s="${s//$'\n'/\\n}"
        s="${s//$'\t'/\\t}"
        printf '%s' "$s"
    }

    # ── Human-readable size không cần numfmt ──────────────────────────────────
    human_size() {
        local b="$1"
        if   (( b < 1024 ));       then printf '%dB'  "$b"
        elif (( b < 1048576 ));    then printf '%dK'  "$(( b / 1024 ))"
        elif (( b < 1073741824 )); then printf '%dM'  "$(( b / 1048576 ))"
        else                            printf '%dG'  "$(( b / 1073741824 ))"
        fi
    }

    # ── HTTP response helper ──────────────────────────────────────────────────
    respond() {
        local status="$1" body="$2"
        printf 'HTTP/1.1 %s\r\nContent-Type: application/json; charset=utf-8\r\nContent-Length: %d\r\nAccess-Control-Allow-Origin: *\r\nConnection: close\r\n\r\n%s' \
            "$status" "${#body}" "$body"
    }

    # ── Parse query string → QS_<key>=<val> ──────────────────────────────────
    parse_qs() {
        local qs="$1"
        local pair key val
        IFS='&' read -ra pairs <<< "$qs"
        for pair in "${pairs[@]}"; do
            key="${pair%%=*}"
            val="${pair#*=}"
            # chỉ set các key an toàn
            [[ "$key" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]] && \
                printf -v "QS_${key}" '%s' "$(urldecode "$val")"
        done
    }

    # ── Endpoint: /health ─────────────────────────────────────────────────────
    ep_health() {
        respond "200 OK" \
            "{\"status\":\"ok\",\"version\":\"${VERSION}\",\"agent\":\"miniapplogs\"}"
    }

    # ── Endpoint: /logs ───────────────────────────────────────────────────────
    ep_logs() {
        local path="${QS_path:-}" lines="${QS_lines:-1000}"
        path="${path/#\~/$HOME}"
        path="$(realpath -m "$path" 2>/dev/null || printf '%s' "$path")"

        [[ -z "$path"    ]] && { respond "400 Bad Request" '{"error":"Missing param: path"}'; return; }
        [[ ! -e "$path"  ]] && { respond "404 Not Found"   "{\"error\":\"Not found: $(json_str "$path")\"}"; return; }
        [[ ! -f "$path"  ]] && { respond "400 Bad Request" "{\"error\":\"Not a file: $(json_str "$path")\"}"; return; }
        [[ ! -r "$path"  ]] && { respond "403 Forbidden"   "{\"error\":\"Permission denied: $(json_str "$path")\"}"; return; }

        # Build JSON array từ tail output
        local json_arr count
        json_arr=$(tail -n "$lines" "$path" | awk '
            BEGIN { printf "[" }
            {
                if (NR > 1) printf ","
                gsub(/\\/, "\\\\"); gsub(/"/, "\\\""); gsub(/\r/, "")
                printf "\"" $0 "\""
            }
            END { printf "]" }
        ')
        count=$(tail -n "$lines" "$path" | wc -l)

        respond "200 OK" \
            "{\"success\":true,\"path\":\"$(json_str "$path")\",\"lines\":${json_arr},\"count\":${count}}"
    }

    # ── Endpoint: /docker-logs ────────────────────────────────────────────────
    ep_docker_logs() {
        local container="${QS_container:-}" lines="${QS_lines:-1000}"
        [[ -z "$container" ]] && { respond "400 Bad Request" '{"error":"Missing param: container"}'; return; }
        
        local json_arr count
        # docker logs in ra stdout va stderr, redirect vao awk
        json_arr=$(docker logs --tail "$lines" "$container" 2>&1 | awk '
            BEGIN { printf "[" }
            {
                if (NR > 1) printf ","
                gsub(/\\/, "\\\\"); gsub(/"/, "\\\""); gsub(/\r/, "")
                printf "\"" $0 "\""
            }
            END { printf "]" }
        ')
        count=$(docker logs --tail "$lines" "$container" 2>&1 | wc -l)
        
        # bat loi neu docker tra ve command not found hoac container not found
        # neu container ko ton tai, log se kha ngan 
        respond "200 OK" \
            "{\"success\":true,\"path\":\"$(json_str "$container")\",\"lines\":${json_arr},\"count\":${count}}"
    }

    # ── Endpoint: /list ───────────────────────────────────────────────────────
    ep_list() {
        local path="${QS_path:-/var/log}"
        path="${path/#\~/$HOME}"
        path="$(realpath -m "$path" 2>/dev/null || printf '%s' "$path")"

        [[ ! -e "$path"  ]] && { respond "404 Not Found"   "{\"error\":\"Not found: $(json_str "$path")\"}"; return; }
        [[ ! -d "$path"  ]] && { respond "400 Bad Request" "{\"error\":\"Not a directory: $(json_str "$path")\"}"; return; }
        [[ ! -r "$path"  ]] && { respond "403 Forbidden"   "{\"error\":\"Permission denied: $(json_str "$path")\"}"; return; }

        local entries="" first=1 parent
        parent="$(dirname "$path")"

        while IFS= read -r -d '' fp; do
            local name is_dir size size_h modified readable
            name="$(basename "$fp")"
            modified=$(stat -c '%Y' "$fp" 2>/dev/null || echo 0)
            readable=$([[ -r "$fp" ]] && echo true || echo false)

            if [[ -d "$fp" ]]; then
                is_dir=true; size=null; size_h="null"
            else
                is_dir=false
                size=$(stat -c '%s' "$fp" 2>/dev/null || echo 0)
                size_h="\"$(human_size "$size")\""
            fi

            [[ $first -eq 0 ]] && entries+=","
            entries+="{\"name\":\"$(json_str "$name")\",\"path\":\"$(json_str "$fp")\",\"is_dir\":${is_dir},\"size\":${size},\"size_human\":${size_h},\"modified\":${modified},\"readable\":${readable}}"
            first=0
        done < <(find "$path" -maxdepth 1 -mindepth 1 -print0 2>/dev/null | sort -z)

        respond "200 OK" \
            "{\"success\":true,\"path\":\"$(json_str "$path")\",\"parent\":\"$(json_str "$parent")\",\"entries\":[${entries}]}"
    }

    # ── Endpoint: /info ───────────────────────────────────────────────────────
    ep_info() {
        local path="${QS_path:-}"
        path="${path/#\~/$HOME}"
        path="$(realpath -m "$path" 2>/dev/null || printf '%s' "$path")"

        [[ -z "$path"   ]] && { respond "400 Bad Request" '{"error":"Missing param: path"}'; return; }
        [[ ! -e "$path" ]] && { respond "404 Not Found"   "{\"error\":\"Not found: $(json_str "$path")\"}"; return; }

        local is_dir size size_h modified readable
        is_dir=$([[ -d "$path" ]] && echo true || echo false)
        size=$(stat -c '%s' "$path" 2>/dev/null || echo 0)
        size_h="$(human_size "$size")"
        modified=$(stat -c '%Y' "$path" 2>/dev/null || echo 0)
        readable=$([[ -r "$path" ]] && echo true || echo false)

        respond "200 OK" \
            "{\"success\":true,\"path\":\"$(json_str "$path")\",\"is_dir\":${is_dir},\"size\":${size},\"size_human\":\"${size_h}\",\"modified\":${modified},\"readable\":${readable}}"
    }

    # ── Endpoint: /execute ────────────────────────────────────────────────────
    ep_execute() {
        local path="${QS_path:-}"
        path="${path/#\~/$HOME}"
        path="$(realpath -m "$path" 2>/dev/null || printf '%s' "$path")"

        [[ -z "$path"    ]] && { respond "400 Bad Request" '{"error":"Missing param: path"}'; return; }
        [[ ! -e "$path"  ]] && { respond "404 Not Found"   "{\"error\":\"Not found: $(json_str "$path")\"}"; return; }
        [[ ! -f "$path"  ]] && { respond "400 Bad Request" "{\"error\":\"Not a script: $(json_str "$path")\"}"; return; }
        # Cho phép chạy nếu là file thường (bash sẽ đọc), không bắt buộc +x nếu ta dùng 'bash path'
        
        local output
        output=$(setsid bash "$path" 2>&1)

        respond "200 OK" \
            "{\"success\":true,\"output\":\"$(json_str "$output")\"}"
    }

    # ── Endpoint: /run-command ────────────────────────────────────────────────
    ep_run_command() {
        local cmd="${QS_command:-}"

        [[ -z "$cmd" ]] && { respond "400 Bad Request" '{"error":"Missing param: command"}'; return; }

        local output
        output=$(setsid bash -c "$cmd" 2>&1)

        respond "200 OK" \
            "{\"success\":true,\"output\":\"$(json_str "$output")\"}"
    }

    # ── Read HTTP request ─────────────────────────────────────────────────────
    local request_line method full_path auth_header=""

    IFS= read -r request_line
    request_line="${request_line%$'\r'}"
    method="${request_line%% *}"
    full_path="${request_line#* }"; full_path="${full_path%% *}"

    # Read headers
    while IFS= read -r hdr; do
        hdr="${hdr%$'\r'}"
        [[ -z "$hdr" ]] && break
        [[ "${hdr,,}" =~ ^authorization:\ bearer\ (.+)$ ]] && auth_header="${BASH_REMATCH[1]}"
        if [[ "${hdr,,}" =~ ^content-length:\ ([0-9]+)$ ]]; then
            clen="${BASH_REMATCH[1]}"
        fi
    done

    # Parse path & query string
    local endpoint="${full_path%%[?]*}" qs=""
    [[ "$full_path" == *"?"* ]] && qs="${full_path#*[?]}"
    [[ -n "$qs" ]] && parse_qs "$qs"

    # Xử lý body nếu là POST
    if [[ "$method" == "POST" ]]; then
        # Đọc body json {"path": "..."}
        if (( clen > 0 )); then
            local body
            body="$(dd bs=1 count="$clen" 2>/dev/null)"
            # Extract path value using awk/sed
            local path_val
            path_val=$(echo "$body" | grep -o '\"path\"[[:space:]]*:[[:space:]]*\"[^\"]*\"' | head -1 | sed 's/.*"path"[[:space:]]*:[[:space:]]*"//;s/".*//')
            [[ -n "$path_val" ]] && QS_path="$path_val"            # Extract command value
            local cmd_val
            cmd_val=$(echo "$body" | grep -o '"command"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*"command"[[:space:]]*:[[:space:]]*"//;s/".*//')
            [[ -n "$cmd_val" ]] && QS_command="$cmd_val"        fi
    fi

    # Auth (trừ /health)
    if [[ "$endpoint" != "/health" && -n "$TOKEN" && "$auth_header" != "$TOKEN" ]]; then
        respond "401 Unauthorized" '{"error":"Unauthorized – invalid or missing token"}'
        exit 0
    fi

    case "$endpoint" in
        /health)      ep_health ;;
        /logs)        ep_logs ;;
        /docker-logs) ep_docker_logs ;;
        /list)        ep_list ;;
        /info)        ep_info ;;
        /execute)     ep_execute ;;
        /run-command) ep_run_command ;;
        *)            respond "404 Not Found" "{\"error\":\"Unknown: $endpoint\"}" ;;
    esac

} # end handle()

handle
exit 0

fi # end --handle mode

# =============================================================================
# SERVER MODE: parse args, start socat
# =============================================================================

while [[ $# -gt 0 ]]; do
    case "$1" in
        --port)  PORT="${2:-}";  shift 2 ;;
        --bind)  BIND="${2:-}";  shift 2 ;;
        --token) TOKEN="${2:-}"; shift 2 ;;
        --help|-h)
            echo "Usage: $0 [--port 9876] [--bind 0.0.0.0] [--token SECRET]"
            exit 0 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# Require socat
if ! command -v socat &>/dev/null; then
    echo "ERROR: socat not found."
    echo "  Ubuntu/Debian : apt install socat"
    echo "  RHEL/CentOS   : yum install socat"
    exit 1
fi

SCRIPT_PATH="$(readlink -f "$0")"
export TOKEN PORT BIND VERSION

printf '✅ MiniAppLogs Agent v%s (bash)\n' "$VERSION"
printf '   Listening : http://%s:%s\n' "$BIND" "$PORT"
printf '   Token     : %s\n' "${TOKEN:+(set)}"
printf '   Endpoints : /health  /logs  /list  /info\n'
printf '   socat     : %s\n' "$(socat -V 2>&1 | head -1)"

exec socat "TCP-LISTEN:${PORT},bind=${BIND},reuseaddr,fork" \
           "EXEC:${SCRIPT_PATH} --handle"
