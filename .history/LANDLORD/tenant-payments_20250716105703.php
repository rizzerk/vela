<?php 
session_start();
require_once '../connection.php';
require_once '../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

$landlord_id = $_SESSION['user_id'] ?? 1;

// Handle status update via AJAX
if (isset($_POST['update_status'])) {
    $payment_id = $_POST['payment_id'];
    $new_status = $_POST['status'];

    // Validate status
    $allowed_statuses = ['pending', 'verified', 'rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // First get tenant email and payment details before updating
    $payment_details_query = "
        SELECT u.email, u.name AS tenant_name, p.amount_paid, p.reference_num, b.bill_type 
        FROM PAYMENT p
        JOIN BILL b ON p.bill_id = b.bill_id
        JOIN LEASE l ON b.lease_id = l.lease_id
        JOIN USERS u ON l.tenant_id = u.user_id
        WHERE p.payment_id = ?
    ";
    $stmt = $conn->prepare($payment_details_query);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_details = $result->fetch_assoc();
    $stmt->close();

    // Update payment status
    $update_query = "UPDATE PAYMENT SET status = ? WHERE payment_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $payment_id);

    if ($stmt->execute()) {
        // If payment is verified, update the bill status to paid
        if ($new_status === 'verified') {
            $bill_update_query = "
                UPDATE BILL b 
                JOIN PAYMENT p ON b.bill_id = p.bill_id 
                SET b.status = 'paid' 
                WHERE p.payment_id = ?
            ";
            $bill_stmt = $conn->prepare($bill_update_query);
            $bill_stmt->bind_param("i", $payment_id);
            $bill_stmt->execute();
            $bill_stmt->close();
        }

        // Send email notification to tenant
        if ($payment_details && !empty($payment_details['email'])) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'velacinco5@gmail.com'; // SMTP username
                $mail->Password   = 'aycm atee woxl lmvj'; // SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Recipients
                $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
                $mail->addAddress($payment_details['email'], $payment_details['tenant_name']);
                
                // Content
                $mail->isHTML(true);
                
                if ($new_status === 'verified') {
                    $mail->Subject = 'Payment Verified - ' . $payment_details['reference_num'];
                    $mail->Body    = '
                        <h2>Payment Verified</h2>
                        <p>Hello ' . htmlspecialchars($payment_details['tenant_name']) . ',</p>
                        <p>Your payment of <strong>₱' . number_format($payment_details['amount_paid'], 2) . '</strong> for ' . 
                        htmlspecialchars($payment_details['bill_type']) . ' (Reference: ' . htmlspecialchars($payment_details['reference_num']) . ') has been verified.</p>
                        <p>Thank you for your payment!</p>
                        <p>Best regards,<br>VELA Team</p>
                    ';
                } else { // rejected
                    $mail->Subject = 'Payment Rejected - ' . $payment_details['reference_num'];
                    $mail->Body    = '
                        <h2>Payment Rejected</h2>
                        <p>Hello ' . htmlspecialchars($payment_details['tenant_name']) . ',</p>
                        <p>Your payment of <strong>₱' . number_format($payment_details['amount_paid'], 2) . '</strong> for ' . 
                        htmlspecialchars($payment_details['bill_type']) . ' (Reference: ' . htmlspecialchars($payment_details['reference_num']) . ') has been rejected.</p>
                        <p>Please review your payment details and submit a new payment if needed.</p>
                        <p>If you believe this was a mistake, please contact your landlord.</p>
                        <p>Best regards,<br>VELA Team</p>
                    ';
                }
                
                $mail->AltBody = strip_tags($mail->Body);
                $mail->send();
            } catch (Exception $e) {
                // Log error but don't fail the operation
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// Modified query to sort pending payments first
$query = "
    SELECT 
        u.name AS tenant_name,
        p.submitted_at,
        p.status,
        p.proof_of_payment,
        p.amount_paid,
        p.payment_id,
        p.reference_num,
        p.mode,
        p.bill_id,
        b.bill_type,
        b.description AS bill_description,
        b.due_date,
        b.billing_period_start,
        b.billing_period_end
    FROM PAYMENT p
    JOIN BILL b ON p.bill_id = b.bill_id
    JOIN LEASE l ON b.lease_id = l.lease_id
    JOIN USERS u ON l.tenant_id = u.user_id
    JOIN PROPERTY prop ON l.property_id = prop.property_id
    WHERE prop.property_id IN (
        SELECT property_id FROM PROPERTY WHERE property_id = prop.property_id
    )
    ORDER BY 
        CASE 
            WHEN p.status = 'pending' THEN 1
            WHEN p.status = 'verified' THEN 2
            WHEN p.status = 'rejected' THEN 3
            ELSE 4
        END,
        p.submitted_at DESC
";

$result = $conn->query($query);
$payments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payments - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f6f6;
            margin: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        h1 {
            font-size: 2rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(22, 102, 186, 0.1);
        }

        th,
        td {
            padding: 0.8rem;
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            background-color: #eef4fb;
            color: #1666ba;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Highlight pending payments */
        tr.pending-payment {
            background-color: #fef3c7 !important;
            border-left: 4px solid #f59e0b;
        }

        tr.pending-payment:nth-child(even) {
            background-color: #fef3c7 !important;
        }

        .status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .pending {
            background-color: #fcd34d;
            color: #92400e;
        }

        .verified {
            background-color: #4ade80;
            color: #065f46;
        }

        .rejected {
            background-color: #f87171;
            color: #7f1d1d;
        }

        .bill-type {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
        }

        .bill-type.rent {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .bill-type.utility {
            background-color: #fef3c7;
            color: #d97706;
        }

        .bill-type.penalty {
            background-color: #fecaca;
            color: #dc2626;
        }

        .bill-type.other {
            background-color: #e5e7eb;
            color: #374151;
        }

        .bill-info {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .bill-id {
            font-weight: 600;
            color: #1666ba;
        }

        .bill-details {
            font-size: 0.8rem;
            color: #64748b;
        }

        .view-proof-btn {
            background-color: #1666ba;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .view-proof-btn:hover {
            background-color: #0d4a8a;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .approve-btn {
            background-color: #10b981;
            color: white;
        }

        .approve-btn:hover:not(:disabled) {
            background-color: #059669;
        }

        .reject-btn {
            background-color: #ef4444;
            color: white;
        }

        .reject-btn:hover:not(:disabled) {
            background-color: #dc2626;
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #9ca3af !important;
        }

        .action-completed {
            font-size: 0.8rem;
            color: #6b7280;
            font-style: italic;
        }
        .no-payments {
            padding: 2rem;
        }

        .no-record {
            text-align: center;
            color: #64748b;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            background: white;
            border-radius: 8px;
            padding: 1rem;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        /* Responsive table */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 0.6rem;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #1666ba;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <h1>Payments</h1>
        <?php if (empty($payments)): ?>
            <div class="no-payments">
                <i class="fas fa-file-invoice-dollar" style="font-size:2rem;"></i>
                <p>No payment records found.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Proof of Payment</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <th>Tenant</th>
                        <th>Bill Info</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Proof</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="<?= $payment['status'] === 'pending' ? 'pending-payment' : '' ?>">
                            <td><?= htmlspecialchars($payment['tenant_name']) ?></td>
                            <td>
                                <div class="bill-info">
                                    <div class="bill-id">Bill #<?= $payment['bill_id'] ?></div>
                                    <div class="bill-details">
                                        <span class="bill-type <?= strtolower($payment['bill_type']) ?>">
                                            <?= ucfirst($payment['bill_type']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($payment['bill_description'])): ?>
                                        <div class="bill-details"><?= htmlspecialchars($payment['bill_description']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($payment['billing_period_start'] && $payment['billing_period_end']): ?>
                                        <div class="bill-details">
                                            Period: <?= date('M d', strtotime($payment['billing_period_start'])) ?> -
                                            <?= date('M d, Y', strtotime($payment['billing_period_end'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($payment['submitted_at'])) ?></td>
                            <td>
                            <span class="status <?= strtolower($payment['status']) ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                                <select class="status-dropdown" onchange="updatePaymentStatus(<?= $payment['payment_id'] ?>, this.value)">
                                    <option value="pending" <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $payment['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="rejected" <?= $payment['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </td>
                            <td>₱<?= number_format($payment['amount_paid'], 2) ?></td>
                            <td><?= ucfirst($payment['mode']) ?></td>
                            <td><?= htmlspecialchars($payment['reference_num']) ?></td>
                            <td>
                                <?php if (!empty($payment['proof_of_payment'])): ?>
                                    <button class="view-proof-btn" onclick="openModal('<?= htmlspecialchars($payment['proof_of_payment']) ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                <?php else: ?>
                                    <em style="color:#94a3b8;">No file</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <button class="action-btn approve-btn"
                                            onclick="updateStatus(<?= $payment['payment_id'] ?>, 'verified')"
                                            id="approve-btn-<?= $payment['payment_id'] ?>">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="action-btn reject-btn"
                                            onclick="updateStatus(<?= $payment['payment_id'] ?>, 'rejected')"
                                            id="reject-btn-<?= $payment['payment_id'] ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="action-completed">
                                        <?= $payment['status'] === 'verified' ? 'Payment approved' : 'Payment rejected' ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for viewing proof of payment -->
    <div id="proofModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="Proof of Payment">
        </div>
    </div>

    <script>
        function openModal(imagePath) {
            // Add the correct path prefix if it's not already there
            const fullPath = imagePath.startsWith('../') ? imagePath : '../' + imagePath;
            document.getElementById('modalImage').src = fullPath;
            document.getElementById('proofModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('proofModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('proofModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        function updateStatus(paymentId, status) {
            if (!confirm(`Are you sure you want to ${status === 'verified' ? 'approve' : 'reject'} this payment?`)) {
                return;
            }

            // Disable buttons and show loading state
            const approveBtn = document.getElementById(`approve-btn-${paymentId}`);
            const rejectBtn = document.getElementById(`reject-btn-${paymentId}`);
            const actionContainer = approveBtn.parentElement;

            approveBtn.disabled = true;
            rejectBtn.disabled = true;
            actionContainer.classList.add('loading');

            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('payment_id', paymentId);
            formData.append('status', status);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the UI immediately instead of reloading
                        const row = approveBtn.closest('tr');
                        const statusCell = row.querySelector('.status');
                        const actionsCell = row.querySelector('.action-buttons').parentElement;

                        // Update status display
                        statusCell.className = `status ${status}`;
                        statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);

                        // Replace action buttons with completion message
                        actionsCell.innerHTML = `
                        <div class="action-completed">
                            ${status === 'verified' ? 'Payment approved' : 'Payment rejected'}
                        </div>
                    `;

                        // Remove pending highlight if present
                        if (row.classList.contains('pending-payment')) {
                            row.classList.remove('pending-payment');
                        }

                        // Show success message
                        showNotification(`Payment ${status === 'verified' ? 'approved' : 'rejected'} successfully!`, 'success');

                        // Optionally refresh after a delay to update sorting
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Re-enable buttons on error
                        approveBtn.disabled = false;
                        rejectBtn.disabled = false;
                        actionContainer.classList.remove('loading');
                        showNotification('Error updating status: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Re-enable buttons on error
                    approveBtn.disabled = false;
                    rejectBtn.disabled = false;
                    actionContainer.classList.remove('loading');
                    showNotification('An error occurred while updating the status.', 'error');
                });
        }

        // Simple notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background-color: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                border-radius: 8px;
                z-index: 1001;
                font-size: 0.9rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add CSS animations for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>