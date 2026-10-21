<?php
/**
 * Headphones1.Duka — Email
 * sendMail() delivers through MAIL_TRANSPORT (log | mail | smtp, see config.php).
 * Email failures are logged and never stop the page that triggered them.
 */

// ============ DELIVERY ============

/**
 * Send an HTML email (a plain-text part is generated). Returns true on success.
 */
function sendMail(string $to, string $subject, string $html, ?string $replyTo = null): bool {
    $to      = trim(str_replace(["\r", "\n"], '', $to));
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $replyTo = $replyTo ? trim(str_replace(["\r", "\n"], '', $replyTo)) : null;
    if (!isValidEmail($to) || ($replyTo && !isValidEmail($replyTo))) return false;

    $fromEmail = MAIL_FROM ?: setting('store_email');
    $fromName  = storeName();
    $text      = htmlToText($html);

    try {
        return match (MAIL_TRANSPORT) {
            'smtp'  => smtpSend($to, $subject, $html, $text, $fromEmail, $fromName, $replyTo),
            'mail'  => phpMailSend($to, $subject, $html, $text, $fromEmail, $fromName, $replyTo),
            default => logMail($to, $subject, $text, $fromEmail, $replyTo),
        };
    } catch (Throwable $e) {
        error_log('Mail to ' . $to . ' failed: ' . $e->getMessage());
        return false;
    }
}

function htmlToText(string $html): string {
    $html = preg_replace('#<(br|/p|/div|/h[1-6]|/li|/tr)[^>]*>#i', "\n", $html);
    $html = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text)));
}

function encodeHeader(string $value): string {
    return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
}

/**
 * Full MIME message (headers + multipart body) shared by mail() and SMTP.
 */
function buildMimeMessage(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName, ?string $replyTo): array {
    $boundary = 'b' . bin2hex(random_bytes(12));
    $domain   = substr(strrchr($fromEmail, '@') ?: '@localhost', 1);
    $headers  = [
        'From: ' . encodeHeader($fromName) . " <$fromEmail>",
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(16)) . "@$domain>",
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
    ];
    if ($replyTo) $headers[] = "Reply-To: $replyTo";

    $body = "--$boundary\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($text))
          . "--$boundary\r\n"
          . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($html))
          . "--$boundary--\r\n";

    return [$headers, $body];
}

function logMail(string $to, string $subject, string $text, string $fromEmail, ?string $replyTo): bool {
    $entry = str_repeat('=', 72) . "\n"
           . 'Date:    ' . date('Y-m-d H:i:s') . "\n"
           . "From:    $fromEmail\nTo:      $to\n"
           . ($replyTo ? "Reply-To: $replyTo\n" : '')
           . "Subject: $subject\n\n$text\n\n";
    $dir = BASE_PATH . '/logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return file_put_contents("$dir/mail.log", $entry, FILE_APPEND | LOCK_EX) !== false;
}

function phpMailSend(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName, ?string $replyTo): bool {
    [$headers, $body] = buildMimeMessage($to, $subject, $html, $text, $fromEmail, $fromName, $replyTo);
    $headers = array_filter($headers, fn($h) => !str_starts_with($h, 'Date:'));
    return mail($to, encodeHeader($subject), $body, implode("\r\n", $headers), '-f' . $fromEmail);
}

/**
 * Minimal SMTP client: STARTTLS or implicit TLS, AUTH LOGIN.
 */
function smtpSend(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName, ?string $replyTo): bool {
    $secure = strtolower(SMTP_SECURE);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . (int) SMTP_PORT;
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]));
    if (!$socket) throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
    stream_set_timeout($socket, 15);

    $read = function () use ($socket): array {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return [(int) substr($data, 0, 3), $data];
    };
    $cmd = function (string $command, array $expect) use ($socket, $read): string {
        fwrite($socket, $command . "\r\n");
        [$code, $reply] = $read();
        if (!in_array($code, $expect, true)) {
            $shown = str_starts_with($command, 'AUTH') || strlen($command) > 60 ? strtok($command, ' ') : $command;
            throw new RuntimeException("SMTP '$shown' failed: " . trim($reply));
        }
        return $reply;
    };

    try {
        [$code, $greeting] = $read();
        if ($code !== 220) throw new RuntimeException('SMTP greeting: ' . trim($greeting));

        $helo = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
        $cmd("EHLO $helo", [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('SMTP STARTTLS negotiation failed');
            }
            $cmd("EHLO $helo", [250]);
        }
        if (SMTP_USER !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode(SMTP_USER), [334]);
            $cmd(base64_encode(SMTP_PASS), [235]);
        }

        [$headers, $body] = buildMimeMessage($to, $subject, $html, $text, $fromEmail, $fromName, $replyTo);
        $headers[] = "To: $to";
        $headers[] = 'Subject: ' . encodeHeader($subject);

        $cmd("MAIL FROM:<$fromEmail>", [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        // Base64 bodies never start a line with '.', so no dot-stuffing is needed
        $cmd(implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.", [250]);
        fwrite($socket, "QUIT\r\n");
        return true;
    } finally {
        fclose($socket);
    }
}

// ============ TEMPLATES ============

/**
 * Wrap content in the branded email layout. $content is trusted HTML;
 * escape any user data with e() before passing it in.
 */
function emailLayout(string $heading, string $content): string {
    $store = e(storeName());
    $home  = e(absoluteUrl('/'));
    $year  = date('Y');
    $contact = e(setting('store_email'));
    return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden">
<tr><td style="background:#0a0a0d;padding:20px 28px"><a href="$home" style="color:#00d4ff;font-size:18px;font-weight:bold;text-decoration:none">$store</a></td></tr>
<tr><td style="padding:28px">
<h1 style="margin:0 0 16px;font-size:20px;color:#111827">$heading</h1>
$content
</td></tr>
<tr><td style="padding:18px 28px;background:#f9fafb;font-size:12px;color:#6b7280">
Questions? Reply to this email or write to <a href="mailto:$contact" style="color:#0891b2">$contact</a>.<br>&copy; $year $store
</td></tr>
</table>
</td></tr>
</table>
</body></html>
HTML;
}

function emailButton(string $url, string $label): string {
    return '<p style="margin:24px 0"><a href="' . e($url) . '" style="display:inline-block;background:#00d4ff;color:#050507;font-weight:bold;padding:12px 22px;border-radius:8px;text-decoration:none">' . e($label) . '</a></p>';
}

/**
 * Items + totals table for order emails.
 */
function emailOrderSummary(array $order, array $items): string {
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:6px 0">' . e($item['product_name']) . ' &times; ' . (int) $item['quantity'] . '</td>'
              .  '<td style="padding:6px 0;text-align:right">' . e(formatPrice($item['price'] * $item['quantity'])) . '</td></tr>';
    }
    $line = fn($label, $value, $bold = false) => '<tr><td style="padding:4px 0;' . ($bold ? 'font-weight:bold' : 'color:#6b7280') . '">' . $label . '</td>'
        . '<td style="padding:4px 0;text-align:right;' . ($bold ? 'font-weight:bold' : '') . '">' . $value . '</td></tr>';

    $totals = $line('Subtotal', e(formatPrice($order['subtotal'])));
    if ((float) ($order['discount'] ?? 0) > 0) {
        $totals .= $line('Discount' . (!empty($order['coupon_code']) ? ' (' . e($order['coupon_code']) . ')' : ''), e(formatPrice(-$order['discount'])));
    }
    $totals .= $line('Shipping', (float) $order['shipping_cost'] > 0 ? e(formatPrice($order['shipping_cost'])) : 'Free');
    $totals .= $line(e(taxLabel()), e(formatPrice($order['tax'])));
    $totals .= $line('Total', e(formatPrice($order['total'])), true);

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;margin:16px 0">'
         . $rows . '<tr><td colspan="2" style="border-top:1px solid #e5e7eb;padding-top:6px"></td></tr>' . $totals . '</table>';
}

function emailPaymentBlock(array $order): string {
    $how = paymentInstructions($order['payment_method'], $order['order_number'], (float) $order['total']);
    if (!$how) return '<p style="font-size:14px">Payment: <strong>' . e(paymentMethodLabel($order['payment_method'])) . '</strong></p>';
    $steps = implode('', array_map(fn($s) => '<li style="margin:4px 0">' . e($s) . '</li>', $how['steps']));
    return '<div style="background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:14px 18px;font-size:14px">'
         . '<strong>' . e($how['title']) . '</strong><ol style="margin:8px 0 8px 18px;padding:0">' . $steps . '</ol>'
         . '<span style="color:#475569">' . e($how['note']) . '</span></div>';
}

/**
 * Load an order with its items for emails.
 */
function loadOrderForEmail(PDO $db, int $orderId): ?array {
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return null;
    $items = $db->prepare('SELECT product_name, quantity, price FROM order_items WHERE order_id = ?');
    $items->execute([$orderId]);
    $order['items'] = $items->fetchAll();
    return $order;
}

// ============ MESSAGES ============

function sendOrderConfirmation(PDO $db, int $orderId): void {
    $order = loadOrderForEmail($db, $orderId);
    if (!$order) return;
    $content = '<p style="font-size:14px">Hi ' . e(strtok($order['shipping_name'], ' ')) . ', thank you for your order! '
             . 'Your order number is <strong>' . e($order['order_number']) . '</strong>.</p>'
             . emailPaymentBlock($order)
             . emailOrderSummary($order, $order['items'])
             . '<p style="font-size:14px;color:#6b7280">Shipping to: ' . e($order['shipping_name']) . ', ' . e($order['shipping_address']) . ', '
             . e($order['shipping_city']) . ', ' . e($order['shipping_country']) . '<br>Estimated delivery: ' . e(setting('delivery_estimate')) . '</p>'
             . ($order['user_id'] ? emailButton(absoluteUrl('/account/order-view.php?id=' . $order['id']), 'View your order') : '');
    sendMail($order['shipping_email'], 'Order ' . $order['order_number'] . ' received — ' . storeName(), emailLayout('Thanks for your order', $content));
}

function sendAdminNewOrder(PDO $db, int $orderId): void {
    if (!settingOn('order_email_notify')) return;
    $order = loadOrderForEmail($db, $orderId);
    if (!$order) return;
    $content = '<p style="font-size:14px"><strong>' . e($order['shipping_name']) . '</strong> (' . e($order['shipping_email'])
             . ($order['shipping_phone'] ? ', ' . e($order['shipping_phone']) : '') . ') placed order <strong>' . e($order['order_number'])
             . '</strong> and chose <strong>' . e(paymentMethodLabel($order['payment_method'])) . '</strong>.</p>'
             . emailOrderSummary($order, $order['items'])
             . emailButton(absoluteUrl('/admin/order-view.php?id=' . $order['id']), 'Open in admin');
    sendMail(adminNotifyEmail(), 'New order ' . $order['order_number'] . ' — ' . formatPrice($order['total']), emailLayout('New order', $content), $order['shipping_email']);
}

function sendOrderStatusUpdate(PDO $db, int $orderId): void {
    $order = loadOrderForEmail($db, $orderId);
    if (!$order) return;
    $messages = [
        'processing' => 'We are preparing your order.',
        'shipped'    => 'Your order is on its way!',
        'delivered'  => 'Your order has been delivered. Enjoy!',
        'cancelled'  => 'Your order has been cancelled. If you already paid, we will contact you about a refund.',
    ];
    if (!isset($messages[$order['status']])) return;
    $content = '<p style="font-size:14px">' . e($messages[$order['status']]) . '</p>'
             . '<p style="font-size:14px">Order <strong>' . e($order['order_number']) . '</strong> is now <strong>' . e(ucfirst($order['status'])) . '</strong>.'
             . ($order['tracking_number'] ? '<br>Tracking number: <strong>' . e($order['tracking_number']) . '</strong>' : '') . '</p>'
             . ($order['user_id'] ? emailButton(absoluteUrl('/account/order-view.php?id=' . $order['id']), 'View your order') : '');
    sendMail($order['shipping_email'], 'Order ' . $order['order_number'] . ' is ' . $order['status'] . ' — ' . storeName(), emailLayout('Order update', $content));
}

function sendLowStockAlert(array $products): void {
    if (!settingOn('stock_email_notify') || !$products) return;
    $list = implode('', array_map(fn($p) => '<li>' . e($p['name']) . ' — ' . (int) $p['stock'] . ' left</li>', $products));
    $content = '<p style="font-size:14px">These products are at or below the low-stock threshold:</p><ul style="font-size:14px">' . $list . '</ul>'
             . emailButton(absoluteUrl('/admin/products.php'), 'Review inventory');
    sendMail(adminNotifyEmail(), 'Low stock alert — ' . storeName(), emailLayout('Low stock', $content));
}

function sendPasswordReset(string $email, string $name, string $resetUrl): void {
    $content = '<p style="font-size:14px">Hi ' . e(strtok($name, ' ')) . ', we received a request to reset your password.</p>'
             . emailButton($resetUrl, 'Choose a new password')
             . '<p style="font-size:13px;color:#6b7280">This link works once and expires in 60 minutes. If you did not ask for a reset, you can ignore this email — your password stays the same.</p>';
    sendMail($email, 'Reset your ' . storeName() . ' password', emailLayout('Reset your password', $content));
}

function sendContactNotification(string $name, string $email, string $subject, string $message): void {
    $content = '<p style="font-size:14px"><strong>' . e($name) . '</strong> (' . e($email) . ') wrote:</p>'
             . '<div style="font-size:14px;background:#f9fafb;border-radius:8px;padding:12px 16px">' . nl2br(e($message)) . '</div>'
             . emailButton(absoluteUrl('/admin/messages.php'), 'Open messages');
    sendMail(adminNotifyEmail(), 'Contact form: ' . $subject, emailLayout('New message', $content), $email);
}

function sendNewsletterWelcome(string $email, string $token): void {
    $code = strtoupper(setting('newsletter_coupon'));
    $offer = $code !== '' ? '<p style="font-size:14px">As promised, here is your welcome code for your first order:</p>'
        . '<p style="font-size:22px;font-weight:bold;letter-spacing:2px;margin:12px 0">' . e($code) . '</p>' : '';
    $content = '<p style="font-size:14px">Thanks for subscribing! You will be the first to hear about new releases and offers.</p>'
             . $offer . emailButton(absoluteUrl('/shop.php'), 'Shop now')
             . '<p style="font-size:12px;color:#9ca3af">Not for you? <a href="' . e(absoluteUrl('/newsletter-unsubscribe.php?token=' . $token)) . '" style="color:#9ca3af">Unsubscribe</a>.</p>';
    sendMail($email, 'Welcome to ' . storeName(), emailLayout('You are subscribed', $content));
}
