-- Cleanup all tables in parfum_shop safely
USE `parfum_shop`;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS order_items, payments, reviews, orders, products, audit_logs, users;
SET FOREIGN_KEY_CHECKS=1;
