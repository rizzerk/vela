<?php
require_once '../connection.php';

// Get all active leases
$leasesQuery = "SELECT l.lease_id, l.property_id, l.tenant_id, p.monthly_rent 
                FROM LEASE l
                JOIN PROPERTY p ON l.property_id = p.property_id
                WHERE l.active = 1";
$leasesResult = $conn->query($leasesQuery);

while ($lease = $leasesResult->fetch_assoc()) {
    // Check if a bill already exists for this month
    $currentMonth = date('Y-m-01');
    $checkBillQuery = "SELECT bill_id FROM BILL 
                      WHERE lease_id = ? 
                      AND bill_type = 'rent'
                      AND billing_period_start = ?";
    $checkStmt = $conn->prepare($checkBillQuery);
    $checkStmt->bind_param("is", $lease['lease_id'], $currentMonth);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        // No bill exists for this month - create one
        $due_date = date('Y-m-d', strtotime('+5 days'));
        $period_start = $currentMonth;
        $period_end = date('Y-m-t'); // Last day of current month
        
        $insertStmt = $conn->prepare("INSERT INTO BILL 
            (lease_id, amount, due_date, status, description, 
             billing_period_start, billing_period_end, bill_type) 
            VALUES (?, ?, ?, 'unpaid', 'Monthly Rent', ?, ?, 'rent')");
        
        $insertStmt->bind_param("idssss", 
            $lease['lease_id'],
            $lease['monthly_rent'],
            $due_date,
            $period_start,
            $period_end
        );
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    $checkStmt->close();
}

echo "Monthly bills generated successfully";
?>