<?php
// Unified staff authentication - delegates to shared root includes/auth.php
require __DIR__ . '/../../includes/auth.php';
initStaffSession('dire_staff');
