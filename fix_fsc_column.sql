-- This script ensures the FSC column exists in the products table
-- Run this on your database if FSC search is not working

-- Check if column exists and add if it doesn't
ALTER TABLE products
ADD COLUMN IF NOT EXISTS fsc VARCHAR(64) NULL UNIQUE;

-- Add index for better search performance
CREATE INDEX IF NOT EXISTS idx_products_fsc ON products(fsc);

-- If you have existing products and want to update FSC from old SKU column or similar
-- UPDATE products SET fsc = sku WHERE fsc IS NULL AND sku IS NOT NULL;