# 🖥️ MiniAppLogs – Laravel Log Viewer

A web application for real-time server log monitoring. Supports reading log files securely via a lightweight **HTTP Agent** or from local paths.

---

## ✨ Features

- 📄 View the **last 1000 lines** of any log file
- 🔄 **Auto-refresh** every 10 seconds or manual reload
- 🔍 **Search / filter** by keyword in the browser
- 🔌 **Lightweight HTTP Agent** – secure streaming without SSH keys
- 👥 **Role-based access control** – Admin / User
- 🌑 **Dark mode** UI with color-coded log levels
- 🌐 **Multi-language** – English & Vietnamese
- 🗄️ **SQLite by default** – no database server required

---

## 🚀 Option 1 – SQLite (recommended, simplest)

No database server needed. Data is stored in a local SQLite file inside the container volume.

### 1. Create `.env`

```env
APP_URL=http://your-server-ip:8080
```

That's it. Everything else is automatic.

### 2. Create `docker-compose.yml`

```yaml
services:
  app:
    image: trannguyenhan/miniapplogs:latest
    container_name: miniapplogs_app
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8080}:80"
    environment:
      APP_URL: ${APP_URL:-http://localhost:8080}
      APP_LOCALE: ${APP_LOCALE:-en}
    volumes:
      - app_storage:/var/www/html/storage

volumes:
  app_storage:
```

### 3. Run

```bash
docker compose up -d
docker compose logs -f app   # watch startup
```

---

## 🚀 Option 2 – MySQL

Use this if you prefer a dedicated database server or need to share the database.

### 1. Create `.env`

```env
APP_URL=http://your-server-ip:8080

DB_PASSWORD=change_me_strong_password
DB_ROOT_PASSWORD=change_me_root_password
```

### 2. Create `docker-compose.yml`

```yaml
services:
  app:
    image: trannguyenhan/miniapplogs:latest
    container_name: miniapplogs_app
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8080}:80"
    environment:
      APP_URL: ${APP_URL:-http://localhost:8080}
      APP_LOCALE: ${APP_LOCALE:-en}
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: miniapplogs
      DB_USERNAME: miniapplogs
      DB_PASSWORD: ${DB_PASSWORD}
    volumes:
      - app_storage:/var/www/html/storage
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    container_name: miniapplogs_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: miniapplogs
      MYSQL_USER: miniapplogs
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

volumes:
  app_storage:
  db_data:
```

### 3. Run

```bash
docker compose up -d
docker compose logs -f app
```

---

## 🔌 Reading Logs via HTTP Agent

MiniAppLogs connects to remote servers using a lightweight HTTP Agent. This agent allows the app to securely stream logs over your LAN without exposing SSH ports or passwords.

### 1. Install the Agent on your server

Run this one-liner command on the server where your logs are located (including the host machine running Docker):

```bash
curl -sSL https://raw.githubusercontent.com/trannguyenhan/miniapplogs/refs/heads/main/agent/install.sh | sudo bash -s -- --token your-secret-token
```

*(The agent is a pure Bash script with `socat`, extremely lightweight and uses < 1MB RAM).*

### 2. Add the Server to MiniAppLogs

Go to the **Add Server** page in the MiniAppLogs UI and enter:
- **Connection Type:** `HTTP Agent`
- **Agent URL:** `http://192.168.1.100:9876` *(replace with your server's IP)*
- **Agent Token:** `your-secret-token`

### 3. Browse Logs

When adding a Log Application, you can click the **Browse 🗂️** button to visually explore the remote server's filesystem and select your log files!

---

## 🔑 Default Credentials

| Role  | Email | Password |
|---|---|---|
| Admin | `admin@miniapplogs.local` | `Admin@123456` |
| User  | `user@miniapplogs.local`  | `User@123456`  |

> ⚠️ Change passwords immediately after first login!

---

## ⚙️ Environment Variables

| Variable | Default | Description |
|---|---|---|
| `APP_URL` | `http://localhost:8080` | Public URL of the app |
| `APP_PORT` | `8080` | Host port to expose |
| `APP_LOCALE` | `en` | UI language: `en` or `vi` |
| `DB_CONNECTION` | `sqlite` | Use `mysql` to switch to MySQL |
| `DB_PASSWORD` | — | Required only for MySQL |
| `DB_ROOT_PASSWORD` | — | Required only for MySQL |

> `APP_KEY`, database migrations, and initial seeding run **automatically** on first boot.

---

## 📦 Tags

| Tag | Description |
|---|---|
| `latest` | Latest stable build |
| `1.0.0` | First stable release |

---

## 🔗 Links

- **Source code**: [github.com/trannguyenhan/miniapplogs](https://github.com/trannguyenhan/miniapplogs)
- **Docker Hub**: [hub.docker.com/r/trannguyenhan/miniapplogs](https://hub.docker.com/r/trannguyenhan/miniapplogs)
