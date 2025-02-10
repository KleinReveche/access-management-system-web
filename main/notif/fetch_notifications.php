<?php
include_once __DIR__ . '/../../database/database.php';

if (isset($_GET['count_only']) && filter_var($_GET['count_only'], FILTER_VALIDATE_BOOLEAN)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE is_read = 0");
    $stmt->execute();
    echo $stmt->fetchColumn();
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, content, created_at, is_read FROM announcements WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($notifications)) {
    echo '<div class="p-2 text-center">No notifications available.</div>';
} else {
    foreach ($notifications as $notification):
        $badge = ($notification['is_read'] == 0)
            ? '<span class="badge bg-primary notification-status">New</span>'
            : '<span class="badge bg-secondary notification-status">Read</span>';
?>
<div class="notification-item <?= ($notification['is_read'] == 0) ? 'unread' : '' ?>" data-id="<?= $notification['id'] ?>">
    <div class="d-flex justify-content-between">
        <div>
            <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
            <div class="notification-timestamp"><?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?></div>
        </div>
        <?php if ($notification['is_read'] == 0): ?>
        <div class="mark-read">Mark as read</div>
        <?php endif; ?>
    </div>
    <div class="notification-content my-2">
        <?= nl2br(htmlspecialchars($notification['content'])) ?>
    </div>
    <?= $badge ?>
</div>
<?php
    endforeach;
}
?>
