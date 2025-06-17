<?php
// Enable error reporting


header('Content-Type: application/json');

$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();

// Grab the 'action' param and other query parameters
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$productName = $_POST['productName'] ?? $_GET['productName'] ?? null;
$categoryName = $_POST['categoryName'] ?? $_GET['categoryName'] ?? null;
$category_id = $_POST['category_id'] ?? $_GET['category_id'] ?? null;
$selectedType = $_POST['selectedType'] ?? $_GET['selectedType'] ?? null;
$productId = $_POST['productId'] ?? $_GET['productId'] ?? null;

// Initialize an array to store data
$data = [];
$suggested = [];
$phones = [];

try {
    switch ($action) {
        case 'fetch':
            $sql = "SELECT p.*, s.* FROM product p
            JOIN product_categories pc ON p.category_id = pc.category_id
            JOIN sss_business s ON p.seller_id = s.seller_id";

            $conditions = [];
            $params = [];
            $types = "";

            // Add conditions dynamically
            if ($categoryName) {
                $conditions[] = "pc.CategoryName = ?";
                $params[] = $categoryName;
                $types .= "s";
            }

            if ($selectedType) {
                $sql .= " JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID";
                $conditions[] = "sc.subCatName = ?";
                $params[] = $selectedType;
                $types .= "s";
            }

            // Add WHERE clause if there are conditions
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ";

            // Prepare the statement
            $stmt = $conn->prepare($sql);

            // Bind parameters dynamically
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            // Execute the statement
            $stmt->execute();

            // Fetch the results
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if ($row['suggested'] === 'yes' && count($suggested) < 10) {
                    $suggested[] = $row;
                }
                if ($row['category_id'] === 6 && count($phones) < 10) {
                    $phones[] = $row;
                }
            }

            $data = [
                'suggested' => $suggested,
                'phones' => $phones,
                'success' => true
            ];
            $stmt->close();
            break;

        case 'item':
            $sql = "SELECT p.*, ps.* FROM product p
                    JOIN product_specs ps ON p.product_id = ps.product_id
                    WHERE p.product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();

            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            $stmt->close();
            break;

        default:
            $sql = "SELECT p.* FROM product p
                    JOIN product_categories pc ON p.category_id = pc.category_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if ($row['suggested'] === 'yes' && count($suggested) < 10) {
                    $suggested[] = $row;
                }
                if ($row['category_id'] === 6 && count($phones) < 10) {
                    $phones[] = $row;
                }
            }

            $data = [
                'suggested' => $suggested,
                'phones' => $phones,
                'success' => true
            ];

            $stmt->close();
            break;
    }

    // Return the JSON response
    echo json_encode($data, JSON_PRETTY_PRINT);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // Always close the connection
    $conn->close();
}
