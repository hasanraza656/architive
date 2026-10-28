-- Access keys for the AI assistant (MCP). Run once in phpMyAdmin (SQL tab), or use Admin > System > "Update the database" instead.
-- Additive only. Back up first.

create table `mcp_tokens` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `name` varchar(80) not null, `token_hash` varchar(64) not null, `hint` varchar(12) not null, `last_used_at` datetime null, `revoked_at` datetime null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `mcp_tokens` add constraint `mcp_tokens_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `mcp_tokens` add unique `mcp_tokens_token_hash_unique`(`token_hash`);

SET @b = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);
INSERT INTO migrations (migration, batch) VALUES ('2026_10_27_000004_create_mcp_tokens', @b);
