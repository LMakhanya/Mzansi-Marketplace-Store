<?php

header('Content-Type: application/json');

session_start();
$data = json_decode(file_get_contents('php://input'), true);
// Check if selectedProduct is set in the POST request
if (isset($data['selectedProduct'])) {
    // Log the received data for debugging
   /*  error_log('Received selectedProduct: ' . print_r($data['selectedProduct'], true)); */

    $_SESSION['selectedProduct'] = $data['selectedProduct'];

    echo json_encode(['status' => 'success', 'message' => 'Session updated successfully', 'selectedProduct' => $data['selectedProduct']]);
} elseif ((isset($_POST['totalAmount'])) && (isset($_POST['totalQuantity']))) {
    $totalAmount =  $_SESSION['totalAmount'] = $_POST['totalAmount'];
    $totalQuantity = $_SESSION['totalQuantity'] = $_POST['totalQuantity'];
    //
    echo json_encode(['status' => 'success', 'message' => 'Session updated successfully', 'totalAmount' => $_POST['totalAmount'], 'totalQuantity' => $_POST['totalQuantity']]);
} else {
    // Log error if selectedProduct is missing
    error_log('Error: selectedProduct is missing in POST data.');
    echo json_encode(['status' => 'error', 'message' => 'No selected product received']);
}
