<?php
require_once 'database-functions.php';

// Get search params
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Category options
$categories = getCategories();

// Fetch teams
$teams = getTeams($search, $category);

$canCreateTeam = false;
$canRequestJoin = false;
$csrfToken = '';
$joinStatusMap = [];
$memberTeamMap = [];
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $canCreateTeam = userCanCreateTeam($userId);
    $canRequestJoin = userCanRequestToJoinTeam($userId);
    if ($canRequestJoin) {
        $csrfToken = ensureCsrfToken();
        $teamIds = array_map(fn($t) => (int)$t['team_id'], $teams);
        if (dbTableExists('join_requests')) {
            $joinStatusMap = getJoinRequestStatusMapForUser($userId, $teamIds);
        }
        $memberTeamIds = getUserActiveTeamIds($userId);
        $memberTeamMap = array_fill_keys($memberTeamIds, true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Finder - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Page Header -->
    <section class="section" style="background-color: #fff; padding: 40px 0;">
        <div class="container">
            <div
                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h1>Find a Research Team</h1>
                    <p>Join an existing team or create your own to start collaborating.</p>
                </div>
                <?php if ($canCreateTeam): ?>
                    <a href="team-create.php" class="btn"><i class="fas fa-plus"></i> Create Team</a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <!-- Faculty/Admin: no team creation UI -->
                <?php else: ?>
                    <a href="login.php" class="btn"><i class="fas fa-lock"></i> Login to Create Team</a>
                <?php endif; ?>
            </div>

            <!-- Search Bar -->
            <div style="margin-top: 30px;">
                <form class="search-form" method="GET" action="team-finder.php" style="display: flex; gap: 10px; max-width: 820px; flex-wrap: wrap;">
                    <input type="text" name="search" class="form-control" placeholder="Search teams by name or description..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 220px;">
                    <select name="category" class="form-control" style="width: 220px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo ((string)$category !== '' && (int)$category === (int)$cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search) || (string)$category !== ''): ?>
                        <a href="team-finder.php" class="btn btn-outline" style="text-decoration:none; padding-top: 10px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>

    <!-- Teams Grid -->
    <section class="section">
        <div class="container">
            <?php if (empty($teams)): ?>
                <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
                    <i class="fas fa-search" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No teams found</h3>
                    <p>Try adjusting your search terms or create a new team!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($teams as $team): ?>
                        <div class="card" style="cursor: pointer; transition: all 0.3s ease; display: flex; flex-direction: column;" onclick="window.location.href='team-profile.php?team_id=<?php echo $team['team_id']; ?>'">
                            <div
                                style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <h3 style="margin-bottom: 0; font-size: 1.2rem;"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                <span
                                    style="background-color: #e3f2fd; color: var(--primary-color); padding: 5px 10px; border-radius: 20px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($team['category_name'] ?? 'General'); ?>
                                </span>
                            </div>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px; flex-grow: 1;">
                                <?php echo htmlspecialchars(substr($team['description'], 0, 100)) . (strlen($team['description']) > 100 ? '...' : ''); ?>
                            </p>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                <i class="fas fa-users" style="color: #999;"></i>
                                <span><?php echo $team['current_members']; ?>/<?php echo $team['max_members']; ?> Members</span>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="team-profile.php?team_id=<?php echo $team['team_id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none;" onclick="event.stopPropagation();">View</a>

                                <?php if ($canRequestJoin): ?>
                                    <?php
                                    $teamId = (int)$team['team_id'];
                                    $isFull = (int)$team['current_members'] >= (int)$team['max_members'];
                                    $status = (string)($joinStatusMap[$teamId] ?? '');
                                    $isMember = isset($memberTeamMap[$teamId]);
                                    ?>

                                    <?php if ($isMember): ?>
                                        <span class="btn btn-outline" style="flex: 1; text-align: center; opacity: 0.7; cursor: default;" onclick="event.stopPropagation();">Member</span>
                                    <?php elseif ($isFull): ?>
                                        <span class="btn btn-outline" style="flex: 1; text-align: center; opacity: 0.7; cursor: not-allowed;" onclick="event.stopPropagation();" title="Team is full">Full</span>
                                    <?php elseif ($status === 'Pending'): ?>
                                        <span class="btn btn-outline" style="flex: 1; text-align: center; opacity: 0.7; cursor: default;" onclick="event.stopPropagation();">Pending</span>
                                    <?php else: ?>
                                        <form method="POST" action="team-request.php" style="flex: 1; margin: 0;" onclick="event.stopPropagation();" onsubmit="event.stopPropagation();">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="team_id" value="<?php echo $teamId; ?>">
                                            <button type="submit" class="btn" style="width: 100%;">
                                                <?php echo $status === 'Rejected' ? 'Request Again' : 'Request to Join'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn" style="flex: 1;" onclick="event.stopPropagation(); window.location.href='messages.php?compose=<?php echo $team['team_leader_id']; ?>'">Contact</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>

</html>
