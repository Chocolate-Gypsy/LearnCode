
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-section about">
                <h3>About LearnCode</h3>
                <p>Our mission is to make programming education accessible, interactive and fun for everyone.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/courses/">All Courses</a></li>
                    <li><a href="/about.php">About Us</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> support@LearnCode.com</p>
                <p><i class="fas fa-phone"></i> +1 (123) 456-7890</p>
                <p><i class="fas fa-map-marker-alt"></i> 123 Coding Street, Dev City, DC 12345</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> LearnCode. All rights reserved.</p>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>