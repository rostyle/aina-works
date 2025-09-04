<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">利用規約</h1>
            <p class="text-xl text-gray-600"><?= h(SITE_NAME) ?>の利用に関する規約</p>
            <div class="mt-4 text-sm text-gray-500">
                最終更新日：<?= date('Y年m月d日') ?>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-2xl shadow-lg p-8 lg:p-12">
            <div class="prose prose-lg max-w-none">
                
                <h2 class="text-2xl font-bold text-gray-900 mb-6">第1条（適用）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    本利用規約（以下「本規約」といいます。）は、株式会社AiNA（以下「当社」といいます。）が提供する「<?= h(SITE_NAME) ?>」（以下「本サービス」といいます。）の利用条件を定めるものです。本サービスをご利用になる場合には、本規約に同意いただいたものとみなします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第2条（利用登録）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    本サービスの利用を希望する方は、本規約に同意の上、当社の定める方法によって利用登録を申請し、当社がこれを承認することによって、利用登録が完了するものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第3条（ユーザーIDおよびパスワードの管理）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    ユーザーは、自己の責任において、本サービスのユーザーIDおよびパスワードを適切に管理するものとします。ユーザーは、いかなる場合にも、ユーザーIDおよびパスワードを第三者に譲渡または貸与し、もしくは第三者と共用することはできません。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第4条（禁止事項）</h2>
                <p class="mb-4 text-gray-700 leading-relaxed">ユーザーは、本サービスの利用にあたり、以下の行為をしてはなりません。</p>
                <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
                    <li>法令または公序良俗に違反する行為</li>
                    <li>犯罪行為に関連する行為</li>
                    <li>本サービスの内容等、本サービスに含まれる著作権、商標権ほか知的財産権を侵害する行為</li>
                    <li>当社、ほかのユーザー、またはその他第三者のサーバーまたはネットワークの機能を破壊したり、妨害したりする行為</li>
                    <li>本サービスによって得られた情報を商業的に利用する行為</li>
                    <li>当社のサービスの運営を妨害するおそれのある行為</li>
                    <li>不正アクセスをし、またはこれを試みる行為</li>
                    <li>他のユーザーに関する個人情報等を収集または蓄積する行為</li>
                    <li>不正な目的を持って本サービスを利用する行為</li>
                    <li>本サービスの他のユーザーまたはその他の第三者に不利益、損害、不快感を与える行為</li>
                    <li>その他、当社が不適切と判断する行為</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第5条（本サービスの提供の停止等）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、以下のいずれかの事由があると判断した場合、ユーザーに事前に通知することなく本サービスの全部または一部の提供を停止または中断することができるものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第6条（著作権）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    ユーザーは、自ら著作権等の必要な知的財産権を有するか、または必要な権利者の許諾を得た文章、画像や映像等の情報に関してのみ、本サービスを利用し、投稿ないしアップロードすることができるものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第7条（利用制限および登録抹消）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、ユーザーが以下のいずれかに該当する場合には、事前の通知なく、投稿データを削除し、ユーザーに対して本サービスの全部もしくは一部の利用を制限しまたはユーザーとしての登録を抹消することができるものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第8条（退会）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    ユーザーは、当社の定める退会手続により、本サービスから退会できるものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第9条（保証の否認および免責事項）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、本サービスに事実上または法律上の瑕疵（安全性、信頼性、正確性、完全性、有効性、特定の目的への適合性、セキュリティなどに関する欠陥、エラーやバグ、権利侵害などを含みます。）がないことを明示的にも黙示的にも保証しておりません。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第10条（サービス内容の変更等）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、ユーザーに通知することなく、本サービスの内容を変更しまたは本サービスの提供を中止することができるものとし、これによってユーザーに生じた損害について一切の責任を負いません。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第11条（利用規約の変更）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、必要と判断した場合には、ユーザーに通知することなくいつでも本規約を変更することができるものとします。なお、本規約の変更後、本サービスの利用を開始した場合には、当該ユーザーは変更後の規約に同意したものとみなします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第12条（個人情報の取扱い）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    当社は、本サービスの利用によって取得する個人情報については、当社「プライバシーポリシー」に従い適切に取り扱うものとします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第13条（通知または連絡）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    ユーザーと当社との間の通知または連絡は、当社の定める方法によって行うものとします。当社は、ユーザーから、当社が別途定める方式に従った変更届け出がない限り、現在登録されている連絡先が有効なものとみなして当該連絡先へ通知または連絡を行い、これらは、発信時にユーザーへ到達したものとみなします。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第14条（権利義務の譲渡の禁止）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    ユーザーは、当社の書面による事前の承諾なく、利用契約上の地位または本規約に基づく権利もしくは義務を第三者に譲渡し、または担保に供することはできません。
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-6">第15条（準拠法・裁判管轄）</h2>
                <p class="mb-6 text-gray-700 leading-relaxed">
                    本規約の解釈にあたっては、日本法を準拠法とします。本サービスに関して紛争が生じた場合には、東京地方裁判所を専属的合意管轄とします。
                </p>

                <!-- Contact Information -->
                <div class="mt-12 p-6 bg-gray-50 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">お問い合わせ先</h3>
                    <div class="text-gray-700">
                        <p class="mb-2"><strong>株式会社AiNA</strong></p>
                        <p class="mb-2">メール：info@ai-na.co.jp</p>
                        <p class="mb-2">電話：03-6894-4209（平日10:00-17:00）</p>
                        <p>住所：〒108-0075 東京都港区港南2-16-1 品川East One Tower 7F・8F</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top -->
        <div class="text-center mt-8">
            <a href="<?= url('index.php') ?>" 
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
