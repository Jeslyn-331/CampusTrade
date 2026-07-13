</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <h3 class="footer-logo">Campus<span class="logo-accent">Trade</span></h3>
            <p>A secondhand marketplace built by students, for students. Buy and sell within your campus community.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse Listings</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="register.php">Create an Account</a></li>
            </ul>
        </div>
        <div>
            <h4>Categories</h4>
            <ul>
                <?php foreach (CATEGORIES as $cat): ?>
                    <li><a href="browse.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Follow Us</h4>
            <ul class="footer-social">
                <li><a href="https://www.facebook.com" target="_blank" rel="noopener">Facebook</a></li>
                <li><a href="https://www.instagram.com" target="_blank" rel="noopener">Instagram</a></li>
                <li><a href="https://www.twitter.com" target="_blank" rel="noopener">X</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> CampusTrade — UECS2094/UECS2194/EECS2194 Group Assignment. For educational use.</p>
    </div>
</footer>

<script src="js/main.js"></script>
</body>
</html>
