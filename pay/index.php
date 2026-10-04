<?php
require_once dirname(__DIR__) . '/api/_bootstrap.php';

$orderNo = $_GET['orderNo'] ?? '';
$successParam = isset($_GET['success']);
$cancelledParam = isset($_GET['cancelled']);

$pdo = api_pdo();
$order = null;
if ($pdo && !empty($orderNo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM recharge_orders WHERE order_no = ? LIMIT 1");
        $stmt->execute([$orderNo]);
        $order = $stmt->fetch();
    } catch (Throwable $e) {
        $order = null;
    }
}
if (!$order && !empty($orderNo)) {
    $order = api_recharge_file_find($orderNo);
}

// If no order found, terminate
if (!$order) {
    http_response_code(404);
    echo '<div style="background:#070913;color:#ff8080;padding:2rem;text-align:center;font-family:sans-serif;height:100vh;display:flex;align-items:center;justify-content:center;"><h3>Error: Recharge order not found.</h3></div>';
    exit;
}

// Fetch active gateway
$methods = api_payment_methods(true);
$method = $methods[0] ?? [
    'method_name' => 'PhonePe',
    'account_name' => 'Dhani Win',
    'account_value' => 'rajputajay22266-1@oksbi',
    'qr_text' => 'upi://pay?pa=rajputajay22266-1@oksbi&pn=Dhani%20Win&cu=INR'
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_utr'])) {
        $utr = trim($_POST['utr'] ?? '');
        if (!preg_match('/^\d{12}$/', $utr)) {
            $error = 'Please enter a valid 12-digit UTR transaction ID.';
        } else {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE recharge_orders SET utr = ?, status = 'PendingReview', updated_at = CURRENT_TIMESTAMP WHERE order_no = ?");
                    $stmt->execute([$utr, $orderNo]);
                } catch (Throwable $e) {
                    $error = 'Failed to update order in database: ' . $e->getMessage();
                }
            }
            api_recharge_file_update($orderNo, ['utr' => $utr, 'status' => 'PendingReview']);
            
            if (empty($error)) {
                header('Location: /pay/?orderNo=' . urlencode($orderNo) . '&success=1');
                exit;
            }
        }
    } elseif (isset($_POST['cancel_order'])) {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE recharge_orders SET status = 'Cancel', updated_at = CURRENT_TIMESTAMP WHERE order_no = ?");
                $stmt->execute([$orderNo]);
            } catch (Throwable $e) {
                $error = 'Failed to cancel order in database: ' . $e->getMessage();
            }
        }
        api_recharge_file_update($orderNo, ['status' => 'Cancel']);
        
        if (empty($error)) {
            header('Location: /pay/?orderNo=' . urlencode($orderNo) . '&cancelled=1');
            exit;
        }
    }
}

$status = $order['status'];
$amount = (float)$order['amount'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dhani Win - Premium Payment Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #090815;
            --accent-gold: #ffb834;
            --accent-gold-hover: #e09e24;
            --accent-gold-gradient: linear-gradient(135deg, #ffd370 0%, #fca524 100%);
            --glass-bg: rgba(25, 23, 48, 0.65);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-light: #f5f6fa;
            --text-muted: #a3a1be;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at 10% 20%, rgba(98, 20, 163, 0.15) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(252, 165, 36, 0.08) 0%, transparent 40%);
            padding: 1.5rem;
        }

        .gateway-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
        }

        .gateway-header {
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--glass-border);
        }

        .gateway-logo {
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--accent-gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.2rem;
            letter-spacing: 0.5px;
        }

        .gateway-body {
            padding: 2rem;
        }

        .amount-box {
            background: rgba(255, 255, 255, 0.04);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            border: 1px dashed rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        .amount-val {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .amount-val span {
            color: var(--accent-gold);
        }

        .qr-container {
            background: white;
            padding: 1rem;
            border-radius: 20px;
            width: fit-content;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease;
        }

        .qr-container:hover {
            transform: scale(1.03);
        }

        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--accent-gold);
            cursor: pointer;
            padding: 0.2rem;
            font-size: 1rem;
            transition: color 0.2s, transform 0.1s;
        }

        .copy-btn:hover {
            color: #fff;
            transform: scale(1.1);
        }

        .copy-btn:active {
            transform: scale(0.9);
        }

        .form-premium-input {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            color: #fff;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-premium-input:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(252, 165, 36, 0.2);
            color: #fff;
        }

        .btn-gold {
            background: var(--accent-gold-gradient);
            color: #090815;
            border: none;
            font-weight: 600;
            border-radius: 14px;
            padding: 0.9rem;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(252, 165, 36, 0.25);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(252, 165, 36, 0.35);
            background: linear-gradient(135deg, #ffe090 0%, #f79510 100%);
            color: #090815;
        }

        .btn-gold:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 14px;
            padding: 0.75rem;
            transition: background 0.2s, color 0.2s;
        }

        .btn-cancel:hover {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.5);
        }

        .countdown-timer {
            font-size: 0.95rem;
            color: var(--accent-gold);
            font-weight: 600;
            background: rgba(252, 165, 36, 0.1);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .toast-notification {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(25, 23, 48, 0.95);
            border: 1px solid var(--accent-gold);
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
            z-index: 9999;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-pending {
            background: rgba(252, 165, 36, 0.15);
            color: #ffd075;
            border: 1px solid rgba(252, 165, 36, 0.25);
        }

        .badge-review {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .badge-approved {
            background: rgba(16, 185, 129, 0.15);
            color: #a7f3d0;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .instruction-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.25rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .instruction-box ol {
            padding-left: 1.2rem;
            margin: 0;
        }

        .instruction-box li {
            margin-bottom: 0.5rem;
        }

        .instruction-box li::marker {
            color: var(--accent-gold);
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="gateway-card">
        <div class="gateway-header">
            <div class="gateway-logo">Dhani Win Gateway</div>
            <div class="text-secondary small">Manual Recharge Checkout</div>
            
            <?php if (strtolower($status) === 'pending' && !$successParam && !$cancelledParam): ?>
                <div class="countdown-timer">
                    <i class="far fa-clock"></i> <span id="countdown">15:00</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="gateway-body">
            <?php if ($successParam): ?>
                <div class="text-center py-4">
                    <div class="text-success mb-3" style="font-size: 4rem;"><i class="fas fa-check-circle"></i></div>
                    <h4 class="fw-bold text-white mb-2">UTR Submitted Successfully</h4>
                    <p class="text-secondary small px-3">Your transaction UTR has been submitted to admin. The balance will be added automatically to your account balance once verified.</p>
                    <div class="badge-review status-badge d-inline-block mt-3">Under Admin Review</div>
                    
                    <a href="/#/recharge" class="btn btn-gold w-100 mt-5">Go Back to App</a>
                </div>
            <?php elseif ($cancelledParam || strtolower($status) === 'cancel'): ?>
                <div class="text-center py-4">
                    <div class="text-danger mb-3" style="font-size: 4rem;"><i class="fas fa-times-circle"></i></div>
                    <h4 class="fw-bold text-white mb-2">Recharge Order Cancelled</h4>
                    <p class="text-secondary small px-3">This payment request has been cancelled by the user. You can safely close this window.</p>
                    <div class="badge-cancelled status-badge d-inline-block mt-3">Cancelled</div>
                    
                    <a href="/#/recharge" class="btn btn-gold w-100 mt-5">Go Back to App</a>
                </div>
            <?php elseif (strtolower($status) === 'approved' || strtolower($status) === 'success' || strtolower($status) === 'completed' || strtolower($status) === 'paid'): ?>
                <div class="text-center py-4">
                    <div class="text-success mb-3" style="font-size: 4rem;"><i class="fas fa-check-double"></i></div>
                    <h4 class="fw-bold text-white mb-2">Payment Completed</h4>
                    <p class="text-secondary small px-3">This order has been verified and completed successfully. The funds are added to your balance.</p>
                    <div class="badge-approved status-badge d-inline-block mt-3">Approved / Completed</div>
                    
                    <a href="/#/recharge" class="btn btn-gold w-100 mt-5">Go Back to App</a>
                </div>
            <?php else: ?>
                <!-- Standard Payment View -->
                <div class="amount-box">
                    <div class="text-secondary small mb-1">Total Payment Amount</div>
                    <div class="amount-val">
                        ₹<?php echo number_format($amount, 2); ?>
                        <button class="copy-btn" onclick="copyText('<?php echo $amount; ?>', 'Amount copied to clipboard')" title="Copy Amount">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <div class="text-secondary small mb-2">Scan QR code using PhonePe, Paytm, or GooglePay</div>
                    <div class="qr-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($method['qr_text']); ?>" alt="Scan to Pay">
                    </div>
                </div>

                <div class="info-row">
                    <span class="info-label">UPI ID Address</span>
                    <span class="info-value">
                        <span id="upi-val"><?php echo htmlspecialchars($method['account_value']); ?></span>
                        <button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($method['account_value']); ?>', 'UPI ID copied to clipboard')" title="Copy UPI ID">
                            <i class="far fa-copy"></i>
                        </button>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Account Holder</span>
                    <span class="info-value"><?php echo htmlspecialchars($method['account_name']); ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Order Number</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($orderNo); ?>
                        <button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($orderNo); ?>', 'Order number copied')" title="Copy Order Number">
                            <i class="far fa-copy"></i>
                        </button>
                    </span>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 text-start py-2 px-3 mb-3 small" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border-radius: 10px;">
                        <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="mt-4">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold mb-1">Enter 12-Digit UTR / Transaction ID after payment</label>
                        <input name="utr" type="text" maxlength="12" pattern="\d{12}" class="form-control form-premium-input text-center" placeholder="e.g. 512398457612" required>
                    </div>

                    <button type="submit" name="submit_utr" class="btn btn-gold w-100 py-3 mb-3 d-flex align-items-center justify-content-center gap-2">
                        Submit UTR <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <form method="post" onsubmit="return confirm('Are you sure you want to cancel this payment request?')">
                    <button type="submit" name="cancel_order" class="btn btn-cancel w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        Cancel Payment Request <i class="fas fa-times"></i>
                    </button>
                </form>

                <div class="instruction-box">
                    <h6 class="fw-bold text-white mb-2"><i class="fas fa-info-circle text-gold me-1"></i> Instructions:</h6>
                    <ol>
                        <li>Open your payment app and scan the QR code, or copy the UPI ID address.</li>
                        <li>Submit the exact amount: <strong>₹<?php echo number_format($amount, 2); ?></strong>.</li>
                        <li>After payment succeeds, check details to copy the 12-digit UTR/Ref number.</li>
                        <li>Paste it inside the field above and click "Submit UTR". The request will be reviewed by admin within 5-10 minutes.</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-notify" class="toast-notification">
        <i class="fas fa-circle-check text-gold"></i> <span id="toast-text">Copied successfully</span>
    </div>

    <script>
        function copyText(text, message) {
            navigator.clipboard.writeText(text).then(function() {
                var toast = document.getElementById('toast-notify');
                var textSpan = document.getElementById('toast-text');
                textSpan.innerText = message;
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 2000);
            }).catch(function() {
                // Fallback copy
                var input = document.createElement('textarea');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                
                var toast = document.getElementById('toast-notify');
                var textSpan = document.getElementById('toast-text');
                textSpan.innerText = message;
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 2000);
            });
        }

        <?php if (strtolower($status) === 'pending' && !$successParam && !$cancelledParam): ?>
        // 15 Minutes Countdown Timer
        var duration = 900; // 15 mins
        var timerSpan = document.getElementById('countdown');
        var interval = setInterval(function() {
            var minutes = Math.floor(duration / 60);
            var seconds = duration % 60;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            timerSpan.innerText = minutes + ':' + seconds;
            duration--;

            if (duration < 0) {
                clearInterval(interval);
                alert('Payment window has expired. Please initiate a new recharge.');
                window.location.reload();
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
