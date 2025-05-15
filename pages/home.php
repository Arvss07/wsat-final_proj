<?php
// pages/home.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/product_functions.php';

$app_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj', '/') . '/';

$products_per_page = 12;

$all_categories = get_all_categories($conn);

// Determine current page for new arrivals (default to 1)
$current_page_new_arrivals = isset($_GET['p_na']) ? max(1, (int)$_GET['p_na']) : 1;
$offset_new_arrivals = ($current_page_new_arrivals - 1) * $products_per_page;

// Fetch new arrivals and total count
$new_arrival_products_data = get_new_arrival_products($conn, $products_per_page, $offset_new_arrivals);
$total_products_new_arrivals = count_new_arrival_products($conn);
$total_pages_new_arrivals = ($products_per_page > 0) ? ceil($total_products_new_arrivals / $products_per_page) : 1;

?>

<!-- Hero Section -->
<section class="py-5 mb-4 rounded-4 position-relative overflow-hidden" style="min-height: 120px;">
    <div class="container position-relative z-2">
        <div class="row align-items-center">
            <div class="col-lg-12">
                <h1 class="display-4 fw-bold mb-3"><i class="bi bi-bag-heart"></i> Welcome to ArLeJeen Step!</h1>
                <p class="lead mb-4">Discover the latest arrivals, exclusive deals, and your favorite brands. Step up your style with our curated collection!</p>
                <a href="#product-display-area" class="btn btn-light btn-lg fw-semibold shadow-sm"><i class="bi bi-shop"></i> Shop Now</a>
            </div>
        </div>
    </div>
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-primary opacity-25 z-1"></div>
</section>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <aside class="col-lg-3 mb-4 mb-lg-0">

            <div class="bg-white p-4 rounded-4 shadow-sm border border-2 border-success-subtle">
                <h5 class="mb-3 text-success"><i class="bi bi-tags"></i> Categories</h5>
                <div class="list-group list-group-flush">
                    <a href="#product-display-area" class="list-group-item list-group-item-action category-nav-link fw-semibold" data-category-id="all_new">
                        <i class="bi bi-stars"></i> New Arrivals
                    </a>
                    <?php if (!empty($all_categories)): ?>
                        <?php foreach ($all_categories as $category): ?>
                            <a href="#product-display-area" class="list-group-item list-group-item-action category-nav-link" data-category-id="<?php echo htmlspecialchars($category['id']); ?>">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted small">No categories found.</span>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="col-lg-9">
            <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-2 border-primary-subtle">
                <h5 class="mb-3 text-primary"><i class="bi bi-search"></i> Search</h5>
                <form id="productSearchForm" action="<?php echo $app_url; ?>index.php" method="GET">
                    <input type="hidden" name="page" value="products">
                    <div class="input-group mb-3">
                        <input type="text" name="query" id="productSearchInput" class="form-control form-control-lg" placeholder="Search products..." autocomplete="off">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>

            <!-- Carousel for Newest Products -->
            <section id="newest-product-carousel" class="mb-5">
                <h2 class="mb-3 text-primary"><i class="bi bi-stars"></i> Hot & New</h2>
                <div id="productCarousel" class="carousel slide rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel">
                    <div class="carousel-indicators"></div>
                    <div class="carousel-inner"></div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </section>

            <!-- Product Display Area (Dynamic based on category nav or default to New Arrivals) -->
            <section id="product-display-area" class="mb-5">
                <h2 id="product-display-title" class="mb-4"><i class="bi bi-grid"></i> New Arrivals</h2>
                <div id="product-grid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php
                    // Initial load: Display New Arrivals or selected category from sidebar link
                    $products_to_display = [];
                    $current_page_dynamic = 1;
                    $total_pages_dynamic = 1;
                    $base_url_dynamic = $app_url . 'index.php?page=home';

                    if (isset($_GET['category_id']) && $conn) {
                        $selected_category_id = $_GET['category_id'];
                        // Find category name for title
                        $category_name_for_title = 'Category Products';
                        foreach ($all_categories as $cat) {
                            if ($cat['id'] == $selected_category_id) {
                                $category_name_for_title = $cat['name'];
                                break;
                            }
                        }
                        // Use JS to set title to avoid issues if headers already sent
                        echo "<script>document.addEventListener('DOMContentLoaded', function() { if(document.getElementById('product-display-title')) { document.getElementById('product-display-title').innerHTML = '<i class=\"bi bi-grid\"></i> ' + decodeURIComponent('" . rawurlencode(htmlspecialchars($category_name_for_title)) . "'); } });</script>";

                        $current_page_dynamic = isset($_GET['p_cat']) ? (int)$_GET['p_cat'] : 1;
                        $offset_dynamic = ($current_page_dynamic - 1) * $products_per_page;
                        $products_to_display = get_products_by_category($conn, $selected_category_id, $products_per_page, $offset_dynamic);
                        $total_products_dynamic = count_products_by_category($conn, $selected_category_id);
                        $total_pages_dynamic = ceil($total_products_dynamic / $products_per_page);
                        $base_url_dynamic = $app_url . 'index.php?page=home&category_id=' . htmlspecialchars($selected_category_id);
                    } else {
                        // Default to New Arrivals if no category_id in URL (or if it's the initial state for AJAX later)
                        $products_to_display = $new_arrival_products_data;
                        $current_page_dynamic = $current_page_new_arrivals;
                        $total_pages_dynamic = $total_pages_new_arrivals;
                        $base_url_dynamic = $app_url . 'index.php?page=home&p_na='; // Base for new arrivals pagination
                        // Ensure the title is set to New Arrivals if this block runs
                        echo "<script> 
                                if (document.getElementById('product-display-title')) { 
                                    document.getElementById('product-display-title').innerHTML = '<i class=\"bi bi-grid\"></i> New Arrivals'; 
                                } 
                                // Also update active state for category nav if needed
                                document.querySelectorAll('.category-nav-link').forEach(link => link.classList.remove('active'));
                                const newArrivalsLink = document.querySelector('.category-nav-link[data-category-id=\"all_new\"]');
                                if (newArrivalsLink) newArrivalsLink.classList.add('active');
                              </script>";
                    }

                    if (!empty($products_to_display)):
                        foreach ($products_to_display as $product) {
                            // Include the reusable product card
                            include __DIR__ . '/../includes/components/product_card.php';
                        }
                    else:
                        echo '<p class="text-center">No products found in this section.</p>';
                    endif;
                    ?>
                </div>
                <div class="mt-4">
                    <?php
                    // Pagination for the dynamic/main product display area
                    if ($total_pages_dynamic > 1) {
                        $current_page = $current_page_dynamic;
                        $total_pages = $total_pages_dynamic;
                        $base_url = $base_url_dynamic; // This needs to be set correctly based on context
                        include __DIR__ . '/../includes/components/pagination_controls.php';
                    }
                    ?>
                </div>
            </section>

            <!-- General Product Listing Section -->
            <section id="all-products-section" class="mb-5">
                <h2 class="mb-4 text-secondary"><i class="bi bi-box"></i> All Products</h2>
                <div id="all-products-grid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php
                    // General product listing (random order)
                    $current_page_all_products = isset($_GET['p_all']) ? max(1, (int)$_GET['p_all']) : 1;
                    $offset_all_products = ($current_page_all_products - 1) * $products_per_page;
                    $all_products_data = get_all_products_random($conn, $products_per_page, $offset_all_products);
                    $total_products_all = count_all_products($conn);
                    $total_pages_all = ($products_per_page > 0) ? ceil($total_products_all / $products_per_page) : 1;

                    if (!empty($all_products_data)):
                        foreach ($all_products_data as $product) {
                            include __DIR__ . '/../includes/components/product_card.php';
                        }
                    else:
                        echo '<p class="text-center">No products found.</p>';
                    endif;
                    ?>
                </div>
                <div class="mt-4">
                    <?php
                    if ($total_pages_all > 1) {
                        $current_page = $current_page_all_products;
                        $total_pages = $total_pages_all;
                        $base_url = $app_url . 'index.php?page=home&p_all=';
                        include __DIR__ . '/../includes/components/pagination_controls.php';
                    }
                    ?>
                </div>
            </section>
        </main>
    </div>
</div>

<style>
    /* Hero Section */
    section.py-5.bg-primary {
        background-blend-mode: multiply;
        min-height: 320px;
    }

    section.py-5.bg-primary .container {
        position: relative;
        z-index: 2;
    }

    section.py-5.bg-primary .img-fluid {
        filter: drop-shadow(0 0 2rem #0d6efd33);
    }

    /* Sidebar */
    .list-group-item.category-nav-link.active,
    .list-group-item.category-nav-link:hover {
        background: linear-gradient(90deg, #0d6efd11 0%, #0d6efd05 100%);
        color: #0d6efd;
        font-weight: 600;
        border-left: 4px solid #0d6efd;
    }

    /* Product Cards */
    #product-grid .card,
    #all-products-grid .card {
        border-radius: 1rem;
        transition: box-shadow 0.2s, transform 0.2s;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    #product-grid .card:hover,
    #all-products-grid .card:hover {
        box-shadow: 0 8px 24px rgba(13, 110, 253, 0.10);
        transform: translateY(-4px) scale(1.02);
        border-color: #0d6efd33;
    }

    #product-grid .card-img-top,
    #all-products-grid .card-img-top {
        border-radius: 1rem 1rem 0 0;
        background: #f8f9fa;
    }

    /* Carousel */
    #productCarousel .carousel-caption {
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
        z-index: 2;
    }

    #productCarousel .carousel-item:hover .carousel-caption {
        opacity: 1;
        pointer-events: auto;
    }

    #productCarousel .carousel-control-prev,
    #productCarousel .carousel-control-next {
        z-index: 3;
    }

    #productCarousel .carousel-control-prev-icon,
    #productCarousel .carousel-control-next-icon {
        background-color: #0d6efd;
        border-radius: 50%;
        width: 2.5rem;
        height: 2.5rem;
        background-size: 60% 60%;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Responsive tweaks */
    @media (max-width: 991.98px) {
        section.py-5.bg-primary .img-fluid {
            display: none;
        }

        aside.col-lg-3 {
            margin-bottom: 2rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryNavLinks = document.querySelectorAll('.category-nav-link');
        const productGrid = document.getElementById('product-grid');
        const productDisplayTitle = document.getElementById('product-display-title');
        const paginationContainer = productGrid.nextElementSibling; // The div holding pagination

        loadCarouselProducts(); // Load carousel products

        categoryNavLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const categoryId = this.dataset.categoryId;
                const categoryName = this.textContent.trim();

                // Update active state for category nav links
                categoryNavLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                // Update URL without full page reload for better UX, and to handle pagination state
                const currentUrl = new URL(window.location.href);
                if (categoryId === 'all_new') {
                    currentUrl.searchParams.delete('category_id');
                    currentUrl.searchParams.set('p_na', '1'); // Reset to page 1 for new arrivals
                    currentUrl.searchParams.delete('p_cat');
                } else {
                    currentUrl.searchParams.set('category_id', categoryId);
                    currentUrl.searchParams.set('p_cat', '1'); // Reset to page 1 for category
                    currentUrl.searchParams.delete('p_na');
                }
                history.pushState({}, '', currentUrl.toString() + '#product-display-area');

                // Fetch and display products for this category via AJAX
                fetchProducts(categoryId, categoryName, 1);
            });
        });

        function loadCarouselProducts() {
            const carouselIndicatorsContainer = document.querySelector('#productCarousel .carousel-indicators');
            const carouselInnerContainer = document.querySelector('#productCarousel .carousel-inner');
            const appUrl = '<?php echo $app_url; ?>';
            const defaultImagePath = 'assets/img/default_avatar.png'; // Adjusted default image

            if (!carouselIndicatorsContainer || !carouselInnerContainer) {
                console.error('Carousel containers not found');
                return;
            }

            fetch(`${appUrl}api/products/homepage_products_handler.php`)
                .then(response => response.json())
                .then(data => {
                    carouselIndicatorsContainer.innerHTML = ''; // Clear existing indicators
                    carouselInnerContainer.innerHTML = ''; // Clear existing items

                    if (data.success && data.data && data.data.length > 0) {
                        const products = data.data;
                        products.forEach((product, index) => {
                            // Add indicator
                            const indicator = document.createElement('button');
                            indicator.type = 'button';
                            indicator.dataset.bsTarget = '#productCarousel';
                            indicator.dataset.bsSlideTo = index.toString();
                            if (index === 0) {
                                indicator.classList.add('active');
                                indicator.setAttribute('aria-current', 'true');
                            }
                            indicator.setAttribute('aria-label', `Slide ${index + 1}`);
                            carouselIndicatorsContainer.appendChild(indicator);

                            // Add carousel item
                            const item = document.createElement('div');
                            item.classList.add('carousel-item');
                            if (index === 0) {
                                item.classList.add('active');
                            }

                            const productImageSrc = product.image_path ? `${appUrl}${product.image_path}` : `${appUrl}${defaultImagePath}`;
                            const productName = product.name || 'Product';
                            const productPrice = parseFloat(product.price).toFixed(2);
                            const productDetailUrl = `${appUrl}index.php?page=product_detail&id=${product.id}`;

                            item.innerHTML = `
                                <img src="${productImageSrc}" class="d-block w-100" alt="${productName}" style="max-height: 400px; object-fit: contain; background-color: #f8f9fa;">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-2 rounded">
                                    <h5>${productName}</h5>
                                    <p>Price: ₱${productPrice} <a href="${productDetailUrl}" class="btn btn-sm btn-light">View</a></p>
                                </div>
                            `;
                            carouselInnerContainer.appendChild(item);
                        });

                        // Reinitialize carousel if needed, though data-bs-ride should handle it
                        // const carouselElement = document.getElementById('productCarousel');
                        // if (carouselElement) {
                        //     new bootstrap.Carousel(carouselElement);
                        // }

                    } else {
                        // No products, or error. Carousel will be empty.
                        // Optionally, hide the carousel section or display a message
                        const carouselSection = document.getElementById('newest-product-carousel');
                        if (carouselSection) {
                            // You could hide the section or display a message like:
                            // carouselInnerContainer.innerHTML = '<p class="text-center text-muted p-5">No new products to display currently.</p>';
                            // For now, it will just be an empty carousel if no products.
                        }
                        console.log(data.message || 'No homepage products found.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching carousel products:', error);
                    carouselInnerContainer.innerHTML = '<p class="text-center text-danger p-5">Could not load new products. Please try again later.</p>';
                });
        }

        function fetchProducts(categoryId, categoryName, page = 1) {
            productGrid.innerHTML = '<div class="text-center w-100"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> <p>Loading products...</p></div>';
            if (paginationContainer) paginationContainer.innerHTML = '';

            let ajaxUrl;
            if (categoryId && categoryId !== 'all_new') {
                ajaxUrl = `<?php echo $app_url; ?>api/products/category_products_handler.php?category_id=${categoryId}&page=${page}`;
            } else {
                ajaxUrl = `<?php echo $app_url; ?>api/products_getter.php?page=${page}`;
            }

            fetch(ajaxUrl)
                .then(response => response.json())
                .then(data => {
                    productGrid.innerHTML = '';
                    let products = [];
                    let pagination = null;
                    if (data.success && data.data) {
                        products = data.data.products || data.products || [];
                        pagination = data.data.pagination || data.pagination || null;
                    } else if (data.products) {
                        products = data.products;
                        pagination = data.pagination || null;
                    }
                    if (products.length > 0) {
                        products.forEach(product => {
                            const cardHTML = `
                        <div class="col mb-4">
                            <div class="card h-100">
                                <img src="<?php echo $app_url; ?>${product.image_path ? product.image_path : 'assets/img/placeholder.png'}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title">${product.name}</h5>
                                    <p class="card-text">Price: ₱${parseFloat(product.price).toFixed(2)}</p>
                                    <a href="<?php echo $app_url; ?>index.php?page=product_detail&id=${product.id}" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>`;
                            productGrid.insertAdjacentHTML('beforeend', cardHTML);
                        });
                    } else {
                        productGrid.innerHTML = '<p class="text-center w-100">No products found in this category.</p>';
                    }
                    if (productDisplayTitle) {
                        productDisplayTitle.innerHTML = `<i class="bi bi-grid"></i> ${categoryName || 'Products'}`;
                    }
                    // Update pagination
                    if (pagination && paginationContainer) {
                        // Build pagination HTML
                        let pagHtml = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
                        const totalPages = pagination.total_pages || 1;
                        const currentPage = pagination.current_page || 1;
                        for (let i = 1; i <= totalPages; i++) {
                            pagHtml += `<li class="page-item${i === currentPage ? ' active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                        }
                        pagHtml += '</ul></nav>';
                        paginationContainer.innerHTML = pagHtml;
                        // Attach event listeners
                        paginationContainer.querySelectorAll('.page-link').forEach(link => {
                            link.addEventListener('click', function(e) {
                                e.preventDefault();
                                fetchProducts(categoryId, categoryName, parseInt(this.dataset.page));
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    productGrid.innerHTML = '<p class="text-center w-100 text-danger">Could not load products. Please try again later.</p>';
                });
        }

        // Handle initial category loading if category_id is in URL (from sidebar link)
        const initialUrlParams = new URLSearchParams(window.location.search);
        const initialCategoryId = initialUrlParams.get('category_id');
        const initialPageCat = initialUrlParams.get('p_cat') || 1;
        const initialPageNA = initialUrlParams.get('p_na') || 1;

        if (initialCategoryId) {
            // If a category is directly linked (e.g. from sidebar), ensure its nav item is active
            // and content is loaded (PHP already handles initial load, JS handles subsequent)
            document.querySelectorAll('.category-nav-link').forEach(link => link.classList.remove('active'));
            const activeCatLink = document.querySelector(`.category-nav-link[data-category-id="${initialCategoryId}"]`);
            if (activeCatLink) activeCatLink.classList.add('active');
        } else {
            // Default to New Arrivals active
            const newArrivalsLink = document.querySelector('.category-nav-link[data-category-id="all_new"]');
            if (newArrivalsLink) newArrivalsLink.classList.add('active');
        }

    });

    // AJAX Product Search Implementation
    (function() {
        const searchInput = document.getElementById('productSearchInput');
        const productGrid = document.getElementById('product-grid');
        const productDisplayTitle = document.getElementById('product-display-title');
        let searchTimeout = null;
        let lastQuery = '';

        function renderProducts(products) {
            if (!products.length) {
                productGrid.innerHTML = '<p class="text-center w-100">No products found.</p>';
                return;
            }
            productGrid.innerHTML = '';
            products.forEach(product => {
                const imgSrc = product.image_path ? '<?php echo $app_url; ?>' + product.image_path : '<?php echo $app_url; ?>assets/img/default_avatar.png';
                const detailUrl = '<?php echo $app_url; ?>index.php?page=product_detail&id=' + product.id;
                productGrid.insertAdjacentHTML('beforeend', `
                    <div class="col mb-4">
                        <div class="card h-100 shadow-sm">
                            <a href="${detailUrl}">
                                <img src="${imgSrc}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="${detailUrl}" class="text-decoration-none text-dark">${product.name}</a>
                                </h5>
                                <p class="card-text text-muted">Price: ₱${parseFloat(product.price).toFixed(2)}</p>
                                <div class="mt-auto">
                                    <a href="${detailUrl}" class="btn btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });
        }

        function doSearch(query) {
            if (!query) {
                // If search is cleared, reload default products (optional: reload page or restore PHP-rendered grid)
                window.location.reload();
                return;
            }
            productGrid.innerHTML = '<div class="text-center w-100"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> <p>Searching...</p></div>';
            fetch('<?php echo $app_url; ?>api/products/search_products.php?query=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        productDisplayTitle.innerHTML = `<i class='bi bi-search'></i> Search Results for "${query}"`;
                        renderProducts(data.products);
                    } else {
                        productGrid.innerHTML = '<p class="text-center w-100 text-danger">No products found.</p>';
                    }
                })
                .catch(() => {
                    productGrid.innerHTML = '<p class="text-center w-100 text-danger">Could not search products. Please try again later.</p>';
                });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                if (query === lastQuery) return;
                lastQuery = query;
                if (searchTimeout) clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => doSearch(query), 400); // Debounce
            });
            // Prevent default form submit
            document.getElementById('productSearchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                doSearch(searchInput.value.trim());
            });
        }
    })();
</script>