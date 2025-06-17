<?php
function getSelectedProduct($productId)
{
    global $conn;

    // Prepare the SQL query with LEFT JOIN for product_specs
    $stmt = $conn->prepare("SELECT 
    p.*, 
    ps.SpecID, ps.Processor, ps.RAM, ps.Storage, ps.DisplaySize, 
    ps.GraphicsCard, ps.OperatingSystem, ps.BatteryLife, 
    ps.Camera, ps.Connectivity, ps.Sensors, ps.Ports, 
    ps.Model, ps.PowerSupply, ps.Features, ps.Material, 
    ps.Dimensions, ps.CapSize, ps.Type, ps.Size, ps.VolumeWeight, 
    ps.KeyIngredients, ps.Author, ps.Pages, ps.ScentNotes, ps.additional_notes, 
    sb.b_id, sb.seller_id, sb.b_name, sb.b_owner_name, sb.created_at, 
    s.* 
    FROM product p
    LEFT JOIN product_specs ps ON p.product_id = ps.product_id  
    LEFT JOIN sellers s ON p.seller_id = s.seller_id
    LEFT JOIN sss_business sb ON p.seller_id = sb.seller_id  
    WHERE p.product_id = ? 
    LIMIT 1;");

    // Bind the productId parameter to prevent SQL injection
    $stmt->bind_param("i", $productId); // Assuming product_id is an integer

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch the row (if any)
    $row = $result->fetch_assoc();

    // Store the result in the session; if no row is found, store an empty array
    $_SESSION['selecteProduct'] = $row ? $row : [];

    // Close the statement
    $stmt->close();

    // Return the selected product
    return $_SESSION['selecteProduct'];
}

// function to get product reviews

function getProductReviews($productId)
{
    global $conn;
    $result = $conn->query("SELECT * FROM product_reviews WHERE product_id = $productId");
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    return $reviews;
}
function getProductVariants($productId)
{
    global $conn;
    $result = $conn->query("SELECT * FROM product_variants WHERE product_id = $productId");
    $varients = [];
    while ($row = $result->fetch_assoc()) {
        $varients[] = $row;
    }
    return $varients;
}

if (!$action) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

$customerID = $_SESSION["customerID"];

$jsonRResponse = [];

// Process action
try {
    switch ($action) {
        case 'refresh':
            $result = getTotalQuantityAndAmount($customerID);

            $jsonRResponse = [
                'status' => 'success',
                'message' => 'Session refreshed',
                'bagID' => $result['bagID'],
                'data' => $result
            ];

            break;
        case 'getItem':
            $productId = $_GET['productId'] ?? $_POST['productId'] ?? null;
            $_SESSION['selecteProduct'] = getSelectedProduct($productId);
            $result = getTotalQuantityAndAmount($customerID);
            $jsonRResponse = [
                'status' => 'success',
                'message' => 'Session refreshed',
                'bagID' => $result['bagID'],
                'selectedProduct' => $_SESSION['selecteProduct'],
                'reviews' => getProductReviews($productId),
                'variants' =>  getProductVariants($productId),
            ];
            break;
            // Access the object directly
    }

    // echo json_encode($jsonRResponse, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    log_to_file("Unexpected error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
}
