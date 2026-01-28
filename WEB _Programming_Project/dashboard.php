<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user = getUserWithRole($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$roleName = (string)($user['role_name'] ?? '');
$isFaculty = strcasecmp($roleName, 'Faculty') === 0;
$canCreateTeam = strcasecmp($roleName, 'Student') === 0;
$canUploadResource = userCanUploadResource($_SESSION['user_id']);
$csrfToken = ensureCsrfToken();

$myTeams = [];
if (!$isFaculty) {
    $myTeams = $db->fetchAll(
        "SELECT t.*, tm.member_role 
         FROM teams t 
         JOIN team_members tm ON t.team_id = tm.team_id 
         WHERE tm.user_id = ? 
         ORDER BY t.created_at DESC",
        [$_SESSION['user_id']]
    );
}
$myResources = getResourcesByUploader($_SESSION['user_id']);
$pendingJoinRequests = getPendingJoinRequestsForLeader((int)$_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="section" style="padding: 40px 0;">
        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Profile updated successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['profile_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php if (($_GET['profile_error'] ?? '') === 'missing_column'): ?>
                        Profile update is not available yet. Please run `dummy/migrate_db_v3.php` to update the database.
                    <?php else: ?>
                        Failed to update profile. Please try again.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (($_GET['access_denied'] ?? '') === 'team_create'): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Team creation is available to students only.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['resource_deleted'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Resource deleted successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['resource_updated'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Resource updated successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['resource_delete_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Failed to delete resource. Please try again.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['team_deleted'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Team deleted successfully!
                </div>
            <?php endif; ?>

            <div class="grid grid-3" style="grid-template-columns: 1fr 3fr; align-items: start; gap: 30px;">
                <!-- Sidebar / User Info -->
                <div class="card" style="text-align: center;">
                    <div style="width: 120px; height: 120px; background-color: var(--primary-light); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--primary-color); overflow: hidden;">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <span style="display: inline-block; padding: 4px 12px; background-color: var(--primary-light); color: var(--primary-color); border-radius: 15px; font-size: 0.8rem; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($user['role_name'] ?? 'User'); ?>
                    </span>
                    <p style="color: #666; margin-bottom: 20px;"><?php echo htmlspecialchars($user['email']); ?></p>

                    <div style="text-align: left; margin: 15px 0 5px; padding-top: 15px; border-top: 1px solid #eee;">
                        <h4 style="margin: 0 0 8px 0; font-size: 1rem;">Skills</h4>
                        <?php
                        $skillsStr = trim((string)($user['skills'] ?? ''));
                        $skillsList = $skillsStr === '' ? [] : array_filter(array_map('trim', explode(',', $skillsStr)));
                        ?>
                        <?php if (empty($skillsList)): ?>
                            <p style="margin: 0 0 12px 0; color: #666; font-size: 0.9rem;">No skills added yet.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                                <?php foreach ($skillsList as $skill): ?>
                                    <span style="font-size: 0.8rem; background: #e3f2fd; color: #2980b9; padding: 3px 10px; border-radius: 999px;">
                                        <?php echo htmlspecialchars($skill); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h4 style="margin: 0 0 8px 0; font-size: 1rem;">Bio</h4>
                        <?php $bioStr = trim((string)($user['bio'] ?? '')); ?>
                        <?php if ($bioStr === ''): ?>
                            <p style="margin: 0; color: #666; font-size: 0.9rem;">No bio added yet.</p>
                        <?php else: ?>
                            <p style="margin: 0; color: #555; font-size: 0.9rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($bioStr)); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Edit Profile Trigger -->
                    <button onclick="document.getElementById('editProfileForm').style.display = 'block'; this.style.display='none';" class="btn btn-outline" style="width: 100%; margin-bottom: 10px;">Edit Profile</button>
                    
                    <!-- Edit Profile Form -->
                    <form id="editProfileForm" method="POST" action="profile-update.php" enctype="multipart/form-data" style="display: none; text-align: left; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="redirect" value="dashboard.php">
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Skills</label>
                            <textarea name="skills" class="form-control" rows="3" placeholder="Python, Java, ML..."><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" class="form-control" rows="4" placeholder="Tell us about your research..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
                        <button type="button" onclick="location.reload()" class="btn btn-outline" style="width: 100%; margin-top: 10px; border: none; color: #666;">Cancel</button>
                    </form>

                    <a href="logout.php" class="btn btn-outline" style="width: 100%; border-color: #e74c3c; color: #e74c3c; margin-top: 20px;">Logout</a>
                </div>

                <!-- Main Content -->
                <div>
                    <?php if (!$isFaculty): ?>
                        <!-- Join Requests (Team Leader Only) -->
                        <div class="card" style="margin-bottom: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h2 style="font-size: 1.5rem; margin: 0;"><i class="fas fa-user-plus"></i> Join Requests</h2>
                                <span id="joinRequestsCount" style="font-size: 0.9rem; color: #666;"><?php echo count($pendingJoinRequests); ?> Pending</span>
                            </div>

                            <?php if (!dbTableExists('join_requests')): ?>
                                <p style="color: #666; margin: 0;">Join requests are not available yet. Run <code>dummy/migrate_db_v4.php</code>.</p>
                            <?php elseif (empty($pendingJoinRequests)): ?>
                                <p id="joinRequestsEmpty" style="color: #666; margin: 0;">No pending join requests.</p>
                            <?php else: ?>
                                <div id="joinRequestsList" style="display: grid; gap: 15px;">
                                    <?php foreach ($pendingJoinRequests as $req): ?>
                                        <div class="join-request-item" data-request-id="<?php echo (int)$req['request_id']; ?>" style="padding: 15px; background: #f9f9f9; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                                            <div>
                                                <h4 style="margin: 0 0 5px 0;">
                                                    <?php echo htmlspecialchars($req['student_name']); ?>
                                                    <span style="font-size: 0.85rem; color: #666; font-weight: 400;">requested to join</span>
                                                    <a href="team-profile.php?team_id=<?php echo (int)$req['team_id']; ?>" style="text-decoration: none; color: inherit;">
                                                        <?php echo htmlspecialchars($req['team_name']); ?>
                                                    </a>
                                                </h4>
                                                <div style="font-size: 0.85rem; color: #666;">
                                                    <?php echo htmlspecialchars($req['student_email']); ?> • <?php echo date('M d, Y', strtotime($req['requested_at'])); ?>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                                <a class="btn btn-outline" style="padding: 6px 12px; font-size: 0.9rem; text-decoration: none;" href="messages.php?compose=<?php echo (int)$req['user_id']; ?>">
                                                    <i class="fas fa-comment"></i> Message
                                                </a>
                                                <form class="join-request-form" method="POST" action="team-request-manage.php" style="margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="team_id" value="<?php echo (int)$req['team_id']; ?>">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$req['request_id']; ?>">
                                                    <input type="hidden" name="action" value="accept">
                                                    <input type="hidden" name="ajax" value="1">
                                                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 0.9rem;" data-confirm="Accept this join request?">Accept</button>
                                                </form>
                                                <form class="join-request-form" method="POST" action="team-request-manage.php" style="margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="team_id" value="<?php echo (int)$req['team_id']; ?>">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$req['request_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="ajax" value="1">
                                                    <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.9rem; border-color: #e74c3c; color: #e74c3c;" data-confirm="Reject this join request?">Reject</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div id="joinRequestsToast" style="display:none; margin-top: 15px; background-color: #d4edda; color: #155724; padding: 12px; border-radius: var(--radius);"></div>
                                <div id="joinRequestsToastErr" style="display:none; margin-top: 15px; background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: var(--radius);"></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isFaculty): ?>
                        <!-- My Teams -->
                        <div class="card" style="margin-bottom: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h2 style="font-size: 1.5rem; margin: 0;"><i class="fas fa-users"></i> My Teams</h2>
                                <?php if ($canCreateTeam): ?>
                                    <a href="team-create.php" class="btn" style="padding: 5px 15px; font-size: 0.9rem;">+ Create Team</a>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($myTeams)): ?>
                                <p style="color: #666;">You haven't joined any teams yet.</p>
                            <?php else: ?>
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($myTeams as $team): ?>
                                        <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <h4 style="margin: 0 0 5px 0;"><a href="team-profile.php?team_id=<?php echo $team['team_id']; ?>" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($team['team_name']); ?></a></h4>
                                                <span style="font-size: 0.8rem; background: #e3f2fd; color: #2980b9; padding: 2px 8px; border-radius: 10px;"><?php echo htmlspecialchars($team['member_role']); ?></span>
                                            </div>
                                            <a href="team-profile.php?team_id=<?php echo $team['team_id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">View</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- My Uploads -->
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="font-size: 1.5rem; margin: 0;"><i class="fas fa-file-upload"></i> My Resources</h2>
                            <?php if ($canUploadResource): ?>
                                <a href="resource-upload.php" class="btn" style="padding: 5px 15px; font-size: 0.9rem;">+ Upload</a>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($myResources)): ?>
                            <p style="color: #666;">You haven't uploaded any resources yet.</p>
                        <?php else: ?>
                            <div style="display: grid; gap: 15px;">
                                <?php foreach ($myResources as $res): ?>
                                    <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($res['title']); ?></h4>
                                            <span style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($res['resource_type']); ?> • <?php echo date('M Y', strtotime($res['created_at'])); ?></span>
                                        </div>
                                        <div style="display: flex; gap: 10px;">
                                            <a href="<?php echo htmlspecialchars($res['file_path']); ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" download>Download</a>
                                            <a href="resource-edit.php?resource_id=<?php echo (int)$res['resource_id']; ?>&redirect=dashboard" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; text-decoration:none;">Edit</a>
                                            <form method="POST" action="resource-delete.php" style="display: inline;" onsubmit="return confirm('Delete this resource? This cannot be undone.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="resource_id" value="<?php echo (int)$res['resource_id']; ?>">
                                                <input type="hidden" name="redirect" value="dashboard">
                                                <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; border-color: #e74c3c; color: #e74c3c;">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        (function() {
            const forms = document.querySelectorAll('.join-request-form');
            if (!forms.length) return;

            const toast = document.getElementById('joinRequestsToast');
            const toastErr = document.getElementById('joinRequestsToastErr');
            const countEl = document.getElementById('joinRequestsCount');
            const listEl = document.getElementById('joinRequestsList');
            const emptyEl = document.getElementById('joinRequestsEmpty');

            const showToast = (el, msg) => {
                if (!el) return;
                el.textContent = msg;
                el.style.display = 'block';
                setTimeout(() => { el.style.display = 'none'; }, 2500);
            };

            const updateCount = () => {
                if (!countEl || !listEl) return;
                const items = listEl.querySelectorAll('.join-request-item');
                countEl.textContent = `${items.length} Pending`;
                if (emptyEl && items.length === 0) {
                    emptyEl.style.display = 'block';
                }
            };

            forms.forEach((form) => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    const confirmMsg = btn?.getAttribute('data-confirm') || 'Are you sure?';
                    if (!confirm(confirmMsg)) return;

                    try {
                        if (toast) toast.style.display = 'none';
                        if (toastErr) toastErr.style.display = 'none';

                        const fd = new FormData(form);
                        const res = await fetch(form.action, {
                            method: 'POST',
                            body: fd,
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json().catch(() => null);
                        if (!res.ok || !json || json.success !== true) {
                            throw new Error(json?.message || 'Request failed');
                        }

                        const item = form.closest('.join-request-item');
                        if (item) item.remove();
                        updateCount();
                        showToast(toast, json.message || 'Updated');
                    } catch (err) {
                        showToast(toastErr, err?.message || 'Failed');
                    }
                });
            });

            updateCount();
        })();
    </script>
</body>
</html>
