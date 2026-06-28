<?php
// config.php - Habaynta Guud

// Database settings (KADDIB ayaa ku dari karaa)
define('DB_HOST', 'localhost');
define('DB_NAME', 'career_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Model API settings (URL-ka model-ka aad soo dejisay)
// Haddii aad isticmaalayso Colab + ngrok
define('MODEL_API_URL', 'https://your-model-url.ngrok-free.app/predict');

// Haddii aad isticmaalayso Hugging Face
// define('MODEL_API_URL', 'https://api-inference.huggingface.co/models/your-model');

// Haddii aad isticmaalayso Local (Ollama)
// define('MODEL_API_URL', 'http://localhost:11434/api/generate');

// Career paths database (Xirfadaha iyo shaqooyinka)
$career_paths = [
    'Software Developer' => [
        'required_skills' => ['Python', 'JavaScript', 'Git', 'SQL', 'Problem Solving'],
        'next_level' => 'Senior Developer',
        'avg_salary' => '$70,000 - $100,000',
        'growth_rate' => '22% (much faster than average)'
    ],
    'Senior Developer' => [
        'required_skills' => ['System Design', 'Leadership', 'Code Review', 'Mentoring', 'Cloud'],
        'next_level' => 'Tech Lead',
        'avg_salary' => '$100,000 - $140,000',
        'growth_rate' => '18%'
    ],
    'Data Scientist' => [
        'required_skills' => ['Python', 'Machine Learning', 'Statistics', 'SQL', 'Data Visualization'],
        'next_level' => 'Senior Data Scientist',
        'avg_salary' => '$90,000 - $130,000',
        'growth_rate' => '35%'
    ],
    'Project Manager' => [
        'required_skills' => ['Communication', 'Agile', 'Risk Management', 'Budgeting', 'Leadership'],
        'next_level' => 'Program Manager',
        'avg_salary' => '$80,000 - $120,000',
        'growth_rate' => '12%'
    ],
    'DevOps Engineer' => [
        'required_skills' => ['Docker', 'Kubernetes', 'CI/CD', 'AWS', 'Linux'],
        'next_level' => 'Site Reliability Engineer',
        'avg_salary' => '$95,000 - $135,000',
        'growth_rate' => '24%'
    ],
    'UX Designer' => [
        'required_skills' => ['Figma', 'User Research', 'Prototyping', 'Wireframing', 'HTML/CSS'],
        'next_level' => 'Senior UX Designer',
        'avg_salary' => '$75,000 - $110,000',
        'growth_rate' => '13%'
    ],
    'Product Manager' => [
        'required_skills' => ['Product Strategy', 'Market Research', 'Analytics', 'Communication', 'Roadmap'],
        'next_level' => 'Director of Product',
        'avg_salary' => '$100,000 - $150,000',
        'growth_rate' => '15%'
    ]
];

// Xirfadaha la xiriira shaqooyinka (for similarity matching)
$skill_to_careers = [
    'Python' => ['Software Developer', 'Data Scientist', 'DevOps Engineer'],
    'JavaScript' => ['Software Developer', 'UX Designer'],
    'SQL' => ['Software Developer', 'Data Scientist'],
    'Machine Learning' => ['Data Scientist'],
    'Docker' => ['DevOps Engineer'],
    'AWS' => ['DevOps Engineer', 'Senior Developer'],
    'Communication' => ['Project Manager', 'Product Manager'],
    'Leadership' => ['Senior Developer', 'Project Manager', 'Product Manager'],
    'Figma' => ['UX Designer'],
    'Agile' => ['Project Manager', 'Product Manager']
];
?>