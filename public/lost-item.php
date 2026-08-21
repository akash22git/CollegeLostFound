<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/auth.php';

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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Report Lost Item</title>

</head>

<body>

    <h1>College Lost & Found</h1>

    <h2>Report Lost Item</h2>

    <?php if ($message !== ''): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <form method="POST" enctype="multipart/form-data">

        <label for="item_name">
            Item Name
        </label>
        <br>

        <input
            type="text"
            id="item_name"
            name="item_name"
            required
        >

        <br><br>


        <label for="category">
            Category
        </label>
        <br>

        <select
            id="category"
            name="category"
            required
        >

            <option value="">
                Select Category
            </option>

            <option value="Mobile">
                Mobile
            </option>

            <option value="Wallet">
                Wallet
            </option>

            <option value="ID Card">
                ID Card
            </option>

            <option value="Book">
                Book
            </option>

            <option value="Bag">
                Bag
            </option>

            <option value="Accessories">
                Accessories
            </option>

            <option value="Other">
                Other
            </option>

        </select>

        <br><br>


        <label for="description">
            Description
        </label>
        <br>

        <textarea
            id="description"
            name="description"
            rows="5"
            cols="40"
        ></textarea>

        <br><br>


        <label for="location">
            Location
        </label>
        <br>

        <input
            type="text"
            id="location"
            name="location"
            required
        >

        <br><br>


        <label for="item_date">
            Date Lost
        </label>
        <br>

        <input
            type="date"
            id="item_date"
            name="item_date"
            required
        >

        <br><br>


        <label for="image">
    Item Image
</label>
<br>

<input
    type="file"
    id="image"
    name="image"
    accept="image/jpeg,image/png,image/webp"
>

<br><br>


        <button type="submit">
            Report Lost Item
        </button>

    </form>

</body>

</html>