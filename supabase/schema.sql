-- Run in Supabase SQL Editor
create extension if not exists "pgcrypto";

create table if not exists public.magic_ticket_registrations (
  id uuid primary key default gen_random_uuid(),
  created_at timestamptz not null default now(),
  ho_ten text not null,
  so_dien_thoai text not null,
  link_checkin text not null,
  platform text,
  user_agent text,
  so_anh integer not null default 0,
  ten_file_anh text[] not null default '{}',
  link_anh text[] not null default '{}',
  status text not null default 'new'
);

create index if not exists idx_magic_ticket_created_at on public.magic_ticket_registrations(created_at desc);
create index if not exists idx_magic_ticket_phone on public.magic_ticket_registrations(so_dien_thoai);

alter table public.magic_ticket_registrations enable row level security;

-- Nếu dùng PHP với SERVICE_ROLE_KEY thì RLS vẫn an toàn, service role bypass RLS.
-- Policy dưới đây chỉ cần khi bạn muốn đọc dữ liệu bằng anon key, mặc định không bật select public.

insert into storage.buckets (id, name, public)
values ('magic-ticket-images', 'magic-ticket-images', true)
on conflict (id) do update set public = true;

-- Chỉ cần public read cho ảnh đã upload để đội vận hành mở link_anh.
drop policy if exists "public_read_magic_ticket_images" on storage.objects;
create policy "public_read_magic_ticket_images"
on storage.objects
for select
to anon
using (bucket_id = 'magic-ticket-images');
