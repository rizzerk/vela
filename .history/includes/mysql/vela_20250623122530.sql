CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    role ENUM('tenant', 'landlord') NOT NULL,
    notifications BOOLEAN DEFAULT TRUE
);

CREATE TABLE PROPERTY (
    property_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100),
    address VARCHAR(255),
    status ENUM('available', 'unavailable', 'maintenance') NOT NULL,
    description VARCHAR(255),
    monthly_rent DECIMAL(10, 2)
);

CREATE TABLE PROPERTY_PHOTO (
    photo_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    file_path VARCHAR(255),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES PROPERTY(property_id)
);

CREATE TABLE APPLICATIONS (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    applicant_id INT,
    status ENUM('pending', 'approved', 'rejected'),
    submitted_at DATETIME,
    approved_at DATETIME,
    documents VARCHAR(255),
    num_of_tenants INT,
    co_tenants VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES PROPERTY(property_id),
    FOREIGN KEY (applicant_id) REFERENCES USERS(user_id)
);

CREATE TABLE LEASE (
    lease_id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT,
    property_id INT,
    start_date DATE,
    end_date DATE,
    active BOOLEAN,
    FOREIGN KEY (tenant_id) REFERENCES USERS(user_id),
    FOREIGN KEY (property_id) REFERENCES PROPERTY(property_id)
);

CREATE TABLE MAINTENANCE_REQUEST (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    lease_id INT,
    description TEXT,
    status ENUM('pending', 'in_progress', 'resolved'),
    requested_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (lease_id) REFERENCES LEASE(lease_id)
);

CREATE TABLE BILL (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    amount DECIMAL(10, 2),
    due_date DATE,
    lease_id INT,
    status ENUM('unpaid', 'paid', 'overdue'),
    description VARCHAR(255),
    FOREIGN KEY (lease_id) REFERENCES LEASE(lease_id)
);

CREATE TABLE PAYMENT (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT,
    amount_paid DECIMAL(10, 2),
    proof_of_payment VARCHAR(255),
    submitted_at DATETIME,
    reference_num VARCHAR(100),
    mode ENUM('cash', 'bpi', 'gcash', 'bdo'),
    status ENUM('pending', 'verified', 'rejected'),
    message TEXT,
    reply TEXT,
    FOREIGN KEY (bill_id) REFERENCES BILL(bill_id)
);

CREATE TABLE ANNOUNCEMENT (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    content TEXT,
    visible_to ENUM('tenant', 'landlord', 'all') NOT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL,
    created_by INT,
    created_at DATETIME,
    FOREIGN KEY (created_by) REFERENCES USERS(user_id)
);
