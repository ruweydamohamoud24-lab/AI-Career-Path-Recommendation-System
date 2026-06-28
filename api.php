<?php
// api.php - PHP Backend for Career Recommendations

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connect.php';

// ============================================
// PYTHON ML MODEL API URL
// ============================================
define('ML_API_URL', 'http://localhost:5000/recommend');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$skills = $input['skills'] ?? [];
$education = $input['education'] ?? 'Bachelor\'s';
$interests = $input['interests'] ?? [];
$user_id = $input['user_id'] ?? null;

if (empty($skills)) {
    echo json_encode(['success' => false, 'error' => 'No skills provided']);
    exit;
}

// ============================================
// CALL PYTHON ML MODEL
// ============================================
$ch = curl_init(ML_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'skills' => $skills,
    'education' => $education,
    'interests' => $interests
]));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && $response) {
    $data = json_decode($response, true);
    
    if ($data['success'] && !empty($data['recommendations'])) {
        
        // ============================================
        // SAVE TO HISTORY AND LATEST RECOMMENDATION
        // ============================================
        if ($user_id) {
            try {
                $top = $data['recommendations'][0];
                $career_name = $top['career'];
                $match_score = $top['match_score'];
                $salary_range = $top['salary_range'];
                $next_level = $top['next_level'];
                
                // 1. Save to history table
                $stmt = $pdo->prepare("INSERT INTO user_history (user_id, recommended_career, match_score) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $career_name, $match_score]);
                
                // 2. Update latest recommendation in users table
                $updateStmt = $pdo->prepare("UPDATE users SET 
                    latest_career = ?, 
                    latest_match_score = ?, 
                    latest_salary = ?, 
                    latest_next_level = ?,
                    latest_recommended_at = NOW() 
                    WHERE id = ?");
                $updateStmt->execute([$career_name, $match_score, $salary_range, $next_level, $user_id]);
                
                // 3. Update user skills
                $updateSkills = $pdo->prepare("UPDATE users SET skills = ? WHERE id = ?");
                $updateSkills->execute([implode(', ', $skills), $user_id]);
                
            } catch (PDOException $e) {
                error_log("Save error: " . $e->getMessage());
            }
        }
        
        echo json_encode($data);
        exit;
    }
}

// ============================================
// FALLBACK LOCAL RECOMMENDATION
// ============================================
$career_paths = [
    'Data Scientist' => [
        'required_skills' => ['Python', 'Machine Learning', 'Statistics', 'SQL', 'Data Analysis'],
        'next_level' => 'Senior Data Scientist',
        'avg_salary' => '$100,000 - $150,000'
    ],
    'Software Engineer' => [
        'required_skills' => ['Java', 'Python', 'JavaScript', 'Git', 'Algorithms'],
        'next_level' => 'Senior Software Engineer',
        'avg_salary' => '$90,000 - $140,000'
    ],
    'DevOps Engineer' => [
        'required_skills' => ['Docker', 'Kubernetes', 'AWS', 'Linux', 'CI/CD'],
        'next_level' => 'Site Reliability Engineer',
        'avg_salary' => '$100,000 - $150,000'
    ],
    'Project Manager' => [
        'required_skills' => ['Communication', 'Agile', 'Leadership', 'Planning', 'Jira'],
        'next_level' => 'Program Manager',
        'avg_salary' => '$90,000 - $130,000'
    ],
    'UX Designer' => [
        'required_skills' => ['Figma', 'User Research', 'Prototyping', 'Adobe XD', 'Wireframing'],
        'next_level' => 'Senior UX Designer',
        'avg_salary' => '$80,000 - $120,000'
    ],
    'Data Analyst' => [
        'required_skills' => ['SQL', 'Excel', 'Tableau', 'Python', 'Statistics'],
        'next_level' => 'Senior Data Analyst',
        'avg_salary' => '$75,000 - $110,000'
    ],
    'Frontend Developer' => [
        'required_skills' => ['HTML', 'CSS', 'JavaScript', 'React', 'Tailwind'],
        'next_level' => 'Senior Frontend Developer',
        'avg_salary' => '$75,000 - $115,000'
    ],
    'Machine Learning Engineer' => [
        'required_skills' => ['Python', 'TensorFlow', 'PyTorch', 'ML', 'Deep Learning'],
        'next_level' => 'Senior ML Engineer',
        'avg_salary' => '$110,000 - $160,000'
    ]
];

$results = [];
foreach ($career_paths as $career => $info) {
    $required = $info['required_skills'];
    $matched = 0;
    foreach ($skills as $skill) {
        if (in_array(trim($skill), $required)) {
            $matched++;
        }
    }
    $score = count($required) > 0 ? ($matched / count($required)) * 100 : 0;
    $results[] = [
        'career' => $career,
        'match_score' => round($score, 1),
        'next_level' => $info['next_level'],
        'salary_range' => $info['avg_salary']
    ];
}

usort($results, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

$fallback_data = [
    'success' => true,
    'recommendations' => array_slice($results, 0, 1),
    'model_used' => 'local_fallback'
];

// Save fallback results to database
if ($user_id && !empty($fallback_data['recommendations'])) {
    try {
        $top = $fallback_data['recommendations'][0];
        $updateStmt = $pdo->prepare("UPDATE users SET 
            latest_career = ?, 
            latest_match_score = ?, 
            latest_salary = ?, 
            latest_next_level = ?,
            latest_recommended_at = NOW() 
            WHERE id = ?");
        $updateStmt->execute([$top['career'], $top['match_score'], $top['salary_range'], $top['next_level'], $user_id]);
    } catch (PDOException $e) {
        error_log("Fallback save error: " . $e->getMessage());
    }
}

echo json_encode($fallback_data);
?>