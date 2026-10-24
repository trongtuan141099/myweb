<?php
// modules/five_s/list.php - Routed to unified 5S Master Hub (Tab: Issues)
if (!defined('INDEX_AUTH')) { define('INDEX_AUTH', true); }
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/check_permission.php';

requirePermission(['five_s.view', 'five_s.audit', 'five_s.settings']);

$initialTab = 'issues';
require __DIR__ . '/overview.php';
