// AiNA Works - オンボーディングツアー
class OnboardingTour {
    constructor() {
        this.currentStep = 0;
        this.steps = [];
        this.isActive = false;
        this.overlay = null;
        this.tooltip = null;
        this.init();
    }

    init() {
        this.setupSteps();
        this.createOverlay();
        this.createTooltip();
        this.bindEvents();
        this.checkIfFirstVisit();
    }

    setupSteps() {
        this.steps = [
            {
                target: '#dashboard-stats',
                title: 'ダッシュボードへようこそ！',
                content: 'ここではあなたの活動状況を確認できます。統計カードで作品数や応募数などを一目で把握できます。',
                position: 'bottom',
                action: 'highlight'
            },
            {
                target: '#dashboard-menu-area',
                title: 'メニューエリア',
                content: '左側はクリエイターとしての機能、右側は依頼者としての機能が表示されています。両方の役割を活用できます。',
                position: 'top',
                action: 'highlight'
            },
            {
                target: 'a[href*="edit-work"]',
                title: '作品を投稿する',
                content: '「新しい作品を投稿」をクリックして、あなたのポートフォリオに作品を追加しましょう。',
                position: 'right',
                action: 'highlight'
            },
            {
                target: 'a[href*="jobs"]',
                title: '案件を探す',
                content: '「案件を探す」から新しい仕事を見つけて応募できます。スキルに合った案件を探してみましょう。',
                position: 'right',
                action: 'highlight'
            },
            {
                target: 'a[href*="post-job"]',
                title: '案件を投稿する',
                content: '「新しい案件を投稿」から、クリエイターに依頼したい案件を投稿できます。',
                position: 'left',
                action: 'highlight'
            },
            {
                target: 'a[href*="chats"]',
                title: 'チャット機能',
                content: '「チャット」からクライアントやクリエイターとのやり取りを管理できます。',
                position: 'left',
                action: 'highlight'
            }
        ];
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'onboarding-overlay';
        this.overlay.innerHTML = `
            <div class="onboarding-spotlight"></div>
        `;
        document.body.appendChild(this.overlay);
    }

    createTooltip() {
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'onboarding-tooltip';
        this.tooltip.innerHTML = `
            <div class="onboarding-tooltip-content">
                <div class="onboarding-tooltip-header">
                    <h3 class="onboarding-tooltip-title"></h3>
                    <button class="onboarding-tooltip-close" aria-label="チュートリアルを閉じる">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="onboarding-tooltip-body">
                    <p class="onboarding-tooltip-text"></p>
                </div>
                <div class="onboarding-tooltip-footer">
                    <div class="onboarding-progress">
                        <span class="onboarding-step-counter"></span>
                        <div class="onboarding-progress-bar">
                            <div class="onboarding-progress-fill"></div>
                        </div>
                    </div>
                    <div class="onboarding-tooltip-actions">
                        <button class="onboarding-btn onboarding-btn-secondary" id="onboarding-skip">スキップ</button>
                        <button class="onboarding-btn onboarding-btn-primary" id="onboarding-next">次へ</button>
                    </div>
                </div>
            </div>
            <div class="onboarding-arrow"></div>
        `;
        document.body.appendChild(this.tooltip);
    }

    bindEvents() {
        // ツールチップのボタンイベント
        this.tooltip.querySelector('#onboarding-next').addEventListener('click', () => this.nextStep());
        this.tooltip.querySelector('#onboarding-skip').addEventListener('click', () => this.skipTour());
        this.tooltip.querySelector('.onboarding-tooltip-close').addEventListener('click', () => this.skipTour());

        // オーバーレイクリックでスキップ
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay || e.target.classList.contains('onboarding-spotlight')) {
                this.skipTour();
            }
        });

        // ESCキーでスキップ
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isActive) {
                this.skipTour();
            }
        });
    }

    checkIfFirstVisit() {
        // 自動ツアー開始を無効化
        // ツアー開始ボタンを押した時のみツアーを開始する
    }

    startTour() {
        this.isActive = true;
        this.currentStep = 0;
        this.showStep();
        this.overlay.classList.add('active');
        this.tooltip.classList.add('active');
        document.body.classList.add('onboarding-active');
    }

    showStep() {
        if (this.currentStep >= this.steps.length) {
            this.completeTour();
            return;
        }

        const step = this.steps[this.currentStep];
        const targetElement = document.querySelector(step.target);

        if (!targetElement) {
            this.nextStep();
            return;
        }

        // ツールチップの内容を更新
        this.tooltip.querySelector('.onboarding-tooltip-title').textContent = step.title;
        this.tooltip.querySelector('.onboarding-tooltip-text').textContent = step.content;
        this.tooltip.querySelector('.onboarding-step-counter').textContent = `${this.currentStep + 1} / ${this.steps.length}`;

        // プログレスバーを更新
        const progressFill = this.tooltip.querySelector('.onboarding-progress-fill');
        progressFill.style.width = `${((this.currentStep + 1) / this.steps.length) * 100}%`;

        // ターゲット要素をハイライト
        this.highlightElement(targetElement, step.position);

        // ターゲット要素にスクロール
        this.scrollToElement(targetElement);
    }

    highlightElement(element, position) {
        const rect = element.getBoundingClientRect();
        const spotlight = this.overlay.querySelector('.onboarding-spotlight');
        
        // スポットライトの位置とサイズを設定
        spotlight.style.left = `${rect.left - 8}px`;
        spotlight.style.top = `${rect.top - 8}px`;
        spotlight.style.width = `${rect.width + 16}px`;
        spotlight.style.height = `${rect.height + 16}px`;

        // ツールチップの位置を設定
        this.positionTooltip(element, position);
    }

    positionTooltip(element, position) {
        const rect = element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const arrow = this.tooltip.querySelector('.onboarding-arrow');
        
        let top, left, arrowClass;

        switch (position) {
            case 'top':
                top = rect.top - tooltipRect.height - 20;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                arrowClass = 'arrow-bottom';
                break;
            case 'bottom':
                top = rect.bottom + 20;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                arrowClass = 'arrow-top';
                break;
            case 'left':
                top = rect.top + (rect.height - tooltipRect.height) / 2;
                left = rect.left - tooltipRect.width - 20;
                arrowClass = 'arrow-right';
                break;
            case 'right':
                top = rect.top + (rect.height - tooltipRect.height) / 2;
                left = rect.right + 20;
                arrowClass = 'arrow-left';
                break;
            default:
                top = rect.bottom + 20;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                arrowClass = 'arrow-top';
        }

        // 画面端での調整
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        if (left < 20) left = 20;
        if (left + tooltipRect.width > viewportWidth - 20) {
            left = viewportWidth - tooltipRect.width - 20;
        }
        if (top < 20) top = 20;
        if (top + tooltipRect.height > viewportHeight - 20) {
            top = viewportHeight - tooltipRect.height - 20;
        }

        this.tooltip.style.top = `${top}px`;
        this.tooltip.style.left = `${left}px`;

        // 矢印のクラスを更新
        arrow.className = `onboarding-arrow ${arrowClass}`;
    }

    scrollToElement(element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'center'
        });
    }

    nextStep() {
        this.currentStep++;
        this.showStep();
    }

    skipTour() {
        this.isActive = false;
        this.overlay.classList.remove('active');
        this.tooltip.classList.remove('active');
        document.body.classList.remove('onboarding-active');
        
        // スキップした場合は完了として記録しない
        this.showSkipMessage();
    }

    completeTour() {
        this.isActive = false;
        this.overlay.classList.remove('active');
        this.tooltip.classList.remove('active');
        document.body.classList.remove('onboarding-active');
        
        // 完了を記録
        localStorage.setItem('aina_works_onboarding_completed', 'true');
        this.showCompletionMessage();
    }

    showSkipMessage() {
        if (window.AiNAWorks && window.AiNAWorks.showToast) {
            window.AiNAWorks.showToast('チュートリアルをスキップしました。いつでも「ツアーを開始」ボタンから再開できます。', 'info');
        }
    }

    showCompletionMessage() {
        if (window.AiNAWorks && window.AiNAWorks.showToast) {
            window.AiNAWorks.showToast('チュートリアルが完了しました！AiNA Worksをお楽しみください。', 'success');
        }
    }

    // 手動でツアーを開始するメソッド（ローカルストレージに保存しない）
    restartTour() {
        this.startTour();
    }

    // ツアーをリセットするメソッド（開発用）
    resetTour() {
        localStorage.removeItem('aina_works_onboarding_completed');
        if (window.AiNAWorks && window.AiNAWorks.showToast) {
            window.AiNAWorks.showToast('チュートリアルがリセットされました。', 'info');
        }
    }
}

// ページ読み込み時にオンボーディングツアーを初期化
document.addEventListener('DOMContentLoaded', function() {
    window.onboardingTour = new OnboardingTour();
});

// グローバル関数として公開
window.startOnboardingTour = function() {
    if (window.onboardingTour) {
        window.onboardingTour.restartTour();
    }
};

window.resetOnboardingTour = function() {
    if (window.onboardingTour) {
        window.onboardingTour.resetTour();
    }
};

