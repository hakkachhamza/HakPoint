<?php
$taxRate = $product['vat'] ?? 20;
$sale = (float)($product['sale_price'] ?? 0);
$buy = (float)($product['buy_price'] ?? 0);
$ttc = $sale * (1 + ($taxRate/100));
$buyTtc = $buy * (1 + ($taxRate/100));
$unit = $product['unit'] ?? 'unitF';
$physical = (int)($product['physical_stock'] ?? 0);
$virtual = (int)($product['virtual_stock'] ?? 0);
$alert = (int)($product['alert_stock'] ?? 20);
$desired = (int)($product['desired_stock'] ?? 0);
?>
