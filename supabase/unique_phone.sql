-- Chạy file này trong Supabase SQL Editor để mỗi số điện thoại chỉ được đăng ký 1 lần.
-- Nên chạy sau khi đã cập nhật helpers.php để số +84 được chuẩn hóa về đầu 0.

-- 1) Chuẩn hóa dữ liệu cũ: +84901234567 hoặc 84901234567 => 0901234567
update public.magic_ticket_registrations
set so_dien_thoai = case
  when so_dien_thoai like '+84%' then '0' || substring(so_dien_thoai from 4)
  when so_dien_thoai like '84%' and length(so_dien_thoai) = 11 then '0' || substring(so_dien_thoai from 3)
  else so_dien_thoai
end;

-- 2) Kiểm tra số nào đang bị trùng trước khi tạo unique index.
-- Nếu query này có kết quả, bạn cần xóa/gộp các bản ghi trùng trước.
select so_dien_thoai, count(*) as total
from public.magic_ticket_registrations
group by so_dien_thoai
having count(*) > 1;

-- 3) Sau khi chắc chắn không còn trùng, chạy lệnh unique index dưới đây.
create unique index if not exists uq_magic_ticket_phone
on public.magic_ticket_registrations (so_dien_thoai);
