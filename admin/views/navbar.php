<?php
declare(strict_types=1);
$adminUser = admin_get_logged_in_user();
?>
<header class="app-header">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn-sidebar-toggle d-lg-none" id="sidebar-toggle-btn" onclick="toggleMobileSidebar()" aria-label="Toggle Navigation">
            <i class="fas fa-bars"></i>
        </button>
        <div class="brand-container">
            <div class="brand-logo">✦</div>
            <span>Dhani<span>.win</span> Admin</span>
        </div>
    </div>
    <div class="header-meta">
        <span class="badge-db d-none d-sm-inline">DB: <?php echo htmlspecialchars(api_db_driver()); ?></span>
        <?php if ($adminUser): ?>
            <span class="text-secondary d-none d-md-inline">
                Welcome, <strong><?php echo htmlspecialchars($adminUser['username']); ?></strong> 
                (<span class="text-gold"><?php echo htmlspecialchars($adminUser['role_label'] ?? 'Admin'); ?></span>)
            </span>
        <?php endif; ?>
        <a href="/admin/?logout=1" class="btn-secondary-premium btn-sm-mobile">
            <i class="fas fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Logout</span>
        </a>
    </div>
</header>
<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeMobileSidebar()"></div>
