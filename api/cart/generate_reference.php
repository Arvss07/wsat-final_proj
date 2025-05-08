<?php
require_once __DIR__ . '/../../utils/uuid.php';
header('Content-Type: application/json');
echo json_encode(['success' => true, 'reference' => generate_numeric_reference()]); 