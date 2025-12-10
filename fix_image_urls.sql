-- This script updates existing image URLs to include /public prefix
-- Run this on your database if images are not displaying

-- Update URLs that don't have /public prefix
UPDATE product_images
SET url = CONCAT('/public', url)
WHERE url NOT LIKE '/public%'
AND url LIKE '/uploads/%';

-- You can also run this to check what will be updated first:
-- SELECT * FROM product_images WHERE url NOT LIKE '/public%' AND url LIKE '/uploads/%';