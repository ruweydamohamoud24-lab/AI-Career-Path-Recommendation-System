# AI-Career-Path-Recommendation-System
An AI-based career recommendation system using Random Forest, Decision Tree, and KNN to predict 32 distinct career roles based on student skills and interests.
# AI-Based Employee Career Path Recommendation System

An intelligent career counseling and pathway prediction system developed as a Semester 8 graduation thesis project. This system leverages Machine Learning classification algorithms to recommend the most suitable professional roles for students and job seekers based on their academic background, specialized skills, and personal interests.

---

##  Project Overview
Finding the right career path in the rapidly evolving tech and business landscapes can be challenging. This project addresses the issue by utilizing historical alignment data between specific skill sets and career success. The core model processes complex multi-categorical inputs and accurately classifies them into one of **32 distinct career paths**.

### Core Features:
* **Multi-Class Classification:** Scales beyond basic binary predictions to accurately identify 32 highly specialized tech, research, and business roles.
* **Feature Engineering:** Implements robust data preprocessing, text splitting, and One-Hot Encoding for skills and interests.
* **Class Imbalance Handling:** Uses optimized hyperparameter tuning (`class_weight='balanced'`) to ensure high predictive precision even for rare career roles.

---

##  Supported Career Paths (32 Roles)
The model is trained to recognize and evaluate attributes for the following professional categories:
1. AI Researcher / Specialist
2. Automation Engineer
3. Backend / Front-end / Full Stack Developer
4. Data Analyst / Data Engineer / Data Scientist
5. Deep Learning / Machine Learning Engineer
6. DevOps Engineer
7. Cybersecurity Analyst / Specialist
8. Cloud Engineer
9. Business Analyst / Project Manager
10. Mobile Developer
11. UX Designer / UX Researcher
12. NLP Engineer
13. Digital Marketer / Marketing Manager
14. Embedded Systems Engineer
15. Financial Analyst
16. Content Strategist
17. Biostatistician
18. Research Analyst / Research Scientist
19. Software Developer / Software Engineer
20. Graphic Designer

---

##  Machine Learning Pipeline & Performance

The framework evaluates and compares the performance of three fundamental classification techniques:
1.  **Random Forest Classifier (Optimized):** An ensemble tree-based algorithm configured with 500 estimators to overcome high-dimensional data sparsity and provide stable majority-voting outputs. *(Top Performer)*
2.  **Decision Tree Classifier:** A baseline rule-induction model prone to local variance but highly interpretable.
3.  **K-Nearest Neighbors (KNN):** A distance-based instance classifier optimized using distance weighting.

### Overall Accuracy Comparison
The bar chart below highlights the significant performance superiority of the tuned Random Forest model when mapped across all 32 classes:

![Overall Accuracy](fixed_accuracy_32classes.png)

### Model Diagnostics (Confusion Matrix)
A comprehensive multi-subplot Confusion Matrix was generated during the validation phase to visualize errors and structural overlaps (e.g., distinguishing between a general *Data Analyst* and a specialized *Financial Analyst*):

![Confusion Matrix](triple_algorithm_confusion_matrix.png)

---

##  Technology Stack
* **Language:** Python
* **Data Manipulation:** Pandas, NumPy
* **Machine Learning Framework:** Scikit-Learn
* **Data Visualization:** Matplotlib, Seaborn
* **Development Environment:** Google Colab / Jupyter Notebooks

---

##  How It Works
1.  **Categorical Input Parsing:** Student data (e.g., *Education: Bachelor's, Skills: SQL;Excel;Financial Modeling, Interests: Finance*) is tokenized and expanded into a numerical binary matrix.
2.  **Probability Weighting:** The trained Random Forest model passes the vector through its optimized decision paths.
3.  **Dynamic Career Prediction:** The system performs an inverse-label transformation to return the final predicted title to the user interface.
