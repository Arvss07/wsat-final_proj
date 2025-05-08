<?php
// includes/components/category_list_item.php
// Displays a single category list item, typically for a sidebar or list.
// Expects:
// - $category: An array containing category details (id, name).
// - $app_url: The base URL of the application.
// - $current_category_id: (Optional) The ID of the currently active category to highlight it.

if (!isset($category) || !is_array($category) || !isset($app_url)) {
    error_log("Category list item component: Missing required variables.");
    return;
}

$category_id = htmlspecialchars($category['id'] ?? '');
$category_name = htmlspecialchars($category['name'] ?? 'Unnamed Category');
// Link to a general products page filtered by this category
$category_link = htmlspecialchars($app_url . 'index.php?page=products&category_id=' . $category_id);

// Determine if this item should be active (e.g., if it's the currently viewed category)
$is_active = (isset($current_category_id) && $current_category_id == $category_id) ? 'active' : '';

?>
<a href="<?php echo $category_link; ?>" class="list-group-item list-group-item-action <?php echo $is_active; ?>">
    <?php echo $category_name; ?>
</a>