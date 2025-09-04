// AiNA Works - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu functionality
    initMobileMenu();
    
    // Intersection Observer for animations
    initScrollAnimations();
    
    // Lazy loading for images
    initLazyLoading();
    
    // Smooth scrolling for anchor links
    initSmoothScrolling();
    
    // Form enhancements
    initFormEnhancements();
    
    // Initialize search functionality
    initSearch();
    
    // Initialize filter functionality
    initFilters();
    
    // Initialize modals
    initModals();
    
    // Initialize tabs
    initTabs();
    
    // Initialize accordions
    initAccordions();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize carousels
    initCarousels();
    
    // Initialize counters
    initCounters();
});

// Mobile Menu
function initMobileMenu() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            const isHidden = mobileMenu.classList.contains('hidden');
            
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                mobileMenu.classList.add('mobile-menu-enter');
                mobileMenuButton.innerHTML = `
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                `;
            } else {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('mobile-menu-enter');
                mobileMenuButton.innerHTML = `
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                `;
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('mobile-menu-enter');
                mobileMenuButton.innerHTML = `
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                `;
            }
        });
    }
}

// Scroll Animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);
    
    // Observe elements with animation classes
    const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-slide-up, .animate-scale-in');
    animatedElements.forEach(el => {
        el.classList.add('fade-in-on-scroll');
        observer.observe(el);
    });
}

// Lazy Loading
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy-image');
                img.classList.add('loaded');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => {
        img.classList.add('lazy-image');
        imageObserver.observe(img);
    });
}

// Smooth Scrolling
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Form Enhancements
function initFormEnhancements() {
    // Add floating labels
    const inputs = document.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        // Add focus/blur event listeners
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if input has value on load
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

// Form Validation
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'この項目は必須です');
            isValid = false;
        } else {
            clearFieldError(field);
        }
        
        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                showFieldError(field, '有効なメールアドレスを入力してください');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1 field-error';
    errorDiv.textContent = message;
    
    field.parentElement.appendChild(errorDiv);
    field.classList.add('border-red-500');
}

function clearFieldError(field) {
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.classList.remove('border-red-500');
}

// Enhanced Search functionality
function initSearch() {
    const searchInputs = document.querySelectorAll('#search-input, .search-input');
    
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                if (query.length > 0) {
                    performSearch(query);
                    showSearchSuggestions(input, query);
                } else {
                    clearSearchResults();
                    hideSearchSuggestions(input);
                }
            }, 300);
        });

        // Search suggestions on focus
        input.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                showSearchSuggestions(this, this.value.trim());
            } else {
                showPopularSearches(this);
            }
        });

        input.addEventListener('blur', function() {
            setTimeout(() => {
                hideSearchSuggestions(this);
            }, 200);
        });
    });
}

function performSearch(query) {
    console.log('Searching for:', query);
    
    // Show loading state
    showSearchLoading();
    
    // Simulate API call
    setTimeout(() => {
        hideSearchLoading();
        updateSearchResults(query);
    }, 500);
}

function showSearchSuggestions(input, query) {
    let suggestions = input.parentNode.querySelector('.search-suggestions');
    if (!suggestions) {
        suggestions = document.createElement('div');
        suggestions.className = 'search-suggestions absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b-md shadow-lg z-10 max-h-60 overflow-y-auto';
        input.parentNode.appendChild(suggestions);
    }
    
    // Generate suggestions based on query
    const suggestionItems = generateSearchSuggestions(query);
    suggestions.innerHTML = suggestionItems;
    suggestions.classList.remove('hidden');
    
    // Add click handlers for suggestions
    suggestions.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', function() {
            input.value = this.textContent.trim();
            performSearch(input.value);
            hideSearchSuggestions(input);
        });
    });
}

function showPopularSearches(input) {
    let suggestions = input.parentNode.querySelector('.search-suggestions');
    if (!suggestions) {
        suggestions = document.createElement('div');
        suggestions.className = 'search-suggestions absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b-md shadow-lg z-10';
        input.parentNode.appendChild(suggestions);
    }
    
    suggestions.innerHTML = `
        <div class="p-3">
            <div class="text-xs text-gray-500 mb-2">人気の検索</div>
            <div class="space-y-1">
                <div class="suggestion-item px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm">Webデザイン</div>
                <div class="suggestion-item px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm">ロゴ制作</div>
                <div class="suggestion-item px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm">動画編集</div>
                <div class="suggestion-item px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm">ライティング</div>
                <div class="suggestion-item px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm">写真撮影</div>
            </div>
        </div>
    `;
    suggestions.classList.remove('hidden');
    
    // Add click handlers
    suggestions.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', function() {
            input.value = this.textContent.trim();
            performSearch(input.value);
            hideSearchSuggestions(input);
        });
    });
}

function hideSearchSuggestions(input) {
    const suggestions = input.parentNode.querySelector('.search-suggestions');
    if (suggestions) {
        suggestions.classList.add('hidden');
    }
}

function generateSearchSuggestions(query) {
    const suggestions = [
        'Webデザイン',
        'ロゴ制作',
        '動画編集',
        'ライティング',
        '写真撮影',
        'UI/UX',
        'イラスト',
        'SNS運用'
    ];
    
    const filtered = suggestions.filter(s => 
        s.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filtered.length === 0) {
        return `<div class="p-3 text-sm text-gray-500">「${query}」に関する候補が見つかりません</div>`;
    }
    
    return `
        <div class="p-3">
            <div class="text-xs text-gray-500 mb-2">検索候補</div>
            <div class="space-y-1">
                ${filtered.map(item => 
                    `<div class="suggestion-item px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm">${item}</div>`
                ).join('')}
            </div>
        </div>
    `;
}

function showSearchLoading() {
    const loadingElements = document.querySelectorAll('.search-loading');
    loadingElements.forEach(el => el.classList.remove('hidden'));
}

function hideSearchLoading() {
    const loadingElements = document.querySelectorAll('.search-loading');
    loadingElements.forEach(el => el.classList.add('hidden'));
}

function updateSearchResults(query) {
    // Update results count
    const countElements = document.querySelectorAll('.results-count');
    countElements.forEach(element => {
        const count = Math.floor(Math.random() * 1000) + 100;
        element.textContent = `${count.toLocaleString()}件`;
    });
}

function clearSearchResults() {
    // Clear search results
    const resultsElements = document.querySelectorAll('.search-results');
    resultsElements.forEach(el => el.innerHTML = '');
}

// Enhanced Filter functionality
function initFilters() {
    const filterCheckboxes = document.querySelectorAll('input[type="checkbox"], input[type="radio"]');
    const clearFiltersBtn = document.querySelector('button:contains("フィルターをクリア")');
    const sortSelects = document.querySelectorAll('select');
    
    // Filter change handlers
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            applyFilters();
        });
    });
    
    // Clear filters handler
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            clearAllFilters();
        });
    }
    
    // Sort handlers
    sortSelects.forEach(select => {
        select.addEventListener('change', function() {
            applySorting(this.value);
        });
    });
    
    // Filter buttons for category filtering
    const filterButtons = document.querySelectorAll('[data-filter]');
    const filterableItems = document.querySelectorAll('[data-category]');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter items
            filterableItems.forEach(item => {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.style.display = 'block';
                    item.classList.add('animate-scale-in');
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

function applyFilters() {
    const activeFilters = getActiveFilters();
    console.log('Active filters:', activeFilters);
    
    // Update results count
    updateResultsCount();
    
    // Show filter applied indicator
    showFilterIndicator(activeFilters);
}

function getActiveFilters() {
    const filters = {};
    const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked');
    
    checkboxes.forEach(checkbox => {
        const section = checkbox.closest('.mb-6');
        const sectionTitle = section?.querySelector('h4')?.textContent || 'unknown';
        const value = checkbox.nextElementSibling?.textContent?.trim() || checkbox.value;
        
        if (value !== 'すべて') {
            if (!filters[sectionTitle]) {
                filters[sectionTitle] = [];
            }
            filters[sectionTitle].push(value);
        }
    });
    
    return filters;
}

function clearAllFilters() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"], input[type="radio"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Check "すべて" options
    const allOptions = document.querySelectorAll('input[value="すべて"]');
    allOptions.forEach(option => {
        option.checked = true;
    });
    
    applyFilters();
    hideFilterIndicator();
}

function applySorting(sortValue) {
    console.log('Sorting by:', sortValue);
    // Implementation for sorting results
    showToast(`${sortValue}で並び替えました`, 'info');
}

function updateResultsCount() {
    const countElements = document.querySelectorAll('.results-count');
    countElements.forEach(element => {
        const count = Math.floor(Math.random() * 1000) + 100;
        element.textContent = `${count.toLocaleString()}件`;
    });
}

function showFilterIndicator(filters) {
    const filterCount = Object.values(filters).reduce((total, arr) => total + arr.length, 0);
    if (filterCount > 0) {
        // Show filter indicator badge
        let indicator = document.querySelector('.filter-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'filter-indicator fixed top-20 right-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm z-40';
            document.body.appendChild(indicator);
        }
        indicator.textContent = `${filterCount}個のフィルター適用中`;
        indicator.classList.remove('hidden');
    }
}

function hideFilterIndicator() {
    const indicator = document.querySelector('.filter-indicator');
    if (indicator) {
        indicator.classList.add('hidden');
    }
}

// Modal functionality
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                openModal(modal);
            }
        });
    });
    
    modals.forEach(modal => {
        const closeButtons = modal.querySelectorAll('.modal-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => closeModal(modal));
        });
        
        // Close on backdrop click
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modal) {
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Tabs functionality
function initTabs() {
    const tabButtons = document.querySelectorAll('[data-tab]');
    const tabPanels = document.querySelectorAll('[data-tab-panel]');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Update active button
            tabButtons.forEach(btn => {
                btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('active', 'border-blue-500', 'text-blue-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Show corresponding panel
            tabPanels.forEach(panel => {
                if (panel.dataset.tabPanel === tabId) {
                    panel.classList.remove('hidden');
                    panel.classList.add('animate-fade-in');
                } else {
                    panel.classList.add('hidden');
                }
            });
        });
    });
}

// Accordion functionality
function initAccordions() {
    const accordionButtons = document.querySelectorAll('.accordion-button');
    
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isOpen = !content.classList.contains('hidden');
            
            if (isOpen) {
                content.classList.add('hidden');
                this.querySelector('svg').style.transform = 'rotate(0deg)';
            } else {
                content.classList.remove('hidden');
                this.querySelector('svg').style.transform = 'rotate(180deg)';
            }
        });
    });
}

// Tooltip functionality
function initTooltips() {
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
    
    tooltipTriggers.forEach(trigger => {
        let tooltip;
        
        trigger.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            tooltip = createTooltip(text);
            document.body.appendChild(tooltip);
            positionTooltip(tooltip, this);
        });
        
        trigger.addEventListener('mouseleave', function() {
            if (tooltip) {
                tooltip.remove();
                tooltip = null;
            }
        });
    });
}

function createTooltip(text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg pointer-events-none';
    tooltip.textContent = text;
    return tooltip;
}

function positionTooltip(tooltip, trigger) {
    const triggerRect = trigger.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    tooltip.style.left = `${triggerRect.left + (triggerRect.width - tooltipRect.width) / 2}px`;
    tooltip.style.top = `${triggerRect.top - tooltipRect.height - 8}px`;
}

// Carousel functionality
function initCarousels() {
    const carousels = document.querySelectorAll('.carousel');
    
    carousels.forEach(carousel => {
        const track = carousel.querySelector('.carousel-track');
        const slides = carousel.querySelectorAll('.carousel-slide');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        const indicators = carousel.querySelectorAll('.carousel-indicator');
        
        let currentSlide = 0;
        const totalSlides = slides.length;
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                updateCarousel(track, indicators, currentSlide);
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentSlide = (currentSlide + 1) % totalSlides;
                updateCarousel(track, indicators, currentSlide);
            });
        }
        
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                updateCarousel(track, indicators, currentSlide);
            });
        });
        
        // Auto-play
        if (carousel.hasAttribute('data-autoplay')) {
            setInterval(() => {
                currentSlide = (currentSlide + 1) % totalSlides;
                updateCarousel(track, indicators, currentSlide);
            }, 5000);
        }
    });
}

function updateCarousel(track, indicators, currentSlide) {
    if (track) {
        track.style.transform = `translateX(-${currentSlide * 100}%)`;
    }
    
    indicators.forEach((indicator, index) => {
        if (index === currentSlide) {
            indicator.classList.add('active');
        } else {
            indicator.classList.remove('active');
        }
    });
}

// Counter animation
function initCounters() {
    const counters = document.querySelectorAll('.counter');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;

                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.floor(current).toLocaleString();
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                };

                updateCounter();
                counterObserver.unobserve(counter);
            }
        });
    });

    counters.forEach(counter => counterObserver.observe(counter));
}

// Utility Functions
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

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${getToastClasses(type)} transform translate-x-full transition-transform duration-300`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

function getToastClasses(type) {
    switch (type) {
        case 'success':
            return 'bg-green-500 text-white';
        case 'error':
            return 'bg-red-500 text-white';
        case 'warning':
            return 'bg-yellow-500 text-white';
        default:
            return 'bg-blue-500 text-white';
    }
}

// Export functions for use in other files
window.AiNAWorks = {
    showToast,
    openModal,
    closeModal,
    initSearch,
    initFilters,
    initTabs,
    initAccordions,
    performSearch,
    applyFilters,
    clearAllFilters
};

