<?php
require_once 'config/config.php';

$pageTitle = 'AiNA Works 激安外注サービス - 雑務・制作代行';
$pageDescription = '漫画制作、動画編集、事務代行。実績作りのために全力で取り組むクリエイターが、あなたのビジネスを激安価格でサポートします。';
$lineUrl = 'https://lin.ee/OZnPlVB';

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden bg-slate-900 text-white">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center animate-pulse-slow transform-gpu" 
             style="background-image: url('<?= asset('images/hero-background.jpg') ?>'); opacity: 0.3;">
        </div>
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-blue-900/40 to-slate-900"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center px-6 py-2 bg-yellow-400/10 backdrop-blur-md rounded-full border border-yellow-400/20 text-yellow-300 font-bold mb-8 shadow-[0_0_15px_rgba(250,204,21,0.3)] animate-bounce-slow">
            <span class="mr-2">⚡</span> 圧倒的コストパフォーマンス
        </div>

        <h1 class="text-6xl md:text-8xl font-black tracking-tighter mb-8 leading-tight drop-shadow-2xl">
            その雑務、<br />
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500">激安価格</span>
            で外注しませんか？
        </h1>

        <p class="mt-8 text-xl md:text-2xl text-slate-200 max-w-3xl mx-auto leading-relaxed font-light">
            クリエイティブから事務作業まで。<br />
            実績作りのために全力で取り組むワーカーが、あなたのビジネスを支えます。
        </p>

        <div class="mt-12 flex flex-col sm:flex-row gap-6 justify-center">
            <a href="#pricing" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full text-white font-bold text-lg hover:shadow-lg hover:shadow-blue-500/50 transition-all transform hover:-translate-y-1">
                料金を見る
            </a>
            <a href="<?= $lineUrl ?>" target="_blank" class="px-8 py-4 bg-[#06C755] rounded-full text-white font-bold text-lg hover:shadow-lg hover:shadow-green-500/50 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2">
                <span>LINEで相談する</span>
            </a>
        </div>
    </div>
</section>

<!-- Why AiNA Works? Section -->
<section class="py-24 bg-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
                AiNA Worksが<span class="text-blue-600">選ばれる理由</span>
            </h2>
            <p class="text-slate-600">AIスクール運営会社が提供する、安心と信頼のサービス体制</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <div class="text-center group">
                <div class="relative w-48 h-48 mx-auto mb-6 rounded-full overflow-hidden border-4 border-blue-50 shadow-lg group-hover:shadow-xl transition-shadow">
                    <img src="<?= asset('images/lp_trust.png') ?>" alt="運営の安心感" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                </div>
                <h3 class="text-xl font-bold mb-4">AIスクール運営の安心感</h3>
                <p class="text-slate-600 leading-relaxed text-sm">
                    AIスクールを運営する母体がバックアップ。ハズレワーカーに当たるリスクを最小限に抑え、スムーズな取引をお約束します。
                </p>
            </div>
            <div class="text-center group">
                <div class="relative w-48 h-48 mx-auto mb-6 rounded-full overflow-hidden border-4 border-blue-50 shadow-lg group-hover:shadow-xl transition-shadow">
                    <img src="<?= asset('images/lp_worker.png') ?>" alt="意欲的なワーカー" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                </div>
                <h3 class="text-xl font-bold mb-4">「実績が欲しい」から全力</h3>
                <p class="text-slate-600 leading-relaxed text-sm">
                    子育てママなどの会員が、実績作りのために全力で取り組みます。「安くても実績が欲しい」という強いモチベーションがあるため、価格以上の熱量で貢献します。
                </p>
            </div>
            <div class="text-center group">
                <div class="relative w-48 h-48 mx-auto mb-6 rounded-full overflow-hidden border-4 border-blue-50 shadow-lg group-hover:shadow-xl transition-shadow">
                    <img src="<?= asset('images/lp_support.png') ?>" alt="プロ講師の検修" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                </div>
                <h3 class="text-xl font-bold mb-4">プロ講師がクオリティ担保</h3>
                <p class="text-slate-600 leading-relaxed text-sm">
                    制作にはスクールの講師やスタッフがアドバイザーとしてサポート。一定以上のクオリティを維持できる体制を整えています。
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-24 bg-slate-50 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-20">
            <h2 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                過去案件<span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">成約単価</span>
            </h2>
            <p class="text-xl text-slate-500">圧倒的なコストパフォーマンスの成約参考例です。</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">COMIC / WEB</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">漫画LPサイト制作</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥100,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">COMIC / MOVIE</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">YouTube広告漫画動画</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥50,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">COMIC / MOVIE</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">4コマ漫画を動画化</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥30,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">WEB</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">HP・LP作成</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥35,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">COMIC / PRINT</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">漫画チラシ・資料</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥5,000</span><span class="text-slate-500 ml-2">〜 / 1P</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">MOVIE</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">動画編集</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥5,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-red-600 font-bold text-sm mb-4">ANIME / COMIC</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">販促画像制作</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥4,000</span><span class="text-slate-500 ml-2"> / 1点</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">DESIGN</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">チラシ作成</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥2,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">MOVIE</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">画像を動画化</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥1,000</span><span class="text-slate-500 ml-2"> / 1点</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">OFFICE</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">入力・事務業務</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥1,000</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
            <div class="bg-white rounded-3xl p-8 hover:shadow-xl transition-all border border-slate-100">
                <div class="text-blue-600 font-bold text-sm mb-4">DESIGN</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">バナー作成</h3>
                <div class="flex items-baseline"><span class="text-3xl font-extrabold text-slate-900">¥500</span><span class="text-slate-500 ml-2">〜</span></div>
            </div>
        </div>
    </div>
</section>

<!-- Features / Other Services -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
            <div class="relative">
                 <div class="w-full h-80 bg-gradient-to-br from-blue-100 to-purple-100 rounded-2xl relative overflow-hidden">
                     <div class="absolute inset-0 bg-[url('<?= asset('images/pattern.svg') ?>')] opacity-10"></div>
                     <div class="absolute inset-0 flex items-center justify-center p-8">
                        <div class="bg-white/80 backdrop-blur-md p-6 rounded-xl shadow-lg border border-white">
                            <p class="text-slate-800 font-bold mb-2">主な対応可能業務</p>
                            <ul class="text-slate-600 grid grid-cols-2 gap-x-8 gap-y-2">
                                <li>・SNS用画像</li><li>・YTサムネイル</li><li>・文字起こし</li><li>・資料作成</li><li>・ショート動画</li><li>・その他雑務</li>
                            </ul>
                        </div>
                     </div>
                 </div>
            </div>
            <div>
                <span class="text-blue-600 font-bold tracking-wider uppercase mb-2 block">Others</span>
                <h2 class="text-4xl font-bold text-slate-900 mb-6">「こんなことも頼める？」にお答えします</h2>
                <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                    手間のかかる単純作業から、クリエイティブな制作まで幅広く対応。AIと人の力を組み合わせることで、圧倒的な低価格と安心感を両立しました。
                </p>
                <a href="<?= $lineUrl ?>" target="_blank" class="text-blue-600 font-bold hover:text-blue-700 inline-flex items-center gap-2 group">
                    LINEで相談してみる
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 text-white text-center relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <h2 class="text-4xl md:text-6xl font-bold mb-8">
            <span class="block text-2xl md:text-3xl lg:text-4xl text-blue-300 font-normal mb-4">スクールバックアップの安心感</span>
            あなたのビジネスを、<br />もっと加速させる。
        </h2>
        <div class="flex justify-center mt-12">
            <a href="<?= $lineUrl ?>" target="_blank" class="px-10 py-5 bg-[#06C755] text-white rounded-full font-bold text-xl hover:shadow-[0_0_30px_rgba(6,199,85,0.4)] transition-all transform hover:-translate-y-1 flex items-center gap-3">
                <span>LINE公式アカウントで相談</span>
            </a>
        </div>
        <p class="mt-8 text-slate-400 text-sm">※ クラウドソーシングサービスに対する案件募集です</p>
    </div>
</section>

<style>
    @keyframes pulse-slow { 0%, 100% { opacity: 0.3; } 50% { opacity: 0.4; } }
    .animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
    @keyframes bounce-slow { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    .animate-bounce-slow { animation: bounce-slow 3s infinite; }
</style>

<?php include 'includes/footer.php'; ?>

