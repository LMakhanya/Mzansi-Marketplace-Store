<?php
session_start();
// address_handler.php
header('Content-Type: application/json');

$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once($ENV_IPATH . "conn.php");

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $customerID = $_SESSION['customerID'] ?? '';

    switch ($action) {
        case 'save_address':
            $houseNumber = $data['houseNumber'] ?? '';
            $addressl1 = $data['addressl1'] ?? '';
            $addressl2 = $data['addressl2'] ?? '';
            $city = $data['city'] ?? '';
            $province = $data['province'] ?? '';
            $postalCode = $data['postalCode'] ?? '';

            if (empty($customerID) || empty($houseNumber) || empty($addressl1) || empty($city) || empty($province) || empty($postalCode)) {
                throw new Exception("All required fields must be filled $customerID and $houseNumber and $addressl1 and $city and $province");
            }

            $stmt = $conn->prepare("INSERT INTO customer_address (CustomerID, houseNumber, addressl1, addressl2, city, province, postalCode, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sissssi", $customerID, $houseNumber, $addressl1, $addressl2, $city, $province, $postalCode);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Address saved successfully', 'addressId' => $conn->insert_id]);
            } else {
                throw new Exception("Error saving address");
            }
            $stmt->close();
            break;

        case 'set_shipping':
            $addressId = $data['addressId'] ?? '';
            if (empty($addressId) || empty($customerID)) {
                throw new Exception("Address ID or customer ID are required");
            }
            $stmt = $conn->prepare("UPDATE customer_address SET is_shipping = 0 WHERE CustomerID = ?");
            $stmt->bind_param("s", $customerID);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE customer SET addressID = ? WHERE CustomerID = ?");
            $stmt->bind_param("is", $addressId, $customerID);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE customer_address SET is_shipping = 1 WHERE id = ? AND CustomerID = ?");
            $stmt->bind_param("is", $addressId, $customerID);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Shipping address updated successfully']);
            } else {
                throw new Exception("Error updating shipping address");
            }
            $stmt->close();
            break;

        case 'update_address':
            $addressId = $data['addressId'] ?? '';
            $houseNumber = $data['edit-houseNumber'] ?? '';
            $addressl1 = $data['edit-addressl1'] ?? '';
            $addressl2 = $data['edit-addressl2'] ?? '';
            $city = $data['edit-city'] ?? '';
            $province = $data['edit-province'] ?? '';
            $postalCode = $data['edit-postalCode'] ?? '';

            if (empty($addressId) || empty($customerID) || empty($houseNumber) || empty($addressl1) || empty($city) || empty($province) || empty($postalCode)) {
                throw new Exception("All required fields must be filled $addressId, $customerID and $houseNumber and $addressl1 and $city and $province");
            }

            $stmt = $conn->prepare("UPDATE customer_address SET houseNumber = ?, addressl1 = ?, addressl2 = ?, city = ?, province = ?, postalCode = ? WHERE id = ? AND CustomerID = ?");
            $stmt->bind_param("isssssis", $houseNumber, $addressl1, $addressl2, $city, $province, $postalCode, $addressId, $customerID);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Address updated successfully']);
            } else {
                throw new Exception("Error updating address");
            }
            $stmt->close();
            break;

        case 'delete_address':
            $addressId = $data['addressId'] ?? '';
            if (empty($addressId) || empty($customerID)) {
                throw new Exception("Address ID or customer ID are required");
            }

            $stmt = $conn->prepare("DELETE FROM customer_address WHERE id = ? AND CustomerID = ?");
            $stmt->bind_param("is", $addressId, $customerID);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Address removed successfully']);
            } else {
                throw new Exception("Error deleting address");
            }
            $stmt->close();
            break;

        default:
            throw new Exception("Invalid action specified");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
