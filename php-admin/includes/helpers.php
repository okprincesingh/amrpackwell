<?php
/**
 * Turn "How Do Pine Wooden Pallets Work?" into "how-do-pine-wooden-pallets-work"
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . time();
}

/** Make a slug unique in the blogs table (appends -2, -3, ... if needed). */
function unique_blog_slug(string $baseSlug, ?int $ignoreId = null): string
{
    $slug = $baseSlug;
    $i = 2;

    while (true) {
        $sql = 'SELECT id FROM blogs WHERE slug = ?' . ($ignoreId ? ' AND id != ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
}

/** Make a slug unique in the products table (appends -2, -3, ... if needed). */
function unique_product_slug(string $baseSlug, ?int $ignoreId = null): string
{
    $slug = $baseSlug;
    $i = 2;

    while (true) {
        $sql = 'SELECT id FROM products WHERE slug = ?' . ($ignoreId ? ' AND id != ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
}

/** Make a slug unique in the categories table (appends -2, -3, ... if needed). */
function unique_category_slug(string $baseSlug, ?int $ignoreId = null): string
{
    $slug = $baseSlug;
    $i = 2;

    while (true) {
        $sql = 'SELECT id FROM categories WHERE slug = ?' . ($ignoreId ? ' AND id != ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
}


/** Verify a Google reCAPTCHA v2 response token server-side. Returns true if the check passed. */
function verify_recaptcha(?string $token): bool
{
    if (!$token) {
        return false;
    }

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);
    return !empty($result['success']);
}