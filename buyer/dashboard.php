<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['order_action'];

    $allowed_actions = [
        'refund' => ['from' => 'completed', 'to' => 'refund_requested']
    ];

    if (isset($allowed_actions[$action])) {
        $rule = $allowed_actions[$action];
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT status, created_at, completed_at FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        $completed_at = $order ? ($order['completed_at'] ?? $order['created_at'] ?? null) : null;
        $is_refund_window_open = $completed_at && strtotime($completed_at) >= strtotime('-7 days');

        if ($order && $order['status'] === $rule['from'] && $is_refund_window_open) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$rule['to'], $order_id, $user_id]);
        }

        $pdo->commit();
    }

    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT
        o.*,
        (
            SELECT COUNT(DISTINCT pv.product_id)
            FROM order_items oi
            JOIN product_variations pv ON pv.id = oi.variation_id
            LEFT JOIN reviews r
                ON r.order_id = o.id
                AND r.product_id = pv.product_id
                AND r.user_id = o.user_id
            WHERE oi.order_id = o.id AND r.id IS NULL
        ) AS pending_review_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<style>
    .order-actions {
        display: flex;
        min-width: 250px;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .order-action-button {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        padding: 0 14px;
        border: 1px solid transparent;
        border-radius: 6px;
        font-family: var(--typography-body-font);
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .order-action-button--primary {
        background: var(--colors-ink);
        color: #fff;
    }

    .order-action-button--primary:hover {
        background: var(--colors-primary);
        color: #fff;
    }

    .order-action-button--refund {
        border-color: #e4aaa4;
        background: #fff;
        color: var(--colors-error);
    }

    .order-action-button--refund:hover {
        border-color: var(--colors-error);
        background: #fff5f4;
    }

    .order-action-state {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--colors-muted);
        font-size: 12px;
        line-height: 1.4;
        text-align: right;
    }

    .order-action-state--reviewed {
        color: var(--colors-success);
        font-weight: 600;
    }

    .refund-dialog {
        position: fixed;
        inset: 0;
        width: min(440px, calc(100% - 32px));
        max-height: calc(100vh - 32px);
        margin: auto;
        padding: 0;
        border: 1px solid var(--colors-hairline-soft);
        border-radius: 8px;
        background: var(--colors-surface);
        color: var(--colors-ink);
        box-shadow: 0 24px 70px rgba(20, 20, 19, 0.22);
    }

    .refund-dialog::backdrop {
        background: rgba(20, 20, 19, 0.48);
    }

    .refund-dialog__body {
        padding: 28px;
    }

    .refund-dialog__eyebrow {
        margin-bottom: 8px;
        color: var(--colors-error);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .refund-dialog h2 {
        margin: 0 0 10px;
        font-family: var(--typography-display-font);
        font-size: 30px;
        font-weight: 500;
        letter-spacing: 0;
    }

    .refund-dialog p {
        margin: 0;
        color: var(--colors-body);
        font-size: 14px;
        line-height: 1.6;
    }

    .refund-dialog__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
    }

    @media (max-width: 760px) {
        .order-actions {
            min-width: 190px;
            flex-direction: column;
            align-items: stretch;
        }

        .order-action-state {
            justify-content: flex-end;
        }

        .refund-dialog__actions {
            flex-direction: column-reverse;
        }

        .refund-dialog__actions .order-action-button {
            width: 100%;
        }
    }
</style>

<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Personal Collection</div>
                <h1 style="margin: 0; font-size: 40px;">Order History</h1>
            </div>
        </header>

        <div class="table-container">
            <?php if ($orders): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th style="text-align: right;">Fulfillment</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-family: var(--typography-code-font); font-size: 13px;">
                                    <a href="order_success.php?id=<?php echo $order['id']; ?>&view=details" style="color: var(--colors-ink); font-weight: 600; text-decoration: underline;">
                                        #ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                                    </a>
                                </td>
                                <td style="font-weight: 500;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="text-align: right;">
                                    <span class="badge badge-<?php echo ($order['status'] == 'completed' ? 'success' : (in_array($order['status'], ['cancelled', 'refunded']) ? 'error' : 'pending')); ?>">
                                        <?php echo strtoupper($order['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($order['status'] == 'completed'): ?>
                                        <?php
                                            $completed_at = $order['completed_at'] ?? $order['created_at'];
                                            $refund_available = strtotime($completed_at) >= strtotime('-7 days');
                                        ?>
                                        <div class="order-actions">
                                            <?php if ((int)$order['pending_review_count'] > 0): ?>
                                                <a href="write_review.php?order_id=<?php echo $order['id']; ?>" class="order-action-button order-action-button--primary">
                                                    Review <?php echo (int)$order['pending_review_count'] === 1 ? 'item' : 'items'; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="order-action-state order-action-state--reviewed">&#10003; Reviewed</span>
                                            <?php endif; ?>

                                            <?php if ($refund_available): ?>
                                                <button
                                                    type="button"
                                                    class="order-action-button order-action-button--refund refund-trigger"
                                                    data-order-id="<?php echo $order['id']; ?>"
                                                    data-order-reference="#ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>"
                                                >Request refund</button>
                                            <?php else: ?>
                                                <span class="order-action-state">Refund window expired</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($order['status'] == 'refund_requested'): ?>
                                            <span class="order-action-state">Refund pending manager approval</span>
                                        <?php else: ?>
                                            <span class="order-action-state">No action available</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: var(--spacing-xxl); text-align: center;">
                    <div style="font-size: 48px; margin-bottom: var(--spacing-md); opacity: 0.2;">🛍️</div>
                    <p style="color: var(--colors-muted); font-size: 16px;">You haven't placed any orders yet.</p>
                    <a href="../products.php" class="button-primary" style="margin-top: var(--spacing-lg);">Browse Collection</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<dialog id="refund-dialog" class="refund-dialog" aria-labelledby="refund-dialog-title">
    <form method="POST" id="refund-request-form" class="refund-dialog__body">
        <input type="hidden" name="order_id" id="refund-order-id" value="">
        <input type="hidden" name="order_action" value="refund">
        <div class="refund-dialog__eyebrow">Refund request</div>
        <h2 id="refund-dialog-title">Request a refund?</h2>
        <p>
            Your request for <strong id="refund-order-reference"></strong> will be sent to a manager for approval. The order status will update after it is reviewed.
        </p>
        <div class="refund-dialog__actions">
            <button type="button" id="refund-dialog-cancel" class="order-action-button order-action-button--refund">Keep order</button>
            <button type="submit" class="order-action-button order-action-button--primary">Send request</button>
        </div>
    </form>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dialog = document.getElementById('refund-dialog');
        const orderIdInput = document.getElementById('refund-order-id');
        const orderReference = document.getElementById('refund-order-reference');
        const cancelButton = document.getElementById('refund-dialog-cancel');

        document.querySelectorAll('.refund-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                orderIdInput.value = button.dataset.orderId;
                orderReference.textContent = button.dataset.orderReference;
                dialog.showModal();
            });
        });

        cancelButton.addEventListener('click', function () {
            dialog.close();
        });

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });
</script>

<?php include $include_path . 'footer.php'; ?>
