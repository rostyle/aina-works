<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">プライバシーポリシー</h1>
            <p class="text-xl text-gray-600"><?= h(SITE_NAME) ?>における個人情報の取り扱いについて</p>
            <div class="mt-4 text-sm text-gray-500">
                最終更新日：<?= date('Y年m月d日') ?>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-2xl shadow-lg p-8 lg:p-12">
            <div class="prose prose-lg max-w-none">
                
                <h2 class="text-2xl font-bold text-gray-900 mb-6">1. 基本方針</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    株式会社AiNA（以下「当社」といいます。）は、当社が提供する「<?= h(SITE_NAME) ?>」（以下「本サービス」といいます。）において、ユーザーの個人情報の保護を重要な責務と考え、個人情報保護法、その他の関係法令を遵守すると共に、以下のプライバシーポリシー（以下「本ポリシー」といいます。）に従って、個人情報を適切に取り扱います。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">2. 個人情報の定義</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    本ポリシーにおいて「個人情報」とは、個人情報保護法第2条第1項により定義された個人情報、すなわち、生存する個人に関する情報であって、当該情報に含まれる氏名、生年月日その他の記述等により特定の個人を識別することができるもの（他の情報と容易に照合することができ、それにより特定の個人を識別することができることとなるものを含みます。）、または個人識別符号が含まれるものを指します。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">3. 個人情報の収集</h2>
                <p class="mb-4 text-gray-700 leading-relaxed">当社は、以下の場合に個人情報を収集することがあります。</p>
                <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
                    <li>ユーザー登録時</li>
                    <li>お問い合わせ時</li>
                    <li>作品投稿時</li>
                    <li>案件応募時</li>
                    <li>その他、本サービスの利用時</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">4. 収集する個人情報の種類</h2>
                <p class="mb-4 text-gray-700 leading-relaxed">当社が収集する個人情報は以下の通りです。</p>
                <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
                    <li>氏名、ニックネーム</li>
                    <li>メールアドレス</li>
                    <li>電話番号</li>
                    <li>住所</li>
                    <li>生年月日</li>
                    <li>職業、所属</li>
                    <li>プロフィール情報</li>
                    <li>作品・ポートフォリオ</li>
                    <li>IPアドレス、ブラウザ情報</li>
                    <li>Cookie情報</li>
                    <li>その他、本サービス利用に関する情報</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">5. 個人情報の利用目的</h2>
                <p class="mb-4 text-gray-700 leading-relaxed">当社は、収集した個人情報を以下の目的で利用します。</p>
                <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
                    <li>本サービスの提供・運営</li>
                    <li>ユーザー認証・管理</li>
                    <li>お問い合わせ対応</li>
                    <li>案件のマッチング</li>
                    <li>決済処理</li>
                    <li>本サービスの改善・開発</li>
                    <li>マーケティング・広告配信</li>
                    <li>不正利用の防止・セキュリティ確保</li>
                    <li>法令遵守</li>
                    <li>その他、本サービスの提供に必要な業務</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">6. 個人情報の第三者提供</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、以下の場合を除き、ユーザーの同意なく個人情報を第三者に提供することはありません。
                </p>
                <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
                    <li>法令に基づく場合</li>
                    <li>人の生命、身体または財産の保護のために必要がある場合</li>
                    <li>公衆衛生の向上または児童の健全な育成の推進のために特に必要がある場合</li>
                    <li>国の機関もしくは地方公共団体またはその委託を受けた者が法令の定める事務を遂行することに対して協力する必要がある場合</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">7. 個人情報の委託</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、本サービスの提供に必要な範囲で、個人情報の取扱いを外部に委託する場合があります。この場合、委託先に対して適切な監督を行います。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">8. Cookieについて</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    本サービスでは、ユーザーの利便性向上のためCookieを使用する場合があります。Cookieの使用を望まない場合は、ブラウザの設定でCookieを無効にすることができますが、本サービスの一部機能が利用できなくなる可能性があります。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">9. アクセス解析ツールについて</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    本サービスでは、Google Analyticsなどのアクセス解析ツールを使用しています。これらのツールはCookieを使用してユーザーの行動を分析しますが、個人を特定する情報は含まれません。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">10. 個人情報の開示・訂正・削除</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    ユーザーは、当社が保有する自己の個人情報について、開示、訂正、削除を求めることができます。これらの請求については、下記のお問い合わせ先までご連絡ください。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">11. 個人情報の安全管理</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、個人情報の漏洩、滅失、毀損の防止その他の個人情報の安全管理のために必要かつ適切な措置を講じます。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">12. 個人情報の保存期間</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、個人情報を利用目的の達成に必要な期間のみ保存し、不要となった個人情報は適切に削除または廃棄します。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">13. 未成年者の個人情報</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、未成年者から個人情報を収集する場合、保護者の同意を得ることを原則とします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">14. プライバシーポリシーの変更</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、法令の変更や本サービスの改善等に伴い、本ポリシーを変更する場合があります。変更後のプライバシーポリシーは、本サイトに掲載した時点で効力を生じるものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">15. 個人情報保護管理者</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、個人情報の適切な管理を行うため、個人情報保護管理者を設置しています。
                </p>

                <!-- Contact Information -->
                <div class="mt-12 p-6 bg-gray-50 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">個人情報に関するお問い合わせ先</h3>
                    <div class="text-gray-700">
                        <p class="mb-2"><strong>株式会社AiNA</strong></p>
                        <p class="mb-2">メール：privacy@ai-na.co.jp</p>
                        <p class="mb-2">電話：03-6894-4209（平日10:00-17:00）</p>
                        <p>住所：〒108-0075 東京都港区港南2-16-1 品川East One Tower 7F・8F</p>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-blue-50 rounded-xl">
                    <p class="text-sm text-blue-800">
                        <strong>制定日：</strong>2025年9月4日<br>
                        <strong>最終改定日：</strong>2025年9月4日
                    </p>
                </div>
            </div>
        </div>

        <!-- Back to Top -->
        <div class="text-center mt-8">
            <a href="<?= url() ?>" 
               class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl transition-all duration-300 hover:scale-105 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                ホームに戻る
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
