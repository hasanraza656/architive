-- To-do checklists inside tasks and projects. Run once in phpMyAdmin (SQL tab), or use Admin > System > "Update the database" instead.
-- Additive only (a new table, nothing existing is changed). Back up first. No foreign keys on purpose: the app cleans up its own rows.

create table if not exists `checklist_items` (`id` bigint unsigned not null auto_increment primary key, `owner_type` varchar(10) not null, `owner_id` bigint unsigned not null, `body` varchar(500) not null, `position` int unsigned not null default 0, `is_done` tinyint(1) not null default 0, `done_by` bigint unsigned null, `done_at` datetime null, `created_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `checklist_items` add index `checklist_items_done_by_index`(`done_by`);
alter table `checklist_items` add index `checklist_items_created_by_index`(`created_by`);
alter table `checklist_items` add index `checklist_items_owner_type_owner_id_position_index`(`owner_type`, `owner_id`, `position`);

SET @b = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);
INSERT INTO migrations (migration, batch) VALUES ('2026_11_16_000001_create_checklist_items', @b);
