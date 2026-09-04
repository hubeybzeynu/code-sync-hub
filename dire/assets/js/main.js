// ============================================
// Dire Dawa Schools - Unified JavaScript
// ============================================

// Print the current page
function printPage() { window.print(); }

// Simple client-side form validation
function validateSearchForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener("submit", function (e) {
        var inputs = form.querySelectorAll("input[required]");
        for (var i = 0; i < inputs.length; i++) {
            if (!inputs[i].value.trim()) {
                e.preventDefault();
                alert("Please fill in: " + (inputs[i].previousElementSibling ? inputs[i].previousElementSibling.textContent : inputs[i].name));
                inputs[i].focus();
                return;
            }
        }
    });
}

// Grade -> Section dependent dropdown
function setupGradeSectionPicker(gradeSelectId, sectionSelectId, sectionsByGrade) {
    var gradeSelect = document.getElementById(gradeSelectId);
    var sectionSelect = document.getElementById(sectionSelectId);
    if (!gradeSelect || !sectionSelect) return;
    function refreshSections() {
        var grade = gradeSelect.value;
        sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
        (sectionsByGrade[grade] || []).forEach(function (sec) {
            var opt = document.createElement("option");
            opt.value = sec; opt.textContent = sec;
            sectionSelect.appendChild(opt);
        });
    }
    gradeSelect.addEventListener("change", refreshSections);
    if (gradeSelect.value) refreshSections();
}

// Dark / Light mode toggle
function toggleSiteTheme() {
    var html = document.documentElement;
    var current = html.getAttribute("data-theme") === "dark" ? "dark" : "light";
    var next = current === "dark" ? "light" : "dark";
    html.setAttribute("data-theme", next);
    try { localStorage.setItem("dire-theme", next); } catch (e) {}
    document.querySelectorAll(".theme-toggle-icon").forEach(function (el) {
        el.innerHTML = next === "dark"
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg>';
    });
}

document.addEventListener("DOMContentLoaded", function () {
    // Mobile sidebar toggle
    var toggle = document.getElementById("menuToggle");
    var sidebar = document.querySelector(".sidebar");
    if (toggle && sidebar) {
        toggle.addEventListener("click", function () { sidebar.classList.toggle("open"); });
    }

    // Editorial full-screen menu
    var megaMenu = document.getElementById("megaMenu");
    var menuTrigger = document.getElementById("menuTrigger");
    var mmClose = document.getElementById("mmClose");
    function setMenu(open) {
        if (!megaMenu) return;
        if (open) {
            megaMenu.classList.add("open");
            megaMenu.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";
            if (menuTrigger) menuTrigger.setAttribute("aria-expanded", "true");
        } else {
            megaMenu.classList.remove("open");
            megaMenu.setAttribute("aria-hidden", "true");
            document.body.style.overflow = "";
            if (menuTrigger) menuTrigger.setAttribute("aria-expanded", "false");
        }
    }
    if (menuTrigger) {
        menuTrigger.addEventListener("click", function () { setMenu(true); });
    }
    if (mmClose) {
        mmClose.addEventListener("click", function () { setMenu(false); });
    }
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") setMenu(false);
    });

    // Confirm before any delete link/button
    document.querySelectorAll(".confirm-delete").forEach(function (el) {
        el.addEventListener("click", function (e) {
            if (!confirm("Are you sure you want to delete this? This cannot be undone.")) {
                e.preventDefault();
            }
        });
    });

    // Auto-calc average in report card editor marks table
    document.querySelectorAll(".marks-table input[type=number]").forEach(function (input) {
        input.addEventListener("input", recalcRowAverage);
    });

    // Scroll-reveal trigger
    var revealEls = document.querySelectorAll(".reveal");
    if (revealEls.length && "IntersectionObserver" in window) {
        var io = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    var delay = Number(el.dataset.revealDelay || 0);
                    setTimeout(function () { el.classList.add("in-view"); }, delay);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
        var groupIndex = 0;
        revealEls.forEach(function (el) {
            el.dataset.revealDelay = (groupIndex % 8) * 70;
            groupIndex++;
            io.observe(el);
        });
    } else if (revealEls.length) {
        revealEls.forEach(function (el) { el.classList.add("in-view"); });
    }

    // Theme toggle icons
    document.querySelectorAll(".theme-toggle-icon").forEach(function (el) {
        el.innerHTML = document.documentElement.getAttribute("data-theme") === "dark"
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg>';
    });

    // Fade in cards
    document.querySelectorAll(".card, .list-item").forEach(function (el) {
        el.style.opacity = "0";
        el.style.transition = "opacity 0.25s ease";
        setTimeout(function () { el.style.opacity = "1"; }, 30);
    });
});

function recalcRowAverage(e) {
    var row = e.target.closest("tr");
    if (!row) return;
    var inputs = row.querySelectorAll("input[type=number][data-quarter]");
    var avgCell = row.querySelector(".row-average");
    if (!inputs.length || !avgCell) return;
    var sum = 0, count = 0;
    inputs.forEach(function (i) {
        var v = parseFloat(i.value);
        if (!isNaN(v) && v > 0) { sum += v; count++; }
    });
    avgCell.textContent = count ? (sum / count).toFixed(1) : "-";
}
