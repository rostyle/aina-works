    </main>

    <!-- Enhanced Footer -->
    <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 20px 20px;"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
                <!-- Enhanced Company Info -->
                <div class="lg:col-span-2">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-500 via-primary-600 to-secondary-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-lg">AW</span>
                        </div>
                        <div>
                            <span class="text-2xl font-bold"><?= h(SITE_NAME) ?></span>
                            <div class="text-sm text-gray-400">AI & Creative Works Platform</div>
                        </div>
                    </div>
                    <p class="text-gray-300 mb-6 max-w-lg text-lg leading-relaxed">
                        AIスクール生と企業をつなぐ、新しいクリエイティブプラットフォーム。
                        才能あるクリエイターと素晴らしいプロジェクトのマッチングを支援します。
                    </p>
                    
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-6 text-white">サービス</h3>
                    <ul class="space-y-3">
                        <li><a href="<?= url('work') ?>" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-primary-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            作品を探す
                        </a></li>
                        <li><a href="<?= url('creators') ?>" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-primary-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            クリエイター
                        </a></li>
                        <li><a href="<?= url('jobs') ?>" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-primary-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            案件一覧
                        </a></li>
                        <li><a href="<?= url('post-job') ?>" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-primary-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            案件を投稿
                        </a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-lg font-semibold mb-6 text-white">サポート</h3>
                    <ul class="space-y-3">
                        <li><a href="<?= url('terms') ?>" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-primary-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            利用規約
                        </a></li>
                        <li><a href="<?= url('privacy') ?>" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-primary-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            プライバシーポリシー
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="border-t border-gray-800 mt-12 pt-8">
                <div class="flex flex-col lg:flex-row justify-between items-center">
                    <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-6">
                <p class="text-gray-400 text-sm">
                    © <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.
                </p>
                        <div class="flex items-center space-x-4 text-sm">
                            <a href="<?= url('terms') ?>" class="text-gray-400 hover:text-white transition-colors duration-300">利用規約</a>
                            <span class="text-gray-600">•</span>
                            <a href="<?= url('privacy') ?>" class="text-gray-400 hover:text-white transition-colors duration-300">プライバシーポリシー</a>
                            <!-- <span class="text-gray-600">•</span>
                            <a href="<?= url('contact.php') ?>" class="text-gray-400 hover:text-white transition-colors duration-300">お問い合わせ</a> -->
                        </div>
                    </div>
                    
                    <!-- Back to Top Button -->
                    <button onclick="scrollToTop()" 
                            class="mt-4 lg:mt-0 p-3 bg-gray-800 hover:bg-primary-600 rounded-xl text-gray-400 hover:text-white transition-all duration-300 hover:scale-110"
                            title="ページトップへ">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- Enhanced JavaScript -->
    <script src="<?= asset('js/main.js') ?>"></script>
    
    <script>
        // Enhanced Mobile menu toggle with animations
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const button = document.getElementById('mobile-menu-button');
            const isHidden = menu.classList.contains('hidden');
            
            if (isHidden) {
                menu.classList.remove('hidden');
                menu.classList.add('mobile-menu-enter');
                button.setAttribute('aria-expanded', 'true');
                button.setAttribute('aria-label', 'メニューを閉じる');
                // Focus management
                const firstLink = menu.querySelector('a');
                if (firstLink) setTimeout(() => firstLink.focus(), 100);
                // Change hamburger to X
                button.innerHTML = `
                    <svg class="h-6 w-6 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                `;
            } else {
                menu.classList.add('hidden');
                menu.classList.remove('mobile-menu-enter');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-label', 'メニューを開く');
                button.focus(); // Return focus to button
                // Change X back to hamburger
                button.innerHTML = `
                    <svg class="h-6 w-6 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                `;
            }
        }

        // Quick Search Modal Toggle
        function toggleQuickSearch() {
            const modal = document.getElementById('quick-search-modal');
            const input = document.getElementById('quick-search-input');
            const isVisible = !modal.classList.contains('opacity-0');
            
            if (!isVisible) {
                modal.classList.remove('opacity-0', 'invisible');
                modal.querySelector('.bg-white').classList.remove('scale-95');
                modal.querySelector('.bg-white').classList.add('scale-100');
                setTimeout(() => input.focus(), 100);
            } else {
                modal.classList.add('opacity-0', 'invisible');
                modal.querySelector('.bg-white').classList.add('scale-95');
                modal.querySelector('.bg-white').classList.remove('scale-100');
            }
        }

        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close quick search modal
                const modal = document.getElementById('quick-search-modal');
                if (modal && !modal.classList.contains('opacity-0')) {
                    toggleQuickSearch();
                    return;
                }
                
                // Close mobile menu
                // Close mobile menu using header's close function if available
                const mobileMenu = document.getElementById('mobile-menu');
                if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    if (typeof closeMobileMenu === 'function') {
                        closeMobileMenu();
                    } else if (typeof toggleMobileMenu === 'function') {
                        toggleMobileMenu();
                    }
                    return;
                }
            }
            
            // Trap focus in mobile menu
            if (!document.getElementById('mobile-menu').classList.contains('hidden')) {
                trapFocus(e, 'mobile-menu');
            }
            
            // Trap focus in quick search modal
            const modal = document.getElementById('quick-search-modal');
            if (modal && !modal.classList.contains('opacity-0')) {
                trapFocus(e, 'quick-search-modal');
            }
        });
        
        // Focus trap utility
        function trapFocus(e, containerId) {
            if (e.key !== 'Tab') return;
            
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const focusableElements = container.querySelectorAll(
                'a[href], button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }

        // Close modal on backdrop click
        document.getElementById('quick-search-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleQuickSearch();
            }
        });

        // Enhanced Auto-hide flash messages with better animations
        function initFlashMessages() {
            const flashMessages = document.querySelectorAll('.flash-message');
            
            flashMessages.forEach((message, index) => {
                // Auto-hide after delay
        setTimeout(() => {
                    if (message.parentNode) {
                        message.style.opacity = '0';
                        message.style.transform = 'translateX(100%) scale(0.9)';
                        setTimeout(() => message.remove(), 300);
                    }
                }, 5000 + (index * 500)); // Stagger the hiding
            });
        }

        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Header scroll effect
        function initHeaderScrollEffect() {
            const header = document.querySelector('header');
            let lastScroll = 0;
            
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll <= 0) {
                    header.classList.remove('shadow-lg');
                    header.classList.add('shadow-sm');
                } else {
                    header.classList.remove('shadow-sm');
                    header.classList.add('shadow-lg');
                }
                
                // Hide/show header on scroll
                if (currentScroll > lastScroll && currentScroll > 100) {
                    header.style.transform = 'translateY(-100%)';
                } else {
                    header.style.transform = 'translateY(0)';
                }
                
                lastScroll = currentScroll;
            });
        }

        // Enhanced smooth scroll for anchor links
        function initSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                    const href = this.getAttribute('href');
                    if (href === '#') return;
                    
                    const target = document.querySelector(href);
                if (target) {
                        const headerHeight = document.querySelector('header').offsetHeight;
                        const targetPosition = target.offsetTop - headerHeight - 20;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        }

        // Intersection Observer for scroll animations
        function initScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target); // Only animate once
                    }
                });
            }, observerOptions);
            
            // Observe elements with scroll animation classes
            document.querySelectorAll('.fade-in-on-scroll, .slide-in-left-on-scroll, .slide-in-right-on-scroll').forEach(el => {
                observer.observe(el);
            });
        }

        // Newsletter signup handler
        function initNewsletterSignup() {
            const form = document.querySelector('footer input[type="email"]')?.parentElement;
            if (form) {
                const input = form.querySelector('input[type="email"]');
                const button = form.querySelector('button');
                
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const email = input.value.trim();
                    
                    if (!email) {
                        showNotification('メールアドレスを入力してください', 'warning');
                        return;
                    }
                    
                    if (!isValidEmail(email)) {
                        showNotification('有効なメールアドレスを入力してください', 'error');
                        return;
                    }
                    
                    // Simulate API call
                    button.innerHTML = '<div class="loading loading-sm"></div>';
                    button.disabled = true;
                    
                    setTimeout(() => {
                        showNotification('ニュースレターの登録が完了しました！', 'success');
                        input.value = '';
                        button.innerHTML = '登録';
                        button.disabled = false;
                    }, 1500);
                });
            }
        }

        // Email validation
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Enhanced notification system with live region
        function showNotification(message, type = 'info', duration = 5000) {
            // Update live region for screen readers
            const liveRegion = document.getElementById('live-region');
            if (liveRegion) {
                liveRegion.textContent = message;
                // Clear after announcement
                setTimeout(() => liveRegion.textContent = '', 1000);
            }
            const notification = document.createElement('div');
            notification.className = `fixed top-24 right-4 z-50 p-4 rounded-2xl shadow-xl backdrop-blur-lg border max-w-sm transform translate-x-full transition-all duration-300 ${getNotificationClasses(type)}`;
            
            notification.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        ${getNotificationIcon(type)}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600 rounded-lg transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);
            
            // Auto remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        function getNotificationClasses(type) {
            switch (type) {
                case 'success': return 'bg-green-50 border-green-200';
                case 'error': return 'bg-red-50 border-red-200';
                case 'warning': return 'bg-yellow-50 border-yellow-200';
                default: return 'bg-blue-50 border-blue-200';
            }
        }

        function getNotificationIcon(type) {
            const baseClasses = 'w-8 h-8 rounded-full flex items-center justify-center';
            switch (type) {
                case 'success':
                    return `<div class="${baseClasses} bg-green-100"><svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>`;
                case 'error':
                    return `<div class="${baseClasses} bg-red-100"><svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></div>`;
                case 'warning':
                    return `<div class="${baseClasses} bg-yellow-100"><svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg></div>`;
                default:
                    return `<div class="${baseClasses} bg-blue-100"><svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>`;
            }
        }

        // Initialize all functionality when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initFlashMessages();
            initHeaderScrollEffect();
            initSmoothScrolling();
            initScrollAnimations();
            initNewsletterSignup();
            
            // Add loading states to forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton && !submitButton.disabled) {
                        const originalText = submitButton.innerHTML;
                        submitButton.innerHTML = '<div class="loading loading-sm mr-2"></div>送信中...';
                        submitButton.disabled = true;
                        
                        // Re-enable after timeout (fallback)
                        setTimeout(() => {
                            submitButton.innerHTML = originalText;
                            submitButton.disabled = false;
                        }, 10000);
                    }
                });
            });
        });

        // Performance optimization: Debounce scroll events
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>

    <?php if (isset($additionalJs)): ?>
        <?= $additionalJs ?>
    <?php endif; ?>
</body>
</html>
