-- Adds the campaign details to website requests (additive, run once).
-- Easier: Admin > System > Update the database.
ALTER TABLE `orders` ADD COLUMN `attribution` TEXT NULL;
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_26_000001_add_attribution_to_orders', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` AS m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_26_000001_add_attribution_to_orders');
