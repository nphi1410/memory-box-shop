<?php
require_once __DIR__ . '/includes/helpers.php';

session_unset();
session_destroy();
session_start();
flash('success', 'Bạn đã đăng xuất.');
redirect('index.php');
