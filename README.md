
<p align="center">
  <img src="{{ asset('images/malaria-detection.jpg') }}" alt="Malaria Detection AI" width="200">
</p>

# Malaria Detection AI - Gemini Flash

## 🚀 Quick Demo
[Watch a 3-Minute Video] (https://youtu.be/w43x7AQngiw) showcasing Malaria Detection AI in action.

## 💡 Problem Statement
Malaria remains a significant health issue in regions like Kenya, where access to timely and accurate diagnostics is challenging, particularly in remote areas. The shortage of specialized medical professionals further complicates the situation, often delaying diagnosis and treatment, which can lead to severe health consequences.

**Solution**: Malaria Detection AI is a web-based solution that leverages AI to analyze microscopic images of blood smears, detecting malaria parasites quickly and accurately. This tool empowers rural clinics with efficient diagnostic capabilities, even with limited resources.

## 🛠 Technical Implementation

### Technical Flow
The technical flow of Malaria Detection AI is divided into three core stages: **Image Processing Pipeline**, **Data Management with BigQuery**, and **Integration with Gemini Flash AI**.

#### 1. **Image Processing Pipeline**
   - **Pre-Processing**: Blood smear images undergo noise reduction and enhancement to improve clarity. Segmentation isolates regions of interest for more focused analysis.
   - **Feature Extraction**: The system extracts critical features related to malaria, helping the AI model to accurately assess each image.

#### 2. **Data Management with BigQuery**
   - **Data Ingestion**: Processed results and metadata are stored in Google BigQuery, with batch processing capabilities to manage large data sets.
   - **Real-Time Data Retrieval**: BigQuery provides robust querying functionality for generating reports and extracting insights from analysis data.
   <!-- - **Data Aggregation**: Aggregates diagnostic data to track trends, supporting public health efforts to monitor and respond to malaria cases. -->

#### 3. **Gemini Flash AI Integration**
   - **Detection and Analysis**: Gemini Flash AI is trained on malaria-specific patterns, allowing for high accuracy even in challenging imaging conditions.
   - **Rapid Processing**: Optimized to provide results in near real-time, crucial for timely healthcare responses.
   - **Scalability**: Handles increased data flow efficiently, ensuring consistent performance.

Together, these components form the backbone of a robust, scalable malaria detection system, using cutting-edge AI for impactful healthcare applications.

### Key Technologies
- **Framework**: Laravel 11 with Laravel Breeze for authentication
- **AI Model**: Gemini Flash AI for malaria parasite detection in blood smear images
- **Data Migration**: Integrated with MySQL and Google BigQuery for scalable data storage
<!-- - **Local and Remote Access**: Exposed locally via ngrok for easy testing and external access -->

## 🎯 Project Impact
- **Increased Diagnostic Access**: Extends diagnostic capabilities to remote, underserved areas.
- **Reduced Diagnosis Time**: Accelerates diagnosis, supporting prompt treatment.
- **Affordable Solution**: Minimizes the need for expensive diagnostic equipment, making it more accessible to low-resource settings.


<!-- ## 🏗 Architecture -->

## 📱 Features Overview
- **AI-based Analysis**: Accurate detection and diagnosis powered by Gemini Flash AI
- **BigQuery Integration**: Scalable, secure storage for analysis and reporting
- **MySQL Intergration**: Backup data storage
- **User-Friendly Interface**: Intuitive design focused on ease of use in clinical settings
<!-- - **Real-Time Notifications**: SMS alerts for diagnosis updates -->


<!-- ## 🔬 Technical Deep-Dive

This section provides an overview of the technical flow of the *MalariaDetect AI* system, detailing the image processing pipeline, BigQuery data management, and the integration of **Gemini Flash AI**.

### 1. **Image Processing Pipeline**
The image processing pipeline focuses on enhancing medical images, typically blood sample slides, to detect malaria parasites accurately. This pipeline includes:
- **Pre-processing**: Raw images undergo noise reduction and enhancement to improve clarity, followed by segmentation to isolate areas of interest.
- **Feature Extraction**: Key features are extracted to highlight potential malaria indicators for further analysis.

These processed images are then analyzed using a machine learning model for parasite detection. -->

<!-- ### 2. **BigQuery Data Management**
BigQuery serves as the backbone for managing and storing the analysis data:
- **Data Ingestion**: Processed results and image metadata are efficiently stored in BigQuery, leveraging batch processing for large-scale data handling.
- **Real-time Data Retrieval**: BigQuery's querying capabilities are used to retrieve insights and generate reports based on the analysis.
- **Data Aggregation**: Aggregated data helps identify trends and support healthcare decisions, especially in tracking malaria outbreaks. -->

<!-- ### 3. **Gemini Flash AI Integration**
Gemini Flash AI, integrated into the platform, plays a pivotal role in analyzing the images for malaria detection:
- **Accurate Detection**: The system is powered by an AI model fine-tuned to recognize malaria parasites, offering high accuracy even under challenging conditions.
- **Fast Analysis**: Thanks to Gemini's optimized architecture, the model provides near-instantaneous results, which is crucial for timely healthcare interventions.
- **Scalability**: As data increases, Gemini Flash AI ensures that performance remains consistent and responsive.

Together, these components form the backbone of a robust, scalable malaria detection system, using cutting-edge technology for impactful healthcare applications. -->



## 📊 Results & Impact
### Results
- **Performance**: The system achieves high accuracy in identifying malaria parasites, using Gemini Flash AI’s model tuned to recognize malaria patterns even in lower-quality images.
- **Efficiency**: With Gemini Flash’s optimizations, the platform delivers analysis results quickly, enabling faster medical response times.
- **Data Management**: Through BigQuery and MySQL as backup, analysis data is stored, retrieved, and queried efficiently, providing valuable insights that can guide healthcare decisions and public health monitoring.

### Projected Impact
The Malaria Detection AI project has the potential to improve healthcare outcomes in regions with limited diagnostic resources. By offering an accessible diagnostic tool, it addresses key challenges in malaria detection, such as the scarcity of trained personnel and the need for quick, reliable diagnosis. This can contribute to reducing malaria incidence rates, supporting timely treatment, and enabling proactive public health strategies.

The platform has ability to process large datasets with BigQuery enables valuable trend analysis. This data can inform regional healthcare efforts, supporting broader malaria eradication programs.

## 🚀 Installation & Setup

To set up and run the *Malaria Detection AI* platform locally, follow these steps:

### Prerequisites
- **PHP** (v8+)
- **Composer** for Laravel dependencies
- **MySQL** (or other supported DB)
- **Google Cloud SDK** for BigQuery integration

### Steps
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-username/malaria-detection-ai.git
   cd malaria-detection-ai
   ```
2. **Install Dependencies**: Install both PHP and JavaScript dependencies.
  ```bash
  composer install
  npm install && npm run dev
  ```
3. **Configure Environment**: Copy ``.env.example`` to ``.env`` and configure the following:
  ```bash
  GOOGLE_CLOUD_PROJECT_ID=your_project_id
  GOOGLE_APPLICATION_CREDENTIALS=path_to_your_credentials.json
  USE_BIGQUERY=true
  ```
4. **Run Migrations**: 
  ```bash
  php artisan migrate
  ```
5. **Serve the Application**:
  ```bash
  php artisan serve
  ```

## 📈 Usage Instructions

1. **Upload Blood Smear Images**: Log in and navigate to the dashboard to upload images for AI analysis.
2. **Run AI Analysis**: Select the uploaded images to initiate the analysis using Gemini Flash AI, which processes the images to detect potential malaria parasites.
3. **View Diagnostic Results**: Access detailed diagnostic reports and data insights directly within the application, providing fast and actionable information.

## 🏆 Accomplishments
- Developed an operational AI-powered malaria detection platform tailored for real-world use.
- Successfully integrated Google BigQuery for scalable data handling and analysis.
- Designed an intuitive and user-friendly interface, making it accessible to healthcare professionals in clinical environments.

## 🧠 Lessons Learned
- Gained in-depth knowledge of deploying AI models in healthcare applications and managing datasets with Google BigQuery.
- Strengthened skills in integrating Laravel with external AI services and developing scalable, cloud-based systems.

## 🔮 What's Next for Malaria Detect
- **Enhanced Model Training**: Plan to further refine the AI model for improved accuracy in diverse image conditions.
- **Notification System**: Implement real-time notification features, such as SMS or email alerts, for rapid diagnosis updates.
- **Mobile Compatibility**: Explore development of a mobile-friendly version to extend accessibility and usability.

## 🤝 Acknowledgments
Acknowledgments also to Google Cloud for providing resources and tools that made this project possible.

## 📜 License
This project is open-source and licensed under the [MIT License](https://opensource.org/licenses/MIT).
