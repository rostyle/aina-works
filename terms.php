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
      <div class="mt-4 text-sm text-gray-500">最終更新日：<?= date('Y年m月d日') ?></div>
    </div>

    <!-- Content -->
    <div class="bg-white rounded-2xl shadow-lg p-8 lg:p-12">
      <div class="prose prose-lg max-w-none">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">第1条（適用）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          本利用規約（以下「本規約」）は、株式会社AiNA（以下「当社」）が提供する「<?= h(SITE_NAME) ?>」（以下「本サービス」）の利用条件を定めるものです。ユーザー（定義は第2条の4参照）は、本サービスを利用することで本規約に同意したものとみなされます。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第2条（利用登録）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          1. 利用希望者は、当社の定める方法で登録申請を行い、当社が承認した時点で登録が完了します。<br>
          2. 当社は、過去の規約違反、虚偽申告、反社会的勢力関与の疑いその他不適切と判断した場合、登録を拒否・保留できるものとします。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第2条の2（年齢等の利用条件）</h2>
        <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
          <li>基本利用年齢は18歳以上とします。</li>
          <li>18歳未満（以下「未成年者」）は、親権者等の法定代理人の包括的同意（氏名・連絡先・同意日を含む）がある場合に限り、当社の承認のもとで利用できます。未成年者が同意なく締結した契約は取り消され得るため、当社は当該取引を停止・無効化できます。</li>
          <li>13歳未満は本サービスを利用できません。</li>
          <li>未成年者は、アダルト・出会い・ギャンブル・酒/たばこ関連・医療行為・年齢制限広告等、当社が不適切と判断する案件を受注できません。</li>
          <li>未成年者の22:00〜翌5:00の打合せ・納品・チャット対応・通話等の実施を禁止します。</li>
        </ul>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第2条の3（本人確認・制裁対応）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社は、犯罪収益移転防止、年齢確認、制裁等遵守のため、本人確認（KYC）・年齢確認・名義一致確認を求めることがあり、未提出・虚偽が判明した場合は利用停止・取引取消し・通報等の措置を行います。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第2条の4（定義と契約形態）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          本サービスは、発注者と受注者のオンライン取引を仲介・支援するプラットフォームです。両者間の契約は原則として請負または委任（準委任）であり、労働契約ではありません。ただし、実態により法令上「労働者性」等が認定される場合は、その法令が優先します。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第3条（アカウント管理）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          ユーザーは自己責任でID・パスワードを管理し、第三者への貸与・譲渡・共用を行わないものとします。不正利用の疑いがある場合は直ちに当社へ通知してください。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第4条（禁止事項）</h2>
        <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
          <li>法令・公序良俗に違反する行為（未成年者への深夜業務要求、危険有害業務従事の斡旋、名誉毀損・差別・ハラスメント、虚偽広告・ステマ等を含む）</li>
          <li>知的財産・肖像・プライバシーその他権利の侵害</li>
          <li>不正アクセス、スパム、マルウェア配布、過度なスクレイピング等の技術的妨害</li>
          <li>虚偽の年齢・身分・経歴の申告、身分証の不正提出、評価操作</li>
          <li>アダルト・出会い・ギャンブル等、当社が不適切と判断する募集・受注</li>
          <li>輸出管理・経済制裁等に違反する依頼・納品・取引</li>
          <li>その他、当社が不適切と判断する行為</li>
        </ul>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第5条（監視義務の不存在と削除権限）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          1. 当社は投稿・通信の事前監視義務を負いません。<br>
          2. ただし当社は、法令違反・権利侵害・本規約違反・当社基準に照らし不適切と判断した情報について、事前通知なく表示停止・削除・検索結果からの除外・アカウント停止等の措置を講じることができます。<br>
          3. 当社は、違反の有無を独自に判断でき、必要に応じてログの保全・第三者提供（適法な要請に限る）・関係機関への通報を行います。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第6条（権利侵害への対応：ノーティス&テイクダウン）</h2>
        <p class="mb-4 text-gray-700 leading-relaxed">
          当社は、「特定電気通信役務提供者の損害賠償責任の制限及び発信者情報の開示に関する法律」（いわゆるプロバイダ責任制限法）に沿い、権利侵害情報の通報を受け付けます。通報には、①侵害された権利、②対象URL・当該箇所、③侵害理由、④通報者の氏名・連絡先、⑤裏付資料、⑥送信防止措置の要請有無を含めてください。当社は相当と判断した場合、送信防止措置や発信者情報の開示手続に対応します。虚偽通報はお控えください。
        </p>
        <p class="mb-6 text-gray-700 leading-relaxed">
          反復的に権利侵害等を行うユーザーに対しては、再発防止のためのアカウント停止・登録抹消等の措置（反復違反者ポリシー）を適用します。適法な反論通知があった場合は、相当な範囲で復旧措置を検討します。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第7条（取引の公正化・表示義務）</h2>
        <p class="mb-4 text-gray-700 leading-relaxed">
          発注者は、募集・契約締結時に、業務内容、成果物要件、対価額・算定方法、検収基準、納期、支払期日、再委託可否、守秘義務、知的財産の帰属、連絡手段、解約条件等を明示し、電磁的方法により提示・保存するものとします。虚偽・誇大表示やステルスマーケティングは禁止します（景品表示法、広告関連ガイドラインの趣旨に従う）。
        </p>


        <h2 class="text-2xl font-bold text-gray-900 mb-6">第8条（ハラスメント防止・健全化）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社は通報窓口の設置等により健全な環境構築に努めます。ユーザーはあらゆるハラスメント行為をしてはなりません。継続的な取引の中断・解約が必要な場合は、相手方の利益を不当に害さないよう誠実に対応してください。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第9条（著作権・成果物の取扱い）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          投稿・納品物は、必要な権利を有するか適法な許諾を得たものに限ります。成果物の権利帰属・著作者人格権不行使の範囲等は当事者間の合意に従います。当社は原則として当該契約の当事者ではありません。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第10条（電気通信事業法上の外部送信の表示）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社は、クッキー等により利用者端末から外部送信される情報について、電気通信事業法の外部送信規律に基づく「外部送信ポリシー」を別途掲示します。ユーザーは同ポリシーを確認のうえ利用してください。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第11条（免責）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          1. 当社は、本サービスに事実上または法律上の瑕疵がないことを保証しません。ユーザー間または第三者との取引・連絡・紛争等について、当社は責任を負いません。<br>
          2. ただし、当社の故意または重過失による場合はこの限りではありません（消費者契約法その他の強行法規に従い、無効となる範囲の免責は主張しません）。<br>
          3. 当社は、天災地変、停電、通信障害、法令・行政要請、感染症等の不可抗力に起因して生じた損害について責任を負いません。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第12条（責任の範囲）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社がユーザーに対して負う損害賠償責任は、当社の軽過失による場合に限り、現実かつ直接の通常損害に限定され、その上限は、当該損害発生直近12か月間にユーザーが当社に支払った本サービス手数料の総額を上限とします。消費者契約法その他の法令により無効となる範囲では適用しません。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第13条（サービスの変更・停止）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社は、保守・障害・安全確保・法令対応等やむを得ない事由がある場合、事前通知なく本サービスの全部または一部を変更・停止できます。重要な変更については、可能な限り<span class="font-semibold">30日前</span>までに告知します（緊急対応を除く）。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第14条（規約違反時の措置・異議申立て）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社は、規約違反またはそのおそれがある場合、投稿削除、検索除外、機能制限、支払保留、アカウント停止・抹消等の措置を講じることができます。措置に対する異議は、当社所定のフォームから合理的期間内に申立てることができます。当社は安全確保・被害拡大防止を優先し、異議審査中も暫定措置を継続できます。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第15条（個人情報の取扱い）</h2>
        <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
          <li>当社は、個人情報を「プライバシーポリシー」および関連法令（個人情報保護法等）に従い適切に取扱います。</li>
          <li>16歳未満の個人情報は、原則として法定代理人同意の下で取扱います。</li>
          <li>第三者提供・国外移転・委託先管理については、法令に従った安全管理措置を講じます。</li>
        </ul>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第16条（フリーランス取引適正化への対応）</h2>
        <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
          <li>当社は、フリーランスと事業者間の取引の適正化および就業環境の整備に関する法令の趣旨に沿い、<u>契約条件の明示</u>、<u>不当な受領拒否・報酬減額・やり直し強制等の禁止</u>、<u>募集情報の適正表示</u>、<u>ハラスメント防止</u>に関する機能・ガイドラインを提供します。</li>
          <li>発注者は、当該法令および当社ガイドラインに従い、必要な情報の表示・通知・保存等を適切に行うものとします。</li>
        </ul>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第17条（特商法・景表法等の遵守）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          物品販売や役務提供に該当する募集・表示を行う場合、当該ユーザーは特定商取引法・景品表示法等に基づく表示義務（事業者情報、対価、返品条件等）を自ら履行し、当社が求める場合は根拠資料を提示するものとします。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第18条（秘密情報・競業避止）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          ユーザーは、取引過程で知り得た相手方・当社の秘密情報を第三者に開示・利用してはなりません。公知の情報等は除きます。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第19条（反社会的勢力の排除）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          ユーザーは、反社会的勢力に該当せず、関与もしないことを表明・保証します。違反が判明した場合、当社は催告なく利用停止・契約解除等の措置を行えます。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第20条（権利義務の譲渡禁止）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          当社の事前承諾なく、利用契約上の地位および本規約に基づく権利義務を譲渡・担保提供できません。事業譲渡・会社分割等に伴う当社側の承継はこの限りではありません。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第21条（準拠法・裁判管轄・紛争解決）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          本規約の準拠法は日本法とします。本サービスに関して紛争が生じ、協議で解決しない場合、東京地方裁判所を第一審の専属的合意管轄裁判所とします。
        </p>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">第22条（分離可能性・存続条項）</h2>
        <p class="mb-6 text-gray-700 leading-relaxed">
          本規約の一部が無効・取消しとなった場合でも、残部は継続して有効とします。秘密保持、知財、免責・責任制限、準拠法・管轄等の条項は利用契約終了後も存続します。
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
