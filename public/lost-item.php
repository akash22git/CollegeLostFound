<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/navigation.php';
require_once __DIR__ . '/../app/validation.php';

requireLogin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $itemName = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $itemDate = $_POST['item_date'] ?? '';

    $imagePath = null;

    if (
        $itemName === '' ||
        $category === '' ||
        $location === '' ||
        $itemDate === ''
    ) {

        $message = 'Please fill in all required fields.';

    } elseif (!isValidReportDate($itemDate)) {

        $message = 'Please enter a valid date that is today or earlier.';

    } else {

    if (
    isset($_FILES['image']) &&
    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $message = 'There was a problem uploading the image.';
    } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        $message = 'Image must be 5 MB or smaller.';
    } else {

        $tmpFile = $_FILES['image']['tmp_name'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpFile);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mimeType])) {

            $message = 'Only JPG, PNG, and WebP images are allowed.';

        } else {

            $extension = $allowedTypes[$mimeType];

            $newFileName = bin2hex(random_bytes(16))
                . '.'
                . $extension;

            $uploadDirectory = __DIR__ . '/uploads/';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $destination = $uploadDirectory . $newFileName;

            if (!move_uploaded_file($tmpFile, $destination)) {

                $message = 'Unable to save the uploaded image.';

            } else {

                $imagePath = $newFileName;
            }
        }
    }
}

        if ($message === '') {
        $stmt = $pdo->prepare(
    'INSERT INTO items
    (user_id, type, item_name, category, description, location, item_date, image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmt->execute([
    $_SESSION['user_id'],
    'lost',
    $itemName,
    $category,
    $description,
    $location,
    $itemDate,
    $imagePath
]);

        $message = 'Lost item reported successfully!';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container-sm">
        <div class="custom-card">
            <div class="page-header text-center mb-4">
                <div class="feature-icon feature-icon-lost mx-auto mb-2" style="width: 50px; height: 50px; font-size: 1.4rem;">
                    <i class="bi bi-exclamation-diamond-fill"></i>
                </div>
                <h1>Report Lost Item</h1>
                <p class="page-subtitle">Submit details about the item you misplaced on campus</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-custom <?= str_contains($message, 'successfully') ? 'alert-success' : 'alert-danger' ?>" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="item_name" class="form-label">Item Name *</label>
                    <input
                        type="text"
                        id="item_name"
                        name="item_name"
                        class="form-control"
                        placeholder="e.g. Blue Dell Laptop Charger"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <option value="Mobile">Mobile</option>
                        <option value="Wallet">Wallet</option>
                        <option value="ID Card">ID Card</option>
                        <option value="Book">Book</option>
                        <option value="Bag">Bag</option>
                        <option value="Accessories">Accessories</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location" class="form-label">Lost Location *</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        class="form-control"
                        placeholder="e.g. Central Library 2nd Floor, Science Block"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="item_date" class="form-label">Date Lost *</label>
                    <input
                        type="date"
                        id="item_date"
                        name="item_date"
                        class="form-control"
                        max="<?= date('Y-m-d') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description (Optional)</label>
                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        placeholder="Provide identifying features, brand, color, stickers, etc."
                        rows="4"
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="image" class="form-label">Item Image (Optional, max 5 MB)</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-send-fill"></i> Submit Lost Report
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <a href="/my-reports.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-journal-text"></i> View My Reports
                </a>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
