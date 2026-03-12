<?php
$conn = mysqli_connect("localhost", "root", "janhavi", "mmcoe_db");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!$conn) { die("<div style='font-family:sans-serif;padding:40px;color:red;'>Database Connection Failed: " . mysqli_connect_error() . "</div>"); }

function head($title) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | MMCOE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        :root {
            --primary: #1a73e8;
            --primary-dark: #1557b0;
            --success: #0d9f6e;
            --danger: #e53e3e;
            --warning: #d97706;
            --bg: #f4f6fb;
            --white: #ffffff;
            --border: #e8ecf4;
            --text: #1a202c;
            --muted: #718096;
            --shadow: 0 2px 12px rgba(26,114,232,0.08);
            --shadow-lg: 0 8px 32px rgba(26,114,232,0.13);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .topbar {
            background: var(--white);
            border-bottom: 2px solid var(--primary);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }
        .topbar-brand {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
        }
        .topbar-brand span { color: #f57c00; }
        .card-pro {
            background: var(--white);
            border-radius: 18px;
            border: 1.5px solid var(--border);
            box-shadow: var(--shadow);
            transition: box-shadow 0.25s, transform 0.25s;
            overflow: hidden;
        }
        .card-pro:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }
        .card-header-pro {
            background: linear-gradient(90deg, var(--primary) 0%, #4a90e2 100%);
            color: white;
            padding: 18px 24px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.2px;
        }
        .card-body-pro { padding: 24px; }
        .btn-primary-pro {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary-pro:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(26,114,232,0.3);
            color: white;
        }
        .btn-success-pro {
            background: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 7px 16px;
            font-weight: 600;
            font-size: 0.82rem;
            transition: 0.2s;
            cursor: pointer;
        }
        .btn-success-pro:hover { background: #0a7a55; transform: scale(1.04); color: white; }
        .btn-danger-pro {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 7px 16px;
            font-weight: 600;
            font-size: 0.82rem;
            transition: 0.2s;
            cursor: pointer;
        }
        .btn-danger-pro:hover { background: #c53030; transform: scale(1.04); color: white; }
        .form-field {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.93rem;
            width: 100%;
            transition: border 0.2s, box-shadow 0.2s;
            font-family: 'Inter', sans-serif;
            color: var(--text);
        }
        .form-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26,114,232,0.12);
            background: white;
        }
        .form-label-pro {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 5px;
            display: block;
        }
        .badge-status {
            padding: 5px 13px;
            border-radius: 50px;
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-Pending { background: #fff3cd; color: #92400e; border: 1px solid #fcd34d; }
        .badge-Confirmed { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-Rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-Cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 22px;
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: 0.25s;
        }
        .stat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
        .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .alert-pro {
            border-radius: 12px;
            border: none;
            padding: 13px 18px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        table.pro-table { border-collapse: separate; border-spacing: 0; width: 100%; }
        table.pro-table thead th { background: #f8faff; color: var(--muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; padding: 12px 16px; border-bottom: 2px solid var(--border); }
        table.pro-table tbody td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 0.9rem; vertical-align: middle; }
        table.pro-table tbody tr:hover { background: #f8faff; }
        table.pro-table tbody tr:last-child td { border-bottom: none; }
        .info-footer {
            background: white;
            border-radius: 18px;
            border: 1.5px solid var(--border);
            padding: 28px 32px;
            margin-top: 36px;
            box-shadow: var(--shadow);
        }
        .info-footer h6 { color: var(--primary); font-weight: 700; font-size: 0.88rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .info-footer p, .info-footer li { font-size: 0.85rem; color: var(--muted); line-height: 1.7; }
        .hall-img-thumb { width: 60px; height: 45px; object-fit: cover; border-radius: 8px; }
        .slot-booked { background: #fee2e2; border-radius: 8px; padding: 4px 10px; font-size: 0.78rem; color: #991b1b; font-weight: 600; }
        .slot-free { background: #d1fae5; border-radius: 8px; padding: 4px 10px; font-size: 0.78rem; color: #065f46; font-weight: 600; }
    </style>
</head>
<body>
<?php }

function footer_info() { ?>
<div class="container-fluid px-4 pb-5">
    <div class="info-footer">
        <div class="row g-4">
            <div class="col-md-3">
                <h6><i class="fas fa-file-contract me-2"></i>Booking Policy</h6>
                <ul class="list-unstyled mb-0">
                    <li>✔ Submit at least 24hrs in advance</li>
                    <li>✔ Coordinator approval mandatory</li>
                    <li>✔ No duplicate time slots allowed</li>
                    <li>✔ Maintain hall cleanliness</li>
                    <li>✔ No heavy electrical equipment</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6><i class="fas fa-users me-2"></i>Hall Coordinators</h6>
                <p class="mb-1"><b>Prof. A. Kulkarni</b><br>Seminar Hall 405 — +91 98765 XXXXX</p>
                <p class="mb-1"><b>Prof. R. Deshmukh</b><br>IMERT Hall — +91 98765 XXXXX</p>
                <p class="mb-0"><b>Prof. S. Patil</b><br>Auditorium — +91 98765 XXXXX</p>
            </div>
            <div class="col-md-3">
                <h6><i class="fas fa-broom me-2"></i>Support Staff</h6>
                <p class="mb-1"><b>Mr. Mahesh</b> — Cleaning & Prep<br>+91 98765 00001</p>
                <p class="mb-0"><b>Mr. Ramesh</b> — AV & Projector Setup<br>+91 98765 00002</p>
            </div>
            <div class="col-md-3">
                <h6><i class="fas fa-key me-2"></i>Key & Access</h6>
                <p class="mb-1">Keys available at <b>Main Security Desk</b></p>
                <p class="mb-0">In-Charge: <b>Mr. Deshpande</b><br>Admin Office, Room 101</p>
                <div class="mt-2 p-2 rounded" style="background:#fff3cd;font-size:0.78rem;color:#92400e;">
                    ⚠ Misuse results in booking suspension
                </div>
            </div>
        </div>
        <div class="text-center mt-4 pt-3 border-top" style="color:#b0bec5;font-size:0.78rem;">
            &copy; 2026 Marathwada Mitra Mandal's College of Engineering &mdash; Seminar Hall Booking System
        </div>
    </div>
</div>
</body>
</html>
<?php } ?>