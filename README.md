# 🧠 Slimenify - AI-Powered Psychology & Wellness Hub

**Slimenify** is a comprehensive mental health ecosystem built with **Symfony 6.4**, designed to bridge the gap between traditional therapy and modern AI-driven support. It provides a seamless experience for patients, therapists, and administrators.

## 🚀 Key Features

- **🤖 AI Therapist Companion**: Real-time therapeutic conversations powered by advanced AI models (Mistral/OpenAI).
- **📅 Appointment Management**: Full-featured scheduling system for patients to book and manage sessions with professional therapists.
- **📊 Mental Health Assessments**: Interactive psychometric quizzes and assessments with AI-generated feedback.
- **🛒 Wellness Marketplace**: Integrated e-commerce platform for health products, featuring Stripe payments and order tracking.
- **📱 Responsive Patient Dashboard**: A centralized hub for tracking appointments, quiz results, and AI interactions.
- **👨‍⚕️ Therapist Workspace**: Dedicated tools for therapists to manage availability, patient notes, and session history.
- **📝 Community & Content**: Built-in blog system with likes, comments, and audio features to foster engagement.

## 🛠️ Tech Stack

- **Backend**: PHP 8.2+ | Symfony 6.4
- **Database**: Doctrine ORM (MySQL/PostgreSQL)
- **Frontend**: Twig, Stimulus, Turbo (Hotwire), Vanilla CSS
- **AI Integration**: Custom AI Analysis Services (OpenAI/Mistral API)
- **Integrations**: 
  - **Payments**: Stripe API
  - **Media**: Cloudinary
  - **Auth**: Google OAuth2 & Symfony Security
  - **Messaging**: Twilio SMS & SendGrid Email

## 🛠️ Installation & Setup



```bash
git clone https://github.com/votre-utilisateur/slimenify.git
cd slimenify

composer install

cp .env .env.local

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
