<?php if ($is_admin_page && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') : ?>
    </div> <!-- close col-md-9 -->
    </div> <!-- close row -->
<?php endif; ?>
</div> <!-- Closing .container from header.php -->

<footer class="mt-5 py-4 bg-light text-center">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store'); ?>. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo asset('assets/js/main.js'); ?>"></script>
<script>
    $(document).ready(function() {
        $('#productTable').DataTable();
        // You can add other DataTable initializations here for other tables
        // $('#anotherTable').DataTable();
    });
</script>
</body>

</html>