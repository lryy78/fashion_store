// Wait until the page markup is ready before attaching shared interactions.
document.addEventListener('DOMContentLoaded', function() {
    // 1. Notification System
    function showNotification(message, type = 'success') {
        const notifyDiv = document.createElement('div');
        notifyDiv.className = `glass-panel fade-in`;
        notifyDiv.style.position = 'fixed';
        notifyDiv.style.top = '20px';
        notifyDiv.style.right = '20px';
        notifyDiv.style.padding = '1rem 2rem';
        notifyDiv.style.borderRadius = 'var(--radius-md)';
        notifyDiv.style.borderLeft = `4px solid var(--${type})`;
        notifyDiv.style.zIndex = '9999';
        notifyDiv.style.display = 'flex';
        notifyDiv.style.alignItems = 'center';
        notifyDiv.style.gap = '10px';
        
        const icon = type === 'success' ? '✓' : '⚠';
        notifyDiv.innerHTML = `<span style="color: var(--${type}); font-weight: bold;">${icon}</span> ${message}`;

        document.body.appendChild(notifyDiv);

        setTimeout(() => {
            notifyDiv.style.opacity = '0';
            notifyDiv.style.transform = 'translateY(-10px)';
            notifyDiv.style.transition = 'all var(--transition-base)';
            setTimeout(() => notifyDiv.remove(), 300);
        }, 3000);
    }
    window.showNotification = showNotification;

    // Remove existing alerts automatically
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    // 2. Form Validation with micro-animations
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredInputs = form.querySelectorAll('[required]');
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'var(--error)';
                    input.classList.add('shake');
                    setTimeout(() => input.classList.remove('shake'), 500);
                } else {
                    input.style.borderColor = 'var(--border)';
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields.', 'error');
            }
        });
    });

    // 3. Smooth Scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if(target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // 4. Parallax effect for hero banners
    const heroBanner = document.querySelector('.hero-banner');
    if (heroBanner) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            heroBanner.style.backgroundPositionY = `${scrolled * 0.5}px`;
        });
    }

    // 5. Accordion Logic for Product Details
    const accordions = document.querySelectorAll('.accordion-header');
    accordions.forEach(acc => {
        acc.addEventListener('click', function() {
            const span = this.querySelector('span:nth-child(2)');
            if (span.textContent === '+') {
                span.textContent = '-';
                const content = document.createElement('div');
                content.className = 'accordion-content fade-in-up';
                content.style.paddingTop = '1rem';
                content.style.color = 'var(--text-muted)';
                content.style.fontSize = '0.9rem';
                content.style.lineHeight = '1.6';
                if(this.textContent.includes('Delivery')) {
                    content.innerHTML = 'Complimentary express delivery on orders over $500. Returns accepted within 14 days of receiving your order. Pieces must be returned in their original condition with all tags attached.';
                } else {
                    content.innerHTML = 'Dry clean only. Handle with care. Our pieces are crafted using premium materials meant to last a lifetime when properly cared for.';
                }
                this.parentNode.appendChild(content);
            } else {
                span.textContent = '+';
                const content = this.parentNode.querySelector('.accordion-content');
                if (content) content.remove();
            }
        });
    });
});

// Add CSS for shake animation dynamically
const style = document.createElement('style');
style.innerHTML = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.shake { animation: shake 0.3s ease-in-out; }
`;
document.head.appendChild(style);
