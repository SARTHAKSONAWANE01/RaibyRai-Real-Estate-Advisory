<?php
/**
 * Rai by Rai - Admin Dashboard
 */

session_start();
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ─── PROCESS STATUS UPDATE ACTIONS ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $inquiry_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    
    if ($inquiry_id) {
        $new_status = 'New';
        if ($action === 'mark_contacted') {
            $new_status = 'Contacted';
        } elseif ($action === 'mark_resolved') {
            $new_status = 'Resolved';
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $inquiry_id]);
            header('Location: dashboard.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            $error_msg = 'Database error: Unable to update lead.';
        }
    }
}

// ─── FILTERS & SEARCH ───
$status_filter = $_GET['status'] ?? 'All';
$search_query = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM inquiries WHERE 1=1";
$params = [];

if ($status_filter !== 'All') {
    $sql .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE :search OR email LIKE :search OR location LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    $inquiries = [];
    $error_msg = 'Database query failed.';
}

// ─── CALCULATE DASHBOARD METRICS ───
$total_count = 0;
$new_count = 0;
$contacted_count = 0;
$resolved_count = 0;
$total_estimated_value = 0; // Cumulative estimate of lead potential in Crores

try {
    $stats_stmt = $pdo->query("SELECT status, budget FROM inquiries");
    while ($row = $stats_stmt->fetch()) {
        $total_count++;
        if ($row['status'] === 'New') $new_count++;
        if ($row['status'] === 'Contacted') $contacted_count++;
        if ($row['status'] === 'Resolved') $resolved_count++;
        
        // Parse budget into a rough estimate in Crores
        $budget = $row['budget'];
        if ($budget === 'Under ₹5Cr') {
            $total_estimated_value += 3; // Average 3 Cr
        } elseif ($budget === '₹5Cr - ₹10Cr') {
            $total_estimated_value += 7.5; // Average 7.5 Cr
        } elseif ($budget === '₹10Cr - ₹50Cr') {
            $total_estimated_value += 30; // Average 30 Cr
        } elseif ($budget === '₹50Cr+') {
            $total_estimated_value += 75; // Minimum 75 Cr
        }
    }
} catch (PDOException $e) {
    // Fail silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rai by Rai - Portfolio Advisory Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f0f0d;
            --cream: #f5f2ed;
            --warm-white: #faf9f6;
            --gold: #b89a5e;
            --gold-light: #d4b97a;
            --muted: #8a8478;
            --border: #e2ddd6;
            --green-accent: #3d5a47;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--warm-white);
            color: var(--ink);
            min-height: 100vh;
            font-weight: 300;
        }

        /* ─── HEADER ─── */
        header {
            background: var(--ink);
            color: var(--warm-white);
            padding: 1.25rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gold);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            text-decoration: none;
            color: var(--warm-white);
            letter-spacing: 0.05em;
        }

        .logo span {
            color: var(--gold);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-name {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gold-light);
        }

        .logout-btn {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--warm-white);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--ink);
        }

        /* ─── MAIN CONTENT ─── */
        main {
            padding: 3rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            font-weight: 300;
        }

        /* Metrics grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .metric-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(15,15,13,0.02);
            border-left: 4px solid var(--border);
        }

        .metric-card.new { border-left-color: var(--gold); }
        .metric-card.contacted { border-left-color: #007bff; }
        .metric-card.resolved { border-left-color: var(--green-accent); }
        .metric-card.aum { border-left-color: var(--ink); }

        .metric-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }

        .metric-val {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            color: var(--ink);
            line-height: 1.1;
        }

        /* Filters and Search Bar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            color: var(--muted);
            text-decoration: none;
            transition: all 0.2s;
        }

        .filter-btn.active, .filter-btn:hover {
            background: var(--ink);
            color: var(--warm-white);
            border-color: var(--ink);
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            max-width: 400px;
        }

        .search-control {
            flex: 1;
            padding: 0.55rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.85rem;
            background: var(--white);
        }

        .search-control:focus {
            outline: none;
            border-color: var(--gold);
        }

        .search-btn {
            background: var(--ink);
            color: var(--warm-white);
            border: none;
            padding: 0 1.25rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Lead data table */
        .table-container {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(15,15,13,0.02);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 1.2rem 1.5rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--cream);
        }

        th {
            background: var(--cream);
            color: var(--muted);
            text-transform: uppercase;
            font-size: 0.68rem;
            letter-spacing: 0.1em;
            font-weight: 600;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.new {
            background: rgba(184, 154, 94, 0.15);
            color: #8c6e30;
            border: 1px solid rgba(184, 154, 94, 0.3);
        }

        .status-badge.contacted {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
            border: 1px solid rgba(0, 123, 255, 0.2);
        }

        .status-badge.resolved {
            background: rgba(61, 90, 71, 0.15);
            color: var(--green-accent);
            border: 1px solid rgba(61, 90, 71, 0.3);
        }

        .view-msg-link {
            color: var(--gold);
            text-decoration: none;
            border-bottom: 1px solid var(--gold);
            cursor: pointer;
        }

        .view-msg-link:hover {
            color: var(--ink);
            border-color: var(--ink);
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        .action-form-btn {
            background: var(--warm-white);
            border: 1px solid var(--border);
            color: var(--ink);
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-form-btn:hover {
            background: var(--ink);
            color: var(--warm-white);
            border-color: var(--ink);
        }

        /* ─── MODAL FOR MESSAGE VIEW ─── */
        .msg-modal {
            position: fixed;
            inset: 0;
            background: rgba(15,15,13,0.5);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .msg-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .msg-modal-container {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            position: relative;
            transform: translateY(15px);
            transition: transform 0.3s;
        }

        .msg-modal.active .msg-modal-container {
            transform: translateY(0);
        }

        .msg-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            color: var(--muted);
            border: 0;
            background: none;
            cursor: pointer;
        }

        .msg-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
        }

        .msg-body {
            font-size: 0.9rem;
            line-height: 1.7;
            color: #625d54;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-line;
            background: var(--cream);
            padding: 1rem;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <header>
        <a href="../index.html" class="logo">Rai <span>by</span> Rai</a>
        <div class="user-info">
            <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </header>

    <main>
        <h1 class="dashboard-title">Advisory Dashboard</h1>

        <!-- ─── METRICS ─── -->
        <div class="metrics-grid">
            <div class="metric-card new">
                <p class="metric-label">New Inquiries</p>
                <p class="metric-val"><?php echo $new_count; ?></p>
            </div>
            <div class="metric-card contacted">
                <p class="metric-label">Contacted Leads</p>
                <p class="metric-val"><?php echo $contacted_count; ?></p>
            </div>
            <div class="metric-card resolved">
                <p class="metric-label">Closed Leads</p>
                <p class="metric-val"><?php echo $resolved_count; ?></p>
            </div>
            <div class="metric-card aum">
                <p class="metric-label">Potential Lead Value (Est.)</p>
                <p class="metric-val">₹<?php echo $total_estimated_value; ?>Cr+</p>
            </div>
        </div>

        <!-- ─── TOOLBAR (FILTERS & SEARCH) ─── -->
        <div class="toolbar">
            <div class="filter-group">
                <a href="dashboard.php?status=All&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'All' ? 'active' : ''; ?>">All (<?php echo $total_count; ?>)</a>
                <a href="dashboard.php?status=New&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'New' ? 'active' : ''; ?>">New (<?php echo $new_count; ?>)</a>
                <a href="dashboard.php?status=Contacted&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'Contacted' ? 'active' : ''; ?>">Contacted (<?php echo $contacted_count; ?>)</a>
                <a href="dashboard.php?status=Resolved&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter === 'Resolved' ? 'active' : ''; ?>">Closed (<?php echo $resolved_count; ?>)</a>
            </div>
            
            <form class="search-form" method="GET" action="dashboard.php">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <input type="text" name="search" class="search-control" placeholder="Search by name, email..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <!-- ─── TABLE ─── -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client Info</th>
                        <th>Location</th>
                        <th>Package Interest</th>
                        <th>Budget Potential</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--muted); padding: 3rem 0;">No inquiries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inquiries as $lead): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($lead['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($lead['name']); ?></strong><br>
                                    <span style="color: var(--muted); font-size: 0.78rem;">
                                        <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($lead['email']); ?></a> | 
                                        <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($lead['phone']); ?></a>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($lead['location']); ?></td>
                                <td><span style="font-weight: 500;"><?php echo htmlspecialchars($lead['package']); ?></span></td>
                                <td><span style="color: var(--gold); font-weight: 500;"><?php echo htmlspecialchars($lead['budget']); ?></span></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($lead['status']); ?>">
                                        <?php echo htmlspecialchars($lead['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="view-msg-link" onclick="openMsgModal('<?php echo htmlspecialchars(addslashes($lead['name'])); ?>', '<?php echo htmlspecialchars(addslashes($lead['message'])); ?>')">Read</span>
                                </td>
                                <td class="actions-cell">
                                    <form method="POST" action="dashboard.php">
                                        <input type="hidden" name="id" value="<?php echo $lead['id']; ?>">
                                        <?php if ($lead['status'] !== 'Contacted'): ?>
                                            <button type="submit" name="action" value="mark_contacted" class="action-form-btn">Contacted</button>
                                        <?php endif; ?>
                                        <?php if ($lead['status'] !== 'Resolved'): ?>
                                            <button type="submit" name="action" value="mark_resolved" class="action-form-btn">Close</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- ─── MESSAGE MODAL ─── -->
    <div class="msg-modal" id="msg-modal">
        <div class="msg-modal-container">
            <button class="msg-close" id="close-msg-btn">&times;</button>
            <h3 class="msg-title" id="msg-modal-title">Client Message</h3>
            <div class="msg-body" id="msg-modal-body"></div>
        </div>
    </div>

    <script>
        const msgModal = document.querySelector('#msg-modal');
        const msgTitle = document.querySelector('#msg-modal-title');
        const msgBody = document.querySelector('#msg-modal-body');
        const closeMsgBtn = document.querySelector('#close-msg-btn');

        function openMsgModal(name, message) {
            msgTitle.textContent = `${name}'s Inquiries / Requirements`;
            msgBody.textContent = message ? message : 'No description provided.';
            msgModal.classList.add('active');
        }

        function closeMsgModal() {
            msgModal.classList.remove('active');
        }

        if (closeMsgBtn) {
            closeMsgBtn.addEventListener('click', closeMsgModal);
        }

        if (msgModal) {
            msgModal.addEventListener('click', (e) => {
                if (e.target === msgModal) closeMsgModal();
            });
        }

        window.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMsgModal();
        });
    </script>
</body>
</html>
