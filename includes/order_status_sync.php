<?php
function syncOrderStatuses(PDO $pdo): void
{
    try {
        $pdo->exec("
            UPDATE orders
            SET
                status = CASE
                    WHEN created_at <= DATE_SUB(NOW(), INTERVAL 4 MINUTE) THEN 'completed'
                    WHEN created_at <= DATE_SUB(NOW(), INTERVAL 3 MINUTE) THEN 'shipped'
                    WHEN created_at <= DATE_SUB(NOW(), INTERVAL 2 MINUTE) THEN 'processing'
                    ELSE status
                END,
                completed_at = CASE
                    WHEN created_at <= DATE_SUB(NOW(), INTERVAL 4 MINUTE) AND completed_at IS NULL THEN NOW()
                    ELSE completed_at
                END
            WHERE status IN ('pending', 'processing', 'shipped')
              AND (
                    (status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL 2 MINUTE))
                 OR (status = 'processing' AND created_at <= DATE_SUB(NOW(), INTERVAL 3 MINUTE))
                 OR (status = 'shipped' AND created_at <= DATE_SUB(NOW(), INTERVAL 4 MINUTE))
              )
        ");
    } catch (Throwable $e) {
        // Setup or migration pages may run before the orders table exists.
    }
}
