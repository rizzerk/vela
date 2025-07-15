<?php
session_start();
require_once 'connection.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - VELA Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #1666ba;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .no-applications {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .no-applications p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: #666;
        }
        
        .no-applications a {
            display: inline-block;
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .no-applications a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 102, 186, 0.2);
        }
        
        .applications-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .application-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .application-property {
            margin-bottom: 1rem;
        }
        
        .application-property h3 {
            font-size: 1.3rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }
        
        .application-property p {
            color: #666;
        }
        
        .application-details h4 {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            color: #444;
        }
        
        .application-details p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .application-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .application-meta {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .application-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarOUT.html" ?>
    
    <div class="container">
            <div class="no-applications">
                <p>Log In to view your applications</p>
                <a href="index.php">Log In</a>
                <p>No account yet? Register to Apply Property Reservations</p>
                <a href="register.php">Register</a>
            </div>
    </div>
</body>
