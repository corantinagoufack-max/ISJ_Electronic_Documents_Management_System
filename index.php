<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ-DMS | Official Academic Document Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/7-pages/index.css">
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="logo">
                <div class="logo-symbol">
                    <i class="fas fa-shield-halved"></i>
                    <span>ISJ</span>
                </div>
                <div class="logo-text">DMS</div>
            </a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#portals">Portals</a>
                <a href="login.php" class="login-btn">Sign In</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Academic Document Management System</h1>
                <p>The premium institutional platform for the Saint Jean Institute. Securely curate, organize, and access scholarly assets under the supervision of University of Yaoundé 1.</p>
                <div class="hero-buttons">
                    <a href="signup.php" class="btn btn-primary btn-large">
                        <i class="fas fa-user-plus"></i> Join the Vault
                    </a>
                    <a href="#" class="btn btn-outline btn-large">
                        <i class="fas fa-book"></i> Documentation
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section id="portals" class="role-section">
        <div class="container">
            <div class="section-title">
                <h2>Institutional Access Portals</h2>
                <p>Role-based access control for the ISJ community</p>
            </div>

            <div class="role-grid">
                <div class="role-card">
                    <div class="role-header viewer">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>VIEWER</h3>
                        <p>Student/Parents Access</p>
                    </div>
                    <div class="role-content">
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Search & View Documents</li>
                            <li><i class="fas fa-check-circle"></i> Download Course Materials</li>
                            <li><i class="fas fa-check-circle"></i> Access Personal Archives</li>
                        </ul>
                        <a href="login.php" class="role-btn">Student/Parents  Login</a>
                    </div>
                </div>

                <div class="role-card">
                    <div class="role-header standard">
                        <i class="fas fa-chalkboard-user"></i>
                        <h3>STANDARD USER</h3>
                        <p>Faculty Access</p>
                    </div>
                    <div class="role-content">
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Upload Academic Content</li>
                            <li><i class="fas fa-check-circle"></i> Manage Assigned Modules</li>
                            <li><i class="fas fa-check-circle"></i> Version Control Handling</li>
                        </ul>
                        <a href="login.php" class="role-btn">Faculty Portal</a>
                    </div>
                </div>

                <div class="role-card">
                    <div class="role-header admin">
                        <i class="fas fa-user-shield"></i>
                        <h3>ADMINISTRATOR</h3>
                        <p>System Control</p>
                    </div>
                    <div class="role-content">
                        <ul>
                            <li><i class="fas fa-check-circle"></i> User & Role Management</li>
                            <li><i class="fas fa-check-circle"></i> Global Permission Settings</li>
                            <li><i class="fas fa-check-circle"></i> System Audit & Reporting</li>
                        </ul>
                        <a href="login.php" class="role-btn">Admin Console</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to secure your academic legacy?</h2>
                <p>Optimizing workflow for thousands of students and teachers at ISJ.</p>
                <a href="signup.php" class="btn btn-green btn-large">
                    <i class="fas fa-cloud-arrow-up"></i> Register Account
                </a>
                <p class="small-text"><i class="fas fa-lock"></i> Institutional-grade security for the 2025-2026 school year.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo"><i class="fas fa-shield-halved"></i> ISJ-DMS</div>
                    <p>Written and presented by Agoufack, Abdoul, and Aloma. Supervised by Dr. Emmanuel Moupodjou.</p>
                </div>
                <div class="footer-col">
                    <h4>Institutional</h4>
                    <a href="#">University of Yaoundé 1</a>
                    <a href="#">Saint Jean Institute</a>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 ISJ Document Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>