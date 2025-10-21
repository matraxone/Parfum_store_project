USE `parfum_shop`;
-- Add volume_ml to products (idempotent)
ALTER TABLE products ADD COLUMN IF NOT EXISTS volume_ml DECIMAL(6,1) NULL AFTER stock;
