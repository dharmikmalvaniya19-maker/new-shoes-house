<?php
// footer.php
?>
<style>
/* Footer Styles */
.footer {
    background-color: #2c3e50;
    color: #ffffffff;
    padding: 60px 0;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
}

.footer-section h3 {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: #ffc107;
}

.footer-section.about p {
    font-size: 1rem;
    line-height: 1.6;
}

.footer-section.links ul {
    list-style: none;
}

.footer-section.links ul li {
    margin-bottom: 10px;
}

.footer-section.links ul li a {
    color: #fff;
    font-size: 1rem;
    transition: color 0.3s ease;
}

.footer-section.links ul li a:hover {
    color: #ffc107;
}

.footer-section.contact p {
    font-size: 1rem;
    margin-bottom: 10px;
}

.footer-section.contact p i {
    margin-right: 10px;
}

.footer-section.social a {
    font-size: 1.8rem;
    color: #fff;
    margin-right: 20px;
    transition: color 0.3s ease;
}

.footer-section.social a:hover {
    color: #ffc107;
}

.footer-bottom {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom p {
    font-size: 0.9rem;
}



/* Footer Responsive Styles (from Media Queries) */
@media (max-width: 1024px) {
    .footer-section h3 {
        font-size: 1.3rem;
    }

    .footer-section.social a {
        font-size: 1.6rem;
        margin-right: 15px;
    }
}

@media (max-width: 480px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
}
</style>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section about">
                 <h3>Shoes House</h3>
                    <p>Your ultimate destination for stepping out in style, comfort, and a whole lot of fun!</p>
                </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                    <ul>
                        <li><a href="profile.php">-> My Account</a></li>
                        <li><a href="men_collection.php">-> Men's Fun</a></li>
                        <li><a href="women_collection.php">-> Women's Sparkle</a></li>
                        <li><a href="kids_collection.php">-> Kids' Adventures</a></li>
                        <li><a href="aboutus.php">-> Know Us</a></li>
                        <li><a href="Contact Us.php">-> Get Help</a></li>
                    </ul>
            </div>
            <div class="footer-section contact">
                 <h3>Get in Touch!</h3>
                    <p><i class="fas fa-map-marker-alt"></i>123 Market Street, Surat, Gujarat 395007, India</p>
                    <p><i class="fas fa-envelope"></i> hello@shoehouse.com</p>
                    <p><i class="fas fa-phone"></i>+91 98765 43210</p>
                </div>
            <div class="footer-section social">
                 <h3>Follow the Fun!</h3>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
                <p>&copy; 2025 Shoes House. All rights reserved. Designed with Sole & Style!</p>
        </div>
    </div>
</footer>

