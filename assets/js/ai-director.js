// AiNA Works - AI Director (Gemini) Frontend

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('ai-director-run');
    const bar = document.getElementById('ai-director-progress');
    const spark = document.getElementById('ai-director-spark');
    const dock = document.getElementById('ai-director-dock');
    const dockBody = document.getElementById('ai-director-dock-body');
    const dockClose = document.getElementById('ai-dock-close');
    const dockMinimize = document.getElementById('ai-dock-minimize');
    const historyBtn = document.getElementById('ai-history-btn');
    const historyModal = document.getElementById('ai-history-modal');
    const historyBody = document.getElementById('ai-history-body');
    const historyClose = document.getElementById('ai-history-close');

    if (!btn) return;

    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // 履歴管理
    const HISTORY_KEY = 'aina_ai_director_history';
    const MAX_HISTORY = 10;

    function saveToHistory(data, inputData) {
        try {
            const history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
            const newEntry = {
                id: Date.now(),
                timestamp: new Date().toISOString(),
                input: {
                    title: inputData.title,
                    description: inputData.description,
                    category: inputData.category_name
                },
                result: data
            };
            
            history.unshift(newEntry);
            if (history.length > MAX_HISTORY) {
                history.splice(MAX_HISTORY);
            }
            
            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
        } catch (e) {
            console.warn('履歴の保存に失敗しました:', e);
        }
    }

    function loadHistory() {
        try {
            return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
        } catch (e) {
            console.warn('履歴の読み込みに失敗しました:', e);
            return [];
        }
    }

    function renderHistory() {
        const history = loadHistory();
        if (history.length === 0) {
            historyBody.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>まだ履歴がありません</p>
                    <p class="text-sm">AIディレクターを使用すると、ここに履歴が表示されます</p>
                </div>
            `;
            return;
        }

        historyBody.innerHTML = history.map(entry => `
            <div class="border border-gray-200 rounded-lg p-4 mb-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-900">${entry.input.title || '無題'}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">${new Date(entry.timestamp).toLocaleString('ja-JP')}</span>
                        <button type="button" class="ai-history-view-btn px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700" data-id="${entry.id}">表示</button>
                        <button type="button" class="ai-history-delete-btn px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700" data-id="${entry.id}">削除</button>
                    </div>
                </div>
                <div class="text-sm text-gray-600 mb-2">
                    <div class="truncate">${escapeHtml(entry.input.description || '').substring(0, 100)}${(entry.input.description || '').length > 100 ? '...' : ''}</div>
                </div>
                <div class="text-xs text-gray-500">
                    カテゴリ: ${entry.input.category || '未設定'} | 
                    推奨予算: ${entry.result.recommended_budget ? `${yen(entry.result.recommended_budget.min)} - ${yen(entry.result.recommended_budget.max)}` : '未設定'}
                </div>
            </div>
        `).join('');

        // イベントリスナーを設定
        document.querySelectorAll('.ai-history-view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.target.dataset.id);
                const entry = history.find(h => h.id === id);
                if (entry) {
                    renderResult(entry.result);
                    openDock();
                    closeHistory();
                }
            });
        });

        document.querySelectorAll('.ai-history-delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.target.dataset.id);
                const newHistory = history.filter(h => h.id !== id);
                localStorage.setItem(HISTORY_KEY, JSON.stringify(newHistory));
                renderHistory();
            });
        });
    }

    function openHistory() {
        if (historyModal) {
            historyModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            renderHistory();
        }
    }

    function closeHistory() {
        if (historyModal) {
            historyModal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function showLoading() {
        const loadingContent = `
            <div class="flex flex-col items-center justify-center py-12">
                <div class="relative">
                    <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <div class="text-lg font-semibold text-gray-900">AIディレクターが分析中...</div>
                    <div class="text-sm text-gray-600 mt-2">案件内容を分析して最適な提案を作成しています</div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                        <div id="ai-director-progress" class="ai-progress h-2" style="width:0%"></div>
                    </div>
                </div>
            </div>
        `;
        if (dockBody) dockBody.innerHTML = loadingContent;
    }

    function openDock() {
        if (dock) {
            dock.classList.add('open');
            dock.classList.remove('minimized');
        }
    }

    function closeDock() {
        if (dock) {
            dock.classList.remove('open', 'minimized');
        }
    }

    function minimizeDock() {
        if (dock) {
            dock.classList.toggle('minimized');
        }
    }

    // ヘッダークリックで最小化解除
    const dockHeader = dock?.querySelector('.ai-dock-header');
    dockHeader?.addEventListener('click', (e) => {
        // ボタンクリック時は除外
        if (e.target.closest('button')) return;
        if (dock?.classList.contains('minimized')) {
            dock.classList.remove('minimized');
        }
    });

    dockClose?.addEventListener('click', closeDock);
    dockMinimize?.addEventListener('click', minimizeDock);

    function setProgress(pct) {
        if (!bar) return;
        bar.style.width = `${pct}%`;
    }

    function burst(xp = 50) {
        if (!spark) return;
        spark.textContent = `+${xp} XP`;
        spark.classList.remove('opacity-0', 'scale-50');
        spark.classList.add('opacity-100', 'scale-100');
        setTimeout(() => {
            spark.classList.add('opacity-0', 'scale-50');
            spark.classList.remove('opacity-100', 'scale-100');
        }, 1000);
    }

    async function run() {
        // 入力収集
        const title = document.getElementById('title')?.value || '';
        const description = document.getElementById('description')?.value || '';
        const categoryId = document.getElementById('category_id')?.value || '';
        const categoryName = document.getElementById('category_id')?.selectedOptions?.[0]?.text || '';
        const budgetMin = parseInt(document.getElementById('budget_min')?.value || '0', 10);
        const budgetMax = parseInt(document.getElementById('budget_max')?.value || '0', 10);
        const durationWeeks = parseInt(document.getElementById('duration_weeks')?.value || '0', 10);
        const urgency = (document.querySelector('input[name="urgency"]:checked')?.value) || 'medium';

        // ボタンのローディング状態を適用
        if (window.loadingManager && btn) {
            window.loadingManager.setButtonLoading(btn, { text: 'AI分析中...' });
        } else if (btn) {
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'AI分析中...';
            btn.dataset.originalText = originalText;
        }

        openDock();
        showLoading();
        setProgress(8);

        try {
            // 擬似アニメーション（高性能っぽく）
            let fake = 8;
            const timer = setInterval(() => {
                fake = Math.min(92, fake + Math.random() * 6);
                setProgress(fake);
            }, 200);

            const res = await fetch('./api/ai-director.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({
                    title, description,
                    category_id: Number(categoryId),
                    category_name: categoryName,
                    budget_min: budgetMin,
                    budget_max: budgetMax,
                    duration_weeks: durationWeeks,
                    urgency,
                    csrf_token: csrf
                })
            });

            const json = await res.json();
            clearInterval(timer);
            setProgress(100);
            
            // ボタンのローディング状態を解除
            if (window.loadingManager && btn) {
                window.loadingManager.removeButtonLoading(btn);
            } else if (btn) {
                btn.disabled = false;
                if (btn.dataset.originalText) {
                    btn.textContent = btn.dataset.originalText;
                    delete btn.dataset.originalText;
                }
            }

            if (!json.success) {
                throw new Error(json.error || 'AI提案の取得に失敗しました');
            }

            const data = json.data || {};
            // 履歴に保存
            saveToHistory(data, {
                title: title,
                description: description,
                category_name: categoryName
            });
            // レンダリング
            renderResult(data);
            burst(json.meta?.xp_awarded || 50);
        } catch (e) {
            // ボタンのローディング状態を解除
            if (window.loadingManager && btn) {
                window.loadingManager.removeButtonLoading(btn);
            } else if (btn) {
                btn.disabled = false;
                if (btn.dataset.originalText) {
                    btn.textContent = btn.dataset.originalText;
                    delete btn.dataset.originalText;
                }
            }
            
            renderError(e?.message || 'AI処理中にエラーが発生しました');
        }
    }


    function renderError(message) {
        const errorContent = `
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <div class="font-semibold mb-1">エラー</div>
                <div class="text-sm">${escapeHtml(message)}</div>
            </div>
        `;
        if (dockBody) dockBody.innerHTML = errorContent;
    }

    function yen(n) {
        if (typeof n !== 'number' || isNaN(n)) return '-';
        return '¥' + Math.round(n).toLocaleString();
    }

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    }

    function copyText(text) {
        if (!text) {
            console.warn('コピーするテキストが空です');
            return;
        }
        
        console.log('コピー実行:', text.substring(0, 50) + '...');
        
        // 複数の方法を試行
        const copyMethods = [
            // 方法1: モダンなClipboard API
            () => {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text);
                }
                return Promise.reject('Clipboard API not available');
            },
            
            // 方法2: 非同期フォールバック
            () => {
                return new Promise((resolve, reject) => {
                    try {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.left = '-9999px';
                        textarea.style.top = '-9999px';
                        textarea.style.opacity = '0';
                        textarea.setAttribute('readonly', '');
                        document.body.appendChild(textarea);
                        
                        // フォーカスと選択
                        textarea.focus();
                        textarea.select();
                        textarea.setSelectionRange(0, text.length);
                        
                        const success = document.execCommand('copy');
                        document.body.removeChild(textarea);
                        
                        if (success) {
                            resolve();
                        } else {
                            reject('execCommand failed');
                        }
                    } catch (err) {
                        reject(err);
                    }
                });
            },
            
            // 方法3: 同期フォールバック
            () => {
                return new Promise((resolve, reject) => {
                    try {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'absolute';
                        textarea.style.left = '-9999px';
                        textarea.style.top = '-9999px';
                        document.body.appendChild(textarea);
                        
                        textarea.select();
                        textarea.setSelectionRange(0, 99999);
                        
                        const success = document.execCommand('copy');
                        document.body.removeChild(textarea);
                        
                        if (success) {
                            resolve();
                        } else {
                            reject('execCommand failed');
                        }
                    } catch (err) {
                        reject(err);
                    }
                });
            }
        ];
        
        // 各方法を順番に試行
        let methodIndex = 0;
        const tryNextMethod = () => {
            if (methodIndex >= copyMethods.length) {
                console.error('すべてのコピー方法が失敗しました');
                if (window.AiNAWorks?.showToast) window.AiNAWorks.showToast('コピーに失敗しました', 'error');
                return;
            }
            
            copyMethods[methodIndex]()
                .then(() => {
                    console.log(`コピー成功 (方法${methodIndex + 1})`);
                    if (window.AiNAWorks?.showToast) window.AiNAWorks.showToast('コピーしました', 'success');
                })
                .catch((err) => {
                    console.warn(`コピー方法${methodIndex + 1}失敗:`, err);
                    methodIndex++;
                    tryNextMethod();
                });
        };
        
        tryNextMethod();
    }

    function applyToForm(d) {
        const title = document.getElementById('title');
        const desc = document.getElementById('description');
        const min = document.getElementById('budget_min');
        const max = document.getElementById('budget_max');
        const weeks = document.getElementById('duration_weeks');
        
        // 改善サンプルがあればそれを使用、なければ元の改善タイトル・説明を使用
        if (d.improved_sample && desc) {
            // 改善サンプルから最初の行をタイトルとして使用
            if (title) {
                const firstLine = d.improved_sample.split('\n')[0];
                title.value = firstLine;
            }
            // 改善サンプル全体を説明欄に設定（タイトル行は除く）
            const lines = d.improved_sample.split('\n');
            const descriptionContent = lines.slice(1).join('\n').trim();
            desc.value = descriptionContent || d.improved_sample;
        } else {
            if (d.improved_title && title) title.value = d.improved_title;
            if (d.improved_description && desc) desc.value = d.improved_description;
        }
        
        if (d.recommended_budget) {
            if (typeof d.recommended_budget.min === 'number' && min) min.value = Math.round(d.recommended_budget.min);
            if (typeof d.recommended_budget.max === 'number' && max) max.value = Math.round(d.recommended_budget.max);
        }
        if (typeof d.timeline_weeks === 'number' && weeks) weeks.value = d.timeline_weeks;
        // フィードバック
        if (window.AiNAWorks?.showToast) {
            window.AiNAWorks.showToast('AI提案をフォームに反映しました', 'success');
        }
    }

    function renderResult(d) {
        if (!dockBody) return;
        window.__aiDirectorLastResult = d;
        const rec = d.recommended_budget || {};
        const items = Array.isArray(d.pricing_table) ? d.pricing_table : [];
        const bullets = Array.isArray(d.bullet_points) ? d.bullet_points : (Array.isArray(d.improvement_points) ? d.improvement_points : []);
        const scope = Array.isArray(d.scope_breakdown) ? d.scope_breakdown : [];
        const tags = Array.isArray(d.tags) ? d.tags : [];

        // ドック用のコンテンツ（添削・改善サンプル版）
        const dockContent = `
            ${generateDockSection('💰 推奨予算', `${escapeHtml(yen(rec.min))} - ${escapeHtml(yen(rec.max))} (${escapeHtml(String(d.timeline_weeks || '-'))}週)`, null, null)}
            ${d.ai_review ? generateDockSection('📝 AIディレクターの添削', escapeHtml(String(d.ai_review)).replace(/\n/g, '<br>'), 'copyText', String(d.ai_review)) : ''}
            ${d.improved_sample ? generateDockSection('✨ 改善サンプル', escapeHtml(String(d.improved_sample)).replace(/\n/g, '<br>'), 'copyText', String(d.improved_sample)) : ''}
            ${bullets.length ? generateDockSection('💡 改善ポイント', bullets.map(b => `• ${escapeHtml(String(b))}`).join('<br>'), null, null) : ''}
            ${tags.length ? generateDockSection('🏷️ タグ', tags.map(t => `<span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs mr-1 mb-1">${escapeHtml(t)}</span>`).join(''), null, null) : ''}
            <div class="ai-result-section">
                <div class="ai-result-header">
                    <span class="font-medium text-gray-700">📋 フォーム反映</span>
                </div>
                <div class="ai-result-content">
                    <button onclick="applyToForm(window.__aiDirectorLastResult)" class="ai-apply-btn">フォームへ反映</button>
                </div>
            </div>
        `;

        // ドックに表示
        if (dockBody) dockBody.innerHTML = dockContent;
    }

    function generateDockSection(title, content, action, actionData) {
        const sectionId = 'section-' + Math.random().toString(36).substr(2, 9);
        const actionButton = action ? `<button class="ai-copy-btn" data-copy-text="${escapeHtml(actionData || '')}">コピー</button>` : '';
        
        const html = `
            <div class="ai-result-section" id="${sectionId}">
                <div class="ai-result-header">
                    <span class="font-medium text-gray-700">${title}</span>
                    ${actionButton}
                </div>
                <div class="ai-result-content">
                    <div class="text-sm text-gray-800">${content}</div>
                </div>
            </div>
        `;
        
        // イベントリスナーを設定
        setTimeout(() => {
            const section = document.getElementById(sectionId);
            if (section) {
                const copyBtn = section.querySelector('.ai-copy-btn');
                if (copyBtn) {
                    copyBtn.addEventListener('click', function() {
                        const text = this.getAttribute('data-copy-text');
                        copyText(text);
                    });
                }
            }
        }, 0);
        
        return html;
    }



    // グローバル関数として定義（onclickで使用）
    window.copyText = copyText;
    window.applyToForm = applyToForm;

    // イベントリスナー
    btn.addEventListener('click', run);
    
    // 履歴ボタン
    historyBtn?.addEventListener('click', openHistory);
    historyClose?.addEventListener('click', closeHistory);
    
    // 履歴モーダルの背景クリックで閉じる
    historyModal?.addEventListener('click', (e) => {
        if (e.target === historyModal) {
            closeHistory();
        }
    });
});



