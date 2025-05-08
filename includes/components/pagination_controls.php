<?php
// includes/components/pagination_controls.php
// Generates pagination links.
// Expects:
// - $current_page: The current active page number.
// - $total_pages: The total number of pages.
// - $base_url: The base URL for pagination links (e.g., "index.php?page=products&category_id=123").
// - $page_param_name: (Optional) The name of the page query parameter (defaults to "p").

if (!isset($current_page) || !isset($total_pages) || !isset($base_url)) {
    // error_log("Pagination controls: Missing required variables (current_page, total_pages, or base_url).");
    return; // Exit if essential variables are missing
}

// Ensure numeric types for pages
$current_page = (int)$current_page;
$total_pages = (int)$total_pages;

if ($total_pages <= 1) {
    return; // No pagination needed for one or zero pages
}

// Default page parameter name if not provided or empty
$page_param_name = (!empty($page_param_name)) ? htmlspecialchars($page_param_name) : 'p';

/**
 * Helper function to generate a pagination link.
 * Ensures correct query string construction.
 */
function generate_pagination_link_helper(string $base_url, string $param_name, int $page_number): string
{
    $url_parts = parse_url($base_url);
    $query = $url_parts['query'] ?? '';
    parse_str($query, $params);
    $params[$param_name] = $page_number;

    $path = $url_parts['path'] ?? '';
    $new_query_string = http_build_query($params);

    return htmlspecialchars($path . '?' . $new_query_string);
}

?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php // Previous Page Link 
        ?>
        <?php if ($current_page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo generate_pagination_link_helper($base_url, $page_param_name, $current_page - 1); ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                    <span class="visually-hidden">Previous</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link" aria-hidden="true">&laquo;</span>
            </li>
        <?php endif; ?>

        <?php // Page Number Links 
        ?>
        <?php
        // Determine the range of page numbers to display
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        if ($current_page <= 3) {
            $end_page = min($total_pages, 5);
        }
        if ($current_page > $total_pages - 3) {
            $start_page = max(1, $total_pages - 4);
        }

        // Always show first page if not in main range
        if ($start_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . generate_pagination_link_helper($base_url, $page_param_name, 1) . '">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo generate_pagination_link_helper($base_url, $page_param_name, $i); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php // Always show last page if not in main range
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="' . generate_pagination_link_helper($base_url, $page_param_name, $total_pages) . '">' . $total_pages . '</a></li>';
        }
        ?>

        <?php // Next Page Link 
        ?>
        <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo generate_pagination_link_helper($base_url, $page_param_name, $current_page + 1); ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="visually-hidden">Next</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link" aria-hidden="true">&raquo;</span>
            </li>
        <?php endif; ?>
    </ul>
</nav>