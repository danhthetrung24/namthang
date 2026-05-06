# NAM THẮNG TRAVEL BUS - PHP + Supabase

Bản này dùng PHP thuần, chạy được trên VS Code, XAMPP, Laragon, MAMP hoặc hosting PHP có cURL.

## 1. Cấu trúc

- `index.php`: landing page.
- `config.php`: chỉnh nội dung, hotline, link Zalo/Messenger, link ảnh.
- `submit.php`: nhận form, upload ảnh lên Supabase Storage, lưu dữ liệu vào Supabase Database.
- `image.php`: proxy/cache ảnh Google Drive để giảm lỗi hotlink ảnh.
- `assets/styles.css`: giao diện.
- `assets/app.js`: xử lý popup, upload ảnh, submit form.
- `supabase/schema.sql`: tạo bảng và bucket.

## 2. Tạo database Supabase

Vào Supabase > SQL Editor, chạy file:

```sql
supabase/schema.sql
```

Sau đó vào Storage kiểm tra bucket `magic-ticket-images` đã được tạo.

## 3. Cấu hình môi trường

Copy file mẫu:

```bash
cp .env.example .env
```

Điền thông tin:

```env
SUPABASE_URL=https://YOUR_PROJECT_ID.supabase.co
SUPABASE_SERVICE_ROLE_KEY=YOUR_SERVICE_ROLE_KEY
SUPABASE_STORAGE_BUCKET=magic-ticket-images
```

`SERVICE_ROLE_KEY` chỉ nằm trên server PHP, không đưa lên frontend hoặc GitHub public.

## 4. Chạy local bằng PHP built-in server

```bash
php -S localhost:8000
```

Mở:

```text
http://localhost:8000
```

## 5. Chạy bằng XAMPP/Laragon

Đưa toàn bộ thư mục vào `htdocs` hoặc `www`, sau đó mở URL tương ứng.

## 6. Sửa hình ảnh

Mở `config.php`, chỉnh các link trong `assets`. Link Google Drive vẫn dùng được, nhưng cần bật quyền xem: Anyone with the link / Viewer.

`image.php` sẽ cố tải và cache ảnh từ Drive trong `storage/cache`.

## 7. Lưu ý deploy hosting

- Hosting cần bật extension `curl`.
- Thư mục `storage/cache` cần quyền ghi.
- Không upload `.env` lên repo public.
