<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
?>

<style>
    /* Logo styling for index page */
    .hero-logo-section {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .hero-logo {
        width: 250px;
        height: 250px;
        border-radius: 50%;
        object-fit: cover;
        background: white;
        padding: -10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        border: 3px solid #ffd700;
        transition: transform 0.3s ease;
    }
    
    .hero-logo:hover {
        transform: scale(1.05);
    }
    
    .hero-title-section {
        text-align: left;
    }
    
    .hero-about {
        position: relative;
        width: 100vw;
        margin-left: calc(-50vw + 50%);
        height: 400px;
        background: url('../assets/image/bg.png') no-repeat center center;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
        overflow: hidden;
    }
    
    /* Optional overlay (your SVG wave) */
    .hero-about::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'><path fill='rgba(255,255,255,0.05)' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L0,320Z'></path></svg>") no-repeat bottom;
        background-size: cover;
        opacity: 1;
        pointer-events: none;
    }
    
    .hero-about h1 {
        font-size: 2.5rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }
    
    .hero-about h2 {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }
    
    .hero-about p {
        font-size: 1.5rem;
        opacity: 1;
        position: relative;
        z-index: 1;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .about-section {
        padding: 3rem 0;
        background: white;
    }
    
    .about-card {
        text-align: center;
        padding: 2rem;
        border-radius: 15px;
        background: #f8f9fa;
        transition: all 0.3s ease;
        height: 100%;
        border: 1px solid #eee;
    }
    
    .about-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-color: #800000;
    }
    
    .about-icon {
        width: 80px;
        height: 80px;
        background: #800000;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: white;
        font-size: 2rem;
    }
    
    .about-card h3 {
        color: #800000;
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }
    
    /* Mission, Vision, Core Values Images Section */
    .mvc-section {
        padding: 3rem 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .mvc-image-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        height: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-align: center;
        border: 1px solid #eee;
    }
    
    .mvc-image-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(128,0,0,0.15);
        border-color: #800000;
    }
    
    .mvc-image-card img {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: contain;
        border-radius: 10px;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: opacity 0.3s ease;
    }
    
    .mvc-image-card img:hover {
        opacity: 0.9;
    }
    
    .mvc-image-card h3 {
        color: #800000;
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }
    
    /* Lightbox Modal */
    .lightbox-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        animation: fadeIn 0.3s ease;
    }
    
    .lightbox-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .lightbox-content {
        position: relative;
        max-width: 90%;
        max-height: 90%;
        animation: zoomIn 0.3s ease;
    }
    
    .lightbox-content img {
        width: 100%;
        height: auto;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 10px;
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }
    
    .lightbox-close {
        position: absolute;
        top: -40px;
        right: 0;
        color: white;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s ease;
        z-index: 10000;
    }
    
    .lightbox-close:hover {
        color: #ffd700;
    }
    
    .lightbox-caption {
        text-align: center;
        color: white;
        padding: 15px 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes zoomIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .courses-section {
        padding: 3rem 0;
        background: white;
    }
    
    .course-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        border: 1px solid #eee;
    }
    
    .course-card:hover {
        background: #800000;
        color: white;
        transform: translateX(5px);
    }
    
    .course-card:hover .course-dept {
        color: #ffd700;
    }
    
    .course-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .course-dept {
        color: #800000;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
    
    .org-card {
        text-align: center;
        padding: 1.5rem;
        border-radius: 12px;
        background: #f8f9fa;
        transition: all 0.3s ease;
        height: 100%;
        border: 1px solid #eee;
    }
    
    .org-card:hover {
        background: #800000;
        color: white;
        transform: translateY(-5px);
    }
    
    .org-icon {
        width: 70px;
        height: 70px;
        background: #800000;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: white;
        font-size: 1.8rem;
        transition: all 0.3s;
    }
    
    .org-card:hover .org-icon {
        background: white;
        color: #800000;
        transform: scale(1.1);
    }
    
    .org-card h4 {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    
    .section-title {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    
    .section-title h2 {
        color: #800000;
        font-size: 2rem;
        font-weight: 700;
        display: inline-block;
        padding-bottom: 10px;
        border-bottom: 3px solid #ffd700;
    }
    
    .btn-view-announcements {
        background: #800000;
        color: white;
        padding: 12px 30px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-view-announcements:hover {
        background: #600000;
        color: white;
        transform: translateY(-2px);
    }
    
    .stats-section {
        background: #800000;
        color: white;
        padding: 3rem 0;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 1rem;
        opacity: 0.9;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .hero-logo {
            width: 70px;
            height: 70px;
        }
        
        .hero-title-section {
            text-align: center;
        }
        
        .hero-about h1 {
            font-size: 1.8rem;
        }
        
        .hero-about h2 {
            font-size: 1.3rem;
        }
        
        .mvc-image-card img {
            max-height: 350px;
        }
        
        .lightbox-close {
            top: -30px;
            right: 10px;
            font-size: 30px;
        }
    }
</style>

<!-- Hero Section with Logo -->
<div class="hero-about">
    <div class="container">
        <div class="hero-logo-section">
            <img src="/assets/image/olshco_logo.png" alt="OLSHCO Logo" class="hero-logo">
            <div class="hero-title-section">
                <h1>OUR LADY OF THE SACRED HEART COLLEGE</h1>
                <h2>of Guimba, Inc.</h2>
            </div>
        </div>
        <p>Excellence, Service, and Faith since 1947</p>
        <div class="mt-4">
            <a href="/dashboard" class="btn-view-announcements">
                <i class="fas fa-bullhorn me-2"></i>View Announcements
            </a>
        </div>
    </div>
</div>

<!-- About Section -->
<div class="about-section">
    <div class="container">
        <div class="section-title">
            <h2>About OLSHCO</h2>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="about-card">
                    <div class="about-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <h3>History</h3>
                    <p>Founded in 1947, Our Lady of the Sacred Heart College of Guimba, Inc. has been a beacon of quality education in Nueva Ecija for over 75 years.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="about-card">
                    <div class="about-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Quality Education</h3>
                    <p>Committed to providing excellent education that molds students into competent professionals and responsible citizens.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
    <div class="about-card">
        <div class="about-icon">
            <i class="fas fa-hands-helping"></i>
        </div>
        <h3>Care for Students</h3>
        <p>Dedicated to nurturing every student's well-being, providing guidance, support, and a caring environment where young minds can flourish and reach their full potential.</p>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<!-- Vision, Mission, and Core Values Images Section -->
<div class="mvc-section">
    <div class="container">
        <div class="section-title">
            <h2>OLSHCO's IDENTITY</h2>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="mvc-image-card">
                    <img src="/assets/image/vision.png" alt="OLSHCO Vision" class="zoomable-image" data-caption="Vision">
                    <h3></h3>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="mvc-image-card">
                    <img src="/assets/image/mission.png" alt="OLSHCO Mission" class="zoomable-image" data-caption="Mission">
                    <h3></h3>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="mvc-image-card">
                    <img src="/assets/image/corevalues.png" alt="OLSHCO Core Values" class="zoomable-image" data-caption="Core Values">
                    <h3></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Courses Offered -->
<div class="courses-section">
    <div class="container">
        <div class="section-title">
            <h2>Courses Offered</h2>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="course-card">
                    <div class="course-title">Bachelor of Science in Information Technology</div>
                    <div class="course-dept">College of Computer Studies</div>
                    <p class="mb-0 small">Develop skills in programming, networking, database management, and system development.</p>
                </div>
                <div class="course-card">
                    <div class="course-title">Bachelor of Science in Hospitality Management</div>
                    <div class="course-dept">College of Hospitality</div>
                    <p class="mb-0 small">Focus on hotel operations, food service management, tourism, and customer service excellence.</p>
                </div>
                <div class="course-card">
                    <div class="course-title">Bachelor of Elementary Education</div>
                    <div class="course-dept">College of Education</div>
                    <p class="mb-0 small">Prepare future educators for elementary level teaching and child development.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="course-card">
                    <div class="course-title">Bachelor of Secondary Education</div>
                    <div class="course-dept">College of Education</div>
                    <p class="mb-0 small">Train secondary school teachers specializing in various academic disciplines.</p>
                </div>
                <div class="course-card">
                    <div class="course-title">Bachelor of Science in Business Administration</div>
                    <div class="course-dept">College of Business and Accountancy</div>
                    <p class="mb-0 small">Develop business leaders with expertise in management, marketing, and finance.</p>
                </div>
                <div class="course-card">
                    <div class="course-title">Bachelor of Science in Criminology</div>
                    <div class="course-dept">College of Criminal Justice Education</div>
                    <p class="mb-0 small">Study criminal behavior, law enforcement, and justice system administration.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Organizations -->
<div class="mvc-section">
    <div class="container">
        <div class="section-title">
            <h2>Student Organizations</h2>
        </div>
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="org-card">
                    <div class="org-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h4>JPCS</h4>
                    <p class="small">Information Technology Student Organization</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="org-card">
                    <div class="org-icon">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <h4>YMF</h4>
                    <p class="small">Education Students Organization</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="org-card">
                    <div class="org-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>PASOA</h4>
                    <p class="small">Business Administration Society</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="org-card">
                    <div class="org-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>CODE-TG</h4>
                    <p class="small">Criminal Justice Students Organization</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="org-card">
                    <div class="org-icon">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <h4>HMSO</h4>
                    <p class="small">Hospitality Management Students Organization</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="stats-section">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-number">75+</div>
                <div class="stat-label">Years of Excellence</div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-number">6+</div>
                <div class="stat-label">Degree Programs</div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-number">20+</div>
                <div class="stat-label">Student Organizations</div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-number">1000+</div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightboxModal" class="lightbox-modal">
    <div class="lightbox-content">
        <span class="lightbox-close">&times;</span>
        <img id="lightboxImage" src="" alt="">
        <div id="lightboxCaption" class="lightbox-caption"></div>
    </div>
</div>

<script>
    // Lightbox functionality
    document.addEventListener('DOMContentLoaded', function() {
        const lightboxModal = document.getElementById('lightboxModal');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxCaption = document.getElementById('lightboxCaption');
        const closeBtn = document.querySelector('.lightbox-close');
        const zoomableImages = document.querySelectorAll('.zoomable-image');
        
        // Open lightbox when image is clicked
        zoomableImages.forEach(image => {
            image.addEventListener('click', function() {
                lightboxModal.classList.add('active');
                lightboxImage.src = this.src;
                lightboxImage.alt = this.alt;
                lightboxCaption.textContent = this.getAttribute('data-caption');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
        });
        
        // Close lightbox when close button is clicked
        closeBtn.addEventListener('click', function() {
            closeLightbox();
        });
        
        // Close lightbox when clicking outside the image
        lightboxModal.addEventListener('click', function(e) {
            if (e.target === lightboxModal) {
                closeLightbox();
            }
        });
        
        // Close lightbox with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightboxModal.classList.contains('active')) {
                closeLightbox();
            }
        });
        
        function closeLightbox() {
            lightboxModal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>



