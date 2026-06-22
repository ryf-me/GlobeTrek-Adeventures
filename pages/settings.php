<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/settings.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <?php include '../includes/user-sidebar.php'; ?>

            <div class="usr-canvas">
                <div class="usr-page-header">
                    <h1>Settings</h1>
                    <p>Manage your account settings and preferences.</p>
                </div>

                <div class="settings-sections">
                    <!-- General Preferences -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">language</span>
                            <h3>General Preferences</h3>
                        </div>
                        <div class="settings-form-row">
                            <div class="settings-form-group">
                                <label for="language">Preferred Language</label>
                                <select id="language">
                                    <option>English (US)</option>
                                    <option>Spanish</option>
                                    <option>French</option>
                                    <option>German</option>
                                </select>
                            </div>
                            <div class="settings-form-group">
                                <label for="currency">Currency Display</label>
                                <select id="currency">
                                    <option>USD ($)</option>
                                    <option>EUR (€)</option>
                                    <option>GBP (£)</option>
                                    <option>JPY (¥)</option>
                                    <option>LKR (Rs)</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Security -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">security</span>
                            <h3>Security</h3>
                        </div>
                        <div class="settings-password-stack">
                            <p class="settings-subheading">Change Password</p>
                            <div class="settings-form-group">
                                <label for="current-password">Current Password</label>
                                <input type="password" id="current-password" placeholder="••••••••">
                            </div>
                            <div class="settings-form-row">
                                <div class="settings-form-group">
                                    <label for="new-password">New Password</label>
                                    <input type="password" id="new-password" placeholder="••••••••">
                                </div>
                                <div class="settings-form-group">
                                    <label for="confirm-password">Confirm New Password</label>
                                    <input type="password" id="confirm-password" placeholder="••••••••">
                                </div>
                            </div>
                            <div>
                                <button type="button" class="settings-btn settings-btn-primary" id="update-password-btn">Update Password</button>
                            </div>
                        </div>
                    </section>

                    <!-- Notifications -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">notifications</span>
                            <h3>Notifications</h3>
                        </div>
                        <div>
                            <div class="settings-toggle-row">
                                <div class="settings-toggle-info">
                                    <p>Email Notifications</p>
                                    <p>Receive booking updates via email</p>
                                </div>
                                <label class="settings-toggle">
                                    <input type="checkbox" checked>
                                    <span class="settings-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="settings-toggle-row">
                                <div class="settings-toggle-info">
                                    <p>SMS Updates</p>
                                    <p>Get real-time trip alerts on your phone</p>
                                </div>
                                <label class="settings-toggle">
                                    <input type="checkbox">
                                    <span class="settings-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="settings-toggle-row">
                                <div class="settings-toggle-info">
                                    <p>Promotional Offers</p>
                                    <p>Occasional discounts and travel inspiration</p>
                                </div>
                                <label class="settings-toggle">
                                    <input type="checkbox" checked>
                                    <span class="settings-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <!-- Account Privacy -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">privacy_tip</span>
                            <h3>Account Privacy</h3>
                        </div>
                        <div>
                            <label class="settings-checkbox-row">
                                <input type="checkbox">
                                <div class="settings-checkbox-info">
                                    <span>Make Profile Public</span>
                                    <p>Allow others to see your travel wishlist and reviews.</p>
                                </div>
                            </label>
                            <a href="#" class="settings-manage-data">
                                <span>Manage Data</span>
                                <span class="material-symbols-outlined">open_in_new</span>
                            </a>
                        </div>
                    </section>

                    <!-- Danger Zone -->
                    <section class="settings-section danger">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">warning</span>
                            <h3>Danger Zone</h3>
                        </div>
                        <div class="settings-danger-row">
                            <div class="settings-danger-info">
                                <p>Delete Account</p>
                                <p>This action is permanent and cannot be undone. All your bookings and data will be lost.</p>
                            </div>
                            <button type="button" class="settings-btn settings-btn-danger" id="delete-account-btn">Delete Account</button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
        // Password update feedback
        document.getElementById('update-password-btn').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerText;
            btn.innerText = 'Updating...';
            btn.disabled = true;
            btn.style.opacity = '0.5';

            setTimeout(function() {
                btn.innerText = 'Updated!';
                btn.style.opacity = '1';
                btn.style.background = '#16a34a';

                setTimeout(function() {
                    btn.innerText = originalText;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2000);
            }, 1000);
        });

        // Delete account confirmation
        document.getElementById('delete-account-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                alert('Account deletion request submitted.');
            }
        });
    </script>
</body>
</html>
