/* ============================================
   Professional Banner System - JavaScript
   نظام البنر الاحترافي للتفاعلات
   ============================================ */

'use strict';

// Initialize banner interactions when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initBannerInteractions();
});

/**
 * Initialize all banner interaction features
 */
function initBannerInteractions() {
    const banner = document.querySelector('.professional-banner');

    if (!banner) return;

    // Add keyboard navigation
    banner.addEventListener('keypress', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            banner.click();
        }
    });

    // Add hover effect enhancements
    const featureCards = document.querySelectorAll('.banner-feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add RTL badge interactions
    const rtlBadge = document.querySelector('.banner-rtl-badge');
    if (rtlBadge) {
        rtlBadge.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) rotate(3deg)';
        });
        rtlBadge.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) rotate(0deg)';
        });
    }

    // Add CTA button pulse effect on hover
    const ctaButton = document.querySelector('.banner-cta-button');
    if (ctaButton) {
        ctaButton.addEventListener('mouseenter', function() {
            this.style.animation = 'pulse 0.5s ease';
        });
        ctaButton.addEventListener('animationend', function() {
            this.style.animation = '';
        });
    }

    // Add analytics tracking if needed
    trackBannerView();
}

/**
 * Track banner view for analytics
 */
function trackBannerView() {
    // This can be connected to your analytics system
    console.log('Professional banner viewed');

    // Example: Send to Google Analytics if available
    if (typeof gtag !== 'undefined') {
        gtag('event', 'banner_view', {
            'event_category': 'engagement',
            'event_label': 'professional_banner'
        });
    }
}

/**
 * Track banner click
 */
function trackBannerClick() {
    console.log('Professional banner clicked');

    // Example: Send to Google Analytics if available
    if (typeof gtag !== 'undefined') {
        gtag('event', 'banner_click', {
            'event_category': 'engagement',
            'event_label': 'professional_banner_cta'
        });
    }
}

// Add pulse animation to CSS dynamically if not exists
if (!document.querySelector('#banner-animations')) {
    const style = document.createElement('style');
    style.id = 'banner-animations';
    style.textContent = `
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
    `;
    document.head.appendChild(style);
}
