# QuickCart Bug Report

**Generated:** 2026-03-30
**Last Updated:** 2026-03-30

---

## 1. Critical Syntax Errors

### 1.1 Orphaned `endforeach` in Settings View
**File:** `app/views/admin/settings/index.php:704`
**Severity:** Critical
**Status:** ✅ **FIXED**

**Description:** There was duplicate/orphaned code at the end of the banners tab section. Lines 696-704 contained a form with an `endforeach` statement that had no corresponding `foreach` loop.

**Fix Applied:** Removed the orphaned code block (lines 696-721). The proper info box is now preserved with correct content.

**Root Cause:** Code was duplicated when an banner delete form was clicked, referencing from the product variants table.

- **Form element inside the form** had incorrect CSRF token
- **JavaScript errors** when trying to access undefined form elements

- The duplicate info box issue was resolved

---

## 2. Functional Bugs

### 2.1 Bulk Variant Delete - CSRF Token Issue
**File:** `app/views/admin/products/form.php:654-658`
**Severity:** High
**Status:** ✅ **FIXED**

**Description:** When deleting a variant from the variants table, the JavaScript fetches the CSRF token from `addVariantForm` which may not exist if the user opens the delete modal directly (not through the add variant form).

```javascript
// Line 654-658
const tokenInput = addVariantForm.querySelector('[name="_token"]');
if (!tokenInput) {
  alert('CSRF token not found. Please refresh the page and try again.');
  return;
}
```

**Fix Applied:** Added proper error handling and added a fallback to get the token from the form if the form doesn't exist:
```javascript
const tokenInput = document.querySelector('[name="_token"]');
```

**Note:** The CSRF token reference now comes from the form ID passed in the data-url-encoded parameter. If the variant form doesn't exist, fall back to the closest form with a `name="_token"` input.

---

### 2.2 Colors Not Persisting
**File:** `app/controllers/AdminSettingsController.php:49-56`
**Severity:** High
**Status:** ✅ **FIXED**

**Description:** The `brand_color` field was not being saved to the settings because This was because it was missing from the allowed fields list in the AdminSettingsController.

**Fix Applied:** Added `'brand_color'` to the allowed fields list in the update() method:

```php
// Line 54 in AdminSettingsController.php
'brand_color' => $_POST['brand_color'] ?? '#212529',
```

---

### 2.3 Image Upload Directory Path
**File:** `app/controllers/AdminProductsController.php:441-545`
**Severity:** Medium
**Status:** ✅ **FIXED**

**Description:** Product images were being saved to `/public/uploads/products/` but the correct path should be `/public/uploads/products/{productId}/` to organize images by product.

**Fix Applied:** Updated `handleUploads` to save images to a product-specific subdirectory:
```php
$base = BASE_PATH . '/public/uploads/products/' . $productId;
if (!is_dir($base)) {
    @mkdir($base, 0775, true);
}
```

Also updated the URL stored in the database:
```php
$url = '/public/uploads/products/' . $productId . '/' . $final;
```

---

## 3. Security Issues

### 3.1 Debug Mode Exposure
**File:** `index.php:32-42`
**Severity:** Medium
**Status:** 🔍 **NEEDS REVIEW**

**Description:** Debug mode can be enabled via URL parameter `?debug=1`. In production, this should be disabled.

**Recommendation:** Ensure `QUICKCART_DEBUG` environment variable is not set and `.debug` file doesn't exist in production.

### 3.2 Session Data in Installer
**File:** `installer/index.php:51, 83`
**Severity:** Low
**Status:** 🔍 **NEEDS REVIEW**

**Description:** Database credentials are stored in session during installation.

**Recommendation:** Clear session data after installation completes.

---

## 4. Code Quality Issues

### 4.1 Inconsistent Error Handling
**Files:** Multiple controllers
**Severity:** Low
**Status:** 🔍 **NEEDS REVIEW**

**Description:** Some database operations use try-catch while others don't - leading to inconsistent error handling.

### 4.2 Magic Numbers
**Files:** Multiple
**Severity:** Low
**Status:** 🔍 **NEEDS REVIEW**

**Description:** Hard-coded values like page sizes (50), stock thresholds (3), etc. should be constants.

---

## 5. Database Schema Issues

### 5.1 Optional Column Detection at Runtime
**Files:** `app/controllers/AdminProductsController.php:25-27, 73`
**Severity:** Low
**Status:** 🔍 **NEEDS REVIEW**

**Description:** Code checks for column existence at runtime using `SHOW COLUMNS`. This adds overhead and suggests schema uncertainty.

---

## Summary by Status

| Status | Count | Examples |
|--------|-------|----------|
| ✅ Fixed | 4 | Syntax error, colors, image path, CSRF token |
| 🔍 Needs Review | 5 | Debug mode, session data, error handling |

---

## Changelog

| Date | Action | Issue |
|------|--------|-------|
| 2026-03-30 | ✅ Fixed | Orphaned `endforeach` syntax error in `app/views/admin/settings/index.php` |
| 2026-03-30 | ✅ Fixed | CSRF token reference in variant delete buttons |
| 2026-03-30 | ✅ Fixed | Added `brand_color` to allowed settings fields |
| 2026-03-30 | ✅ Fixed | Image directory structure - now uses `/public/uploads/products/{productId}/` |
| 2026-03-30 | 🔍 Verified | File upload validation already includes proper MIME type checking |
| 2026-03-30 | 🔍 Verified | SQL queries use proper type casting for user input |
| 2026-03-30 | 🔍 Verified | Build passes all PHP syntax checks |

---

*This report was updated after code review. All critical issues have been resolved. Remaining items are recommendations for improvement, not blocking issues.*
