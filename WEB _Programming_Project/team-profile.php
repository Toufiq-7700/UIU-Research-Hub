<?php
require_once 'database-functions.php';

$teamId = (int)($_GET['team_id'] ?? 0);
if ($teamId <= 0) {
    header('Location: team-finder.php');
    exit;
}

$team = getTeamProfile($teamId);
if (!$team) {
    header('Location: team-finder.php');
    exit;
}

$members = getTeamMembers($teamId);
$activeMemberCount = count($members);
$isFull = $activeMemberCount >= (int)$team['max_members'];

$isLoggedIn = isset($_SESSION['user_id']);
$csrfToken = $isLoggedIn ? ensureCsrfToken() : '';

$currentUserId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$isLeader = $isLoggedIn && (int)$team['team_leader_id'] === $currentUserId;
$canRequest = $isLoggedIn && userCanRequestToJoinTeam($currentUserId);
$isMember = $isLoggedIn && $db->exists('team_members', 'team_id = ? AND user_id = ? AND status = ?', [$teamId, $currentUserId, 'Active']);

$categories = $isLeader ? getCategories() : [];

$joinRequest = null;
$joinStatus = null;
if ($canRequest && !$isMember && dbTableExists('join_requests')) {
    $joinRequest = getJoinRequest($teamId, $currentUserId);
    if ($joinRequest) {
        $joinStatus = (string)$joinRequest['status'];
    }
}

$pendingRequests = [];
if ($isLeader && dbTableExists('join_requests')) {
    $pendingRequests = getJoinRequestsForTeam($teamId, true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .team-header { background: linear-gradient(135deg, var(--primary-color) 0%, rgba(52, 152, 219, 0.8) 100%); color: #fff; padding: 55px 0; margin-bottom: 30px; }
        .section-title { color: var(--primary-color); margin-bottom: 20px; font-size: 1.6rem; font-weight: 600; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 20px 0; }
        .info-card { background: #f8f9fa; padding: 16px; border-radius: 10px; border-left: 4px solid var(--primary-color); }
        .info-card h4 { margin: 0 0 8px 0; font-size: 0.85rem; text-transform: uppercase; opacity: 0.8; color: var(--primary-color); }
        .info-card p { margin: 0; font-size: 1.05rem; font-weight: 600; color: #333; }
        .members-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
        .member-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 18px; text-align: center; }
        .member-avatar { width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.6rem; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 0.85rem; font-weight: 600; }
        .badge-ok { background: #e8f5e9; color: #27ae60; }
        .badge-warn { background: #fff3e0; color: #f39c12; }
        .badge-bad { background: #ffebee; color: #e74c3c; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="team-header">
        <div class="container">
            <a href="team-finder.php" class="back-link" style="color: white; text-decoration: none; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Back to Teams
            </a>
            <h1 style="margin: 15px 0 10px;"><?php echo htmlspecialchars($team['team_name']); ?></h1>
            <div class="badge <?php echo $isFull ? 'badge-bad' : 'badge-ok'; ?>">
                <?php echo $isFull ? 'Full' : 'Open'; ?> • <?php echo $activeMemberCount; ?>/<?php echo (int)$team['max_members']; ?>
            </div>
            <p style="margin: 15px 0 0; opacity: 0.95;">
                Category: <strong><?php echo htmlspecialchars($team['category_name'] ?? 'General'); ?></strong>
                <?php if (!empty($team['event_name'])): ?>
                    • Event: <strong><?php echo htmlspecialchars($team['event_name']); ?></strong>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if (($_GET['join_request'] ?? '') === 'sent'): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    Join request sent!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['join_request_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string)($_GET['join_request_error'] ?? '');
                    $msg = 'Failed to send join request.';
                    if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'full') $msg = 'This team is already full.';
                    elseif ($err === 'pending') $msg = 'Your join request is already pending.';
                    elseif ($err === 'member') $msg = 'You are already a member of this team.';
                    elseif ($err === 'unavailable') $msg = 'Join requests are not available yet. Please run dummy/migrate_db_v4.php.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['manage_success'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    Request <?php echo htmlspecialchars($_GET['manage_success']); ?>.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['manage_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string)($_GET['manage_error'] ?? '');
                    $msg = 'Failed to manage request.';
                    if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'invalid') $msg = 'Invalid request.';
                    elseif ($err === 'unavailable') $msg = 'Join requests are not available yet. Please run dummy/migrate_db_v4.php.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['member_removed'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    Member removed successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['member_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string)($_GET['member_error'] ?? '');
                    $msg = 'Failed to remove member.';
                    if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'invalid') $msg = 'Invalid member.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['team_updated'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    Team updated successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['team_update_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string)($_GET['team_update_error'] ?? '');
                    $msg = 'Failed to update team.';
                    if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'forbidden') $msg = 'Only the team leader can update the team.';
                    elseif ($err === 'too_small') $msg = 'Max members cannot be less than current active members.';
                    elseif ($err === 'required') $msg = 'Team name and description are required.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['leader_changed'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    Team leader updated successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['leader_change_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string)($_GET['leader_change_error'] ?? '');
                    $msg = 'Failed to change team leader.';
                    if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'forbidden') $msg = 'Only the team leader can transfer leadership.';
                    elseif ($err === 'not_member') $msg = 'New leader must be an active team member.';
                    elseif ($err === 'same') $msg = 'New leader must be different.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['team_delete_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 14px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string)($_GET['team_delete_error'] ?? '');
                    $msg = 'Failed to delete team.';
                    if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'forbidden') $msg = 'Only the team leader can delete the team.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>

            <h2 class="section-title">About The Team</h2>
            <p style="font-size: 1.02rem; line-height: 1.8; color: #555; margin-bottom: 25px;">
                <?php echo nl2br(htmlspecialchars((string)($team['description'] ?? ''))); ?>
            </p>

            <div class="info-grid">
                <div class="info-card">
                    <h4>Leader</h4>
                    <p><?php echo htmlspecialchars($team['leader_name'] ?? ''); ?></p>
                </div>
                <div class="info-card">
                    <h4>Status</h4>
                    <p><?php echo htmlspecialchars($team['status'] ?? ''); ?></p>
                </div>
                <div class="info-card">
                    <h4>Created</h4>
                    <p><?php echo !empty($team['created_at']) ? date('M d, Y', strtotime($team['created_at'])) : '—'; ?></p>
                </div>
                <div class="info-card">
                    <h4>Slots</h4>
                    <p><?php echo $isFull ? 'No slots available' : 'Slots available'; ?></p>
                </div>
            </div>

            <?php if ($isLeader): ?>
                <div class="card" style="margin: 25px 0;">
                    <div style="display:flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <h2 style="margin: 0; font-size: 1.4rem;"><i class="fas fa-cog"></i> Team Management</h2>
                        <span style="color:#666; font-size: 0.9rem;">Only visible to the team leader</span>
                    </div>

                    <form method="POST" action="team-update.php" style="margin-top: 15px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">

                        <div class="grid grid-2" style="gap: 15px; grid-template-columns: 2fr 1fr;">
                            <div class="form-group">
                                <label for="team_name">Team Name *</label>
                                <input id="team_name" name="team_name" class="form-control" type="text" required maxlength="100"
                                       value="<?php echo htmlspecialchars((string)($team['team_name'] ?? '')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="0">General</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo ((int)($team['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)$cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-2" style="gap: 15px; grid-template-columns: 1fr 1fr;">
                            <div class="form-group">
                                <label for="max_members">Max Members</label>
                                <input id="max_members" name="max_members" class="form-control" type="number" min="2" max="50"
                                       value="<?php echo (int)($team['max_members'] ?? 5); ?>">
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="Recruiting" <?php echo ((string)($team['status'] ?? '') === 'Recruiting' || (string)($team['status'] ?? '') === 'Full') ? 'selected' : ''; ?>>Active (auto)</option>
                                    <option value="Inactive" <?php echo ((string)($team['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div style="margin-top: 6px; color:#666; font-size: 0.85rem;">Active status auto-switches to Full when capacity is reached.</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars((string)($team['description'] ?? '')); ?></textarea>
                        </div>

                        <button type="submit" class="btn">Save Team</button>
                    </form>

                    <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 18px;">
                        <h3 style="margin: 0 0 10px; font-size: 1.1rem;"><i class="fas fa-exchange-alt"></i> Change Team Leader</h3>
                        <?php
                        $eligible = array_values(array_filter($members, fn($m) => (int)$m['user_id'] !== (int)$team['team_leader_id']));
                        ?>
                        <?php if (empty($eligible)): ?>
                            <p style="margin: 0; color:#666;">Add at least one other member to transfer leadership.</p>
                        <?php else: ?>
                            <form method="POST" action="team-transfer-leader.php" style="display:flex; gap: 10px; flex-wrap: wrap; align-items: center; margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">
                                <select name="new_leader_user_id" class="form-control" required style="min-width: 240px;">
                                    <option value="">Select new leader</option>
                                    <?php foreach ($eligible as $m): ?>
                                        <option value="<?php echo (int)$m['user_id']; ?>"><?php echo htmlspecialchars((string)$m['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-outline" onclick="return confirm('Transfer leadership to the selected member?');">Transfer</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 18px;">
                        <h3 style="margin: 0 0 10px; font-size: 1.1rem; color:#e74c3c;"><i class="fas fa-trash"></i> Delete Team</h3>
                        <form method="POST" action="team-delete.php" style="margin:0;" onsubmit="return confirm('Delete this team? This will remove all members and pending requests. This cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">
                            <button type="submit" class="btn btn-outline" style="border-color:#e74c3c; color:#e74c3c;">Delete Team</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div style="display:flex; gap:12px; flex-wrap: wrap; margin: 20px 0;">
                <?php if ($isLoggedIn): ?>
                    <a class="btn" href="messages.php?compose=<?php echo (int)$team['team_leader_id']; ?>">
                        <i class="fas fa-comment"></i> Message Leader
                    </a>
                <?php else: ?>
                    <a class="btn" href="login.php"><i class="fas fa-lock"></i> Login to Message</a>
                <?php endif; ?>

                <?php if (!$isLoggedIn): ?>
                    <a class="btn btn-outline" href="login.php" title="Login to request">
                        <i class="fas fa-user-plus"></i> Request to Join
                    </a>
                <?php elseif (!$canRequest): ?>
                    <!-- Faculty/Admin cannot request -->
                <?php elseif ($isMember): ?>
                    <span class="btn btn-outline" style="opacity:0.7; cursor: default;">You are a member</span>
                <?php elseif ($isFull): ?>
                    <span class="btn btn-outline" style="opacity:0.7; cursor: not-allowed;" title="Team is full">
                        <i class="fas fa-ban"></i> Team Full
                    </span>
                <?php elseif ($joinStatus === 'Pending'): ?>
                    <span class="btn btn-outline" style="opacity:0.7; cursor: default;">
                        <i class="fas fa-clock"></i> Request Pending
                    </span>
                <?php else: ?>
                    <form method="POST" action="team-request.php" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">
                        <button type="submit" class="btn" <?php echo ($joinStatus === 'Rejected') ? '' : ''; ?>>
                            <i class="fas fa-user-plus"></i> <?php echo ($joinStatus === 'Rejected') ? 'Request Again' : 'Request to Join'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($isLeader): ?>
        <section class="section" style="background-color: #f8f9fa;">
            <div class="container">
                <h2 class="section-title">Join Requests</h2>
                <?php if (!dbTableExists('join_requests')): ?>
                    <p style="color:#666;">Join requests are not available yet. Run <code>dummy/migrate_db_v4.php</code>.</p>
                <?php elseif (empty($pendingRequests)): ?>
                    <p style="color:#666;">No pending join requests.</p>
                <?php else: ?>
                    <div style="display:grid; gap: 12px;">
                        <?php foreach ($pendingRequests as $req): ?>
                            <div class="card" style="display:flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div>
                                    <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($req['full_name']); ?></h4>
                                    <div style="color:#666; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($req['email']); ?> • Requested <?php echo date('M d, Y', strtotime($req['requested_at'])); ?>
                                    </div>
                                </div>
                                <div style="display:flex; gap: 10px;">
                                    <form method="POST" action="team-request-manage.php" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$req['request_id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn">Accept</button>
                                    </form>
                                    <form method="POST" action="team-request-manage.php" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$req['request_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-outline" style="border-color:#e74c3c; color:#e74c3c;">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <h2 class="section-title">Current Members</h2>
            <?php if (empty($members)): ?>
                <p style="color:#666;">No members found.</p>
            <?php else: ?>
                <div class="members-grid">
                    <?php foreach ($members as $m): ?>
                        <div class="member-card">
                            <div class="member-avatar"><i class="fas fa-user"></i></div>
                            <div style="font-weight: 700;"><?php echo htmlspecialchars($m['full_name']); ?></div>
                            <div style="color: var(--primary-color); font-weight: 600; margin: 6px 0;">
                                <?php echo htmlspecialchars($m['member_role']); ?>
                            </div>
                            <div style="display:flex; gap:10px; justify-content:center; flex-wrap: wrap; margin-top: 10px;">
                                <?php if ($isLoggedIn): ?>
                                    <a href="messages.php?compose=<?php echo (int)$m['user_id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.9rem; text-decoration:none;">
                                        <i class="fas fa-comment"></i> Message
                                    </a>
                                <?php endif; ?>

                                <?php if ($isLeader && (int)$m['user_id'] !== (int)$team['team_leader_id']): ?>
                                    <form method="POST" action="team-member-remove.php" style="margin:0;" onsubmit="return confirm('Remove this member from the team?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="team_id" value="<?php echo (int)$teamId; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$m['user_id']; ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.9rem; border-color:#e74c3c; color:#e74c3c;">
                                            <i class="fas fa-user-minus"></i> Remove
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
