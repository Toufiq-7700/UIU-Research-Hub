<?php
require_once 'database-functions.php';

// Category details data (Static fallback/enrichment)
$static_data = [
    'Artificial Intelligence' => [
        'color' => '#3498db',
        'overview' => 'AI research at UIU focuses on developing intelligent systems that can learn, reason, and solve complex problems. Our teams work on cutting-edge projects ranging from autonomous systems to intelligent decision-making algorithms.',
        'research_areas' => ['Machine Learning Algorithms', 'Deep Learning', 'Natural Language Understanding', 'Computer Vision', 'Intelligent Automation'],
        'applications' => ['Healthcare diagnostics', 'Autonomous vehicles', 'Smart assistants', 'Predictive analytics'],
        'teams_count' => 8,
        'active_projects' => 15
    ],
    'Natural Language Processing' => [
        'color' => '#27ae60',
        'overview' => 'NLP research at UIU focuses on developing technologies that can process and understand Bengali, English, and other languages.',
        'research_areas' => ['Sentiment Analysis', 'Machine Translation', 'Question Answering', 'Information Extraction'],
        'applications' => ['Text summarization', 'Chatbots', 'Translation services'],
        'teams_count' => 6,
        'active_projects' => 10
    ],
    // Default fallback
    'default' => [
        'color' => '#34495e',
        'overview' => 'Research in this field aims to solve critical problems through innovative technology and collaboration.',
        'research_areas' => ['Core Algorithms', 'System Design', 'Performance Optimization'],
        'applications' => ['Industry Solutions', 'Academic Research', 'Social Impact'],
        'teams_count' => 5,
        'active_projects' => 8
    ]
];

// Get category from URL parameter
$categoryId = isset($_GET['category']) ? $_GET['category'] : null;
$cat = null;
$teamsForCategory = [];
$resourcesForCategory = [];

if ($categoryId) {
    // Try to fetch from DB
    $dbCat = getCategory($categoryId);
    if ($dbCat) {
        $catIdInt = (int)($dbCat['category_id'] ?? 0);
        if ($catIdInt > 0) {
            $teamsForCategory = getTeams('', $catIdInt);
            $resourcesForCategory = getResources('', $catIdInt, '');
        }

        // Enriched data
        $enrichment = $static_data[$dbCat['category_name']] ?? $static_data['default'];
        
        $cat = [
            'id' => $dbCat['category_id'],
            'title' => $dbCat['category_name'],
            'icon' => $dbCat['icon_class'],
            'description' => $dbCat['description'],
            'color' => $enrichment['color'],
            'overview' => $enrichment['overview'],
            'research_areas' => $enrichment['research_areas'],
            'applications' => $enrichment['applications'],
            'teams_count' => count($teamsForCategory),
            'active_projects' => count($resourcesForCategory)
        ];
    }
}

// Redirect if invalid
if (!$cat) {
    header('Location: categories.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cat['title']); ?> - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .category-header {
            background: linear-gradient(135deg, <?php echo $cat['color']; ?> 0%, rgba(52, 152, 219, 0.8) 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .category-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .research-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .research-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .research-item h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, rgba(52, 152, 219, 0.6) 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <!-- Category Header -->
    <section class="category-header" style="background: linear-gradient(135deg, <?php echo $cat['color']; ?> 0%, rgba(52, 152, 219, 0.8) 100%);">
        <div class="container">
            <i class="<?php echo htmlspecialchars($cat['icon'] ?? 'fas fa-layer-group'); ?> category-icon"></i>
            <div class="category-content">
                <a href="categories.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Categories
                </a>
                <h1><?php echo htmlspecialchars($cat['title'] ?? 'Category'); ?></h1>
                <p class="category-description"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></p>
            </div>
        </div>
    </section>

    <!-- Overview Section -->
    <section class="section" style="padding-top: 40px;">
        <div class="container">
            <h2 class="section-title">Overview</h2>
            <p style="font-size: 1.05rem; line-height: 1.6; color: #555;">
                <?php echo htmlspecialchars($cat['overview'] ?? ''); ?>
            </p>
        </div>
    </section>

    <!-- Statistics -->
    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <h2 class="section-title">By The Numbers</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $cat['teams_count']; ?></div>
                    <div class="stat-label">Active Teams</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, var(--accent-color) 0%, rgba(46, 204, 113, 0.6) 100%);">
                    <div class="stat-number"><?php echo $cat['active_projects']; ?></div>
                    <div class="stat-label">Resources</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Teams -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Teams In This Category</h2>
            <?php if (empty($teamsForCategory)): ?>
                <p style="color:#666;">No teams found for this category yet.</p>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach (array_slice($teamsForCategory, 0, 6) as $t): ?>
                        <div class="card">
                            <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars((string)$t['team_name']); ?></h3>
                            <p style="color:#666; font-size: 0.9rem;">
                                <?php
                                $desc = trim((string)($t['description'] ?? ''));
                                echo htmlspecialchars($desc === '' ? 'No description.' : (mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc));
                                ?>
                            </p>
                            <a href="team-profile.php?team_id=<?php echo (int)$t['team_id']; ?>" class="btn btn-outline" style="margin-top: 10px; text-decoration:none;">View Team</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($teamsForCategory) > 6): ?>
                    <div style="margin-top: 15px;">
                        <a href="team-finder.php?category=<?php echo urlencode((string)$cat['id']); ?>" class="btn" style="text-decoration:none;">View All Teams</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Resources -->
    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <h2 class="section-title">Resources In This Category</h2>
            <?php if (empty($resourcesForCategory)): ?>
                <p style="color:#666;">No resources found for this category yet.</p>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach (array_slice($resourcesForCategory, 0, 6) as $r): ?>
                        <div class="card">
                            <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars((string)$r['title']); ?></h3>
                            <p style="color:#666; font-size: 0.9rem; margin-bottom: 12px;">
                                <?php echo htmlspecialchars((string)($r['resource_type'] ?? 'Resource')); ?> • <?php echo date('M d, Y', strtotime((string)$r['created_at'])); ?>
                            </p>
                            <a href="<?php echo htmlspecialchars((string)$r['file_path']); ?>" target="_blank" class="btn btn-outline" style="text-decoration:none;" download>Download</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($resourcesForCategory) > 6): ?>
                    <div style="margin-top: 15px;">
                        <a href="resources.php?category=<?php echo urlencode((string)$cat['id']); ?>" class="btn" style="text-decoration:none;">View All Resources</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Research Areas -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Research Areas</h2>
            <div class="research-grid">
                <?php foreach ($cat['research_areas'] as $area): ?>
                    <div class="research-item">
                        <h4><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($area); ?></h4>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Applications -->
    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <h2 class="section-title">Real-World Applications</h2>
            <div class="research-grid">
                <?php foreach ($cat['applications'] as $app): ?>
                    <div class="research-item">
                        <h4><i class="fas fa-lightbulb" style="margin-right: 10px;"></i><?php echo htmlspecialchars($app); ?></h4>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="section">
        <div class="container" style="text-align: center;">
            <h2 class="section-title">Get Involved</h2>
            <p style="font-size: 1.05rem; margin-bottom: 30px; color: #555;">
                Join one of our research teams or start your own project in this exciting field!
            </p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="team-finder.php" class="btn"><i class="fas fa-users"></i> Find a Team</a>
                <a href="categories.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Categories</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <h3>UIU Research Hub</h3>
                    <p>&copy; 2025 UIU Research Hub. All rights reserved.</p>
                </div>
                <div class="footer-links">
                    <a href="about.php">About</a>
                    <a href="terms.php">Terms</a>
                    <a href="contact.php">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>

</html>
