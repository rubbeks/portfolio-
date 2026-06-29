// ── Progress Bar ──────────────────────────────────────────
const ProgressBar = (() => {
    let timer = null;

    function show(message) {
        // Remove existing if any
        hide();

        const bar = document.createElement('div');
        bar.id = 'progress-toast';
        bar.innerHTML = `
            <div id="progress-msg">${message}</div>
            <div id="progress-track">
                <div id="progress-fill"></div>
            </div>
        `;
        document.body.appendChild(bar);

        // Trigger animation (drain from full to empty)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                document.getElementById('progress-fill').style.width = '0%';
            });
        });

        // Auto-hide after 10s
        timer = setTimeout(hide, 10000);
    }

    function hide() {
        clearTimeout(timer);
        const el = document.getElementById('progress-toast');
        if (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            setTimeout(() => el && el.remove(), 300);
        }
    }

    return { show, hide };
})();

// ── Intercept nav links ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Nav link clicks
    document.querySelectorAll('a.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            const label = link.textContent.trim();
            ProgressBar.show(`Navigating to ${label}...`);
        });
    });

    // Form submits
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            // Try to detect what kind of form
            const submitBtn = form.querySelector('[type=submit]');
            const btnText = submitBtn ? submitBtn.textContent.trim() : 'Saving';
            const hasFile = form.querySelector('[type=file]');
            const msg = hasFile
                ? `Uploading and saving changes...`
                : `${btnText}...`;
            ProgressBar.show(msg);
        });
    });

    // Delete / action buttons (links with ?delete or ?edit)
    document.querySelectorAll('a[href*="delete"]').forEach(link => {
        link.addEventListener('click', (e) => {
            if (!link.onclick || confirm) {
                ProgressBar.show('Deleting item...');
            }
        });
    });

    // Edit links
    document.querySelectorAll('a[href*="edit"], a[href*="product_form"], a[href*="transaction_view"]').forEach(link => {
        link.addEventListener('click', () => {
            ProgressBar.show('Loading...');
        });
    });

    // Back buttons
    document.querySelectorAll('a[href*="products.php"], a[href*="transactions.php"], a[href*="categories.php"]').forEach(link => {
        if (link.textContent.includes('←') || link.textContent.includes('Back')) {
            link.addEventListener('click', () => ProgressBar.show('Going back...'));
        }
    });

    // Receipt submit button (index.php)
    const receiptBtn = document.querySelector('button[onclick="submitReceipt()"]');
    if (receiptBtn) {
        receiptBtn.addEventListener('click', () => {
            ProgressBar.show('Submitting receipt...');
        });
    }

    // Print button
    const printBtn = document.querySelector('button[onclick="window.print()"]');
    if (printBtn) {
        printBtn.addEventListener('click', () => ProgressBar.show('Opening print dialog...'));
    }

    // Filter form (transactions)
    const filterForm = document.querySelector('form[method="GET"]');
    if (filterForm) {
        filterForm.addEventListener('submit', () => ProgressBar.show('Filtering transactions...'));
    }
});
