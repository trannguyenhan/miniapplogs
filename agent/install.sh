#!/usr/bin/env bash
# =============================================================================
# MiniAppLogs Agent — Install Script
# =============================================================================
# Cài agent bash lên server, tạo systemd service, tự start.
#
# Usage:
#   sudo ./install.sh [--token SECRET] [--port 9876]
#
# Hoặc dùng --url để tải agent từ xa:
#   curl -sSL http://YOUR_HOST/agent/install.sh | sudo bash -s -- --token abc123
# =============================================================================
set -euo pipefail

# ── Defaults ──────────────────────────────────────────────────────────────────
AGENT_PORT=9876
AGENT_TOKEN=""
AGENT_BIND="0.0.0.0"
AGENT_USER="miniapplogs-agent"
INSTALL_DIR="/opt/miniapplogs-agent"
SERVICE_NAME="miniapplogs-agent"
AGENT_SCRIPT_URL=""
# Auto-detect: dùng agent.sh cùng thư mục với install.sh
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AGENT_SCRIPT_LOCAL="${SCRIPT_DIR}/miniapplogs-agent.sh"

# ── Parse args ────────────────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
    case "$1" in
        --token)  AGENT_TOKEN="$2";       shift 2 ;;
        --port)   AGENT_PORT="$2";        shift 2 ;;
        --bind)   AGENT_BIND="$2";        shift 2 ;;
        --url)    AGENT_SCRIPT_URL="$2";  shift 2 ;;
        --local)  AGENT_SCRIPT_LOCAL="$2";shift 2 ;;
        --help|-h)
            echo "Usage: $0 [--token TOKEN] [--port 9876] [--bind 0.0.0.0]"
            exit 0 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# ── Colours ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; NC='\033[0m'; BOLD='\033[1m'
info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }

# ── Checks ────────────────────────────────────────────────────────────────────
echo -e "\n${BOLD}MiniAppLogs Agent Installer${NC}\n"

[[ $EUID -ne 0 ]] && error "Cần quyền root. Chạy: sudo $0"

command -v systemctl &>/dev/null || error "systemd không tìm thấy."

if [[ -z "$AGENT_TOKEN" ]]; then
    warn "Không có --token. Agent sẽ chạy KHÔNG xác thực (ok cho mạng LAN nội bộ)."
fi

# ── Install socat nếu chưa có ─────────────────────────────────────────────────
if ! command -v socat &>/dev/null; then
    info "Cài socat..."
    if command -v apt-get &>/dev/null; then
        apt-get install -y socat
    elif command -v yum &>/dev/null; then
        yum install -y socat
    elif command -v dnf &>/dev/null; then
        dnf install -y socat
    else
        error "Không tự cài được socat. Cài thủ công rồi chạy lại."
    fi
    ok "socat đã cài"
else
    info "socat đã có: $(socat -V 2>&1 | head -1)"
fi

# ── Create system user ────────────────────────────────────────────────────────
if ! id "$AGENT_USER" &>/dev/null; then
    info "Tạo system user: $AGENT_USER"
    useradd --system --no-create-home --shell /usr/sbin/nologin "$AGENT_USER"
    ok "User $AGENT_USER đã tạo"
else
    info "User $AGENT_USER đã tồn tại, bỏ qua"
fi

# ── Install directory ─────────────────────────────────────────────────────────
info "Tạo thư mục cài đặt: $INSTALL_DIR"
mkdir -p "$INSTALL_DIR"

# ── Copy / download agent script ─────────────────────────────────────────────
AGENT_SH="$INSTALL_DIR/miniapplogs-agent.sh"

if [[ -n "$AGENT_SCRIPT_LOCAL" && -f "$AGENT_SCRIPT_LOCAL" ]]; then
    info "Copy agent từ local: $AGENT_SCRIPT_LOCAL"
    cp "$AGENT_SCRIPT_LOCAL" "$AGENT_SH"
elif [[ -n "$AGENT_SCRIPT_URL" ]]; then
    info "Tải agent từ: $AGENT_SCRIPT_URL"
    if command -v curl &>/dev/null; then
        curl -sSL "$AGENT_SCRIPT_URL" -o "$AGENT_SH"
    elif command -v wget &>/dev/null; then
        wget -qO "$AGENT_SH" "$AGENT_SCRIPT_URL"
    else
        error "Không tìm thấy curl hoặc wget"
    fi
else
    error "Không tìm thấy agent script. Đặt miniapplogs-agent.sh cùng thư mục với install.sh, hoặc dùng --url."
fi

chmod 755 "$AGENT_SH"
chown -R "$AGENT_USER:$AGENT_USER" "$INSTALL_DIR"
ok "Agent script đã cài tại $AGENT_SH"

# ── Ghi file cấu hình ────────────────────────────────────────────────────────
CONFIG_FILE="$INSTALL_DIR/agent.conf"
info "Ghi cấu hình: $CONFIG_FILE"
cat > "$CONFIG_FILE" <<EOF
# MiniAppLogs Agent Configuration
# Chỉnh sửa rồi restart: systemctl restart $SERVICE_NAME
AGENT_PORT=$AGENT_PORT
AGENT_BIND=$AGENT_BIND
AGENT_TOKEN=$AGENT_TOKEN
EOF
chmod 600 "$CONFIG_FILE"
chown "$AGENT_USER:$AGENT_USER" "$CONFIG_FILE"
ok "Cấu hình đã ghi"

# ── Tạo systemd service ───────────────────────────────────────────────────────
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
info "Tạo systemd service: $SERVICE_FILE"
cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=MiniAppLogs HTTP Agent (bash)
After=network.target

[Service]
Type=simple
User=$AGENT_USER
Group=$AGENT_USER
WorkingDirectory=$INSTALL_DIR
EnvironmentFile=$CONFIG_FILE
ExecStart=/usr/bin/bash $AGENT_SH --port \$AGENT_PORT --bind \$AGENT_BIND
Restart=on-failure
RestartSec=5s
NoNewPrivileges=yes

[Install]
WantedBy=multi-user.target
EOF

# ── Enable & start ────────────────────────────────────────────────────────────
info "Reload systemd daemon"
systemctl daemon-reload

# Restart nếu đã chạy, enable+start nếu chưa
if systemctl is-active --quiet "$SERVICE_NAME" 2>/dev/null; then
    info "Restart $SERVICE_NAME"
    systemctl restart "$SERVICE_NAME"
else
    info "Enable & start $SERVICE_NAME"
    systemctl enable --now "$SERVICE_NAME"
fi

sleep 2

if systemctl is-active --quiet "$SERVICE_NAME"; then
    ok "Service $SERVICE_NAME đang chạy!"
else
    error "Service không start được. Kiểm tra: journalctl -u $SERVICE_NAME -n 30"
fi

# ── Done ─────────────────────────────────────────────────────────────────────
LOCAL_IP=$(hostname -I | awk '{print $1}')
echo ""
echo -e "${BOLD}════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Cài đặt hoàn tất!${NC}"
echo ""
echo -e "  Agent URL  : ${CYAN}http://${LOCAL_IP}:${AGENT_PORT}${NC}"
echo -e "  Health     : ${CYAN}curl http://${LOCAL_IP}:${AGENT_PORT}/health${NC}"
echo -e "  Token      : ${YELLOW}${AGENT_TOKEN:-'(none)'}${NC}"
echo ""
echo -e "${BOLD}Cấu hình trong MiniAppLogs → Add Server:${NC}"
echo -e "  Connection : ${CYAN}HTTP Agent${NC}"
echo -e "  Agent URL  : ${CYAN}http://${LOCAL_IP}:${AGENT_PORT}${NC}"
echo -e "  Agent Token: ${CYAN}${AGENT_TOKEN:-'(để trống)'}${NC}"
echo ""
echo -e "${YELLOW}⚠️  Nếu có firewall, mở port ${AGENT_PORT}:${NC}"
echo    "   ufw allow ${AGENT_PORT}/tcp"
echo    "   firewall-cmd --add-port=${AGENT_PORT}/tcp --permanent && firewall-cmd --reload"
echo -e "${BOLD}════════════════════════════════════════════${NC}"
