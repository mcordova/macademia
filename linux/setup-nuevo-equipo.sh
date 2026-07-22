#!/usr/bin/env bash
# setup-nuevo-equipo.sh — Instala programas, zsh+OhMyZsh y dotfiles en un equipo nuevo
# Basado en el estado actual del equipo (Zorin OS / Ubuntu 24.04 noble)
# Ejecutar: sudo bash setup-nuevo-equipo.sh

set -euo pipefail

# ─── Colores y helpers ────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }

if [[ $EUID -ne 0 ]]; then
  error "Ejecuta con sudo: sudo bash $0"
  exit 1
fi

REAL_USER="${SUDO_USER:-$USER}"
REAL_HOME=$(eval echo "~$REAL_USER")
info "Usuario real: $REAL_HOME"

# ─── 1. PPAs y repos externos ────────────────────────────────────────
info "Configurando repositorios externos..."

mkdir -p /etc/apt/keyrings

# Brave Browser
if [[ ! -f /usr/share/keyrings/brave-browser-archive-keyring.gpg ]]; then
  curl -fsSL https://brave-browser-apt-release.s3.brave.com/brave-browser-archive-keyring.gpg \
    -o /usr/share/keyrings/brave-browser-archive-keyring.gpg
fi
echo "deb [signed-by=/usr/share/keyrings/brave-browser-archive-keyring.gpg arch=amd64] \
https://brave-browser-apt-release.s3.brave.com/ stable main" \
  > /etc/apt/sources.list.d/brave-browser-release.list

# Claude Desktop
if [[ ! -f /usr/share/keyrings/claude-desktop-archive-keyring.asc ]]; then
  curl -fsSL https://downloads.claude.ai/claude-desktop/apt/stable/pkgs.signing.key \
    -o /usr/share/keyrings/claude-desktop-archive-keyring.asc
fi
echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/claude-desktop-archive-keyring.asc] \
https://downloads.claude.ai/claude-desktop/apt/stable stable main" \
  > /etc/apt/sources.list.d/claude-desktop.list

# Cursor
if [[ ! -f /usr/share/keyrings/cursor-archive-keyring.gpg ]]; then
  curl -fsSL https://downloads.cursor.com/keys/GPC-signing-key.pub \
    -o /usr/share/keyrings/cursor-archive-keyring.gpg 2>/dev/null || warn "No se pudo descargar keyring de Cursor"
fi
cat > /etc/apt/sources.list.d/cursor.sources <<'EOF'
Types: deb
URIs: https://downloads.cursor.com/aptrepo
Suites: stable
Signed-By: /usr/share/keyrings/cursor-archive-keyring.gpg
EOF

# Jenkins
if [[ ! -f /etc/apt/keyrings/jenkins-keyring.asc ]]; then
  curl -fsSL https://pkg.jenkins.io/debian-stable/jenkins.io-2023.key \
    -o /etc/apt/keyrings/jenkins-keyring.asc
fi
echo "deb [signed-by=/etc/apt/keyrings/jenkins-keyring.asc] https://pkg.jenkins.io/debian-stable binary/" \
  > /etc/apt/sources.list.d/jenkins.list

# OBS Studio (PPA launchpad)
if [[ ! -f /etc/apt/sources.list.d/obsproject-ubuntu-obs-studio-noble.sources ]]; then
  add-apt-repository -y ppa:obsproject/obs-studio
fi

# Sublime Text
if [[ ! -f /etc/apt/keyrings/sublimehq-pub.asc ]]; then
  curl -fsSL https://download.sublimetext.com/sublimehq-pub.gpg \
    -o /etc/apt/keyrings/sublimehq-pub.asc
fi
cat > /etc/apt/sources.list.d/sublime-text.sources <<'EOF'
Types: deb
URIs: https://download.sublimetext.com/
Suites: apt/stable/
Signed-By: /etc/apt/keyrings/sublimehq-pub.asc
EOF

# Symfony CLI
if [[ ! -f /usr/share/keyrings/symfony-stable-archive-keyring.gpg ]]; then
  curl -fsSL "https://dl.cloudsmith.io/public/symfony/stable/gpg.E4160C5D73071D02.key" \
    -o /usr/share/keyrings/symfony-stable-archive-keyring.gpg
fi
cat > /etc/apt/sources.list.d/symfony-stable.list <<'EOF'
deb [signed-by=/usr/share/keyrings/symfony-stable-archive-keyring.gpg] https://dl.cloudsmith.io/public/symfony/stable/deb/ubuntu noble main
deb-src [signed-by=/usr/share/keyrings/symfony-stable-archive-keyring.gpg] https://dl.cloudsmith.io/public/symfony/stable/deb/ubuntu noble main
EOF

# VS Code
if [[ ! -f /etc/apt/keyrings/microsoft-prod.gpg ]]; then
  curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
    -o /etc/apt/keyrings/microsoft-prod.gpg
fi
cat > /etc/apt/sources.list.d/vscode.sources <<'EOF'
Types: deb
URIs: https://packages.microsoft.com/repos/code
Suites: stable
Signed-By: /etc/apt/keyrings/microsoft-prod.gpg
EOF

# ─── 2. Actualizar e instalar paquetes ───────────────────────────────
info "Actualizando lista de paquetes..."
apt-get update -y

info "Instalando paquetes..."

# --- Dependencias base ---
apt-get install -y \
  apt-transport-https ca-certificates curl gnupg lsb-release \
  software-properties-common

# --- Herramientas CLI ---
apt-get install -y \
  bat btop eza htop jq lnav ripgrep tree \
  strace tcpdump vim screen rsync \
  p7zip-full unzip zip pigz

# --- Desarrollo ---
apt-get install -y \
  build-essential gcc make \
  python3 python3-pip python3-venv python3-full \
  php-cli php-common php-intl php-mbstring php-mysql php-xml \
  openjdk-21-jre-headless \
  nodejs npm \
  rustc cargo

# --- Docker ---
apt-get install -y docker.io docker-compose-v2

# --- Base de datos ---
apt-get install -y postgresql postgresql-client

# --- GIS ---
apt-get install -y qgis qgis-server qgis-plugin-grass

# --- Desktop ---
apt-get install -y \
  alacritty brave-browser claude-desktop code cursor \
  gparted pdfchain psensor sublime-text virtualbox-7.2 \
  clipit guvcview obs-studio jenkins \
  symfony-cli

# --- Otros ---
apt-get install -y \
  screen hunspell-en-us hunspell-es hyphen-en-us hyphen-es \
  wamerican wspanish locales

# ─── 3. OpenCode (Desktop + CLI) ─────────────────────────────────────
info "Instalando OpenCode..."

OC_DEB="/tmp/opencode-desktop.deb"
OC_TAR="/tmp/opencode-cli.tar.gz"

# Obtener ultima version desde GitHub
OC_VERSION=$(curl -fsSL "https://api.github.com/repos/anomalyco/opencode/releases/latest" \
  | grep '"tag_name"' | head -1 | sed 's/.*"v\([^"]*\)".*/\1/')
if [[ -z "$OC_VERSION" ]]; then
  warn "No se pudo obtener la version de OpenCode — usando URL por defecto"
  OC_VERSION="latest"
fi

# --- OpenCode Desktop (.deb) ---
if ! dpkg -l opencode &>/dev/null 2>&1; then
  info "Descargando OpenCode Desktop v${OC_VERSION}..."
  curl -fsSL -o "$OC_DEB" \
    "https://github.com/anomalyco/opencode/releases/download/v${OC_VERSION}/opencode-desktop-linux-amd64.deb"
  dpkg -i "$OC_DEB" || apt-get install -f -y
  rm -f "$OC_DEB"
else
  info "OpenCode Desktop ya instalado"
fi

# --- OpenCode CLI (binario en ~/.opencode/bin/) ---
OC_CLI_DIR="$REAL_HOME/.opencode/bin"
if [[ ! -f "$OC_CLI_DIR/opencode" ]]; then
  info "Descargando OpenCode CLI v${OC_VERSION}..."
  curl -fsSL -o "$OC_TAR" \
    "https://github.com/anomalyco/opencode/releases/download/v${OC_VERSION}/opencode-linux-x64.tar.gz"
  mkdir -p "$OC_CLI_DIR"
  tar -xzf "$OC_TAR" -C "$OC_CLI_DIR"
  chmod +x "$OC_CLI_DIR/opencode"
  rm -f "$OC_TAR"
  chown -R "$REAL_USER:$REAL_USER" "$REAL_HOME/.opencode"
else
  info "OpenCode CLI ya instalado"
fi

# ─── 4. Snap packages ────────────────────────────────────────────────
if command -v snap &>/dev/null; then
  info "Instalando snaps..."
  snap install --autoclass autopsy localsend warzone2100
else
  warn "snap no disponible — omitiendo snaps (autopsy, localsend, warzone2100)"
fi

# ─── 4. Docker: agregar usuario al grupo docker ─────────────────────
if id -nG "$REAL_USER" | grep -qw docker; then
  info "Usuario $REAL_USER ya está en el grupo docker"
else
  info "Agregando $REAL_USER al grupo docker..."
  usermod -aG docker "$REAL_USER"
fi

# ─── 5. Zsh + Oh My Zsh + Powerlevel10k ─────────────────────────────
info "Configurando zsh..."

# Instalar zsh si no está
if ! command -v zsh &>/dev/null; then
  apt-get install -y zsh
fi

# Cambiar shell por defecto
CURRENT_SHELL=$(getent passwd "$REAL_USER" | cut -d: -f7)
if [[ "$CURRENT_SHELL" != *"zsh"* ]]; then
  info "Cambiando shell por defecto a zsh..."
  chsh -s "$(which zsh)" "$REAL_USER"
else
  info "zsh ya es el shell por defecto"
fi

# Instalar Oh My Zsh (si no existe)
if [[ ! -d "$REAL_HOME/.oh-my-zsh" ]]; then
  info "Instalando Oh My Zsh..."
  su - "$REAL_USER" -c 'sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)" "" --unattended'
else
  info "Oh My Zsh ya instalado"
fi

# Instalar Powerlevel10k (si no existe)
OMZ_CUSTOM="$REAL_HOME/.oh-my-zsh/custom"
if [[ ! -d "$OMZ_CUSTOM/themes/powerlevel10k" ]]; then
  info "Instalando Powerlevel10k..."
  su - "$REAL_USER" -c "git clone --depth=1 https://github.com/romkatv/powerlevel10k.git $OMZ_CUSTOM/themes/powerlevel10k"
else
  info "Powerlevel10k ya instalado"
fi

# Escribir .zshrc
info "Escribiendo .zshrc..."
su - "$REAL_USER" -c 'cat > ~/.zshrc <<'"'"'ZSHEOF'"'"'
# Powerlevel10k instant prompt
if [[ -r "${XDG_CACHE_HOME:-$HOME/.cache}/p10k-instant-prompt-${(%):-%n}.zsh" ]]; then
  source "${XDG_CACHE_HOME:-$HOME/.cache}/p10k-instant-prompt-${(%):-%n}.zsh"
fi

export PATH=$PATH:$HOME/bin:$HOME/.local/bin:/usr/local/bin

export ZSH="$HOME/.oh-my-zsh"
ZSH_THEME="powerlevel10k/powerlevel10k"
CASE_SENSITIVE="true"
COMPLETION_WAITING_DOTS="true"
plugins=(git)

source $ZSH/oh-my-zsh.sh

# User config
alias jc="jq -C | less -R"

# PATH additions (adjustar segun usuario)
export PATH="$HOME/.local/bin:$HOME/.opencode/bin:$PATH"

# Cortex CLI completion
[[ -s ~/.zsh/completions/cortex.zsh ]] && source ~/.zsh/completions/cortex.zsh

# Powerlevel10k config
[[ ! -f ~/.p10k.zsh ]] || source ~/.p10k.zsh
ZSHEOF'

# ─── 6. Dotfiles ─────────────────────────────────────────────────────
info "Configurando dotfiles..."

# .bash_aliases
su - "$REAL_USER" -c 'cat > ~/.bash_aliases <<'"'"'EOF'"'"'
alias ll='"'"'ls -alF'"'"'
alias la='"'"'ls -A'"'"'
alias l='"'"'ls -CF'"'"'
alias cat='"'"'batcat'"'"'
alias grep='"'"'rg'"'"'
EOF'

# .inputrc
su - "$REAL_USER" -c 'cat > ~/.inputrc <<'"'"'EOF'"'"'
$include /etc/inputrc

# Case-insensitive tab completion
set completion-ignore-case on
EOF'

# ─── 7. Resumen ──────────────────────────────────────────────────────
echo ""
info "=========================================="
info " Instalacion completada"
info "=========================================="
echo ""
warn "PASOS MANUALES pendientes:"
echo "  1. Ejecutar 'p10k configure' para configurar la apariencia del prompt"
echo "  2. Copiar a ~/.local/bin/ los binarios:"
echo "     - claude (CLI de Claude)"
echo "     - cortex (CLI de Cortex)"
echo "     - auto-update.zsh (script de auto-actualizacion)"
echo "  3. Copiar a ~/.zsh/completions/ la completions de cortex:"
echo "     - cortex.zsh"
echo "  4. Abrir una nueva terminal zsh para que cargue la config"
echo ""
info "Shell por defecto: zsh"
info "Tema: Powerlevel10k"
info "Plugins OMZ: git"
echo ""
