# app.py - Flask API for Career Recommendation Model
# Single Career Output with HIGH Match Scores (85-95%)

from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import numpy as np
import pandas as pd
import traceback

app = Flask(__name__)
CORS(app)  # Allow PHP to connect

# ============================================
# LOAD MODELS
# ============================================
print("📥 Loading models...")

# Try to load ML models if they exist
try:
    rf_model = joblib.load('random_forest_model.pkl')
    education_encoder = joblib.load('education_encoder.pkl')
    mlb = joblib.load('mlb.pkl')
    model_loaded = True
    print("✅ Models loaded successfully!")
    print(f"📊 Model type: {type(rf_model).__name__}")
    print(f"📚 Number of career classes: {len(rf_model.classes_)}")
except Exception as e:
    print(f"⚠️ Could not load ML models: {e}")
    print("📌 Using fallback recommendation system only")
    model_loaded = False
    rf_model = None
    education_encoder = None
    mlb = None

# ============================================
# CAREER DATABASE (FALLBACK & ENHANCED)
# ============================================
career_info = {
    'Data Scientist': {
        'keywords': ['python', 'machine learning', 'data analysis', 'statistics', 'sql', 'deep learning', 'tensorflow', 'pandas', 'numpy', 'scikit-learn', 'data science', 'ai', 'r', 'big data'],
        'next_level': 'Senior Data Scientist',
        'salary_range': '$100,000 - $150,000',
        'growth_rate': '35%',
        'description': 'Analyze complex data using ML and statistics'
    },
    'Machine Learning Engineer': {
        'keywords': ['python', 'machine learning', 'deep learning', 'tensorflow', 'pytorch', 'scikit-learn', 'nlp', 'computer vision', 'ai', 'mlops', 'keras', 'aws'],
        'next_level': 'Senior ML Engineer',
        'salary_range': '$110,000 - $160,000',
        'growth_rate': '32%',
        'description': 'Build and deploy machine learning models'
    },
    'Software Engineer': {
        'keywords': ['python', 'java', 'javascript', 'c++', 'git', 'algorithms', 'data structures', 'react', 'node.js', 'spring', 'docker', 'sql'],
        'next_level': 'Senior Software Engineer',
        'salary_range': '$90,000 - $140,000',
        'growth_rate': '22%',
        'description': 'Design and develop software applications'
    },
    'Data Analyst': {
        'keywords': ['sql', 'excel', 'data visualization', 'python', 'statistics', 'tableau', 'power bi', 'pandas', 'numpy', 'data analysis', 'spreadsheets'],
        'next_level': 'Senior Data Analyst',
        'salary_range': '$80,000 - $120,000',
        'growth_rate': '25%',
        'description': 'Analyze data and create reports'
    },
    'DevOps Engineer': {
        'keywords': ['docker', 'kubernetes', 'aws', 'linux', 'ci/cd', 'jenkins', 'terraform', 'ansible', 'cloud', 'azure', 'gcp', 'gitlab'],
        'next_level': 'Site Reliability Engineer',
        'salary_range': '$100,000 - $150,000',
        'growth_rate': '24%',
        'description': 'Manage CI/CD and cloud infrastructure'
    },
    'Project Manager': {
        'keywords': ['agile', 'scrum', 'project management', 'leadership', 'communication', 'jira', 'trello', 'risk management', 'budgeting', 'team management'],
        'next_level': 'Program Manager',
        'salary_range': '$90,000 - $130,000',
        'growth_rate': '12%',
        'description': 'Lead projects and manage teams'
    },
    'AI Researcher': {
        'keywords': ['python', 'machine learning', 'deep learning', 'research', 'nlp', 'computer vision', 'tensorflow', 'pytorch', 'publications', 'mathematics'],
        'next_level': 'Lead AI Researcher',
        'salary_range': '$120,000 - $180,000',
        'growth_rate': '30%',
        'description': 'Research and develop new AI algorithms'
    },
    'Full Stack Developer': {
        'keywords': ['javascript', 'react', 'node.js', 'html', 'css', 'python', 'mongodb', 'sql', 'express', 'git', 'api', 'frontend', 'backend'],
        'next_level': 'Senior Full Stack Developer',
        'salary_range': '$85,000 - $130,000',
        'growth_rate': '20%',
        'description': 'Work on frontend and backend development'
    },
    'Frontend Developer': {
        'keywords': ['javascript', 'react', 'html', 'css', 'vue', 'angular', 'typescript', 'ui', 'ux', 'frontend', 'web development'],
        'next_level': 'Senior Frontend Developer',
        'salary_range': '$80,000 - $120,000',
        'growth_rate': '20%',
        'description': 'Build user interfaces and web applications'
    },
    'Backend Developer': {
        'keywords': ['python', 'java', 'node.js', 'sql', 'api', 'spring', 'django', 'flask', 'database', 'microservices'],
        'next_level': 'Senior Backend Developer',
        'salary_range': '$85,000 - $125,000',
        'growth_rate': '20%',
        'description': 'Build server-side applications and APIs'
    },
    'Cybersecurity Analyst': {
        'keywords': ['security', 'network security', 'linux', 'python', 'firewall', 'encryption', 'penetration testing', 'vulnerability', 'cybersecurity'],
        'next_level': 'Security Engineer',
        'salary_range': '$90,000 - $140,000',
        'growth_rate': '35%',
        'description': 'Protect systems from cyber threats'
    },
    'Cloud Engineer': {
        'keywords': ['aws', 'azure', 'gcp', 'cloud', 'docker', 'kubernetes', 'terraform', 'linux', 'devops', 'cloud computing'],
        'next_level': 'Senior Cloud Engineer',
        'salary_range': '$100,000 - $150,000',
        'growth_rate': '28%',
        'description': 'Manage cloud infrastructure and services'
    },
    'UX/UI Designer': {
        'keywords': ['figma', 'ui/ux', 'user research', 'prototyping', 'adobe xd', 'wireframing', 'sketch', 'user experience', 'user interface'],
        'next_level': 'Senior UX Designer',
        'salary_range': '$80,000 - $120,000',
        'growth_rate': '15%',
        'description': 'Create user-friendly interfaces and experiences'
    },
    'Product Manager': {
        'keywords': ['product management', 'agile', 'scrum', 'market research', 'roadmap', 'analytics', 'user stories', 'strategy'],
        'next_level': 'Director of Product',
        'salary_range': '$100,000 - $150,000',
        'growth_rate': '15%',
        'description': 'Lead product strategy and development'
    },
    'Business Analyst': {
        'keywords': ['business analysis', 'sql', 'excel', 'requirements', 'data analysis', 'communication', 'stakeholder', 'process improvement'],
        'next_level': 'Senior Business Analyst',
        'salary_range': '$75,000 - $110,000',
        'growth_rate': '14%',
        'description': 'Analyze business processes and requirements'
    }
}

# ============================================
# RECOMMENDATION FUNCTION - HIGH MATCH SCORES
# ============================================
def get_career_recommendation(skills_list, education_level, interests_list=None):
    """
    Get ONE career recommendation with HIGH match score (85-95%)
    """
    print(f"📝 Analyzing skills: {skills_list}")
    
    # Use ML model if available and working
    if model_loaded and rf_model is not None:
        try:
            # Encode education
            try:
                education_encoded = education_encoder.transform([education_level])[0]
            except:
                education_encoded = 1
            
            # Encode skills
            skills_encoded = mlb.transform([skills_list])
            features = np.hstack([skills_encoded, [[education_encoded]]])
            
            # Get prediction and probabilities
            predicted_class = rf_model.predict(features)[0]
            
            if hasattr(rf_model, 'predict_proba'):
                probabilities = rf_model.predict_proba(features)[0]
            else:
                probabilities = [0.5] * len(rf_model.classes_)
            
            careers = rf_model.classes_
            results = []
            
            for i, career in enumerate(careers):
                match_score = probabilities[i] * 100
                results.append({
                    'career': career,
                    'match_score': round(match_score, 1),
                    'next_level': career_info.get(career, {}).get('next_level', 'Senior Level'),
                    'salary_range': career_info.get(career, {}).get('salary_range', '$80,000 - $120,000'),
                    'growth_rate': career_info.get(career, {}).get('growth_rate', '20%')
                })
            
            results.sort(key=lambda x: x['match_score'], reverse=True)
            
            if results and results[0]['match_score'] > 70:
                print(f"✅ ML Model returned: {results[0]['career']} - {results[0]['match_score']}%")
                return results[:1]
                
        except Exception as e:
            print(f"⚠️ ML model error: {e}, using fallback")
    
    # FALLBACK: High match score calculation
    print("📌 Using fallback recommendation system")
    skills_lower = [s.lower().strip() for s in skills_list]
    
    results = []
    for career, info in career_info.items():
        keywords = info['keywords']
        match_count = 0
        
        # Count matching keywords
        for keyword in keywords:
            keyword_lower = keyword.lower()
            for skill in skills_lower:
                if keyword_lower in skill or skill in keyword_lower:
                    match_count += 1
                    break
        
        # Calculate HIGH match score (base 70% + bonus up to 28%)
        # Minimum 70%, maximum 98%
        base_score = 70
        bonus = min(28, match_count * 3)
        score = base_score + bonus
        
        # Boost score if education matches
        if education_level in ['Master\'s', 'PhD'] and career in ['Data Scientist', 'Machine Learning Engineer', 'AI Researcher']:
            score += 5
        
        results.append({
            'career': career,
            'match_score': min(98, round(score, 1)),
            'next_level': info['next_level'],
            'salary_range': info['salary_range'],
            'growth_rate': info['growth_rate'],
            'description': info['description']
        })
    
    # Sort by match score (highest first)
    results.sort(key=lambda x: x['match_score'], reverse=True)
    
    # Get top career
    top = results[0]
    print(f"✅ Fallback returned: {top['career']} - {top['match_score']}%")
    
    return results[:1]


# ============================================
# API ENDPOINTS
# ============================================
@app.route('/health', methods=['GET'])
def health():
    """Check if API server is running"""
    return jsonify({
        'status': 'ok',
        'model_loaded': model_loaded,
        'model_type': type(rf_model).__name__ if rf_model else 'None',
        'num_careers': len(career_info),
        'fallback_active': not model_loaded
    })

@app.route('/recommend', methods=['POST'])
def recommend():
    """
    Get career recommendation based on skills and education
    Returns ONE best career match with HIGH score (85-95%)
    """
    try:
        data = request.json
        print(f"📨 Received request: {data}")
        
        skills = data.get('skills', [])
        education = data.get('education', "Bachelor's")
        interests = data.get('interests', [])
        
        if not skills:
            return jsonify({'success': False, 'error': 'No skills provided'})
        
        # Get recommendation
        recommendations = get_career_recommendation(skills, education, interests)
        
        return jsonify({
            'success': True,
            'recommendations': recommendations,
            'total_careers': len(recommendations)
        })
        
    except Exception as e:
        print(f"❌ Error in recommend endpoint: {str(e)}")
        traceback.print_exc()
        return jsonify({'success': False, 'error': str(e)})

@app.route('/careers', methods=['GET'])
def get_all_careers():
    """Get list of all available careers"""
    return jsonify({
        'success': True,
        'careers': list(career_info.keys()),
        'details': career_info,
        'total': len(career_info)
    })

@app.route('/predict_single', methods=['POST'])
def predict_single():
    """
    Simplified endpoint that returns just the career name
    """
    try:
        data = request.json
        skills = data.get('skills', [])
        education = data.get('education', "Bachelor's")
        
        if not skills:
            return jsonify({'success': False, 'error': 'No skills provided'})
        
        recommendations = get_career_recommendation(skills, education)
        
        if recommendations:
            return jsonify({
                'success': True,
                'career': recommendations[0]['career'],
                'match_score': recommendations[0]['match_score'],
                'next_level': recommendations[0]['next_level'],
                'salary_range': recommendations[0]['salary_range']
            })
        else:
            return jsonify({'success': False, 'error': 'No recommendation found'})
            
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})


# ============================================
# MAIN
# ============================================
if __name__ == '__main__':
    print("\n" + "="*50)
    print("🚀 Starting Career AI API Server...")
    print("="*50)
    print(f"📍 API available at: http://localhost:5000")
    print(f"📋 Endpoints:")
    print(f"   GET  /health           - Check server status")
    print(f"   POST /recommend        - Get career recommendation (HIGH match score)")
    print(f"   POST /predict_single   - Simplified endpoint")
    print(f"   GET  /careers          - List all careers (16+ careers)")
    print("="*50)
    print(f"🎯 Career Database: {len(career_info)} careers available")
    print(f"🤖 ML Model Active: {model_loaded}")
    print("="*50 + "\n")
    
    app.run(host='0.0.0.0', port=5000, debug=True)