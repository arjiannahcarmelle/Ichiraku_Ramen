<?php
// order_summary.php
require_once 'functions.php';
require_login();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php'); exit;
}

// fetch items & totals
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$stmt = $mysqli->prepare("SELECT product_id, product_name, price FROM products WHERE product_id IN ($placeholders)");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
$total = 0;
while ($r = $res->fetch_assoc()) {
    $r['qty'] = $cart[$r['product_id']];
    $r['subtotal'] = $r['price'] * $r['qty'];
    $items[] = $r;
    $total += $r['subtotal'];
}
$stmt->close();

$customer_id = $_SESSION['customer_id'];
$stmt = $mysqli->prepare("SELECT customer_name, customer_address, contact_number FROM customers_info WHERE customer_id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$stmt->bind_result($cname, $caddress, $ccontact);
$stmt->fetch();
$stmt->close();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Order Summary</title></head>
<body>
<h2>Order Summary</h2>
<p>Deliver to: <?php echo htmlspecialchars($caddress ?: 'Not set'); ?></p>
<p>Contact: <?php echo htmlspecialchars($ccontact ?: ''); ?></p>

<ul>
<?php foreach($items as $it): ?>
    <li><?php echo htmlspecialchars($it['product_name']) . ' x' . $it['qty'] . ' — ₱' . number_format($it['subtotal'],2); ?></li>
<?php endforeach; ?>
</ul>

<h3>Total: ₱<?php echo number_format($total,2); ?></h3>

<form method="post" action="checkout.php">
    <label>Payment method:
      <select name="payment_method">
        <option value="Cash on Delivery">Cash on Delivery</option>
        <option value="GCash">GCash</option>
        <option value="Debit Card">Debit Card</option>
      </select>
    </label>
    <br><br>
    <button type="submit">Place Order</button>
</form>
</body>
</html>
