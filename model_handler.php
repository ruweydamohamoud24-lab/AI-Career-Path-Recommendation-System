<?php
// model_handler.php - Integration with external AI models

require_once 'config.php';

class AIModelHandler {
    private $api_url;
    private $model_type;
    
    public function __construct() {
        $this->api_url = defined('MODEL_API_URL') ? MODEL_API_URL : '';
        $this->model_type = 'local';
    }
    
    /**
     * Send request to AI model
     */
    public function predict($data) {
        // Try external API first
        if (!empty($this->api_url)) {
            $result = $this->callExternalAPI($data);
            if ($result) {
                return $result;
            }
        }
        
        // Fallback to local logic
        return $this->localPredict($data);
    }
    
    /**
     * Call external API (Google Colab, Hugging Face, Ollama)
     */
    private function callExternalAPI($data) {
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Local prediction logic (fallback)
     */
    private function localPredict($data) {
        $skills = $data['skills'] ?? [];
        $current_role = $data['current_role'] ?? '';
        $experience = $data['experience'] ?? 0;
        
        global $career_paths, $skill_to_careers;
        
        // Simple matching algorithm
        $scores = [];
        foreach ($career_paths as $career => $info) {
            $required = $info['required_skills'];
            $matched = 0;
            foreach ($skills as $skill) {
                if (in_array($skill, $required)) {
                    $matched++;
                }
            }
            $score = count($required) > 0 ? ($matched / count($required)) * 100 : 0;
            $scores[$career] = $score;
        }
        
        arsort($scores);
        $top_careers = array_slice($scores, 0, 3, true);
        
        $recommendations = [];
        foreach ($top_careers as $career => $score) {
            $recommendations[] = [
                'career' => $career,
                'confidence' => round($score, 2),
                'required_skills' => $career_paths[$career]['required_skills']
            ];
        }
        
        return [
            'success' => true,
            'predictions' => $recommendations,
            'model_used' => 'local_fallback'
        ];
    }
}

// Create global instance
$modelHandler = new AIModelHandler();
?>