-- Run by the migration on Supabase PostgreSQL, using the website's owner connection.
-- These functions expose only publication eligibility, never private CMS records.
create or replace function public.mobile_reader_allowed()
returns boolean language sql stable set search_path = '' as $$
    select auth.uid() is not null
      and auth.jwt()->'app_metadata'->>'provider' = 'azure'
      and lower(split_part(auth.jwt()->>'email', '@', 2)) = '__EMAIL_DOMAIN__';
$$;

create or replace function public.mobile_record_visible(kind text, record_key bigint)
returns boolean language sql stable security definer set search_path = '' as $$
    select case kind
      when 'articles' then exists(select 1 from public.articles where id = record_key and approval_status = 'approved' and content_status = 'active')
      when 'activities' then exists(select 1 from public.activities where id = record_key and approval_status = 'approved' and content_status = 'active')
      when 'achievements' then exists(select 1 from public.achievements where id = record_key and approval_status = 'approved' and content_status = 'active')
      when 'documents' then exists(select 1 from public.documents where id = record_key and approval_status = 'approved' and content_status = 'active')
      when 'liquidations' then exists(select 1 from public.liquidations where id = record_key and approval_status = 'approved' and content_status = 'active')
      when 'officers' then exists(select 1 from public.officer_terms where id = record_key)
      else false end;
$$;

revoke all on function public.mobile_reader_allowed() from public, anon;
revoke all on function public.mobile_record_visible(text, bigint) from public, anon;
grant execute on function public.mobile_reader_allowed() to authenticated;
grant execute on function public.mobile_record_visible(text, bigint) to authenticated;
alter table public.mobile_publications enable row level security;
revoke all on public.mobile_publications from anon, authenticated;
grant select on public.mobile_publications to authenticated;
create policy mobile_publications_read on public.mobile_publications for select to authenticated
using (public.mobile_reader_allowed() and public.mobile_record_visible(module, record_id));

-- A private bucket: clients can sign only files referenced by a currently visible record.
insert into storage.buckets (id, name, public)
values ('__MOBILE_BUCKET__', '__MOBILE_BUCKET__', false)
on conflict (id) do update set public = false;
create policy mobile_publication_files_read on storage.objects for select to authenticated
using (bucket_id = '__MOBILE_BUCKET__' and public.mobile_reader_allowed()
    and exists (select 1 from public.mobile_publications p
        where storage.objects.name in (p.payload->>'image_path', p.payload->>'file_path', p.payload->>'photo_path')));
