<?php
require_once 'config/db.php';

echo "<h2>Expanding HypeThread Data...</h2>";

try {
    // 1. Add More Categories
    $categories = [
        ['Outerwear', 'Coats, jackets, and blazers'],
        ['Footwear', 'Shoes, boots, and sneakers'],
        ['Loungewear', 'Comfortable home wear'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "✔ Categories expanded.<br>";

    // 2. Add More Products
    $new_products = [
        ['Trench Coat', 'Classic beige trench coat with belt', 189.99, 1, 1],
        ['Leather Jacket', 'Black faux leather moto jacket', 120.00, 1, 1],
        ['Designer Sneakers', 'White leather minimalist sneakers', 150.00, 1, 2],
        ['Silk Pajama Set', 'Luxury silk set in navy blue', 95.00, 0, 3],
        ['Cashmere Sweater', 'Soft grey turtleneck cashmere sweater', 210.00, 1, 1],
        ['Chelsea Boots', 'Suede tan chelsea boots', 135.00, 0, 2]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, is_featured, category_id) VALUES (?, ?, ?, ?, (SELECT id FROM categories WHERE name = ? LIMIT 1))");
    foreach ($new_products as $p) {
        $stmt->execute($p);
        $product_id = $pdo->lastInsertId();
        
        // Add variations for each product
        $v_stmt = $pdo->prepare("INSERT INTO product_variations (product_id, size, color, stock_quantity) VALUES (?, ?, ?, ?)");
        $v_stmt->execute([$product_id, 'S', 'Default', rand(5, 20)]);
        $v_stmt->execute([$product_id, 'M', 'Default', rand(5, 20)]);
        $v_stmt->execute([$product_id, 'L', 'Default', rand(5, 20)]);
    }
    echo "✔ Products and variations expanded.<br>";

    // 3. Add More Buyers
    $buyers = [
        ['emily_vogue', 'emily@fashion.com', 'Emily Smith'],
        ['michael_style', 'michael@style.com', 'Michael Brown'],
        ['sophia_chic', 'sophia@chic.it', 'Sophia Rossi'],
        ['liam_trend', 'liam@trend.uk', 'Liam Wilson']
    ];
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role, full_name) VALUES (?, ?, ?, 'buyer', ?)");
    foreach ($buyers as $b) {
        $stmt->execute([$b[0], $b[1], $hash, $b[2]]);
    }
    echo "✔ Buyer base expanded.<br>";

    // 4. Generate Realistic Orders (Last 30 Days)
    $all_buyers = $pdo->query("SELECT id FROM users WHERE role = 'buyer'")->fetchAll(PDO::FETCH_COLUMN);
    $all_variations = $pdo->query("SELECT pv.id, p.price FROM product_variations pv JOIN products p ON pv.product_id = p.id")->fetchAll();
    
    $statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    
    for ($i = 0; $i < 30; $i++) {
        $user_id = $all_buyers[array_rand($all_buyers)];
        $date = date('Y-m-d H:i:s', strtotime("-RMi days -" . rand(1, 10) . " hours"));
        $status = $statuses[array_rand($statuses)];
        
        // Pick 1-3 items
        $items_count = rand(1, 3);
        $total_amount = 0;
        $order_items = [];
        
        for ($j = 0; $j < $items_count; $j++) {
            $var = $all_variations[array_rand($all_variations)];
            $qty = rand(1, 2);
            $price = $var['price'];
            $total_amount += ($price * $qty);
            $order_items[] = ['variation_id' => $var['id'], 'qty' => $qty, 'price' => $price];
        }
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $total_amount, $status, $date]);
        $order_id = $pdo->lastInsertId();
        
        $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, variation_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($order_items as $item) {
            $item_stmt->execute([$order_id, $item['variation_id'], $item['qty'], $item['price']]);
        }
    }
    echo "✔ 30 historical orders generated.<br>";

    // 5. Add Enquiries
    $enquiries = [
        ['Size Guide Question', 'Could you tell me if the trench coat runs true to size?'],
        ['Shipping to UK', 'Do you offer international shipping to London?'],
        ['Return Policy', 'How do I return an item that doesn\'t fit?'],
        ['Restock Alert', 'Will the sneakers be restocked in size 42 soon?']
    ];
    $stmt = $pdo->prepare("INSERT INTO enquiries (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
    foreach ($enquiries as $enq) {
        $user_id = $all_buyers[array_rand($all_buyers)];
        $stmt->execute([$user_id, $enq[0], $enq[1]]);
    }
    echo "✔ Customer enquiries generated.<br>";

    echo "<h3>Expansion Complete!</h3>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
