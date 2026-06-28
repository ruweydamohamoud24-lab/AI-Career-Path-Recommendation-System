<?php
// dashboard.php - Vertical Stack (Education, Skills, Interests only)

require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];

// Handle Delete History
if (isset($_GET['delete_history'])) {
    $stmt = $pdo->prepare("DELETE FROM user_history WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header('Location: dashboard.php?success=history_deleted');
    exit;
}

if (isset($_GET['delete_history_item']) && isset($_GET['id'])) {
    $item_id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM user_history WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    header('Location: dashboard.php?success=history_deleted');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get latest recommendation
$latest_career = $user['latest_career'] ?? null;
$latest_match_score = $user['latest_match_score'] ?? null;
$latest_salary = $user['latest_salary'] ?? null;
$latest_next_level = $user['latest_next_level'] ?? null;
$latest_recommended_at = $user['latest_recommended_at'] ?? null;

// Get user history
$stmt = $pdo->prepare("SELECT * FROM user_history WHERE user_id = ? ORDER BY recommended_at DESC LIMIT 20");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

// Get courses
$stmt = $pdo->prepare("SELECT * FROM courses");
$stmt->execute();
$all_courses = $stmt->fetchAll();

$is_admin = $user['is_admin'] ?? 0;
?>

<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CareerAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.8rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(135deg, #00b4d8, #90e0ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .logo i { color: #00b4d8; margin-right: 8px; }
        .nav-links { display: flex; gap: 2rem; }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .nav-links a:hover { color: #00b4d8; }
        .nav-links a.active { color: #00b4d8; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-name { color: white; font-weight: 500; font-size: 0.85rem; }
        .logout-btn {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
            padding: 0.35rem 0.8rem;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 80px 1.5rem 2rem;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }
        .glass-card:hover { transform: translateY(-3px); border-color: rgba(0, 180, 216, 0.5); }
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 0.7rem;
        }
        .card-title i { color: #00b4d8; font-size: 1.2rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 0.4rem;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .form-group label i { margin-right: 6px; color: #00b4d8; }
        
        /* SELECT DROPDOWN - DARK BACKGROUND */
        select.form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }
        select.form-control option {
            background: #1a1a2e;
            color: white;
        }
        select.form-control:focus {
            outline: none;
            border-color: #00b4d8;
        }
        
        /* TEXTAREA */
        textarea.form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 100px;
        }
        textarea.form-control:focus {
            outline: none;
            border-color: #00b4d8;
            background: rgba(255, 255, 255, 0.25);
        }
        ::placeholder { color: rgba(255, 255, 255, 0.5); font-size: 0.8rem; }
        
        .example-text {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.3rem;
            display: block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: white;
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 180, 216, 0.4); }
        
        .button-wrapper { display: flex; justify-content: center; margin-top: 1rem; margin-bottom: 0.5rem; }
        
        .welcome-card { text-align: center; }
        .welcome-card h1 { color: white; font-size: 1.6rem; margin-bottom: 0.3rem; }
        .latest-card { background: linear-gradient(135deg, rgba(0,180,216,0.15), rgba(0,119,182,0.05)); border: 2px solid #00b4d8; }
        
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem; }
        
        .btn-secondary, .btn-danger {
            padding: 0.35rem 0.8rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .btn-secondary { background: rgba(255, 255, 255, 0.2); color: white; }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.3); }
        .btn-danger { background: rgba(255, 107, 107, 0.2); color: #ff6b6b; border: 1px solid #ff6b6b; }
        .btn-danger:hover { background: #ff6b6b; color: white; }
        
        .history-item {
            padding: 0.6rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
            color: white;
        }
        .history-item:hover { background: rgba(255, 255, 255, 0.05); border-radius: 8px; }
        
        .course-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 0.6rem;
            margin-bottom: 0.6rem;
            transition: all 0.2s;
            color: white;
        }
        .course-card:hover { background: rgba(255, 255, 255, 0.1); }
        .course-card strong { color: #00b4d8; font-size: 0.8rem; }
        .course-card p { font-size: 0.65rem; color: rgba(255, 255, 255, 0.7); margin-top: 3px; }
        
        .result-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            border-left: 3px solid #00b4d8;
            transition: all 0.2s;
            color: white;
        }
        .result-card:hover { background: rgba(255, 255, 255, 0.1); }
        .result-card strong { color: #00b4d8; font-size: 0.9rem; }
        
        .success-msg {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 0.6rem;
            margin-bottom: 1rem;
            color: #4caf50;
            font-size: 0.75rem;
            display: none;
        }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .container { padding: 70px 1rem 1rem; }
            .nav-links { display: none; }
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #00b4d8; border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div class="logo"><i class="fas fa-brain"></i> Career<span style="color:#00b4d8">AI</span></div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="profile.php">Profile</a>
        </div>
        <div class="user-info">
            <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($fullname); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div id="successMsg" class="success-msg"></div>

    <!-- Welcome Card -->
    <div class="glass-card welcome-card">
        <h1>Welcome back, <?php echo htmlspecialchars($fullname); ?>! 👋</h1>
        <p style="color: rgba(255, 255, 255, 0.8);">Find your perfect career path with AI-powered recommendations based on your skills and interests.</p>
    </div>

    <!-- Latest Recommendation Card -->
    <?php if ($latest_career): ?>
    <div class="glass-card latest-card">
        <div class="card-title">
            <i class="fas fa-star" style="color: gold;"></i> Your Last Recommendation
            <span style="font-size: 0.7rem; margin-left: auto;">
                <i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($latest_recommended_at)); ?>
            </span>
        </div>
        <div style="text-align: center; padding: 0.5rem;">
            <div style="font-size: 0.9rem; color: rgba(255,255,255,0.7);">🎯 Best Career Match for You</div>
            <h2 style="color: #00b4d8; font-size: 1.8rem; margin: 0.5rem 0;"><?php echo htmlspecialchars($latest_career); ?></h2>
            <div style="margin: 0.5rem 0;">
               
            </div>
            <div class="grid-2" style="margin-top: 1rem; text-align: left;">
                <div>
                    <p style="color: white;"><i class="fas fa-arrow-up"></i> <strong>Next Level:</strong> <?php echo htmlspecialchars($latest_next_level); ?></p>
                    <p style="color: white;"><i class="fas fa-dollar-sign"></i> <strong>Salary Range:</strong> <?php echo htmlspecialchars($latest_salary); ?></p>
                </div>
                <div>
                    <p style="color: white;"><i class="fas fa-chart-line"></i> <strong>Growth Rate:</strong> 15-35%</p>
                    <p style="color: white;"><i class="fas fa-calendar"></i> <strong>Recommended:</strong> <?php echo date('F j, Y', strtotime($latest_recommended_at)); ?></p>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <button onclick="scrollToForm()" class="btn-primary" style="padding: 0.5rem 1.5rem; font-size: 0.8rem;">
                    <i class="fas fa-sync"></i> Get New Recommendation
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Get New Career Recommendation - VERTICAL STACK (ISDABA XIGSAN) -->
    <div class="glass-card" id="recommendationForm">
        <div class="card-title">
            <i class="fas fa-magic"></i> Get New Career Recommendation
        </div>
        
        <!-- Education Level -->
        <div class="form-group">
            <label><i class="fas fa-graduation-cap"></i> Education Level</label>
            <select id="education" class="form-control">
                <option value="Bachelor's">Bachelor's Degree</option>
                <option value="Master's">Master's Degree</option>
                <option value="PhD">PhD / Doctorate</option>
                
            </select>
        </div>
        
        <!-- Your Skills -->
        <div class="form-group">
            <label><i class="fas fa-code"></i> Your Skills <span style="color:#00b4d8;">(comma separated)</span></label>
            <textarea id="skills" class="form-control" placeholder="e.g., Python, JavaScript, SQL, Machine Learning, Data Analysis"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
            <span class="example-text">💡 Example: Python, JavaScript, SQL, Machine Learning, Data Analysis, Project Management</span>
        </div>
        
        <!-- Your Interests -->
        <div class="form-group">
            <label><i class="fas fa-heart"></i> Your Interests <span style="color:#00b4d8;">(comma separated)</span></label>
            <textarea id="interests" class="form-control" placeholder="e.g., Technology, Data Science, Web Development, AI"><?php echo htmlspecialchars($user['interests'] ?? ''); ?></textarea>
            <span class="example-text">💡 Example: Technology, Data Science, Web Development, AI, Business, Healthcare</span>
        </div>
        
        <!-- Button Centered -->
        <div class="button-wrapper">
            <button onclick="getRecommendation()" class="btn-primary">
                <i class="fas fa-search"></i> Get Recommendations
            </button>
        </div>
        
        <div id="recommendResults" style="margin-top: 1rem;"></div>
    </div>

    <!-- Skill Gap Chart & Courses -->
    <div class="grid-2" style="margin-bottom: 1.5rem;">
        <div class="glass-card">
            <div class="card-title"><i class="fas fa-chart-pie"></i> Skill Gap Analysis</div>
            <div style="max-width: 250px; margin: 0 auto;">
                <canvas id="skillGapChart" width="250" height="250"></canvas>
            </div>
            <p style="color: rgba(255,255,255,0.5); text-align: center; margin-top: 1rem; font-size: 0.7rem;">
                Based on your skills vs market demand
            </p>
        </div>
        <div class="glass-card">
            <div class="card-title"><i class="fas fa-graduation-cap"></i> Recommended Courses</div>
            <div style="max-height: 280px; overflow-y: auto;">
                <?php 
                $shown = [];
                foreach ($all_courses as $course):
                    if (in_array($course['skill_name'], $shown)) continue;
                    $shown[] = $course['skill_name'];
                ?>
                    <div class="course-card">
                        <strong>📚 <?php echo htmlspecialchars($course['skill_name']); ?></strong>
                        <p><?php echo htmlspecialchars($course['course_name']); ?></p>
                        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($course['platform']); ?> • <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($shown)): ?>
                    <p style="color: rgba(255,255,255,0.4); text-align: center; padding: 1rem;">No courses available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recommendation History -->
    <div class="glass-card">
        <div class="card-title">
            <i class="fas fa-history"></i> Recommendation History
            <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                <button onclick="exportToPDF()" class="btn-secondary">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button onclick="deleteAllHistory()" class="btn-danger">
                    <i class="fas fa-trash"></i> Delete All
                </button>
            </div>
        </div>
        <div id="historyContent" style="max-height: 350px; overflow-y: auto;">
            <?php if (count($history) > 0): ?>
                <?php foreach ($history as $item): ?>
                    <div class="history-item">
                        <div>
                            <strong style="color: #00b4d8;"><?php echo htmlspecialchars($item['recommended_career']); ?></strong>
                            <span style="background: rgba(0,180,216,0.2); padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.65rem; margin-left: 0.5rem;">
                                <?php echo $item['match_score']; ?>% Match
                            </span>
                            <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); margin-top: 3px;">
                                <i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($item['recommended_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <button onclick="deleteHistoryItem(<?php echo $item['id']; ?>)" class="btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.65rem;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: rgba(255,255,255,0.5); text-align: center; padding: 1.5rem;">No history yet. Get recommendations first!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const user_id = <?php echo $user_id; ?>;
let skillChart = null;

function scrollToForm() {
    document.getElementById('recommendationForm').scrollIntoView({ behavior: 'smooth' });
}

function updateSkillChart() {
    const skillsText = document.getElementById('skills').value;
    if (!skillsText) {
        const ctx = document.getElementById('skillGapChart').getContext('2d');
        if (skillChart) skillChart.destroy();
        return;
    }
    const skills = skillsText.split(',').map(s => s.trim().toLowerCase());
    const marketSkills = ['Python', 'JavaScript', 'SQL', 'Java', 'React', 'AWS', 'Docker', 'Leadership', 'Communication', 'Machine Learning', 'Data Analysis'];
    const hasCount = skills.filter(s => marketSkills.map(m => m.toLowerCase()).includes(s)).length;
    const missingCount = marketSkills.length - hasCount;
    const ctx = document.getElementById('skillGapChart').getContext('2d');
    if (skillChart) skillChart.destroy();
    skillChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Skills You Have', 'Skills You Need'],
            datasets: [{
                data: [hasCount, missingCount > 0 ? missingCount : 1],
                backgroundColor: ['#00b4d8', '#ff6b6b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { labels: { color: 'white', font: { size: 11 } } } }
        }
    });
}

function exportToPDF() {
    const element = document.getElementById('historyContent');
    if (!element.innerHTML.trim() || element.innerHTML.includes('No history yet')) {
        alert('No history to export!');
        return;
    }
    const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: `career_history_<?php echo $user_id; ?>.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, backgroundColor: '#1a1a2e' },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

function deleteAllHistory() {
    if (confirm('Are you sure you want to delete ALL your recommendation history?')) {
        window.location.href = 'dashboard.php?delete_history=1';
    }
}

function deleteHistoryItem(id) {
    if (confirm('Delete this recommendation?')) {
        window.location.href = `dashboard.php?delete_history_item=1&id=${id}`;
    }
}

async function getRecommendation() {
    const skillsText = document.getElementById('skills').value;
    const education = document.getElementById('education').value;
    const interestsText = document.getElementById('interests').value;
    
    if (!skillsText.trim()) {
        alert('Please enter your skills!');
        return;
    }
    
    const resultsDiv = document.getElementById('recommendResults');
    resultsDiv.innerHTML = '<div style="text-align: center; padding: 1rem;"><div style="width:35px;height:35px;border:2px solid rgba(255,255,255,0.2);border-top-color:#00b4d8;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 0.5rem;"></div><p style="color:white;">🤔 AI is analyzing your skills...</p></div>';
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                skills: skillsText.split(',').map(s => s.trim()),
                education: education,
                interests: interestsText ? interestsText.split(',').map(i => i.trim()) : [],
                user_id: user_id
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.recommendations) {
            let rec = data.recommendations[0];
            let html = `<div class="result-card" style="text-align: center; background: rgba(0,180,216,0.1); border: 1px solid #00b4d8;">
                            <h3 style="color: #00b4d8; margin-bottom: 0.5rem;">🎉 Your New Match!</h3>
                            <h2 style="color: white; font-size: 1.3rem;">${rec.career}</h2>
                            <div style="font-size: 1rem; margin: 0.5rem 0;">
                                
                            </div>
                            <p style="color: white;"><i class="fas fa-arrow-up"></i> Next Level: ${rec.next_level}</p>
                            <p style="color: white;"><i class="fas fa-dollar-sign"></i> Salary: ${rec.salary_range}</p>
                            <div style="margin-top: 0.5rem;">
                                <span style="font-size:0.7rem;">Saving your recommendation...</span>
                            </div>
                        </div>`;
            resultsDiv.innerHTML = html;
            setTimeout(() => { location.reload(); }, 1500);
        } else {
            resultsDiv.innerHTML = `<p style="color:#ff6b6b;">Error: ${data.error || 'Something went wrong'}</p>`;
        }
    } catch (error) {
        resultsDiv.innerHTML = `<p style="color:#ff6b6b;">Network error. Please try again.</p>`;
    }
}

document.getElementById('skills').addEventListener('input', updateSkillChart);
document.getElementById('skills').value = '<?php echo htmlspecialchars($user['skills'] ?? ''); ?>';
document.getElementById('interests').value = '<?php echo htmlspecialchars($user['interests'] ?? ''); ?>';
updateSkillChart();

<?php if (isset($_GET['success'])): ?>
    const msgDiv = document.getElementById('successMsg');
    msgDiv.textContent = '✅ History deleted successfully!';
    msgDiv.style.display = 'block';
    setTimeout(() => msgDiv.style.display = 'none', 3000);
<?php endif; ?>
</script>

</body>
</html>