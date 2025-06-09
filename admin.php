<?php
require_once "includes/session_handler.php";
CustomSessionHandler::initialize();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Get total users count
$total_users = 0;
$sql = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
if($result = mysqli_query($conn, $sql)){
    if($row = mysqli_fetch_assoc($result)){
        $total_users = $row['total'];
    }
}

// Get total analysis count
$total_analysis = 0;
$sql = "SELECT COUNT(*) as total FROM analysis_results";
if($result = mysqli_query($conn, $sql)){
    if($row = mysqli_fetch_assoc($result)){
        $total_analysis = $row['total'];
    }
}

// Get recent users with search filters
$recent_users = array();
$user_search_date = isset($_GET['user_date']) ? $_GET['user_date'] : '';
$user_search_status = isset($_GET['user_status']) ? $_GET['user_status'] : '';

$user_sql = "SELECT email, created_at, email_verified FROM users WHERE is_admin = 0";
$params = array();
$types = "";

if (!empty($user_search_date)) {
    $user_sql .= " AND DATE(created_at) = ?";
    $params[] = $user_search_date;
    $types .= "s";
}

if ($user_search_status !== '') {
    $user_sql .= " AND email_verified = ?";
    $params[] = $user_search_status;
    $types .= "i";
}

$user_sql .= " ORDER BY created_at DESC LIMIT 10";

if ($stmt = mysqli_prepare($conn, $user_sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $recent_users[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get recent analysis results with search filters
$recent_analysis = array();
$analysis_search_date = isset($_GET['analysis_date']) ? $_GET['analysis_date'] : '';
$analysis_search_result = isset($_GET['analysis_result']) ? $_GET['analysis_result'] : '';

$analysis_sql = "SELECT ar.*, u.email 
                 FROM analysis_results ar 
                 JOIN users u ON ar.user_id = u.id";

$analysis_params = array();
$analysis_types = "";

if (!empty($analysis_search_date)) {
    $analysis_sql .= " AND DATE(ar.timestamp) = ?";
    $analysis_params[] = $analysis_search_date;
    $analysis_types .= "s";
}

if ($analysis_search_result !== '') {
    $analysis_sql .= " AND ar.is_spliced = ?";
    $analysis_params[] = $analysis_search_result;
    $analysis_types .= "i";
}

$analysis_sql .= " ORDER BY ar.timestamp DESC LIMIT 10";

if ($stmt = mysqli_prepare($conn, $analysis_sql)) {
    if (!empty($analysis_params)) {
        mysqli_stmt_bind_param($stmt, $analysis_types, ...$analysis_params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $recent_analysis[] = $row;
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="includes/session_timeout.js"></script>
    <style>
        :root {
            --teal-dark: #005761;
            --teal-medium: #6BADA6;
            --teal-light: #E5F2F5;
            --charcoal: #2A2A2A;
            --teal-accent: #3B9999;
            --purple-medium: #b5179e;
            --pink-accent: #ff006e;
            --white: #FFFFFF;
            --gray-light: #f3f4f6;
            --gray-medium: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--charcoal);
            background: #0B1437;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: var(--teal-dark);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--purple-medium);
        }

        .data-table {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .data-table h2 {
            color: var(--teal-dark);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        th {
            background-color: var(--teal-light);
            color: var(--teal-dark);
            font-weight: 600;
        }

        tr:hover {
            background-color: var(--gray-light);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-verified {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background-color: #fef9c3;
            color: #854d0e;
        }

        .status-spliced {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-authentic {
            background-color: #dcfce7;
            color: #166534;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--teal-dark), var(--purple-medium));
            color: var(--white);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .welcome-banner h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .welcome-banner p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .delete-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }

        .toast.success {
            background: #059669;
        }

        .toast.error {
            background: #dc2626;
        }

        .toast.show {
            opacity: 1;
        }

        .confirm-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .confirm-dialog.show {
            display: block;
        }

        .dialog-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .dialog-overlay.show {
            display: block;
        }

        .confirm-dialog h3 {
            color: var(--charcoal);
            margin-bottom: 1rem;
        }

        .confirm-dialog p {
            margin-bottom: 1.5rem;
            color: var(--gray-medium);
        }

        .dialog-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .dialog-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
        }

        .dialog-btn.cancel {
            background: var(--gray-light);
            color: var(--charcoal);
        }

        .dialog-btn.confirm {
            background: #ef4444;
            color: white;
        }

        .dialog-btn:hover {
            opacity: 0.9;
        }

        .search-filters {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-filters select, .search-filters input[type="date"] {
            padding: 0.5rem;
            border: 1px solid var(--teal-medium);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            color: var(--charcoal);
        }

        .search-filters button {
            padding: 0.5rem 1rem;
            background: var(--teal-medium);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .search-filters button:hover {
            background: var(--teal-dark);
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: var(--gray-medium);
            font-style: italic;
        }

        /* Override header styles for admin page */
        .nav-buttons {
            display: flex;
            gap: 2rem;
            align-items: center;
            justify-content: flex-end;
        }

        .user-email {
            color: var(--teal-light);
            font-size: 0.95rem;
            margin-right: 1rem;
        }

        .logout-btn {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--white);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome, Admin!</h1>
            <p>Monitor SpliceNoise's activity and user statistics.</p>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-number"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Analysis</h3>
                <div class="stat-number"><?php echo $total_analysis; ?></div>
            </div>
        </div>

        <div class="data-table">
            <h2>Recent Users</h2>
            <form class="search-filters" method="GET">
                <input type="date" name="user_date" value="<?php echo $user_search_date; ?>">
                <select name="user_status">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $user_search_status === '1' ? 'selected' : ''; ?>>Verified</option>
                    <option value="0" <?php echo $user_search_status === '0' ? 'selected' : ''; ?>>Pending</option>
                </select>
                <button type="submit">Search</button>
                <?php if(!empty($user_search_date) || $user_search_status !== ''): ?>
                    <a href="admin.php" style="text-decoration: none;">
                        <button type="button">Clear</button>
                    </a>
                <?php endif; ?>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_users)): ?>
                        <tr>
                            <td colspan="4" class="no-results">No users found matching the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $user['email_verified'] ? 'status-verified' : 'status-pending'; ?>">
                                    <?php echo $user['email_verified'] ? 'Verified' : 'Pending'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="delete-btn" onclick="confirmDelete('<?php echo htmlspecialchars($user['email']); ?>')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="data-table">
            <h2>Recent Analysis Results</h2>
            <form class="search-filters" method="GET">
                <input type="date" name="analysis_date" value="<?php echo $analysis_search_date; ?>">
                <select name="analysis_result">
                    <option value="">All Results</option>
                    <option value="1" <?php echo $analysis_search_result === '1' ? 'selected' : ''; ?>>Spliced</option>
                    <option value="0" <?php echo $analysis_search_result === '0' ? 'selected' : ''; ?>>Authentic</option>
                </select>
                <button type="submit">Search</button>
                <?php if(!empty($analysis_search_date) || $analysis_search_result !== ''): ?>
                    <a href="admin.php" style="text-decoration: none;">
                        <button type="button">Clear</button>
                    </a>
                <?php endif; ?>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Date</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_analysis)): ?>
                        <tr>
                            <td colspan="3" class="no-results">No analysis results found matching the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($recent_analysis as $analysis): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($analysis['email']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($analysis['timestamp'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $analysis['is_spliced'] ? 'status-spliced' : 'status-authentic'; ?>">
                                    <?php echo $analysis['is_spliced'] ? 'Spliced' : 'Authentic'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <div class="dialog-overlay" id="dialogOverlay"></div>
    <div class="confirm-dialog" id="confirmDialog">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
        <div class="dialog-buttons">
            <button class="dialog-btn cancel" onclick="hideDialog()">Cancel</button>
            <button class="dialog-btn confirm" onclick="deleteUser()">Delete</button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script>
        // Initialize session timeout manager
        const sessionTimeoutManager = new SessionTimeoutManager(60);

        let userToDelete = null;

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function confirmDelete(email) {
            userToDelete = email;
            document.getElementById('dialogOverlay').classList.add('show');
            document.getElementById('confirmDialog').classList.add('show');
        }

        function hideDialog() {
            document.getElementById('dialogOverlay').classList.remove('show');
            document.getElementById('confirmDialog').classList.remove('show');
            userToDelete = null;
        }

        function deleteUser() {
            if (!userToDelete) return;

            const formData = new FormData();
            formData.append('email', userToDelete);

            // Disable all delete buttons during the operation
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(btn => btn.disabled = true);

            fetch('delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Remove the row from the table
                    const row = document.querySelector(`tr:has(td:contains('${userToDelete}'))`);
                    if (row) {
                        row.remove();
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred while deleting the user.', 'error');
            })
            .finally(() => {
                hideDialog();
                deleteButtons.forEach(btn => btn.disabled = false);
            });
        }

        // Helper function to find text in table cells
        HTMLElement.prototype.contains = function(text) {
            return this.textContent.includes(text);
        };

        // Remove all navigation links except logout
        document.addEventListener('DOMContentLoaded', function() {
            const navButtons = document.querySelector('.nav-buttons');
            if (navButtons) {
                // Clear existing buttons
                navButtons.innerHTML = '';
                
                // Add admin email
                const emailSpan = document.createElement('span');
                emailSpan.className = 'user-email';
                emailSpan.textContent = '<?php echo htmlspecialchars($_SESSION["email"]); ?>';
                navButtons.appendChild(emailSpan);
                
                // Add logout button
                const logoutLink = document.createElement('a');
                logoutLink.href = 'logout.php';
                logoutLink.className = 'logout-btn';
                logoutLink.textContent = 'Logout';
                navButtons.appendChild(logoutLink);
            }
        });
    </script>
</body>
</html> 