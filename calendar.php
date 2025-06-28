<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Minimal Calendar</title>
  <script src="https://kit.fontawesome.com/dddee79f2e.js" crossorigin="anonymous"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      display: flex;
      justify-content: center;
      padding: 40px;
      background-color: white;
    }

    .calendar-wrapper {
      display: flex;
      max-width: 1100px;
      width: 100%;
      justify-content: space-between;
    }

    .calendar {
      width: 70%;
    }

    .calendar-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .calendar-header .year {
      font-size: 3rem;
      font-weight: bold;
    }

    .calendar-header .month-nav {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .month-nav button {
      border: none;
      background: none;
      font-size: 1.5rem;
      cursor: pointer;
    }

    .month-title {
      font-size: 2rem;
      font-weight: 500;
    }

    .calendar-table {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      border: 1px solid black;
    }

    .calendar-table div {
      border: 1px solid black;
      aspect-ratio: 1 / 1;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .day-header {
      background: black;
      color: white;
      font-weight: 600;
    }

    .sidebar {
      width: 25%;
      padding-left: 30px;
    }

    .sidebar h3 {
      margin-bottom: 20px;
      font-weight: 600;
    }

    .calendar-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .calendar-list div {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .calendar-list span {
      height: 15px;
      width: 15px;
      background: gray;
      border-radius: 3px;
      display: inline-block;
    }
  </style>
</head>

<body>
  <div class="calendar-wrapper">
    <div class="calendar">
      <div class="calendar-header">
        <div class="year">2025</div>
        <div class="month-nav">
          <button><i class="fas fa-chevron-left"></i></button>
          <div class="month-title">February</div>
          <button><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>

      <!-- Calendar Grid -->
      <div class="calendar-table">
        <!-- Day headers -->
        <div class="day-header">Sunday</div>
        <div class="day-header">Monday</div>
        <div class="day-header">Tuesday</div>
        <div class="day-header">Wednesday</div>
        <div class="day-header">Thursday</div>
        <div class="day-header">Friday</div>
        <div class="day-header">Saturday</div>

        <!-- February 2025 Grid (static for now) -->
        <!-- Empty boxes for days before Feb 1 -->
        <div></div><div></div><div></div><div></div><div></div><div></div>
        <div>1</div>
        <div>2</div><div>3</div><div>4</div><div>5</div><div>6</div><div>7</div><div>8</div>
        <div>9</div><div>10</div><div>11</div><div>12</div><div>13</div><div>14</div><div>15</div>
        <div>16</div><div>17</div><div>18</div><div>19</div><div>20</div><div>21</div><div>22</div>
        <div>23</div><div>24</div><div>25</div><div>26</div><div>27</div><div>28</div>
      </div>
    </div>

    <div class="sidebar">
      <h3>CALENDARS</h3>
      <div class="calendar-list">
        <div><span></span><p>Personal</p></div>
        <div><span></span><p>Work</p></div>
        <div><span></span><p>Holidays</p></div>
        <div><span></span><p>Reminders</p></div>
        <div><span></span><p>Other</p></div>
      </div>
    </div>
  </div>
</body>

</html>
