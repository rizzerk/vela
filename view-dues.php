<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIEW DUES</title>
    <script src="https://kit.fontawesome.com/dddee79f2e.js" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: white;
            position: relative;
            min-height: 100vh;
        }

        .header {
            background-color: #155670;
            color: white;
            padding: 15px 5%;
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            box-sizing: border-box;
        }

        .page-title {
            color: #155670;
            margin: 30px 0 20px 10%;
            font-size: 2rem;
            font-weight: 700;
        }

        .main-container {
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 0 10% 80px; 
            background-color: white;
        }

        .dues-container {
            max-width: 1200px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 40%;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 30%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 30%;
            text-align: right;
            padding-right: 20px;
        }

        th {
            background-color: #155670;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: rgba(21, 86, 112, 0.05);
        }

        tr:hover {
            background-color: rgba(21, 86, 112, 0.1);
        }

        tr.total-row-rent {
            background-color: #e3f2fd; 
            font-weight: 600;
        }

        tr.total-row-utilities {
            background-color: #e8f5e9;
            font-weight: 600;
        }

        tr.total-row-other {
            background-color: #fff8e1;
            font-weight: 600;
        }

        .grand-total {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grand-total-label {
            font-size: 1.2rem;
            font-weight: 600;
            color: #155670;
        }

        .grand-total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: #155670;
        }

        td {
            color: #333;
        }

        .table-title {
            color: #155670;
            margin: 25px 0 10px;
            font-size: 1.3rem;
            font-weight: 600;
            padding-left: 10px;
        }

        /* Updated navigation to show only right arrow */
        .navigation-arrows {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100;
            display: flex;
            justify-content: flex-end;
        }

        .nav-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-text {
            color: #155670;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }

        .arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: #357e9c;
            border-radius: 50%;
            color: white;
            font-size: 22px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .arrow:hover {
            background-color: #155670;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .page-title,
            .main-container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            th,
            td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }

            th:nth-child(1),
            td:nth-child(1) {
                width: 50%;
            }

            th:nth-child(2),
            td:nth-child(2) {
                width: 25%;
            }

            th:nth-child(3),
            td:nth-child(3) {
                width: 25%;
                padding-right: 10px;
            }

            .table-title {
                font-size: 1.1rem;
                padding-left: 0;
            }

            .navigation-arrows {
                right: 10px;
                bottom: 10px;
            }

            .arrow {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .nav-group {
                gap: 10px;
            }

            .nav-text {
                font-size: 0.9rem;
            }

            .grand-total-label {
                font-size: 1rem;
            }

            .grand-total-amount {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>
    <nav class="header">
        <div class="header-logo">
            <h1 id="logo">Logo</h1>
        </div>
    </nav>

    <h1 class="page-title">DUES</h1>

    <div class="main-container">
        <div class="dues-container">
            <h3 class="table-title">RENT</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monthly Dues</td>
                        <td>2024-01-01</td>
                        <td>₱1,234.00</td>
                    </tr>
                    <tr>
                        <td>Monthly Dues</td>
                        <td>2024-02-01</td>
                        <td>₱1,234.00</td>
                    </tr>
                    <tr class="total-row-rent">
                        <td><strong>Total Monthly Dues</strong></td>
                        <td></td>
                        <td><strong>₱2,468.00</strong></td>
                    </tr>
                </tbody>
            </table>

            <h3 class="table-title">UTILITIES</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Electricity</td>
                        <td>2024-01-01</td>
                        <td>₱1,234.00</td>
                    </tr>
                    <tr>
                        <td>Water</td>
                        <td>2024-01-01</td>
                        <td>₱1,234.00</td>
                    </tr>
                    <tr class="total-row-utilities">
                        <td><strong>Total Utilities Dues</strong></td>
                        <td></td>
                        <td><strong>₱2,468.00</strong></td>
                    </tr>
                </tbody>
            </table>

            <h3 class="table-title">OTHER</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Maintenance Fee</td>
                        <td>2024-01-01</td>
                        <td>₱1,234.00</td>
                    </tr>
                    <tr class="total-row-other">
                        <td><strong>Total Other Dues</strong></td>
                        <td></td>
                        <td><strong>₱1,234.00</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="grand-total">
                <span class="grand-total-label">TOTAL DUES:</span>
                <span class="grand-total-amount">₱6,170.00</span>
            </div>
        </div>
    </div>

    <div class="navigation-arrows">
        <div class="nav-group">
            <p class="nav-text">Proceed to Payment</p>
            <a href="#" class="arrow"><i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</body>

</html>