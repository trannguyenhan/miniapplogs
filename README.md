# MiniAppLogs - Hệ thống xem log server

Ứng dụng Laravel để xem log realtime từ nhiều server kết nối qua SSH (private IP).

## Tính năng

- 🔐 **Phân quyền**: Admin (cấu hình) & User (chỉ xem)
- 🖥️ **Quản lý server**: Thêm/sửa/xóa server với thông tin SSH
- 📋 **Quản lý ứng dụng**: Khai báo tên app và path log
- 📄 **Xem log**: 1000 dòng cuối, nút Reload + Auto-refresh 10s
- 🔍 **Tìm kiếm**: Filter theo từ khóa trong log
- 🎨 **Dark Theme**: UI hiện đại, color-coded theo log level

---

## 🐳 Docker – Build & Push lên Docker Hub

> Thực hiện trên máy dev hoặc CI/CD. Server deploy **không cần** source code.

### Yêu cầu
- Docker >= 24 (có sẵn `buildx`)
- Đã đăng nhập Docker Hub: `docker login`

---

### 1. Đặt biến môi trường

```bash
# Tên image trên Docker Hub (đổi thành username của bạn)
export IMAGE=trannguyenhan/miniapplogs

# Tag phiên bản
export VERSION=1.0.0
```

---

### 2. Build image

```bash
docker build \
  --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
  --build-arg GIT_COMMIT=$(git rev-parse --short HEAD) \
  --build-arg VERSION=${VERSION} \
  -t ${IMAGE}:${VERSION} \
  -t ${IMAGE}:latest \
  .
```

> **Multi-platform** (build cho cả `linux/amd64` và `linux/arm64`):
>
> ```bash
> docker buildx build \
>   --platform linux/amd64,linux/arm64 \
>   --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
>   --build-arg GIT_COMMIT=$(git rev-parse --short HEAD) \
>   --build-arg VERSION=${VERSION} \
>   -t ${IMAGE}:${VERSION} \
>   -t ${IMAGE}:latest \
>   --push \
>   .
> ```
> *(Lệnh này build + push một bước, không cần bước 3)*

---

### 3. Push lên Docker Hub

```bash
docker push ${IMAGE}:${VERSION}
docker push ${IMAGE}:latest
```

---

### 4. Kiểm tra image đã push

```bash
docker pull ${IMAGE}:latest
docker inspect ${IMAGE}:latest | grep -A 10 '"Labels"'
```

---

## 🚀 Deploy bằng Docker Compose

> Trên server chỉ cần 2 file: `docker-compose.yml` và `.env`

### 1. Chuẩn bị file `.env`

```bash
cp .env.docker .env
# Chỉnh sửa các biến: APP_KEY, DB_PASSWORD, APP_URL, IMAGE, VERSION...
```

Các biến quan trọng trong `.env`:

```env
DOCKER_IMAGE=trannguyenhan/miniapplogs
DOCKER_TAG=latest          # hoặc 1.0.0

APP_KEY=base64:...         # Lấy bằng: php artisan key:generate --show
APP_URL=http://your-domain.com
APP_PORT=8080

DB_DATABASE=miniapplogs
DB_USERNAME=miniapplogs
DB_PASSWORD=strong_password_here
DB_ROOT_PASSWORD=strong_root_password
```

### 2. Pull image & chạy

```bash
docker compose pull        # pull image mới nhất từ Docker Hub
docker compose up -d       # chạy nền
docker compose logs -f app # xem logs
```

### 3. Các lệnh hữu ích

```bash
# Xem trạng thái
docker compose ps

# Chạy artisan command
docker compose exec app php artisan migrate --status
docker compose exec app php artisan db:seed

# Restart
docker compose restart app

# Dừng và xóa container (giữ data)
docker compose down

# Dừng và xóa cả data (cẩn thận!)
docker compose down -v
```

---

## 💻 Cài đặt thủ công (không dùng Docker)

### Yêu cầu

- PHP >= 8.2 với extensions: `pdo_mysql`, `openssl`, `bcmath`, `gd`, `zip`
- MySQL >= 8.0 / MariaDB
- Composer, Node.js >= 18

### 1. Cấu hình database trong `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miniapplogs
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 2. Tạo database

```bash
mysql -u root -p -e "CREATE DATABASE miniapplogs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Cài đặt dependencies

```bash
composer install
npm install && npm run build
php artisan key:generate
```

### 4. Chạy migration + seed

```bash
php artisan migrate --seed
```

### 5. Khởi động

```bash
php artisan serve
```

Truy cập: http://localhost:8000

---

## Tài khoản mặc định

| Role  | Email                   | Mật khẩu     |
|-------|-------------------------|--------------|
| Admin | admin@miniapplogs.local | Admin@123456 |
| User  | user@miniapplogs.local  | User@123456  |

---

## Hướng dẫn sử dụng

### Thêm Server (Admin)
1. Vào **Admin > Quản lý Server** → **Thêm server**
2. Điền tên, IP private, SSH port, user
3. Chọn xác thực: Password SSH hoặc Private Key

### Thêm Log Application (Admin)
1. Vào **Admin > Quản lý Log App** → **Thêm ứng dụng**
2. Chọn server, điền tên và **đường dẫn tuyệt đối** tới file log
   - `/var/log/nginx/access.log`
   - `/home/app/storage/logs/laravel.log`

### Xem Log
1. Trang chủ → Chọn ứng dụng
2. Click **Reload** để tải log mới nhất
3. Click badge **Auto-refresh** để bật tự động reload (10s)
4. Dùng thanh tìm kiếm để lọc theo từ khóa
