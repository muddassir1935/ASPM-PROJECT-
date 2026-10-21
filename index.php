<?php 
require_once 'includes/header.php'; 
?>

<?php if($currentUser): ?>
    <!-- Logged In Dashboard View -->
    <div style="max-width: 850px; margin: 1rem auto;">
        <!-- Welcome Banner Card -->
        <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border-radius: 16px; padding: 2.2rem; box-shadow: 0 10px 25px rgba(5, 150, 105, 0.2); margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="background: rgba(255,255,255,0.2); font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em;">
                        Student Portal
                    </span>
                    <h1 style="font-size: 1.85rem; font-weight: 800; margin-top: 8px;">
                        Welcome back, <?= htmlspecialchars($currentUser['name']) ?>! 👋
                    </h1>
                    <p style="color: #d1fae5; font-size: 0.95rem; margin-top: 4px;">
                        <?= htmlspecialchars($currentUser['email']) ?>
                    </p>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b;">
                Your Feed
            </h3>
            <button style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                + New Post
            </button>
        </div>

        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
            <h4 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">No updates yet</h4>
            <p style="font-size: 0.9rem; color: #64748b; max-width: 300px; margin: 0 auto;">
                When you follow other students or societies, their announcements and posts will appear here.
            </p>
        </div>
    </div>

<?php else: ?>
    <!-- Public Hero View -->
    <div style="max-width: 850px; margin: 4rem auto 2rem auto; text-align: center;">
        <span style="background: #ecfdf5; color: #059669; font-size: 0.85rem; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-block; margin-bottom: 1.2rem; border: 1px solid #a7f3d0;">
            🎓 The #1 Student Network
        </span>

        <h1 style="font-size: 3rem; font-weight: 800; color: #0f172a; line-height: 1.15; letter-spacing: -0.03em; margin-bottom: 1.2rem;">
            Campus Life,<br>Connected.
        </h1>

        <p style="font-size: 1.1rem; color: #64748b; max-width: 600px; margin: 0 auto 2.5rem auto; line-height: 1.6;">
            Join the ultimate student network. Share updates, discover university events, and stay connected with your campus community in real-time.
        </p>

        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="signup.php" class="btn-nav-primary" style="padding: 14px 32px; font-size: 1.05rem;">
                Create Your Account
            </a>
            <a href="login.php" class="nav-link" style="padding: 14px 32px; font-size: 1.05rem; border: 1.5px solid #cbd5e1; background: white; border-radius: 10px; color: #1e293b;">
                Log In
            </a>
        </div>

        <!-- Features Overview Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-top: 5rem; text-align: left;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">📢</div>
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Real-time Announcements</h4>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                    Never miss an important update. Get instant announcements from faculty, societies, and administration.
                </p>
            </div>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">🤝</div>
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Connect with Peers</h4>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                    Find and follow students from your department. Share notes, discuss projects, and build your network.
                </p>
            </div>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">📱</div>
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Always Accessible</h4>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                    Experience a fast, responsive design that works perfectly on your laptop, tablet, or smartphone.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>