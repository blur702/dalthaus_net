<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?> - CMS Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <?php if (isset($extraStyles)): ?>
    <?= $extraStyles ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">