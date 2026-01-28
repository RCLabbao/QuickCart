-- =====================================================
-- Fix for: Duplicate entry '' for key 'products.fsc'
-- =====================================================
-- Problem: The fsc column has a UNIQUE constraint but empty
-- strings ('') are treated as valid values by MySQL. This causes
-- duplicate key errors when importing multiple products without FSC.
--
-- Solution: Convert empty strings to NULL (which allows multiple rows)
-- =====================================================

USE ecommer2_quickcart;

-- Step 1: Convert all empty FSC strings to NULL
UPDATE products SET fsc = NULL WHERE fsc = '' OR fsc = ' ';

-- Step 2: Verify the fix
SELECT 'Empty FSC strings remaining:' as status, COUNT(*) as count FROM products WHERE fsc = '' OR fsc = ' '
UNION ALL
SELECT 'NULL FSC values:' as status, COUNT(*) as count FROM products WHERE fsc IS NULL
UNION ALL
SELECT 'Non-empty FSC values:' as status, COUNT(*) as count FROM products WHERE fsc IS NOT NULL AND fsc != '';

-- Step 3: (Optional) Optimize the table to reclaim space
OPTIMIZE TABLE products;

-- =====================================================
-- Additional diagnostic queries (run separately to check)
-- =====================================================

-- Check for any remaining duplicate FSC values (excluding NULL)
-- SELECT fsc, COUNT(*) as count FROM products WHERE fsc IS NOT NULL AND fsc != '' GROUP BY fsc HAVING count > 1;

-- Show all products with NULL FSC
-- SELECT id, title, fsc, price FROM products WHERE fsc IS NULL LIMIT 20;
