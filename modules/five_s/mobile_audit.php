<?php
// modules/five_s/mobile_audit.php - Routed to unified 5S Master Hub (Tab: Audit)
if (!defined('INDEX_AUTH')) { define('INDEX_AUTH', true); }
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/check_permission.php';

requirePermission('five_s.audit');

$initialTab = 'audit';
require __DIR__ . '/overview.php';
