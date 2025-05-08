<?php
// includes/product_functions.php

/**
 * Fetches the latest products (New Arrivals) with their primary image, paginated.
 * @param mysqli $conn
 * @param int $limit
 * @param int $offset
 * @return array
 */
function get_new_arrival_products(mysqli $conn, int $limit, int $offset): array {
    $products = [];
    $sql = "
        SELECT p.id, p.name, p.price, p.created_at,
               (
                   SELECT image_path FROM product_images 
                   WHERE product_id = p.id AND is_primary = 1 
                   ORDER BY created_at DESC LIMIT 1
               ) AS image_path
        FROM products p
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
    }
    return $products;
}

/**
 * Counts the total number of products (for pagination).
 * @param mysqli $conn
 * @return int
 */
function count_new_arrival_products(mysqli $conn): int {
    $sql = "SELECT COUNT(*) as total FROM products";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
} 