-- Fix existing banner image URLs to include /public prefix
UPDATE banners SET image_url = CONCAT('/public', image_url)
WHERE image_url IS NOT NULL
AND image_url NOT LIKE '/public%'
AND image_url LIKE '/uploads/%';

-- Fix existing mobile banner image URLs
UPDATE banners SET mobile_image_url = CONCAT('/public', mobile_image_url)
WHERE mobile_image_url IS NOT NULL
AND mobile_image_url NOT LIKE '/public%'
AND mobile_image_url LIKE '/uploads/%';
