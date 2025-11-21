<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get total users count
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_result = $conn->query($total_users_query);
$total_users = $total_result->fetch_assoc()['total'];

// Get gender distribution
$gender_query = "SELECT gender, COUNT(*) as count FROM users GROUP BY gender";
$gender_result = $conn->query($gender_query);
$gender_data = [];
while ($row = $gender_result->fetch_assoc()) {
    $gender_data[] = $row;
}

// Get registration by month WITH GENDER BREAKDOWN
$month_gender_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    gender,
    COUNT(*) as count 
FROM users 
GROUP BY DATE_FORMAT(created_at, '%Y-%m'), gender 
ORDER BY month";
$month_gender_result = $conn->query($month_gender_query);
$month_gender_data = [];
while ($row = $month_gender_result->fetch_assoc()) {
    $month_gender_data[] = $row;
}

// Process data for stacked chart
$months = [];
$male_counts = [];
$female_counts = [];

foreach ($month_gender_data as $data) {
    if (!in_array($data['month'], $months)) {
        $months[] = $data['month'];
    }
}

foreach ($months as $month) {
    $male = 0;
    $female = 0;
    foreach ($month_gender_data as $data) {
        if ($data['month'] === $month) {
            if ($data['gender'] === 'Male') {
                $male = (int)$data['count'];
            } elseif ($data['gender'] === 'Female') {
                $female = (int)$data['count'];
            }
        }
    }
    $male_counts[] = $male;
    $female_counts[] = $female;
}

// Get all users for table
$users_query = "SELECT id, fullname, email, gender, city, DATE_FORMAT(created_at, '%b %d, %Y') as registration_date 
                FROM users 
                ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - User Management System</title>
    <link rel="stylesheet" href="style.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                        <circle cx="20" cy="20" r="20" fill="#fff"/>
                        <path d="M20 20c3.3 0 6-2.7 6-6s-2.7-6-6-6-6 2.7-6 6 2.7 6 6 6zm0 3c-4 0-12 2-12 6v3h24v-3c0-4-8-6-12-6z" fill="#3B4A6B"/>
                    </svg>
                </div>
                <h3><?php echo htmlspecialchars($_SESSION['fullname']); ?></h3>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" id="dashboardNav">
                    <span class="nav-indicator"></span>
                    DASHBOARD
                </a>
                <a href="#" class="nav-item" id="analyticsNav">ANALYTICS</a>
                <a href="#" class="nav-item" id="usersNav">USERS</a>
            </nav>
            
            <a href="logout.php" class="btn btn-logout">LOGOUT</a>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard View -->
            <div id="dashboardView">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p class="stat-number"><?php echo $total_users; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Registration by Month</h3>
                        <canvas id="monthChart1"></canvas>
                    </div>
                    
                    <div class="stat-card">
                        <h3>User Gender Distribution</h3>
                        <canvas id="genderChart"></canvas>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Registration by Month</h3>
                        <canvas id="monthChart2"></canvas>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="table-container">
                    <h2>All Users</h2>
                    <table id="usersTable" class="display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Gender</th>
                                <th>City</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                <td><?php echo htmlspecialchars($user['city']); ?></td>
                                <td><?php echo htmlspecialchars($user['registration_date']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Analytics View (Hidden by default) -->
            <div id="analyticsView" style="display: none;">
                <h1 style="color: #3B4A6B; margin-bottom: 30px;">Analytics Overview</h1>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Registrations</h3>
                        <p class="stat-number"><?php echo $total_users; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>User Gender Distribution</h3>
                        <canvas id="genderChartAnalytics"></canvas>
                    </div>
                    <div class="stat-card" style="grid-column: span 2;">
                        <h3>Registration Trends by Gender</h3>
                        <canvas id="monthChartAnalytics"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Users View (Hidden by default) -->
            <div id="usersView" style="display: none;">
                <h1 style="color: #3B4A6B; margin-bottom: 30px;">All Users Management</h1>
                <div class="table-container">
                    <table id="usersTableManagement" class="display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Gender</th>
                                <th>City</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $users_query2 = "SELECT id, fullname, email, gender, city, DATE_FORMAT(created_at, '%b %d, %Y') as registration_date 
                                            FROM users 
                                            ORDER BY created_at DESC";
                            $users_result2 = $conn->query($users_query2);
                            while ($user = $users_result2->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                <td><?php echo htmlspecialchars($user['city']); ?></td>
                                <td><?php echo htmlspecialchars($user['registration_date']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#usersTable').DataTable({
                pageLength: 5,
                order: [[0, 'asc']]
            });
            
            $('#usersTableManagement').DataTable({
                pageLength: 10,
                order: [[0, 'asc']]
            });
        });
        
        // Navigation functionality
        document.getElementById('dashboardNav').addEventListener('click', function(e) {
            e.preventDefault();
            showView('dashboard');
            setActiveNav(this);
        });
        
        document.getElementById('analyticsNav').addEventListener('click', function(e) {
            e.preventDefault();
            showView('analytics');
            setActiveNav(this);
        });
        
        document.getElementById('usersNav').addEventListener('click', function(e) {
            e.preventDefault();
            showView('users');
            setActiveNav(this);
        });
        
        function showView(view) {
            // Hide all views
            document.getElementById('dashboardView').style.display = 'none';
            document.getElementById('analyticsView').style.display = 'none';
            document.getElementById('usersView').style.display = 'none';
            
            // Show selected view
            if (view === 'dashboard') {
                document.getElementById('dashboardView').style.display = 'block';
            } else if (view === 'analytics') {
                document.getElementById('analyticsView').style.display = 'block';
            } else if (view === 'users') {
                document.getElementById('usersView').style.display = 'block';
            }
        }
        
        function setActiveNav(element) {
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                item.querySelector('.nav-indicator')?.remove();
            });
            
            // Add active class and indicator to clicked item
            element.classList.add('active');
            const indicator = document.createElement('span');
            indicator.className = 'nav-indicator';
            element.insertBefore(indicator, element.firstChild);
        }
        
        // Gender Distribution Pie Chart
        const genderData = <?php echo json_encode($gender_data); ?>;
        const genderLabels = genderData.map(item => item.gender);
        const genderCounts = genderData.map(item => parseInt(item.count));
        
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderCounts,
                    backgroundColor: ['#6DB4E8', '#FF9AAB', '#A8E6CF']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 10 } }
                    }
                }
            }
        });
        
        // Analytics Gender Chart
        const genderCtxAnalytics = document.getElementById('genderChartAnalytics').getContext('2d');
        new Chart(genderCtxAnalytics, {
            type: 'doughnut',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderCounts,
                    backgroundColor: ['#6DB4E8', '#FF9AAB', '#A8E6CF']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 12 } }
                    }
                }
            }
        });
        
        // Registration by Month - Grouped Bar Charts with Male/Female side by side
        const monthLabels = <?php echo json_encode($months); ?>;
        const maleCounts = <?php echo json_encode($male_counts); ?>;
        const femaleCounts = <?php echo json_encode($female_counts); ?>;
        
        const groupedChartConfig = {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Male',
                        data: maleCounts,
                        backgroundColor: '#6DB4E8',
                        borderRadius: 5,
                        barThickness: 40
                    },
                    {
                        label: 'Female',
                        data: femaleCounts,
                        backgroundColor: '#FF9AAB',
                        borderRadius: 5,
                        barThickness: 40
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'bottom',
                        labels: { 
                            font: { size: 11 },
                            padding: 10,
                            usePointStyle: true,
                            pointStyle: 'rect'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' user(s)';
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        stacked: false,
                        grid: { display: false }
                    },
                    y: { 
                        stacked: false,
                        beginAtZero: true, 
                        ticks: { stepSize: 1 },
                        title: {
                            display: true,
                            text: 'Number of Users'
                        }
                    }
                }
            }
        };
        
        const month1Ctx = document.getElementById('monthChart1').getContext('2d');
        new Chart(month1Ctx, groupedChartConfig);
        
        const month2Ctx = document.getElementById('monthChart2').getContext('2d');
        new Chart(month2Ctx, groupedChartConfig);
        
        // Analytics chart with line chart showing trends
        const monthCtxAnalytics = document.getElementById('monthChartAnalytics').getContext('2d');
        new Chart(monthCtxAnalytics, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Male',
                        data: maleCounts,
                        borderColor: '#6DB4E8',
                        backgroundColor: 'rgba(109, 180, 232, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Female',
                        data: femaleCounts,
                        borderColor: '#FF9AAB',
                        backgroundColor: 'rgba(255, 154, 171, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html> 