<?php
// profile.php - Edit Profile Page

require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];

// Handle Edit Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'] ?? '';
    $new_user_role = $_POST['user_role'] ?? '';
    $new_education_level = $_POST['education_level'] ?? '';
    $new_skills = $_POST['skills'] ?? '';
    
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, password = ?, user_role = ?, education_level = ?, skills = ? WHERE id = ?");
        $stmt->execute([$new_fullname, $new_email, $hashed_password, $new_user_role, $new_education_level, $new_skills, $user_id]);
        $_SESSION['fullname'] = $new_fullname;
        $_SESSION['email'] = $new_email;
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, user_role = ?, education_level = ?, skills = ? WHERE id = ?");
        $stmt->execute([$new_fullname, $new_email, $new_user_role, $new_education_level, $new_skills, $user_id]);
        $_SESSION['fullname'] = $new_fullname;
        $_SESSION['email'] = $new_email;
    }
    header('Location: profile.php?success=updated');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CareerAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            padding: 1rem 2rem;
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
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00b4d8, #90e0ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .logo i { color: #00b4d8; margin-right: 10px; }
        .nav-links { display: flex; gap: 2rem; }
        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: #00b4d8; }
        .nav-links a.active { color: #00b4d8; }
        .user-info { display: flex; align-items: center; gap: 1.5rem; }
        .user-name { color: white; font-weight: 500; }
        .logout-btn {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
            padding: 0.5rem 1rem;
            border-radius: 40px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 100px 2rem 2rem;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i { color: #00b4d8; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #00b4d8;
        }
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .success-msg {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 0.8rem;
            margin-bottom: 1rem;
            color: #4caf50;
            display: none;
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #00b4d8;
            text-decoration: none;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div class="logo"><i class="fas fa-brain"></i> Career<span style="color:#00b4d8">AI</span></div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php" class="active">Profile</a>
        </div>
        <div class="user-info">
            <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($fullname); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="glass-card">
        <div class="card-title">
            <i class="fas fa-user-edit"></i> Edit Profile
        </div>
        
        <div id="successMsg" class="success-msg">
            <?php if (isset($_GET['success']) && $_GET['success'] == 'updated'): ?>
                ✅ Profile updated successfully!
            <?php endif; ?>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-briefcase"></i> Current Job Title</label>
                <input type="text" name="user_role" class="form-control" value="<?php echo htmlspecialchars($user['user_role'] ?? ''); ?>" placeholder="e.g., Software Developer">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-graduation-cap"></i> Education Level</label>
                <select name="education_level" class="form-control">
                    <option value="highschool" <?php echo ($user['education_level'] ?? '') == 'highschool' ? 'selected' : ''; ?>>High School</option>
                    <option value="bachelor" <?php echo ($user['education_level'] ?? '') == 'bachelor' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                    <option value="master" <?php echo ($user['education_level'] ?? '') == 'master' ? 'selected' : ''; ?>>Master's Degree</option>
                    <option value="phd" <?php echo ($user['education_level'] ?? '') == 'phd' ? 'selected' : ''; ?>>PhD</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-code"></i> Your Skills (comma separated)</label>
                <textarea name="skills" class="form-control" rows="3" placeholder="e.g., Python, JavaScript, SQL, Communication"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control" placeholder="Enter new password">
            </div>
            
            <button type="submit" name="edit_profile" class="btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
        
        <div class="back-link">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
    const successMsg = document.getElementById('successMsg');
    if (successMsg.textContent.trim()) {
        successMsg.style.display = 'block';
        setTimeout(() => {
            successMsg.style.display = 'none';
        }, 3000);
    }

</script>
<div class="form-group">
    <label><i class="fas fa-heart"></i> Your Interests (comma separated)</label>
    <textarea name="interests" class="form-control" rows="3" placeholder="e.g., Technology, Data Science, Web Development, AI"><?php echo htmlspecialchars($user['interests'] ?? ''); ?></textarea>
</div>

</body>
</html>