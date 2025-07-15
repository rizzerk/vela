-- Insert sample properties
INSERT INTO PROPERTY (title, address, description, monthly_rent, property_type, status) VALUES
('Sunset Apartment', '123 Main Street, Downtown', 'Modern 2-bedroom apartment with city view', 25000.00, 'apartment', 'vacant'),
('Garden Villa', '456 Oak Avenue, Suburbs', 'Spacious 3-bedroom house with garden', 35000.00, 'house', 'vacant'),
('City Loft', '789 Pine Road, Business District', 'Contemporary loft in prime location', 30000.00, 'condo', 'vacant'),
('Cozy Studio', '321 Elm Street, University Area', 'Perfect studio for students', 15000.00, 'studio', 'vacant'),
('Family Home', '654 Maple Drive, Residential', '4-bedroom family house with garage', 45000.00, 'house', 'vacant');

-- Insert sample announcements
INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) VALUES
('Welcome to Vela Properties', 'Welcome to our property management system. Please feel free to contact us for any concerns.', 'all', 'medium', 1, NOW()),
('Rent Payment Reminder', 'Monthly rent is due on the 5th of each month. Late payments will incur penalty fees.', 'tenant', 'high', 1, NOW()),
('Maintenance Schedule', 'Regular maintenance will be conducted every first Saturday of the month from 9AM-12PM.', 'tenant', 'medium', 1, NOW());

-- Insert sample tenant
INSERT INTO USERS (name, email, phone, password, role) VALUES
('Jane Smith', 'tenant@example.com', '09987654321', 'password123', 'tenant');

-- Insert sample lease (assuming tenant_id=2, property_id=1)
INSERT INTO LEASE (tenant_id, property_id, start_date, end_date, active) VALUES
(2, 1, '2024-01-01', '2024-12-31', 1);

-- Insert sample bill (assuming lease_id=1)
INSERT INTO BILL (amount, due_date, lease_id, status, description, billing_period_start, billing_period_end, bill_type) VALUES
(25000.00, '2024-02-05', 1, 'unpaid', 'Monthly rent for February 2024', '2024-02-01', '2024-02-28', 'rent');