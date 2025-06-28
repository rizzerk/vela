<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Page</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #fff;
      padding: 40px;
      display: flex;
      justify-content: center;
    }

    .container {
      display: flex;
      justify-content: space-between;
      max-width: 1200px;
      width: 100%;
      gap: 40px;
    }

    .left-panel,
    .right-panel {
      flex: 1;
    }

    .left-panel h2,
    .right-panel h2 {
      margin-bottom: 20px;
      font-size: 1.5rem;
      font-weight: bold;
    }

    /* Profile Circle */
    .profile-pic {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: #ccc;
      margin-bottom: 20px;
    }

    .info-lines {
      background: #ccc;
      height: 14px;
      margin-bottom: 10px;
      width: 70%;
    }

    .social-icons {
      display: flex;
      gap: 15px;
      margin: 20px 0;
    }

    .social-icons div {
      width: 30px;
      height: 30px;
      background: #999;
      border-radius: 5px;
    }

    .edit-btn {
      padding: 10px 20px;
      background: #ccc;
      border: none;
      border-radius: 20px;
      cursor: pointer;
      font-weight: bold;
    }

    .right-panel {
      border-left: 2px solid #ccc;
      padding-left: 40px;
    }

    .contact-info {
      margin-bottom: 20px;
    }

    .contact-info .info-lines {
      width: 60%;
    }

    .image-boxes {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
    }

    .image-box {
      border: 2px solid #333;
      width: 150px;
      height: 120px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: 5px;
    }

    .image-box::before {
      content: "";
      width: 100%;
      height: 80%;
      background: repeating-linear-gradient(
        45deg,
        #eee,
        #eee 10px,
        #ddd 10px,
        #ddd 20px
      );
    }

    .image-label {
      margin-top: 5px;
      font-weight: 600;
    }

    .logout-btn {
      padding: 10px 30px;
      background: #ccc;
      border: none;
      border-radius: 20px;
      font-weight: bold;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }

      .right-panel {
        border-left: none;
        padding-left: 0;
        border-top: 2px solid #ccc;
        padding-top: 20px;
        margin-top: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
      <h2>ACCOUNT</h2>
      <div class="profile-pic"></div>
      <div class="info-lines"></div>
      <div class="info-lines"></div>
      <div class="info-lines"></div>
      <div class="info-lines" style="width: 90%;"></div>
      <div class="info-lines" style="width: 90%;"></div>

      <div class="social-icons">
        <div></div>
        <div></div>
        <div></div>
      </div>
      <button class="edit-btn">Edit profile</button>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
      <h2>Landlord Contact</h2>
      <div class="contact-info">
        <div class="info-lines"></div>
        <div class="info-lines"></div>
      </div>

      <div class="image-boxes">
        <div class="image-box">
          <div class="image-label">License</div>
        </div>
        <div class="image-box">
          <div class="image-label">ID</div>
        </div>
      </div>

      <button class="logout-btn">Log Out</button>
    </div>
  </div>
</body>
</html>
