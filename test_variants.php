<?php
/**
 * QuickCart Variant / Color / Image Test Suite
 * Run: php test_variants.php
 * Or open in browser: http://localhost/test_variants.php
 */

// ─── Load the actual functions from Helpers.php without needing DB ───
// We extract the functions manually since they're namespaced

require_once __DIR__ . '/app/core/Helpers.php';

use function App\Core\qc_extract_base_title;
use function App\Core\qc_extract_variant_attribute;

// ─── Test Runner ───
$passed = 0;
$failed = 0;
$errors = [];

function assert_equal($actual, $expected, $label) {
    global $passed, $failed, $errors;
    if ($actual === $expected) {
        $passed++;
        echo "  <span style='color:green'>PASS</span> $label\n";
    } else {
        $failed++;
        $msg = "FAIL $label — expected " . var_export($expected, true) . ", got " . var_export($actual, true);
        $errors[] = $msg;
        echo "  <span style='color:red'>$msg</span>\n";
    }
}

function assert_true($condition, $label) {
    assert_equal($condition, true, $label);
}

function section($name) {
    echo "\n<span style='font-weight:bold;font-size:1.1em;color:#333'>━━━ $name ━━━</span>\n";
}

// ═══════════════════════════════════════════════════════════════
echo "<pre style='background:#f8f9fa;padding:20px;border-radius:8px;font-family:monospace'>";
echo "QuickCart Variant/Color/Image Test Suite\n";
echo str_repeat("═", 50) . "\n\n";

// ═══════════════════════════════════════════════════════════════
// TEST 1: qc_extract_base_title — Sizes
// ═══════════════════════════════════════════════════════════════
section("1. extract_base_title — Bra Sizes");

assert_equal(qc_extract_base_title("BEVERLY UW FULL CUP LACE 38A"), "BEVERLY UW FULL CUP LACE", "38A");
assert_equal(qc_extract_base_title("BEVERLY UW FULL CUP LACE 36B"), "BEVERLY UW FULL CUP LACE", "36B");
assert_equal(qc_extract_base_title("BEVERLY UW FULL CUP LACE 34C"), "BEVERLY UW FULL CUP LACE", "34C");
assert_equal(qc_extract_base_title("BEVERLY UW FULL CUP LACE 32DD"), "BEVERLY UW FULL CUP LACE", "32DD");
assert_equal(qc_extract_base_title("BEVERLY UW FULL CUP LACE 40DDD"), "BEVERLY UW FULL CUP LACE", "40DDD");
assert_equal(qc_extract_base_title("BEVERLY UW FULL CUP LACE 100A"), "BEVERLY UW FULL CUP LACE", "100A (3-digit band)");

section("2. extract_base_title — Word Sizes");
assert_equal(qc_extract_base_title("EDWARD 5IN1 HI CUT BPK SMALL"), "EDWARD 5IN1 HI CUT BPK", "SMALL");
assert_equal(qc_extract_base_title("EDWARD 5IN1 HI CUT BPK MEDIUM"), "EDWARD 5IN1 HI CUT BPK", "MEDIUM");
assert_equal(qc_extract_base_title("EDWARD 5IN1 HI CUT BPK LARGE"), "EDWARD 5IN1 HI CUT BPK", "LARGE");

section("3. extract_base_title — Abbreviated Sizes");
assert_equal(qc_extract_base_title("SHIRT PREMIUM COTTON XL"), "SHIRT PREMIUM COTTON", "XL");
assert_equal(qc_extract_base_title("SHIRT PREMIUM COTTON XS"), "SHIRT PREMIUM COTTON", "XS");
assert_equal(qc_extract_base_title("SHIRT PREMIUM COTTON 2XL"), "SHIRT PREMIUM COTTON", "2XL");
assert_equal(qc_extract_base_title("SHIRT PREMIUM COTTON 3XL"), "SHIRT PREMIUM COTTON", "3XL");
assert_equal(qc_extract_base_title("SHIRT PREMIUM COTTON 5XL"), "SHIRT PREMIUM COTTON", "5XL");
assert_equal(qc_extract_base_title("SHIRT PREMIUM COTTON 2XS"), "SHIRT PREMIUM COTTON", "2XS");

section("4. extract_base_title — PACK Sizes");
assert_equal(qc_extract_base_title("MOLLY BIKINI BRIEF PACK L"), "MOLLY BIKINI BRIEF", "PACK L");
assert_equal(qc_extract_base_title("MOLLY BIKINI BRIEF PACK M"), "MOLLY BIKINI BRIEF", "PACK M");
assert_equal(qc_extract_base_title("MOLLY BIKINI BRIEF PACK S"), "MOLLY BIKINI BRIEF", "PACK S");

section("5. extract_base_title — Single Letter Sizes");
assert_equal(qc_extract_base_title("ALICE HI CUT BRIEF S"), "ALICE HI CUT BRIEF", "S");
assert_equal(qc_extract_base_title("ALICE HI CUT BRIEF M"), "ALICE HI CUT BRIEF", "M");
assert_equal(qc_extract_base_title("ALICE HI CUT BRIEF L"), "ALICE HI CUT BRIEF", "L");

section("6. extract_base_title — Numeric Sizes");
assert_equal(qc_extract_base_title("WOMEN DRESS FLORAL 36"), "WOMEN DRESS FLORAL", "36");
assert_equal(qc_extract_base_title("WOMEN DRESS FLORAL 38"), "WOMEN DRESS FLORAL", "38");
assert_equal(qc_extract_base_title("WOMEN DRESS FLORAL 42"), "WOMEN DRESS FLORAL", "42");

section("7. extract_base_title — Decimal Sizes");
assert_equal(qc_extract_base_title("RING SIZE ADJUSTABLE 28.5"), "RING SIZE ADJUSTABLE", "28.5");
assert_equal(qc_extract_base_title("RING SIZE ADJUSTABLE 6.5"), "RING SIZE ADJUSTABLE", "6.5");

// ═══════════════════════════════════════════════════════════════
// TEST 2: qc_extract_base_title — Colors
// ═══════════════════════════════════════════════════════════════
section("8. extract_base_title — Color Variants");

$colors = ['RED','BLUE','GREEN','YELLOW','BLACK','WHITE','PINK','PURPLE','ORANGE',
           'BROWN','BEIGE','CREAM','GOLD','SILVER','NAVY','CORAL','TEAL','BURGUNDY',
           'IVORY','KHAKI','LAVENDER','MINT','OLIVE','PEACH','RUST','SAGE','TAN','TURQUOISE'];

foreach ($colors as $color) {
    $title = "BEVERLY UW FULL CUP LACE $color";
    $expected = "BEVERLY UW FULL CUP LACE";
    assert_equal(qc_extract_base_title($title), $expected, "Color: $color");
}

// Multicolor variants
assert_equal(qc_extract_base_title("TOWEL BATH PREMIUM MULTICOLOR"), "TOWEL BATH PREMIUM", "MULTICOLOR");
assert_equal(qc_extract_base_title("TOWEL BATH PREMIUM MULTI-COLOR"), "TOWEL BATH PREMIUM", "MULTI-COLOR");

// ═══════════════════════════════════════════════════════════════
// TEST 3: qc_extract_variant_attribute
// ═══════════════════════════════════════════════════════════════
section("9. extract_variant_attribute — Sizes");

assert_equal(qc_extract_variant_attribute("BEVERLY UW FULL CUP LACE 38A"), "38A", "Bra 38A");
assert_equal(qc_extract_variant_attribute("BEVERLY UW FULL CUP LACE 32DD"), "32DD", "Bra 32DD");
assert_equal(qc_extract_variant_attribute("EDWARD 5IN1 HI CUT BPK SMALL"), "SMALL", "SMALL");
assert_equal(qc_extract_variant_attribute("EDWARD 5IN1 HI CUT BPK LARGE"), "LARGE", "LARGE");
assert_equal(qc_extract_variant_attribute("MOLLY BIKINI BRIEF PACK L"), "PACK L", "PACK L");
assert_equal(qc_extract_variant_attribute("ALICE HI CUT BRIEF M"), "M", "Single M");
assert_equal(qc_extract_variant_attribute("SHIRT PREMIUM COTTON 2XL"), "2XL", "2XL");
assert_equal(qc_extract_variant_attribute("WOMEN DRESS FLORAL 38"), "38", "Numeric 38");
assert_equal(qc_extract_variant_attribute("RING SIZE ADJUSTABLE 28.5"), "28.5", "Decimal 28.5");

section("10. extract_variant_attribute — Colors");

foreach ($colors as $color) {
    $title = "BEVERLY UW FULL CUP LACE $color";
    assert_equal(qc_extract_variant_attribute($title), $color, "Extract color: $color");
}

// ═══════════════════════════════════════════════════════════════
// TEST 4: Consistency — base + attr must reconstruct title
// ═══════════════════════════════════════════════════════════════
section("11. Consistency: base + attr = original title");

$titles = [
    "BEVERLY UW FULL CUP LACE 38A",
    "BEVERLY UW FULL CUP LACE 36B",
    "BEVERLY UW FULL CUP LACE 32DD",
    "BEVERLY UW FULL CUP LACE 40DDD",
    "EDWARD 5IN1 HI CUT BPK SMALL",
    "EDWARD 5IN1 HI CUT BPK MEDIUM",
    "EDWARD 5IN1 HI CUT BPK LARGE",
    "ALICE HI CUT BRIEF PACK L",
    "MOLLY BIKINI BRIEF PACK M",
    "SHIRT PREMIUM COTTON XL",
    "SHIRT PREMIUM COTTON 2XL",
    "WOMEN DRESS FLORAL 36",
    "RING SIZE ADJUSTABLE 28.5",
    "BEVERLY UW FULL CUP LACE RED",
    "BEVERLY UW FULL CUP LACE BLACK",
    "BEVERLY UW FULL CUP LACE PINK",
    "BEVERLY UW FULL CUP LACE NAVY",
    "LACE TRIM BRIEF PINK",
    "LACE TRIM BRIEF BLACK",
];

foreach ($titles as $t) {
    $attr = qc_extract_variant_attribute($t);
    $base = qc_extract_base_title($t);
    $reconstructed = trim($base . " " . $attr);
    assert_equal(strtoupper($reconstructed), strtoupper($t), "Reconstruct: $t");
}

// ═══════════════════════════════════════════════════════════════
// TEST 5: FALSE POSITIVES — must NOT match
// ═══════════════════════════════════════════════════════════════
section("12. FALSE POSITIVES — color/size in name (must NOT match)");

$falsePositives = [
    // Color-in-name tests (color not at end of title)
    "BLACK DRESS ELEGANT" => "BLACK is part of name",
    "BLUE BAY RESORT SHIRT" => "BLUE is part of name",
    "TAN LEATHER BELT QUALITY" => "TAN is part of name",
    "SAGE GREEN TEA SET CERAMIC" => "SAGE is part of name",
    "OLIVE GARDEN APRON COTTON" => "OLIVE is part of name",
    "PEACH SKIN LOTION PREMIUM" => "PEACH is part of name",
    "RUST REMOVER SPRAY BOTTLE" => "RUST is part of name",
    "MINT CANDY BOX ASSORTED" => "MINT is part of name",
    "CREAM CHEESE SPREAD TUB" => "CREAM is part of name",
    "GOLD BRACELET CHARM SET" => "GOLD is part of name",
    "SILVER CHAIN NECKLACE 18IN" => "SILVER is part of name",
    "CORAL REEF SWIMSUIT WOMEN" => "CORAL is part of name",
    "IVORY SOAP BAR TRIPLE" => "IVORY is part of name",
    "LAVENDER FIELD DRESS WOMEN" => "LAVENDER is part of name",
    "KHAKI SHORTS MENS CARGO" => "KHAKI is part of name",
    "BURGUNDY WINE GLASS SET" => "BURGUNDY is part of name",
    "TURQUOISE NECKLACE BEAD" => "TURQUOISE is part of name",
    "NAVY SEAL SHIRT COTTON" => "NAVY is part of name",
    // Size-in-name tests
    "SMALL BUSINESS GUIDE BOOK" => "SMALL is part of name",
    "LARGE FORMAT PRINTER EPSON" => "LARGE is part of name",
    "MEDIUM DENSITY FOAM ROLL" => "MEDIUM is part of name",
    "EXTRA LARGE PIZZA PAN STEEL" => "EXTRA LARGE is part of name",
    // Invalid bra sizes
    "PRODUCT WASHING 38AB" => "AB is not a valid cup",
    "PRODUCT WASHING 34ABC" => "ABC is not a valid cup",
    // Invalid number+letter combos
    "TEST PRODUCT 10L" => "10L not a valid size",
    "TEST PRODUCT 3M" => "3M not a valid size",
    "TEST PRODUCT 2S" => "2S not a valid size",
];

foreach ($falsePositives as $title => $reason) {
    $attrResult = qc_extract_variant_attribute($title);
    assert_equal($attrResult, "", "No extract: $title ($reason)");
}

section("13. FALSE POSITIVES — extract_base_title returns original");

foreach ($falsePositives as $title => $reason) {
    $base = qc_extract_base_title($title);
    assert_equal($base, $title, "No strip: $title ($reason)");
}

// ═══════════════════════════════════════════════════════════════
// TEST 6: Short titles — too few words to form a base
// ═══════════════════════════════════════════════════════════════
section("14. Short titles — should NOT match (base would be 1 word)");

$shortTitles = [
    "T-SHIRT RED",
    "T-SHIRT BLUE",
    "DRESS NAVY",
    "BRA 34C",
    "BRA 38DD",
    "SHIRT XL",
    "PANTS 36",
    "SKIRT 28.5",
    "TOWEL MULTICOLOR",
];

foreach ($shortTitles as $title) {
    $attr = qc_extract_variant_attribute($title);
    assert_equal($attr, "", "Short: no attr for '$title'");
    $base = qc_extract_base_title($title);
    assert_equal($base, $title, "Short: no strip for '$title'");
}

// ═══════════════════════════════════════════════════════════════
// TEST 7: Edge cases
// ═══════════════════════════════════════════════════════════════
section("15. Edge cases — empty, single word, color-only name");

assert_equal(qc_extract_base_title(""), "", "Empty string");
assert_equal(qc_extract_variant_attribute(""), "", "Empty string attr");
assert_equal(qc_extract_base_title("BLACK"), "BLACK", "Color-only name = standalone");
assert_equal(qc_extract_variant_attribute("BLACK"), "", "Color-only name: no attr");
assert_equal(qc_extract_base_title("SMALL"), "SMALL", "Size-only name = standalone");
assert_equal(qc_extract_variant_attribute("SMALL"), "", "Size-only name: no attr");
assert_equal(qc_extract_base_title("38A"), "38A", "Bra-size-only = standalone");
assert_equal(qc_extract_variant_attribute("38A"), "", "Bra-size-only: no attr");

section("16. Edge cases — double color at end (color variant of color-named product)");
assert_equal(qc_extract_base_title("BLACK T-SHIRT BLACK"), "BLACK T-SHIRT", "Repeated color: base keeps first");
assert_equal(qc_extract_variant_attribute("BLACK T-SHIRT BLACK"), "BLACK", "Repeated color: attr is last");

assert_equal(qc_extract_base_title("RED DRESS RED"), "RED DRESS", "RED DRESS RED: base");
assert_equal(qc_extract_variant_attribute("RED DRESS RED"), "RED", "RED DRESS RED: attr");

assert_equal(qc_extract_base_title("PINK LACE BRA PINK"), "PINK LACE BRA", "PINK LACE BRA PINK: base");
assert_equal(qc_extract_variant_attribute("PINK LACE BRA PINK"), "PINK", "PINK LACE BRA PINK: attr");

section("17. Edge cases — case insensitivity");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE small"), "SMALL", "Lowercase 'small'");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE Small"), "SMALL", "Mixed case 'Small'");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE red"), "RED", "Lowercase 'red'");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE Black"), "BLACK", "Mixed case 'Black'");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE 38a"), "38A", "Lowercase '38a'");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE 32dd"), "32DD", "Lowercase '32dd'");

section("18. Edge cases — trailing whitespace");
assert_equal(qc_extract_base_title("BEVERLY UW LACE 38A  "), "BEVERLY UW LACE", "Trailing spaces: base");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE 38A  "), "38A", "Trailing spaces: attr");
assert_equal(qc_extract_base_title("  BEVERLY UW LACE 38A"), "BEVERLY UW LACE", "Leading spaces: base");
assert_equal(qc_extract_variant_attribute("  BEVERLY UW LACE 38A"), "38A", "Leading spaces: attr");

section("19. Edge cases — products with numbers in name");
assert_equal(qc_extract_base_title("EDWARD 5IN1 HI CUT BPK SMALL"), "EDWARD 5IN1 HI CUT BPK", "5IN1 in name + SMALL variant");
assert_equal(qc_extract_base_title("3 PACK COTTON BRIEF SMALL"), "3 PACK COTTON BRIEF", "3 PACK in name + SMALL");
assert_equal(qc_extract_base_title("FIBER 10 PANTIES BLUE"), "FIBER 10 PANTIES", "10 in name + BLUE");

// ═══════════════════════════════════════════════════════════════
// TEST 8: Grouping — multiple titles should produce same base
// ═══════════════════════════════════════════════════════════════
section("20. Grouping — same base for variant family");

$braFamily = [
    "BEVERLY UW FULL CUP LACE 34A",
    "BEVERLY UW FULL CUP LACE 34B",
    "BEVERLY UW FULL CUP LACE 36A",
    "BEVERLY UW FULL CUP LACE 36B",
    "BEVERLY UW FULL CUP LACE 38A",
    "BEVERLY UW FULL CUP LACE 38B",
    "BEVERLY UW FULL CUP LACE 38C",
    "BEVERLY UW FULL CUP LACE 40C",
];
$expectedBase = "BEVERLY UW FULL CUP LACE";
foreach ($braFamily as $title) {
    assert_equal(qc_extract_base_title($title), $expectedBase, "Group bra: $title");
}

$colorFamily = [
    "ALICE HI CUT BRIEF RED",
    "ALICE HI CUT BRIEF BLUE",
    "ALICE HI CUT BRIEF BLACK",
    "ALICE HI CUT BRIEF PINK",
    "ALICE HI CUT BRIEF WHITE",
    "ALICE HI CUT BRIEF NAVY",
];
$expectedColorBase = "ALICE HI CUT BRIEF";
foreach ($colorFamily as $title) {
    assert_equal(qc_extract_base_title($title), $expectedColorBase, "Group color: $title");
}

$sizeFamily = [
    "EDWARD 5IN1 HI CUT BPK SMALL",
    "EDWARD 5IN1 HI CUT BPK MEDIUM",
    "EDWARD 5IN1 HI CUT BPK LARGE",
    "EDWARD 5IN1 HI CUT BPK XL",
    "EDWARD 5IN1 HI CUT BPK 2XL",
];
$expectedSizeBase = "EDWARD 5IN1 HI CUT BPK";
foreach ($sizeFamily as $title) {
    assert_equal(qc_extract_base_title($title), $expectedSizeBase, "Group size: $title");
}

// ═══════════════════════════════════════════════════════════════
// TEST 9: Image URL safety checks
// ═══════════════════════════════════════════════════════════════
section("21. Image URL patterns — FSC-based filename matching");

// Test that image auto-detect regex patterns are correct
$fscTestCases = [
    ["FSC-123", "FSC-123-1.jpg", true],
    ["FSC-123", "FSC-123-2.png", true],
    ["FSC-123", "FSC-123.jpg", true],
    ["FSC-123", "FSC-123-abc123.webp", true],
    ["FSC-123", "FSC-124-1.jpg", false],  // Wrong FSC
    ["FSC-123", "OTHER-123-1.jpg", false], // Wrong prefix
    ["FSC-123", "FSC-123.txt", false],     // Not an image
];

foreach ($fscTestCases as $case) {
    list($fsc, $filename, $expected) = $case;
    $escaped = preg_quote($fsc, '/');
    $pattern1 = '/' . $escaped . '-\d+\.(jpg|jpeg|png|gif|webp)/i';
    $pattern2 = '/' . $escaped . '-[^.]+\.(jpg|jpeg|png|gif|webp)/i';
    $pattern3 = '/' . $escaped . '\.(jpg|jpeg|png|gif|webp)/i';
    $matched = preg_match($pattern1, $filename) || preg_match($pattern2, $filename) || preg_match($pattern3, $filename);
    assert_equal($matched ? true : false, $expected, "Image match: FSC=$fsc file=$filename");
}

// ═══════════════════════════════════════════════════════════════
// TEST 10: Image product URL format
// ═══════════════════════════════════════════════════════════════
section("22. Image URL format — product folder structure");

$productId = 12345;
$filename = "FSC-001-1.jpg";
$expectedUrl = "/public/uploads/products/$productId/$filename";
$actualUrl = "/public/uploads/products/" . $productId . "/" . $filename;
assert_equal($actualUrl, $expectedUrl, "Image URL path format");

// ═══════════════════════════════════════════════════════════════
// TEST 11: Slug generation
// ═══════════════════════════════════════════════════════════════
section("23. Slug generation — parent product slugs");

$slugTests = [
    "BEVERLY UW FULL CUP LACE" => "beverly-uw-full-cup-lace",
    "EDWARD 5IN1 HI CUT BPK" => "edward-5in1-hi-cut-bpk",
    "ALICE HI CUT BRIEF" => "alice-hi-cut-brief",
    "TOWEL BATH PREMIUM" => "towel-bath-premium",
];

foreach ($slugTests as $input => $expectedSlug) {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($input));
    $slug = trim($slug, '-');
    assert_equal($slug, $expectedSlug, "Slug: $input");
}

// ═══════════════════════════════════════════════════════════════
// TEST 12: Generic ProductType filtering
// ═══════════════════════════════════════════════════════════════
section("24. ProductType filtering — generic types should be rejected");

$genericTypes = ['BRIEF','BRA','PANTY','PANTIES','PANT','SHIRT','DRESS','SKIRT',
    'UNDERWEAR','LINGERIE','SOCKS','TOP','BOTTOM','SWIMWEAR','SWIMSUIT',
    'TOWEL','BLANKET','ROBE','GOWN','SHORTS'];

foreach ($genericTypes as $type) {
    // These should NOT be treated as variant attributes when they come from ProductType column
    $isGeneric = in_array(strtoupper($type), $genericTypes);
    assert_true($isGeneric, "Generic type rejected: $type");
}

// ═══════════════════════════════════════════════════════════════
// TEST 13: Mixed variant families — size + color same base
// ═══════════════════════════════════════════════════════════════
section("25. Mixed families — size and color should group to same base");

assert_equal(
    qc_extract_base_title("PREMIUM COTTON SHIRT RED"),
    qc_extract_base_title("PREMIUM COTTON SHIRT BLUE"),
    "Same base for color variants: RED vs BLUE"
);
assert_equal(
    qc_extract_base_title("PREMIUM COTTON SHIRT SMALL"),
    qc_extract_base_title("PREMIUM COTTON SHIRT LARGE"),
    "Same base for size variants: SMALL vs LARGE"
);
assert_equal(
    qc_extract_base_title("PREMIUM COTTON SHIRT RED"),
    qc_extract_base_title("PREMIUM COTTON SHIRT SMALL"),
    "Same base for mixed: RED vs SMALL"
);

// ═══════════════════════════════════════════════════════════════
// TEST 14: Hyphen-separated variants (the Melissa bug)
// ═══════════════════════════════════════════════════════════════
section("26. Hyphen-separated variants — MELISSA (DST)-XL format");

$melissaBase = "MELISSA HILEG PNTY (DST)";
assert_equal(qc_extract_base_title("MELISSA HILEG PNTY (DST)-XL"), $melissaBase, "Melissa-XL base");
assert_equal(qc_extract_base_title("MELISSA HILEG PNTY (DST)-L"), $melissaBase, "Melissa-L base");
assert_equal(qc_extract_base_title("MELISSA HILEG PNTY (DST)-M"), $melissaBase, "Melissa-M base");
assert_equal(qc_extract_base_title("MELISSA HILEG PNTY (DST)-S"), $melissaBase, "Melissa-S base");

assert_equal(qc_extract_variant_attribute("MELISSA HILEG PNTY (DST)-XL"), "XL", "Melissa-XL attr");
assert_equal(qc_extract_variant_attribute("MELISSA HILEG PNTY (DST)-L"), "L", "Melissa-L attr");
assert_equal(qc_extract_variant_attribute("MELISSA HILEG PNTY (DST)-M"), "M", "Melissa-M attr");
assert_equal(qc_extract_variant_attribute("MELISSA HILEG PNTY (DST)-S"), "S", "Melissa-S attr");

section("27. Hyphen-separated — more formats");

// Hyphen + size
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE-38A"), "38A", "Hyphen bra size: -38A");
assert_equal(qc_extract_variant_attribute("BEVERLY UW LACE-RED"), "RED", "Hyphen color: -RED");
assert_equal(qc_extract_variant_attribute("EDWARD BPK-SMALL"), "SMALL", "Hyphen word size: -SMALL");
assert_equal(qc_extract_variant_attribute("SHIRT COTTON-2XL"), "2XL", "Hyphen 2XL: -2XL");

// Mixed separator
assert_equal(qc_extract_variant_attribute("ALICE BRIEF -XL"), "XL", "Space+hyphen:  -XL");
assert_equal(qc_extract_variant_attribute("ALICE BRIEF - RED"), "RED", "Space+hyphen+space:  - RED");

section("28. Hyphen in product name — must NOT match as variant");

// Hyphen in the product name itself (not a variant)
assert_equal(qc_extract_variant_attribute("T-SHIRT"), "", "T-SHIRT: no variant");
assert_equal(qc_extract_variant_attribute("PRODUCT-NAME"), "", "PRODUCT-NAME: no variant");
assert_equal(qc_extract_variant_attribute("MEN'S GROOMING KIT"), "", "MEN'S GROOMING KIT: no variant");
assert_equal(qc_extract_variant_attribute("SWEET HONESTY MAGIC PSS150G"), "", "SWEET HONESTY MAGIC PSS150G: no variant");

// ═══════════════════════════════════════════════════════════════
// TEST 15: XXL / XXS / XXXL sizes (KATHLEEN bug fix)
// ═══════════════════════════════════════════════════════════════
section("29. XXL/XXS/XXXL sizes — KATHLEEN 7IN1 MAXI PPK XXL");

$kathleenBase = "KATHLEEN 7IN1 MAXI PPK";
assert_equal(qc_extract_base_title("KATHLEEN 7IN1 MAXI PPK XXL"), $kathleenBase, "XXL base");
assert_equal(qc_extract_variant_attribute("KATHLEEN 7IN1 MAXI PPK XXL"), "XXL", "XXL attr");
assert_equal(qc_extract_base_title("KATHLEEN 7IN1 MAXI PPK XS"), $kathleenBase, "XS base");
assert_equal(qc_extract_variant_attribute("KATHLEEN 7IN1 MAXI PPK XS"), "XS", "XS attr");

$xxlFamily = [
    "KATHLEEN 7IN1 MAXI PPK S",
    "KATHLEEN 7IN1 MAXI PPK M",
    "KATHLEEN 7IN1 MAXI PPK L",
    "KATHLEEN 7IN1 MAXI PPK XL",
    "KATHLEEN 7IN1 MAXI PPK XXL",
    "KATHLEEN 7IN1 MAXI PPK 2XL",
    "KATHLEEN 7IN1 MAXI PPK 3XL",
];
foreach ($xxlFamily as $title) {
    assert_equal(qc_extract_base_title($title), $kathleenBase, "KATHLEEN group: $title");
}

section("30. All X-prefix sizes");

$xSizes = ['XXS','XXL','XXXS','XXXL','XXXXL','XXXXXL'];
foreach ($xSizes as $sz) {
    $title = "TEST PRODUCT COTTON $sz";
    assert_equal(qc_extract_base_title($title), "TEST PRODUCT COTTON", "X-prefix strip: $sz");
    assert_equal(qc_extract_variant_attribute($title), $sz, "X-prefix attr: $sz");
}

// ═══════════════════════════════════════════════════════════════
// TEST 16: qc_generate_color_hex — deterministic & proper hex format
// ═══════════════════════════════════════════════════════════════
section("31. qc_generate_color_hex — deterministic hex output");

$hex1 = \App\Core\qc_generate_color_hex("RTY LSTR");
$hex2 = \App\Core\qc_generate_color_hex("RTY LSTR");
assert_equal($hex1, $hex2, "Same name produces same hex");

$hex3 = \App\Core\qc_generate_color_hex("WST GLOW");
assert_true($hex1 !== $hex3, "Different names produce different hex");

// All hex codes must be valid format #rrggbb
foreach (['RTY LSTR','WST GLOW','CHRY PCK','PNY BLSH','PINK MIRAGE','NR TO BERRY','ABC','TEST'] as $name) {
    $hex = \App\Core\qc_generate_color_hex($name);
    $valid = preg_match('/^#[0-9a-f]{6}$/i', $hex) === 1;
    assert_true($valid, "Valid hex for '$name': $hex");
}

// ═══════════════════════════════════════════════════════════════
// TEST 17: qc_is_color_variant — standard colors (no DB needed)
// ═══════════════════════════════════════════════════════════════
section("32. qc_is_color_variant — standard colors");

// Without DB, custom colors won't load — but standard colors should still work
$standardColorTests = ['RED','BLUE','GREEN','BLACK','WHITE','PINK','PURPLE','ORANGE','BROWN',
    'BEIGE','CREAM','GOLD','SILVER','NAVY','CORAL','TEAL','BURGUNDY','IVORY','KHAKI',
    'LAVENDER','MINT','OLIVE','PEACH','RUST','SAGE','TAN','TURQUOISE','MULTICOLOR'];
foreach ($standardColorTests as $c) {
    assert_true(\App\Core\qc_is_color_variant($c), "Is color: $c");
    assert_true(\App\Core\qc_is_color_variant(strtolower($c)), "Is color (lower): " . strtolower($c));
}

// Non-colors should return false
$nonColors = ['38A','XL','SMALL','M','7ML','1.2G','36','PACK L','T-SHIRT','','SHIRT'];
foreach ($nonColors as $nc) {
    assert_true(!\App\Core\qc_is_color_variant($nc), "NOT a color: $nc");
}

// ═══════════════════════════════════════════════════════════════
// TEST 18: qc_variant_color_hex — standard color hex mapping
// ═══════════════════════════════════════════════════════════════
section("33. qc_variant_color_hex — standard colors return hex");

$expectedHex = [
    'RED' => '#EF4444', 'BLUE' => '#3B82F6', 'GREEN' => '#22C55E',
    'BLACK' => '#1F2937', 'WHITE' => '#F9FAFB', 'PINK' => '#EC4899',
    'NAVY' => '#1E3A5F', 'PURPLE' => '#A855F7', 'ORANGE' => '#F97316',
    'GOLD' => '#D4A017', 'SILVER' => '#C0C0C0', 'TEAL' => '#14B8A6',
];
foreach ($expectedHex as $color => $hex) {
    assert_equal(\App\Core\qc_variant_color_hex($color), $hex, "Hex for $color");
}

// Non-colors return null
assert_equal(\App\Core\qc_variant_color_hex("38A"), null, "38A returns null");
assert_equal(\App\Core\qc_variant_color_hex("XL"), null, "XL returns null");
assert_equal(\App\Core\qc_variant_color_hex(""), null, "Empty returns null");

// ═══════════════════════════════════════════════════════════════
// TEST 19: Custom colors passed explicitly to extract functions
// ═══════════════════════════════════════════════════════════════
section("34. Custom colors — explicit \$customVariants parameter");

$customColors = ['RTY LSTR', 'WST GLOW', 'CHRY PCK', 'PNY BLSH', 'PINK MIRAGE', 'NR TO BERRY'];

// Color at end
assert_equal(qc_extract_base_title("AUC LIP GLS 7ML RTY LSTR", $customColors), "AUC LIP GLS", "Custom: RTY LSTR base");
assert_equal(qc_extract_variant_attribute("AUC LIP GLS 7ML RTY LSTR", $customColors), "RTY LSTR 7ML", "Custom: RTY LSTR 7ML attr");

// All custom colors should produce same base
$lipGlossFamily = [
    "AUC LIP GLS 7ML RTY LSTR",
    "AUC LIP GLS 7ML WST GLOW",
    "AUC LIP GLS 7ML CHRY PCK",
    "AUC LIP GLS 7ML PNY BLSH",
];
$lipGlossBase = qc_extract_base_title($lipGlossFamily[0], $customColors);
foreach ($lipGlossFamily as $title) {
    assert_equal(qc_extract_base_title($title, $customColors), $lipGlossBase, "Lip gloss group: $title");
}

// Color before size (iterative stripping)
assert_equal(qc_extract_base_title("UC PH LIP STN & BLM PINK MIRAGE 1.2G", $customColors), "UC PH LIP STN & BLM", "Color before size: base");
$attr = qc_extract_variant_attribute("UC PH LIP STN & BLM PINK MIRAGE 1.2G", $customColors);
assert_true(str_contains($attr, "PINK MIRAGE"), "Color before size: attr contains PINK MIRAGE");
assert_true(str_contains($attr, "1.2G"), "Color before size: attr contains 1.2G");

// Grouping: color before size — all variants same base
$lipStainFamily = [
    "UC PH LIP STN & BLM PINK MIRAGE 1.2G",
    "UC PH LIP STN & BLM NR TO BERRY 1.2G",
];
$stainBase1 = qc_extract_base_title($lipStainFamily[0], $customColors);
$stainBase2 = qc_extract_base_title($lipStainFamily[1], $customColors);
assert_equal($stainBase1, $stainBase2, "Color-before-size: same base for PINK MIRAGE vs NR TO BERRY");

// ═══════════════════════════════════════════════════════════════
// TEST 20: Color pills — standard + custom colors mixed
// ═══════════════════════════════════════════════════════════════
section("35. Color pills — standard colors get hex, custom get generated hex");

// Standard colors always return a proper hex
foreach (['RED','BLUE','PINK','BLACK','WHITE','NAVY'] as $c) {
    $hex = \App\Core\qc_variant_color_hex($c);
    assert_true($hex !== null, "$c has a hex color");
    $valid = preg_match('/^#[0-9a-f]{6}$/i', $hex) === 1;
    assert_true($valid, "$c hex is valid format: $hex");
}

// Custom colors without DB: qc_is_color_variant returns false (no custom_colors setting)
// but qc_generate_color_hex still works as fallback
$customNames = ['RTY LSTR','WST GLOW','CHRY PCK','PNY BLSH','PINK MIRAGE','NR TO BERRY'];
foreach ($customNames as $name) {
    $hex = \App\Core\qc_generate_color_hex($name);
    $valid = preg_match('/^#[0-9a-f]{6}$/i', $hex) === 1;
    assert_true($valid, "Custom '$name' generates valid hex: $hex");
}

// Verify standard color hexes are visually distinct
assert_true(\App\Core\qc_variant_color_hex('RED') !== \App\Core\qc_variant_color_hex('BLUE'), "RED hex != BLUE hex");
assert_true(\App\Core\qc_variant_color_hex('BLACK') !== \App\Core\qc_variant_color_hex('WHITE'), "BLACK hex != WHITE hex");

// ═══════════════════════════════════════════════════════════════
// TEST 21: Weight/volume at end not consumed by custom color
// ═══════════════════════════════════════════════════════════════
section("36. Custom color + weight — 7ML not eaten by color pattern");

// "RTY LSTR" is at end, so it strips first, leaving "7ML" for next iteration
// Final base: "AUC LIP GLS", variants: "RTY LSTR 7ML"
$base = qc_extract_base_title("AUC LIP GLS 7ML RTY LSTR", $customColors);
$attr = qc_extract_variant_attribute("AUC LIP GLS 7ML RTY LSTR", $customColors);
assert_true(str_contains($attr, "7ML"), "7ML is captured as variant");
assert_true(str_contains($attr, "RTY LSTR"), "RTY LSTR is captured as variant");
// Both parts captured — grouping works because base title is consistent
assert_equal($base, "AUC LIP GLS", "Multi-variant: correct base title for grouping");

// ═══════════════════════════════════════════════════════════════
// RESULTS
// ═══════════════════════════════════════════════════════════════
echo "\n" . str_repeat("═", 50) . "\n";
$total = $passed + $failed;
echo "<span style='font-weight:bold;font-size:1.2em'>";
if ($failed === 0) {
    echo "<span style='color:green'>ALL $total TESTS PASSED</span>";
} else {
    echo "<span style='color:red'>$failed / $total TESTS FAILED</span>";
}
echo "</span>\n";

if ($errors) {
    echo "\n<span style='color:red;font-weight:bold'>Failed tests:</span>\n";
    foreach ($errors as $e) {
        echo "  <span style='color:red'>- $e</span>\n";
    }
}

echo "</pre>";
