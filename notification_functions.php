<?php
require_once 'mailer.php';
require_once '../connection.php';

class NotificationSystem {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // ==================== BILL NOTIFICATIONS ====================
    public function sendBillNotification($billId) {
        $bill = $this->getBillDetails($billId);
        if (!$bill) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($bill['tenant_email'], $bill['tenant_name']);
            
            // CC landlord for certain bill types
            if ($bill['bill_type'] !== 'rent') {
                $mail->addCC($bill['landlord_email'], $bill['landlord_name']);
            }
            
            $mail->Subject = 'New Bill Notification: ' . $bill['property_title'];
            $mail->Body = $this->generateBillEmailBody($bill);
            $mail->AltBody = $this->generateBillTextBody($bill);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Bill notification failed for bill #$billId: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendOverdueNotice($billId) {
        $bill = $this->getBillDetails($billId);
        if (!$bill || $bill['status'] !== 'overdue') return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($bill['tenant_email'], $bill['tenant_name']);
            $mail->addCC($bill['landlord_email'], $bill['landlord_name']);
            
            $mail->Subject = 'URGENT: Overdue Payment for ' . $bill['property_title'];
            $mail->Body = $this->generateOverdueEmailBody($bill);
            $mail->AltBody = $this->generateOverdueTextBody($bill);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Overdue notice failed for bill #$billId: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== PAYMENT NOTIFICATIONS ====================
    public function sendPaymentConfirmation($paymentId) {
        $payment = $this->getPaymentDetails($paymentId);
        if (!$payment) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($payment['tenant_email'], $payment['tenant_name']);
            $mail->addCC($payment['landlord_email'], $payment['landlord_name']);
            
            $mail->Subject = 'Payment Confirmation #' . $payment['payment_id'];
            $mail->Body = $this->generatePaymentEmailBody($payment);
            $mail->AltBody = $this->generatePaymentTextBody($payment);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Payment confirmation failed for payment #$paymentId: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPaymentVerification($paymentId, $isApproved, $adminMessage = '') {
        $payment = $this->getPaymentDetails($paymentId);
        if (!$payment) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($payment['tenant_email'], $payment['tenant_name']);
            
            $status = $isApproved ? 'Approved' : 'Rejected';
            $mail->Subject = "Payment Verification $status: #" . $payment['payment_id'];
            
            $mail->Body = $this->generatePaymentVerificationEmailBody($payment, $isApproved, $adminMessage);
            $mail->AltBody = $this->generatePaymentVerificationTextBody($payment, $isApproved, $adminMessage);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Payment verification notice failed: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== APPLICATION NOTIFICATIONS ====================
    public function sendApplicationStatusUpdate($applicationId) {
        $application = $this->getApplicationDetails($applicationId);
        if (!$application) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($application['applicant_email'], $application['applicant_name']);
            
            $mail->Subject = 'Application Update: ' . $application['property_title'];
            $mail->Body = $this->generateApplicationEmailBody($application);
            $mail->AltBody = $this->generateApplicationTextBody($application);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Application update failed for application #$applicationId: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendNewApplicationAlert($applicationId) {
        $application = $this->getApplicationDetails($applicationId);
        if (!$application) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($application['landlord_email'], $application['landlord_name']);
            
            $mail->Subject = 'New Application: ' . $application['property_title'];
            $mail->Body = $this->generateNewApplicationEmailBody($application);
            $mail->AltBody = $this->generateNewApplicationTextBody($application);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("New application alert failed: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== MAINTENANCE NOTIFICATIONS ====================
    public function sendMaintenanceStatusUpdate($requestId) {
        $request = $this->getMaintenanceDetails($requestId);
        if (!$request) return false;
        
        $mail = getMailer();
        
        try {
            // Always notify tenant
            $mail->addAddress($request['tenant_email'], $request['tenant_name']);
            
            // Notify landlord when status changes from pending
            if ($request['status'] != 'pending') {
                $mail->addCC($request['landlord_email'], $request['landlord_name']);
            }
            
            $mail->Subject = 'Maintenance Request #' . $requestId . ' Update';
            $mail->Body = $this->generateMaintenanceEmailBody($request);
            $mail->AltBody = $this->generateMaintenanceTextBody($request);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Maintenance update failed for request #$requestId: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendNewMaintenanceAlert($requestId) {
        $request = $this->getMaintenanceDetails($requestId);
        if (!$request) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($request['landlord_email'], $request['landlord_name']);
            
            $mail->Subject = 'New Maintenance Request: ' . $request['property_title'];
            $mail->Body = $this->generateNewMaintenanceEmailBody($request);
            $mail->AltBody = $this->generateNewMaintenanceTextBody($request);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("New maintenance alert failed: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== ANNOUNCEMENT NOTIFICATIONS ====================
    public function sendAnnouncement($announcementId, $recipientId) {
        $announcement = $this->getAnnouncementDetails($announcementId);
        $recipient = $this->getUserDetails($recipientId);
        
        if (!$announcement || !$recipient) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($recipient['email'], $recipient['name']);
            
            $mail->Subject = 'Announcement: ' . $announcement['title'];
            $mail->Body = $this->generateAnnouncementEmailBody($announcement, $recipient);
            $mail->AltBody = $this->generateAnnouncementTextBody($announcement, $recipient);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Announcement failed to send: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== DATABASE QUERY HELPERS ====================
    private function getBillDetails($billId) {
        $query = "SELECT b.*, p.title as property_title, 
                         u.name as tenant_name, u.email as tenant_email,
                         ul.name as landlord_name, ul.email as landlord_email
                  FROM BILL b
                  JOIN LEASE l ON b.lease_id = l.lease_id
                  JOIN PROPERTY p ON l.property_id = p.property_id
                  JOIN USERS u ON l.tenant_id = u.user_id
                  JOIN USERS ul ON p.landlord_id = ul.user_id
                  WHERE b.bill_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $billId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getPaymentDetails($paymentId) {
        $query = "SELECT p.*, b.bill_type, b.amount, b.due_date,
                         pr.title as property_title, 
                         u.name as tenant_name, u.email as tenant_email,
                         ul.name as landlord_name, ul.email as landlord_email
                  FROM PAYMENT p
                  JOIN BILL b ON p.bill_id = b.bill_id
                  JOIN LEASE l ON b.lease_id = l.lease_id
                  JOIN PROPERTY pr ON l.property_id = pr.property_id
                  JOIN USERS u ON l.tenant_id = u.user_id
                  JOIN USERS ul ON pr.landlord_id = ul.user_id
                  WHERE p.payment_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getApplicationDetails($applicationId) {
        $query = "SELECT a.*, p.title as property_title,
                         u.name as applicant_name, u.email as applicant_email,
                         ul.name as landlord_name, ul.email as landlord_email
                  FROM APPLICATIONS a
                  JOIN PROPERTY p ON a.property_id = p.property_id
                  JOIN USERS u ON a.applicant_id = u.user_id
                  JOIN USERS ul ON p.landlord_id = ul.user_id
                  WHERE a.application_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getMaintenanceDetails($requestId) {
        $query = "SELECT mr.*, p.title as property_title,
                         u.name as tenant_name, u.email as tenant_email,
                         ul.name as landlord_name, ul.email as landlord_email
                  FROM MAINTENANCE_REQUEST mr
                  JOIN LEASE l ON mr.lease_id = l.lease_id
                  JOIN PROPERTY p ON l.property_id = p.property_id
                  JOIN USERS u ON l.tenant_id = u.user_id
                  JOIN USERS ul ON p.landlord_id = ul.user_id
                  WHERE mr.request_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getAnnouncementDetails($announcementId) {
        $query = "SELECT * FROM ANNOUNCEMENT WHERE announcement_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $announcementId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getUserDetails($userId) {
        $query = "SELECT * FROM USERS WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // ==================== EMAIL TEMPLATE GENERATORS ====================
    
    // Bill Notification Templates
    private function generateBillEmailBody($bill) {
        $periodInfo = '';
        if ($bill['bill_type'] === 'rent' && $bill['billing_period_start']) {
            $periodInfo = "<p><strong>Billing Period:</strong> " . 
                date('M j', strtotime($bill['billing_period_start'])) . " - " . 
                date('M j, Y', strtotime($bill['billing_period_end'])) . "</p>";
        }
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #1666ba;'>New Bill Notification</h2>
                <p>Hello {$bill['tenant_name']},</p>
                <p>A new bill has been issued for your tenancy at <strong>{$bill['property_title']}</strong>.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Bill Details</h3>
                    <p><strong>Type:</strong> " . ucfirst($bill['bill_type']) . "</p>
                    <p><strong>Amount Due:</strong> ₱" . number_format($bill['amount'], 2) . "</p>
                    <p><strong>Due Date:</strong> " . date('F j, Y', strtotime($bill['due_date'])) . "</p>
                    $periodInfo
                    " . (!empty($bill['description']) ? "<p><strong>Notes:</strong> {$bill['description']}</p>" : "") . "
                </div>
                
                <p>Please ensure payment is made by the due date to avoid late fees.</p>
                <p>You can view and pay this bill through your tenant portal.</p>
                
                <p>Thank you,<br>
                <strong>Property Management Team</strong></p>
                
                <p style='font-size: 0.8em; color: #666; margin-top: 30px;'>
                    This is an automated message. Please do not reply directly to this email.
                </p>
            </div>";
    }
    
    private function generateOverdueEmailBody($bill) {
        $daysOverdue = floor((time() - strtotime($bill['due_date'])) / (60 * 60 * 24));
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #d9534f;'>URGENT: Overdue Payment</h2>
                <p>Hello {$bill['tenant_name']},</p>
                <p>Your payment for <strong>{$bill['property_title']}</strong> is <strong>{$daysOverdue} days overdue</strong>.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Overdue Bill</h3>
                    <p><strong>Type:</strong> " . ucfirst($bill['bill_type']) . "</p>
                    <p><strong>Amount Due:</strong> ₱" . number_format($bill['amount'], 2) . "</p>
                    <p><strong>Original Due Date:</strong> " . date('F j, Y', strtotime($bill['due_date'])) . "</p>
                    <p><strong>Days Overdue:</strong> {$daysOverdue}</p>
                </div>
                
                <p style='color: #d9534f;'><strong>Immediate payment is required to avoid further penalties.</strong></p>
                
                <p>Please make payment immediately through your preferred method:</p>
                <ul>
                    <li>Bank Transfer (BPI/BDO)</li>
                    <li>GCash</li>
                    <li>Cash at our office</li>
                </ul>
                
                <p>If you've already made this payment, please submit your proof of payment through the tenant portal.</p>
                
                <p>Thank you,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    // Payment Notification Templates
    private function generatePaymentEmailBody($payment) {
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #5cb85c;'>Payment Received</h2>
                <p>Hello {$payment['tenant_name']},</p>
                <p>Thank you for your payment. Here are the details:</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Payment Details</h3>
                    <p><strong>Property:</strong> {$payment['property_title']}</p>
                    <p><strong>Payment ID:</strong> #{$payment['payment_id']}</p>
                    <p><strong>Amount:</strong> ₱" . number_format($payment['amount_paid'], 2) . "</p>
                    <p><strong>Payment Method:</strong> " . ucfirst($payment['mode']) . "</p>
                    <p><strong>Date Received:</strong> " . date('F j, Y', strtotime($payment['submitted_at'])) . "</p>
                </div>
                
                <p>This payment has been recorded in our system. You can view your updated balance in your tenant portal.</p>
                
                <p>Thank you,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    private function generatePaymentVerificationEmailBody($payment, $isApproved, $adminMessage) {
        $status = $isApproved ? 'APPROVED' : 'REJECTED';
        $color = $isApproved ? '#5cb85c' : '#d9534f';
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: $color;'>Payment Verification: $status</h2>
                <p>Hello {$payment['tenant_name']},</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Payment Details</h3>
                    <p><strong>Property:</strong> {$payment['property_title']}</p>
                    <p><strong>Payment ID:</strong> #{$payment['payment_id']}</p>
                    <p><strong>Amount:</strong> ₱" . number_format($payment['amount_paid'], 2) . "</p>
                    <p><strong>Status:</strong> <span style='color: $color; font-weight: bold;'>$status</span></p>
                    " . (!empty($adminMessage) ? "<p><strong>Message:</strong> $adminMessage</p>" : "" . "
                </div>
                
                " . ($isApproved ? 
                    "<p>Your payment has been verified and applied to your account.</p>" :
                    "<p>Your payment submission was not approved. Please review the message above and submit a new payment if needed.</p>") . "
                
                <p>Thank you,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    // Application Notification Templates
    private function generateApplicationEmailBody($application) {
        $status = ucfirst($application['status']);
        $color = $application['status'] == 'approved' ? '#5cb85c' : 
                ($application['status'] == 'rejected' ? '#d9534f' : '#f0ad4e');
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: $color;'>Application Update</h2>
                <p>Hello {$application['applicant_name']},</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Application Details</h3>
                    <p><strong>Property:</strong> {$application['property_title']}</p>
                    <p><strong>Application ID:</strong> #{$application['application_id']}</p>
                    <p><strong>Status:</strong> <span style='color: $color; font-weight: bold;'>$status</span></p>
                    <p><strong>Date Submitted:</strong> " . date('F j, Y', strtotime($application['submitted_at'])) . "</p>
                </div>
                
                " . ($application['status'] == 'approved' ? 
                    "<p>Congratulations! Your application has been approved. A property manager will contact you shortly to complete the lease signing process.</p>" :
                    ($application['status'] == 'rejected' ? 
                        "<p>We regret to inform you that your application was not approved at this time.</p>" :
                        "<p>Your application is still under review. We will notify you once a decision has been made.</p>")) . "
                
                <p>Thank you for your interest in our property.</p>
                
                <p>Sincerely,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    // Maintenance Notification Templates
    private function generateMaintenanceEmailBody($request) {
        $status = ucfirst(str_replace('_', ' ', $request['status']));
        $color = $request['status'] == 'resolved' ? '#5cb85c' : 
                ($request['status'] == 'in_progress' ? '#f0ad4e' : '#5bc0de');
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: $color;'>Maintenance Request Update</h2>
                <p>Hello {$request['tenant_name']},</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Request Details</h3>
                    <p><strong>Property:</strong> {$request['property_title']}</p>
                    <p><strong>Request ID:</strong> #{$request['request_id']}</p>
                    <p><strong>Status:</strong> <span style='color: $color; font-weight: bold;'>$status</span></p>
                    <p><strong>Date Submitted:</strong> " . date('F j, Y', strtotime($request['requested_at'])) . "</p>
                    <p><strong>Description:</strong> {$request['description']}</p>
                </div>
                
                " . ($request['status'] == 'resolved' ? 
                    "<p>Your maintenance request has been completed. If you have any concerns about the work performed, please contact us.</p>" :
                    ($request['status'] == 'in_progress' ? 
                        "<p>A technician has been assigned to your request and will be in touch shortly.</p>" :
                        "<p>Your request has been received and is being reviewed. We will update you on next steps.</p>")) . "
                
                <p>Thank you,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    // Text versions of all email templates (for plain-text fallback)
    private function generateBillTextBody($bill) {
        $periodInfo = '';
        if ($bill['bill_type'] === 'rent' && $bill['billing_period_start']) {
            $periodInfo = "Billing Period: " . 
                date('M j', strtotime($bill['billing_period_start'])) . " - " . 
                date('M j, Y', strtotime($bill['billing_period_end'])) . "\n";
        }
        
        return "New Bill Notification\n\n" .
               "Hello {$bill['tenant_name']},\n" .
               "A new bill has been issued for your tenancy at {$bill['property_title']}.\n\n" .
               "BILL DETAILS\n" .
               "Type: " . ucfirst($bill['bill_type']) . "\n" .
               "Amount Due: ₱" . number_format($bill['amount'], 2) . "\n" .
               "Due Date: " . date('F j, Y', strtotime($bill['due_date'])) . "\n" .
               $periodInfo .
               (!empty($bill['description']) ? "Notes: {$bill['description']}\n" : "") . "\n" .
               "Please ensure payment is made by the due date to avoid late fees.\n" .
               "You can view and pay this bill through your tenant portal.\n\n" .
               "Thank you,\n" .
               "Property Management Team";
    }
    
    // ... (similar text versions for all other email types)
    
    private function generateOverdueTextBody($bill) {
        $daysOverdue = floor((time() - strtotime($bill['due_date'])) / (60 * 60 * 24));
        
        return "URGENT: Overdue Payment\n\n" .
               "Hello {$bill['tenant_name']},\n" .
               "Your payment for {$bill['property_title']} is {$daysOverdue} days overdue.\n\n" .
               "OVERDUE BILL\n" .
               "Type: " . ucfirst($bill['bill_type']) . "\n" .
               "Amount Due: ₱" . number_format($bill['amount'], 2) . "\n" .
               "Original Due Date: " . date('F j, Y', strtotime($bill['due_date'])) . "\n" .
               "Days Overdue: {$daysOverdue}\n\n" .
               "IMMEDIATE PAYMENT IS REQUIRED TO AVOID FURTHER PENALTIES.\n\n" .
               "Please make payment immediately through your preferred method:\n" .
               "- Bank Transfer (BPI/BDO)\n" .
               "- GCash\n" .
               "- Cash at our office\n\n" .
               "If you've already made this payment, please submit your proof of payment through the tenant portal.\n\n" .
               "Thank you,\n" .
               "Property Management Team";
    }
    
    private function generateNewApplicationEmailBody($application) {
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #5bc0de;'>New Application Submitted</h2>
                <p>Hello {$application['landlord_name']},</p>
                <p>A new application has been submitted for your property:</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Application Details</h3>
                    <p><strong>Property:</strong> {$application['property_title']}</p>
                    <p><strong>Applicant:</strong> {$application['applicant_name']}</p>
                    <p><strong>Application ID:</strong> #{$application['application_id']}</p>
                    <p><strong>Date Submitted:</strong> " . date('F j, Y g:i a', strtotime($application['submitted_at'])) . "</p>
                    <p><strong>Number of Tenants:</strong> {$application['num_of_tenants']}</p>
                    <p><strong>Monthly Income:</strong> ₱" . number_format($application['monthly_income'], 0) . "</p>
                    <p><strong>Occupation:</strong> {$application['occupation']}</p>
                </div>
                
                <p>Please review this application in your landlord portal and approve or reject it within 3 business days.</p>
                
                <p>Sincerely,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    private function generateNewApplicationTextBody($application) {
        return "New Application Submitted\n\n" .
               "Hello {$application['landlord_name']},\n" .
               "A new application has been submitted for your property:\n\n" .
               "APPLICATION DETAILS\n" .
               "Property: {$application['property_title']}\n" .
               "Applicant: {$application['applicant_name']}\n" .
               "Application ID: #{$application['application_id']}\n" .
               "Date Submitted: " . date('F j, Y g:i a', strtotime($application['submitted_at'])) . "\n" .
               "Number of Tenants: {$application['num_of_tenants']}\n" .
               "Monthly Income: ₱" . number_format($application['monthly_income'], 0) . "\n" .
               "Occupation: {$application['occupation']}\n\n" .
               "Please review this application in your landlord portal and approve or reject it within 3 business days.\n\n" .
               "Sincerely,\n" .
               "Property Management Team";
    }

    private function generateNewMaintenanceEmailBody($request) {
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #5bc0de;'>New Maintenance Request</h2>
                <p>Hello {$request['landlord_name']},</p>
                <p>A new maintenance request has been submitted for your property:</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Request Details</h3>
                    <p><strong>Property:</strong> {$request['property_title']}</p>
                    <p><strong>Tenant:</strong> {$request['tenant_name']}</p>
                    <p><strong>Request ID:</strong> #{$request['request_id']}</p>
                    <p><strong>Date Submitted:</strong> " . date('F j, Y g:i a', strtotime($request['requested_at'])) . "</p>
                    <p><strong>Description:</strong><br>{$request['description']}</p>
                </div>
                
                <p>Please review this request in your landlord portal and update its status within 24 hours.</p>
                
                <p>Sincerely,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    private function generateNewMaintenanceTextBody($request) {
        return "New Maintenance Request\n\n" .
               "Hello {$request['landlord_name']},\n" .
               "A new maintenance request has been submitted for your property:\n\n" .
               "REQUEST DETAILS\n" .
               "Property: {$request['property_title']}\n" .
               "Tenant: {$request['tenant_name']}\n" .
               "Request ID: #{$request['request_id']}\n" .
               "Date Submitted: " . date('F j, Y g:i a', strtotime($request['requested_at'])) . "\n" .
               "Description:\n{$request['description']}\n\n" .
               "Please review this request in your landlord portal and update its status within 24 hours.\n\n" .
               "Sincerely,\n" .
               "Property Management Team";
    }

    private function generateAnnouncementEmailBody($announcement, $recipient) {
        $priorityClass = '';
        $priorityText = '';
        
        switch($announcement['priority']) {
            case 'high':
                $priorityClass = 'color: #d9534f;';
                $priorityText = '❗️ HIGH PRIORITY ❗️';
                break;
            case 'medium':
                $priorityClass = 'color: #f0ad4e;';
                $priorityText = '⚠️ IMPORTANT';
                break;
            default:
                $priorityClass = 'color: #5bc0de;';
                $priorityText = 'ℹ️ Information';
        }
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='{$priorityClass}'>Announcement: {$announcement['title']}</h2>
                <p style='font-weight: bold;'>{$priorityText}</p>
                <p>Hello {$recipient['name']},</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <p style='white-space: pre-line;'>{$announcement['content']}</p>
                </div>
                
                <p><strong>Visible To:</strong> " . ucfirst($announcement['visible_to']) . "</p>
                <p><strong>Posted On:</strong> " . date('F j, Y g:i a', strtotime($announcement['created_at'])) . "</p>
                
                <p>Please take appropriate action based on this announcement.</p>
                
                <p>Sincerely,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    private function generateAnnouncementTextBody($announcement, $recipient) {
        $priorityText = '';
        
        switch($announcement['priority']) {
            case 'high':
                $priorityText = '❗️ HIGH PRIORITY ❗️';
                break;
            case 'medium':
                $priorityText = '⚠️ IMPORTANT';
                break;
            default:
                $priorityText = 'ℹ️ Information';
        }
        
        return "ANNOUNCEMENT: {$announcement['title']}\n\n" .
               "{$priorityText}\n\n" .
               "Hello {$recipient['name']},\n\n" .
               "{$announcement['content']}\n\n" .
               "Visible To: " . ucfirst($announcement['visible_to']) . "\n" .
               "Posted On: " . date('F j, Y g:i a', strtotime($announcement['created_at'])) . "\n\n" .
               "Please take appropriate action based on this announcement.\n\n" .
               "Sincerely,\n" .
               "Property Management Team";
    }

    public function sendLeaseRenewalNotice($leaseId, $daysBeforeExpiry = 30) {
        $lease = $this->getLeaseDetails($leaseId);
        if (!$lease) return false;
        
        $mail = getMailer();
        
        try {
            $mail->addAddress($lease['tenant_email'], $lease['tenant_name']);
            $mail->addCC($lease['landlord_email'], $lease['landlord_name']);
            
            $mail->Subject = "Lease Renewal Notice: {$lease['property_title']}";
            $mail->Body = $this->generateLeaseRenewalEmailBody($lease, $daysBeforeExpiry);
            $mail->AltBody = $this->generateLeaseRenewalTextBody($lease, $daysBeforeExpiry);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Lease renewal notice failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateLeaseRenewalEmailBody($lease, $daysBeforeExpiry) {
        $renewalDeadline = date('F j, Y', strtotime($lease['end_date'] . " -{$daysBeforeExpiry} days"));
        
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #1666ba;'>Lease Renewal Notice</h2>
                <p>Hello {$lease['tenant_name']},</p>
                <p>Your lease for <strong>{$lease['property_title']}</strong> will expire on <strong>" . 
                date('F j, Y', strtotime($lease['end_date'])) . "</strong>.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='margin-top: 0;'>Lease Details</h3>
                    <p><strong>Property:</strong> {$lease['property_title']}</p>
                    <p><strong>Current Lease Term:</strong> " . 
                    date('M j, Y', strtotime($lease['start_date'])) . " to " . 
                    date('M j, Y', strtotime($lease['end_date'])) . "</p>
                    <p><strong>Monthly Rent:</strong> ₱" . number_format($lease['monthly_rent'], 2) . "</p>
                    <p><strong>Renewal Deadline:</strong> {$renewalDeadline}</p>
                </div>
                
                <p>Please indicate your intention to renew or vacate by the deadline above.</p>
                <p>If you wish to renew, we will send you the updated lease agreement for signing.</p>
                
                <p>Sincerely,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    private function getLeaseDetails($leaseId) {
        $query = "SELECT l.*, p.title as property_title, p.monthly_rent,
                         u.name as tenant_name, u.email as tenant_email,
                         ul.name as landlord_name, ul.email as landlord_email
                  FROM LEASE l
                  JOIN PROPERTY p ON l.property_id = p.property_id
                  JOIN USERS u ON l.tenant_id = u.user_id
                  JOIN USERS ul ON p.landlord_id = ul.user_id
                  WHERE l.lease_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $leaseId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function sendEmergencyAlert($propertyId, $message) {
        $property = $this->getPropertyWithContacts($propertyId);
        if (!$property) return false;
        
        $mail = getMailer();
        
        try {
            // Send to tenant
            $mail->addAddress($property['tenant_email'], $property['tenant_name']);
            
            // Send to landlord
            $mail->addCC($property['landlord_email'], $property['landlord_name']);
            
            // Set emergency subject
            $mail->Subject = "🚨 EMERGENCY ALERT: {$property['title']}";
            
            $mail->Body = $this->generateEmergencyEmailBody($property, $message);
            $mail->AltBody = $this->generateEmergencyTextBody($property, $message);
            
            // High priority header
            $mail->Priority = 1;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Emergency alert failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateEmergencyEmailBody($property, $message) {
        return "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #d9534f;'>🚨 EMERGENCY ALERT</h2>
                <p>Hello {$property['tenant_name']},</p>
                
                <div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #ffcdd2;'>
                    <p style='white-space: pre-line; font-weight: bold;'>{$message}</p>
                </div>
                
                <p><strong>Property:</strong> {$property['title']}</p>
                <p><strong>Address:</strong> {$property['address']}</p>
                
                <p>Please take immediate action as instructed above. Emergency services have been notified.</p>
                
                <p style='font-weight: bold;'>For immediate assistance, contact:</p>
                <ul>
                    <li>Property Manager: 09123456789</li>
                    <li>Emergency Services: 911</li>
                </ul>
                
                <p>Sincerely,<br>
                <strong>Property Management Team</strong></p>
            </div>";
    }
    
    private function getPropertyWithContacts($propertyId) {
        $query = "SELECT p.*, 
                         u.name as tenant_name, u.email as tenant_email, u.phone as tenant_phone,
                         ul.name as landlord_name, ul.email as landlord_email, ul.phone as landlord_phone
                  FROM PROPERTY p
                  LEFT JOIN LEASE l ON p.property_id = l.property_id AND l.active = 1
                  LEFT JOIN USERS u ON l.tenant_id = u.user_id
                  JOIN USERS ul ON p.landlord_id = ul.user_id
                  WHERE p.property_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>