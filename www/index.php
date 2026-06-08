<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'mydatabase';
$username = getenv('DB_USERNAME') ?: 'bitrix';
$password = getenv('DB_PASSWORD') ?: '+Tr+()8]!szl[HQIsoT5';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $host,
    $port,
    $database
);

$error = null;
$items = [];

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS shopping_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            is_bought TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $count = (int) $pdo->query('SELECT COUNT(*) FROM shopping_items')->fetchColumn();
    if ($count === 0) {
        $seed = $pdo->prepare(
            'INSERT INTO shopping_items (name, quantity, is_bought) VALUES (:name, :quantity, :is_bought)'
        );
        foreach ([
            ['Молоко', 2, 0],
            ['Хлеб', 1, 0],
            ['Яйца', 10, 0],
            ['Сыр', 1, 0],
            ['Масло', 1, 0],
        ] as [$name, $quantity, $isBought]) {
            $seed->execute([
                'name' => $name,
                'quantity' => $quantity,
                'is_bought' => $isBought,
            ]);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

            if ($name !== '') {
                $stmt = $pdo->prepare(
                    'INSERT INTO shopping_items (name, quantity, is_bought) VALUES (:name, :quantity, 0)'
                );
                $stmt->execute(['name' => $name, 'quantity' => $quantity]);
            }
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
            $isBought = isset($_POST['is_bought']) ? 1 : 0;

            if ($id > 0 && $name !== '') {
                $stmt = $pdo->prepare(
                    'UPDATE shopping_items
                     SET name = :name, quantity = :quantity, is_bought = :is_bought
                     WHERE id = :id'
                );
                $stmt->execute([
                    'id' => $id,
                    'name' => $name,
                    'quantity' => $quantity,
                    'is_bought' => $isBought,
                ]);
            }
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id > 0) {
                $stmt = $pdo->prepare('DELETE FROM shopping_items WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
        }

        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }

    $items = $pdo
        ->query('SELECT id, name, quantity, is_bought, created_at FROM shopping_items ORDER BY is_bought ASC, id ASC')
        ->fetchAll();
} catch (Throwable $exception) {
    http_response_code(500);
    $error = $exception->getMessage();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Список покупок</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1a1a2e;
            background: #f0f2f5;
            line-height: 1.5;
        }

        main {
            max-width: 720px;
            margin: 40px auto;
            padding: 0 16px 40px;
        }

        h1 {
            margin: 0 0 24px;
            font-size: 28px;
            font-weight: 700;
        }

        .panel {
            background: #fff;
            border: 1px solid #e2e6ea;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .panel h2 {
            margin: 0 0 16px;
            font-size: 16px;
            font-weight: 600;
            color: #5c6370;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 13px;
            color: #5c6370;
            flex: 1;
            min-width: 120px;
        }

        input[type="text"],
        input[type="number"] {
            padding: 10px 12px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            font-size: 15px;
            width: 100%;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #4f6ef7;
            box-shadow: 0 0 0 3px rgba(79, 110, 247, 0.15);
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: #4f6ef7;
            color: #fff;
        }

        .btn-primary:hover {
            background: #3d5ce5;
        }

        .btn-danger {
            background: #fff;
            color: #c0392b;
            border: 1px solid #e8b4b0;
        }

        .btn-danger:hover {
            background: #fdf0ef;
        }

        .btn-save {
            background: #27ae60;
            color: #fff;
        }

        .btn-save:hover {
            background: #219a52;
        }

        .item-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .item {
            border: 1px solid #e8ecf0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 10px;
            background: #fafbfc;
        }

        .item.bought {
            opacity: 0.65;
            background: #f4f6f8;
        }

        .item.bought .item-name input {
            text-decoration: line-through;
            color: #8a9199;
        }

        .item-form {
            display: grid;
            grid-template-columns: 1fr 80px auto auto;
            gap: 10px;
            align-items: center;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #5c6370;
            cursor: pointer;
            flex-direction: row;
            min-width: auto;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .empty {
            text-align: center;
            color: #8a9199;
            padding: 24px;
        }

        .error {
            color: #c0392b;
            background: #fdf0ef;
            border: 1px solid #e8b4b0;
            border-radius: 8px;
            padding: 16px;
        }

        @media (max-width: 560px) {
            .item-form {
                grid-template-columns: 1fr 1fr;
            }

            .item-form .btn-save,
            .item-form .btn-danger {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
<main>
    <h1>Список покупок</h1>

    <?php if ($error !== null): ?>
        <div class="panel error">
            <strong>Ошибка базы данных:</strong>
            <?= e($error) ?>
        </div>
    <?php else: ?>

        <section class="panel">
            <h2>Добавить продукт</h2>
            <form method="post" class="form-row">
                <input type="hidden" name="action" value="create">
                <label>
                    Название
                    <input type="text" name="name" placeholder="Например, помидоры" required maxlength="255">
                </label>
                <label style="flex: 0 0 100px;">
                    Кол-во
                    <input type="number" name="quantity" value="1" min="1" max="9999" required>
                </label>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </form>
        </section>

        <section class="panel">
            <h2>Продукты (<?= count($items) ?>)</h2>

            <?php if ($items === []): ?>
                <p class="empty">Список пуст. Добавьте первый продукт.</p>
            <?php else: ?>
                <ul class="item-list">
                    <?php foreach ($items as $item): ?>
                        <li class="item<?= (int) $item['is_bought'] === 1 ? ' bought' : '' ?>">
                            <form method="post" class="item-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

                                <label class="item-name">
                                    <input type="text" name="name" value="<?= e($item['name']) ?>" required maxlength="255">
                                </label>

                                <label>
                                    <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="9999" required>
                                </label>

                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_bought" value="1"<?= (int) $item['is_bought'] === 1 ? ' checked' : '' ?>>
                                    Куплено
                                </label>

                                <div style="display: flex; gap: 6px;">
                                    <button type="submit" class="btn btn-save">Сохранить</button>
                                </div>
                            </form>
                            <form method="post" style="margin-top: 8px; text-align: right;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Удалить «<?= e($item['name']) ?>»?')">Удалить</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

    <?php endif; ?>
</main>
</body>
</html>
