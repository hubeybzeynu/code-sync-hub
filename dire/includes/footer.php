<?php
// includes/footer.php — Unified footer for all layouts
$__isAdmin = isset($admin) && $admin;
$__isStaff = $__isAdmin;
?>
<?php if ($__isStaff): ?>
            </main>
        </div>
    </div>
<?php else: ?>
    </main>
    <?php
    $__showFullFooter = !isset($student) && !isset($parent);
    if ($__showFullFooter):
        try {
            $__schoolCount = $pdo->query("SELECT COUNT(*) c FROM schools WHERE status='active'")->fetch()['c'];
            $__studentCount = $pdo->query("SELECT COUNT(*) c FROM students WHERE reg_status='active'")->fetch()['c'];
        } catch (Exception $e) { $__schoolCount = 0; $__studentCount = 0; }
    ?>
    <footer class="site-footer-full">
        <div class="footer-inner">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="brand" style="color:#fff;">
                        <span class="brand-icon"><?php if ($__logoPath ?? ''): ?><img src="<?php echo htmlspecialchars(assetUrl($__logoPath)); ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:4px;"><?php else: ?><svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg><?php endif; ?></span>
                        <span><?php echo htmlspecialchars($__siteName); ?></span>
                    </div>
                    <p>Complete school management platform to handle admissions, attendance, exams, report cards and communication — all in one place.</p>
                    <p class="footer-contact"> info@diredawaeducation.gov.et<br> +251 25 111 2233<br> Dire Dawa, Ethiopia</p>
                </div>
                <div class="footer-col">
                    <h4>Platform</h4>
                    <a href="school.php">Browse Schools</a>
                    <a href="portals.php">Login Portals</a>
                    <a href="report-card.php">Report Card Lookup</a>
                    <a href="exam-result.php">Exam Result Lookup</a>
                    <a href="ministry-result.php">Ministry Result</a>
                </div>
                <div class="footer-col">
                    <h4>Students &amp; Staff</h4>
                    <a href="direschool-student/login.php">Student Portal</a>
                    <a href="direschool-student/register.php">Student Registration</a>
                    <a href="direschool-parent/login.php">Parent Portal</a>
                    <a href="direschool-admin/login.php">Staff Login</a>
                    <a href="faq.php">FAQ</a>
                </div>
                <div class="footer-col">
                    <h4>Connect</h4>
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact</a>
                    <a href="terms.php">Terms &amp; Conditions</a>
                    <a href="privacy.php">Privacy Policy</a>
                </div>
            </div>
            <div class="footer-stats">
                <span><?php echo $__schoolCount; ?>+ Schools</span>
                <span>&bull;</span>
                <span><?php echo $__studentCount; ?>+ Students</span>
                <span>&bull;</span>
                <span>Serving Dire Dawa</span>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($__siteName); ?> &middot; Dire Dawa Administration Education Bureau. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <?php else: ?>
    <footer class="site-footer" style="margin-top:40px;">
        <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($__siteName); ?> &middot; Dire Dawa Education Bureau.</p>
    </footer>
    <?php endif; ?>
<?php endif; ?>
    <script src="<?php echo $__js; ?>"></script></body></html>
