-- Team roles, projects/tasks and chat upgrades. Run once in phpMyAdmin (SQL tab) on the live database. Additive only: no existing rows are changed.
-- Back up first. Run it exactly once (running twice fails safely with 'already exists').

create table `roles` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(80) not null, `slug` varchar(100) not null, `description` varchar(255) null, `color` varchar(9) not null default '#FFD60A', `permissions` text null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `roles` add unique `roles_slug_unique`(`slug`);
alter table `users` add `role_id` bigint unsigned null after `role`, add `invited_at` datetime null after `last_seen_at`;
alter table `users` add constraint `users_role_id_foreign` foreign key (`role_id`) references `roles` (`id`) on delete set null;
create table `order_chat_members` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `user_id` bigint unsigned not null, `added_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `order_chat_members` add constraint `order_chat_members_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;
alter table `order_chat_members` add constraint `order_chat_members_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `order_chat_members` add constraint `order_chat_members_added_by_foreign` foreign key (`added_by`) references `users` (`id`) on delete set null;
alter table `order_chat_members` add unique `order_chat_members_order_id_user_id_unique`(`order_id`, `user_id`);
insert into `roles` (`name`, `slug`, `description`, `color`, `permissions`, `created_at`, `updated_at`) values ('Manager', 'manager', 'Runs projects and customer work day to day.', '#6D28D9', '[\"dashboard.view\",\"orders.view\",\"orders.create\",\"orders.edit\",\"orders.send\",\"orders.deliver\",\"orders.complete\",\"orders.cancel\",\"chat.access_all\",\"chat.manage_members\",\"inbox.view\",\"customers.view\",\"customers.create\",\"customers.edit\",\"projects.view\",\"projects.create\",\"projects.edit\",\"tasks.view\",\"tasks.create\",\"tasks.edit\",\"tasks.delete\",\"team.view\"]', '2026-10-27 10:12:17', '2026-10-27 10:12:17');
insert into `roles` (`name`, `slug`, `description`, `color`, `permissions`, `created_at`, `updated_at`) values ('Customer support', 'support', 'Answers customers and keeps orders moving.', '#0E7490', '[\"dashboard.view\",\"orders.view\",\"chat.access_all\",\"inbox.view\",\"customers.view\",\"tasks.create\"]', '2026-10-27 10:12:17', '2026-10-27 10:12:17');
insert into `roles` (`name`, `slug`, `description`, `color`, `permissions`, `created_at`, `updated_at`) values ('Designer / team member', 'designer', 'Works on assigned tasks and in the chats they are added to.', '#B45309', '[\"tasks.create\"]', '2026-10-27 10:12:17', '2026-10-27 10:12:17');
create table `projects` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(160) not null, `description` text null, `status` varchar(20) not null default 'active', `color` varchar(9) not null default '#FFD60A', `start_date` date null, `due_date` date null, `customer_id` bigint unsigned null, `order_id` bigint unsigned null, `visible_to_customer` tinyint(1) not null default '0', `created_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `projects` add constraint `projects_customer_id_foreign` foreign key (`customer_id`) references `users` (`id`) on delete set null;
alter table `projects` add constraint `projects_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete set null;
alter table `projects` add constraint `projects_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete set null;
alter table `projects` add index `projects_status_index`(`status`);
alter table `projects` add index `projects_due_date_index`(`due_date`);
create table `project_members` (`project_id` bigint unsigned not null, `user_id` bigint unsigned not null, `role` varchar(10) not null default 'member', `created_at` timestamp null, `updated_at` timestamp null, primary key (`project_id`, `user_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `project_members` add constraint `project_members_project_id_foreign` foreign key (`project_id`) references `projects` (`id`) on delete cascade;
alter table `project_members` add constraint `project_members_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
create table `tasks` (`id` bigint unsigned not null auto_increment primary key, `project_id` bigint unsigned not null, `title` varchar(200) not null, `description` text null, `status` varchar(20) not null default 'todo', `priority` varchar(10) not null default 'normal', `due_date` date null, `position` int unsigned not null default '0', `created_by` bigint unsigned null, `completed_at` datetime null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `tasks` add constraint `tasks_project_id_foreign` foreign key (`project_id`) references `projects` (`id`) on delete cascade;
alter table `tasks` add constraint `tasks_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete set null;
alter table `tasks` add index `tasks_status_index`(`status`);
alter table `tasks` add index `tasks_due_date_index`(`due_date`);
create table `task_assignees` (`task_id` bigint unsigned not null, `user_id` bigint unsigned not null, `created_at` timestamp null, `updated_at` timestamp null, primary key (`task_id`, `user_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `task_assignees` add constraint `task_assignees_task_id_foreign` foreign key (`task_id`) references `tasks` (`id`) on delete cascade;
alter table `task_assignees` add constraint `task_assignees_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
create table `task_comments` (`id` bigint unsigned not null auto_increment primary key, `task_id` bigint unsigned not null, `user_id` bigint unsigned null, `kind` varchar(10) not null default 'comment', `body` text not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `task_comments` add constraint `task_comments_task_id_foreign` foreign key (`task_id`) references `tasks` (`id`) on delete cascade;
alter table `task_comments` add constraint `task_comments_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;
alter table `order_messages` add `thread_root_id` bigint unsigned null after `user_id`, add `edited_at` datetime null after `body`;
alter table `order_messages` add constraint `order_messages_thread_root_id_foreign` foreign key (`thread_root_id`) references `order_messages` (`id`) on delete cascade;
alter table `order_files` add `kind` varchar(12) not null default 'file' after `mime`, add `duration` int unsigned null after `kind`, add `width` int unsigned null after `duration`, add `height` int unsigned null after `width`, add `thumb_path` varchar(255) null after `path`, add `meta` text null after `size`, add `annotated_of` bigint unsigned null after `meta`;
create table `order_message_reactions` (`id` bigint unsigned not null auto_increment primary key, `message_id` bigint unsigned not null, `user_id` bigint unsigned not null, `emoji` varchar(16) not null, `created_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `order_message_reactions` add constraint `order_message_reactions_message_id_foreign` foreign key (`message_id`) references `order_messages` (`id`) on delete cascade;
alter table `order_message_reactions` add constraint `order_message_reactions_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `order_message_reactions` add unique `order_message_reactions_message_id_user_id_emoji_unique`(`message_id`, `user_id`, `emoji`);
create table `order_message_mentions` (`id` bigint unsigned not null auto_increment primary key, `message_id` bigint unsigned not null, `type` varchar(8) not null, `target_id` bigint unsigned not null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `order_message_mentions` add constraint `order_message_mentions_message_id_foreign` foreign key (`message_id`) references `order_messages` (`id`) on delete cascade;
alter table `order_message_mentions` add index `order_message_mentions_type_target_id_index`(`type`, `target_id`);
alter table `order_message_mentions` add index `order_message_mentions_message_id_index`(`message_id`);

SET @b = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);
INSERT INTO migrations (migration, batch) VALUES ('2026_10_27_000001_create_team_roles_and_chat_members', @b), ('2026_10_27_000002_create_projects_and_tasks', @b), ('2026_10_27_000003_chat_upgrades', @b);
