<?php
// pages/home.php

// You can set a specific title for the home page if needed
// $page_title = "Welcome to Our Shoe Store";

?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="p-5 mb-4 bg-light rounded-3">
                <div class="container-fluid py-5">
                    <h1 class="display-5 fw-bold">Welcome to <?php echo htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store'); ?>!</h1>
                    <p class="col-md-8 fs-4">Your one-stop shop for the latest and greatest footwear. Browse our collection and find your perfect pair today.</p>
                    <a href="index.php?page=products" class="btn btn-primary btn-lg" type="button">Shop Now</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-md-stretch">
        <div class="col-md-6">
            <div class="h-100 p-5 text-white bg-dark rounded-3">
                <h2>New Arrivals</h2>
                <p>Check out the freshest styles just landed in our store. Limited stock available!</p>
                <button class="btn btn-outline-light" type="button">View New Arrivals</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="h-100 p-5 bg-light border rounded-3">
                <h2>Special Offers</h2>
                <p>Don't miss out on our exclusive deals and discounts. Save big on your favorite brands.</p>
                <button class="btn btn-outline-secondary" type="button">See Offers</button>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="row text-center">
        <div class="col-lg-4">
            <svg class="bd-placeholder-img rounded-circle" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Quality" preserveAspectRatio="xMidYMid slice" focusable="false">
                <title>Quality</title>
                <rect width="100%" height="100%" fill="#777" /><text x="50%" y="50%" fill="#777" dy=".3em">140x140</text>
            </svg>
            <h2 class="fw-normal">Quality Footwear</h2>
            <p>We offer only the best quality shoes from top brands around the world.</p>
            <p><a class="btn btn-secondary" href="#">View details &raquo;</a></p>
        </div><!-- /.col-lg-4 -->
        <div class="col-lg-4">
            <svg class="bd-placeholder-img rounded-circle" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Fast Shipping" preserveAspectRatio="xMidYMid slice" focusable="false">
                <title>Fast Shipping</title>
                <rect width="100%" height="100%" fill="#777" /><text x="50%" y="50%" fill="#777" dy=".3em">140x140</text>
            </svg>
            <h2 class="fw-normal">Fast Shipping</h2>
            <p>Get your new shoes delivered to your doorstep quickly and reliably.</p>
            <p><a class="btn btn-secondary" href="#">View details &raquo;</a></p>
        </div><!-- /.col-lg-4 -->
        <div class="col-lg-4">
            <svg class="bd-placeholder-img rounded-circle" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Great Support" preserveAspectRatio="xMidYMid slice" focusable="false">
                <title>Great Support</title>
                <rect width="100%" height="100%" fill="#777" /><text x="50%" y="50%" fill="#777" dy=".3em">140x140</text>
            </svg>
            <h2 class="fw-normal">Great Support</h2>
            <p>Our customer support team is always here to help you with any queries.</p>
            <p><a class="btn btn-secondary" href="#">View details &raquo;</a></p>
        </div><!-- /.col-lg-4 -->
    </div><!-- /.row -->

</div>