</div> <!-- Close main-content container -->
    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5><i class="fas fa-bullhorn me-2"></i>OLSHCO Announcements</h5>
                    <p class="small">Stay updated with the latest campus news, events, and announcements from Our Lady of the Sacred Heart College of Guimba, Inc.</p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/">Home</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="/login">Login</a></li>
                            <li><a href="/register">Register</a></li>
                        <?php else: ?>
                            <li><a href="/dashboard">Dashboard</a></li>
                            <li><a href="/profile">My Profile</a></li>
                            <li><a href="/logout">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Guimba, Nueva Ecija</li>
                        <li><i class="fas fa-envelope me-2"></i> info@olshco.edu.ph</li>
                        <li><i class="fas fa-phone me-2"></i> (044) 123-4567</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-3 mb-3" style="background-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <small>&copy; <?= date('Y') ?> OLSHCO Student Announcement System. All rights reserved.</small>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/script.js"></script>
</body>
</html>