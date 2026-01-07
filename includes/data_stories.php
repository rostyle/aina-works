<?php
// 会員記事データ
$interviews = [
    1 => [
        'id' => 1,
        'category' => '動画編集',
        'type' => '副業スタート',
        'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&q=80&w=600',
        'hero_image' => 'https://images.unsplash.com/photo-1603513492128-ba7bc9b3e143?auto=format&fit=crop&q=80&w=1200',
        'result' => '月収 35万円 達成',
        'title' => '営業職の傍ら、夜の3時間で動画編集。未経験から半年で本業に迫る副収入を実現した話。',
        'intro' => '日中はメーカーの営業職として働く山本さん。将来への不安から「手に職をつけたい」とAi Native Academyに入会。全くの未経験からスタートし、わずか半年で月収35万円（副業のみ）を達成した軌跡を伺いました。',
        'name' => 'T. Yamamoto',
        'role' => '会社員 / 副業クリエイター',
        'date' => '2025.11.28',
        'body' => [
            [
                'h' => 'きっかけは「AIなら自分にもできるかも」という直感。',
                'p' => '元々パソコン作業は得意な方ではありませんでした。でも、ニュースで生成AIのことを知り、「これならセンスのない自分でも、クリエイティブな仕事ができるんじゃないか」と思ったのがきっかけです。AiNAのカリキュラムは実践的で、最初の1ヶ月で「AIを使った動画制作」の基礎が叩き込まれました。'
            ],
            [
                'h' => '本業との両立。鍵は「徹底的な時短」。',
                'p' => '平日は夜の21時から24時までの3時間しか作業時間が取れません。だからこそ、AIツール（RunwayやMidjourney）をフル活用しました。普通なら10時間かかる編集作業も、AIを使えば2時間で終わる。この「時給単価の高さ」が、副業で成功できた一番の理由だと思います。'
            ],
            [
                'h' => '初報酬の感動と、これからの目標。',
                'p' => '初めて納品して振り込まれた5,000円は、額面以上に重みがありました。「自分のスキルでお金を稼げた」という自信がついたんです。今は法人案件も増え、月30万円代が安定してきました。来年にはフリーランスとして独立し、場所にとらわれない働き方を実現したいです。'
            ]
        ]
    ],
    2 => [
        'id' => 2,
        'category' => '広報・SNS',
        'type' => '主婦・ママ',
        'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=600',
        'hero_image' => 'https://images.unsplash.com/photo-1588669670692-75d342f15569?auto=format&fit=crop&q=80&w=1200',
        'result' => '在宅で月12万',
        'title' => '「子供との時間を守りたい」完全在宅のSNS運用代行で、パート時代の収入を軽々超えました。',
        'intro' => '2人のお子さんを育てる田中さん。外に働きに出るのが難しい中、在宅ワークを模索。AiNAで身につけた「ChatGPTを使ったSNS運用術」で、隙間時間を活用しながら企業の広報担当として活躍されています。',
        'name' => 'M. Tanaka',
        'role' => '2児のママ / SNS運用',
        'date' => '2025.11.25',
        'body' => [
            [
                'h' => '「子供が寝たあとの1時間」が私のオフィス。',
                'p' => '飲食店でのパートを考えていましたが、子供の急な発熱などで休むのが申し訳なくて...。在宅でできる仕事を探していた時にAiNAに出会いました。スマホとChatGPTがあれば、子供を寝かしつけながらでも投稿案が作れる。この働き方は私にとって革命的でした。'
            ],
            [
                'h' => 'AI運用で「共感」を生むテクニック。',
                'p' => '最初は「AIの文章なんて冷たいんじゃない？」と半信半疑でした。でも、プロンプト（指示出し）次第で、すごく人間味のある文章が作れるんです。クライアントのトーン＆マナーを学習させることで、今では「田中さんの文章、ファンが多いですよ」と言われるまでになりました。'
            ],
            [
                'h' => '自分のペースで働き、自信を取り戻す。',
                'p' => '今は3社のSNSアカウントを運用しています。収入はパート時代の倍以上。何より、「社会と繋がっている」「必要とされている」という実感が、毎日の育児のモチベーションにもなっています。'
            ]
        ]
    ],
    3 => [
        'id' => 3,
        'category' => 'AI画像生成',
        'type' => 'フリーランス転向',
        'image' => 'https://images.unsplash.com/photo-1522075469751-3a3694c2dd88?auto=format&fit=crop&q=80&w=600',
        'result' => '最高月収 80万円',
        'title' => 'ニッチな「AI画像生成」に特化して独立。クリエイティブの常識を変える、私の生存戦略。',
        'name' => 'S. Fujimoto',
        'role' => 'AIクリエイター',
        'date' => '2025.11.20',
        'body' => [] // ダミー（テキスト省略時はデフォルト表示など）
    ],
    4 => [
        'id' => 4,
        'category' => '動画編集',
        'type' => '学生インターン',
        'image' => 'https://images.unsplash.com/photo-1544717297-fa95b6ee9643?auto=format&fit=crop&q=80&w=600',
        'result' => '初案件で 5万円',
        'title' => '大学の授業の合間にTikTok制作。スキルゼロの文系学生が、クリエイティブで稼ぐ自信をつけるまで。',
        'name' => 'K. Sato',
        'role' => '大学生',
        'date' => '2025.11.15',
        'body' => []
    ],
    5 => [
        'id' => 5,
        'category' => 'デザイン',
        'type' => 'リスキリング',
        'image' => 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&q=80&w=600',
        'result' => 'コンペ採用',
        'title' => '50代からの挑戦。事務職一筋だった私が、AIデザインで「名刺デザインコンペ」に採用されるまで。',
        'name' => 'Y. Suzuki',
        'role' => '事務職 / デザイン勉強中',
        'date' => '2025.11.10',
        'body' => []
    ],
    6 => [
        'id' => 6,
        'category' => 'コンサル',
        'type' => '本業シナジー',
        'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&q=80&w=600',
        'result' => '案件単価 2倍',
        'title' => 'Web制作会社勤務の私が、AI活用をクライアントに提案して単価アップと時短を同時に叶えた方法。',
        'name' => 'H. Kimura',
        'role' => 'Webディレクター',
        'date' => '2025.11.05',
        'body' => []
    ],
    7 => [
        'id' => 7,
        'category' => '動画編集',
        'type' => 'チーム制作',
        'image' => 'https://images.unsplash.com/photo-1603415526960-f7e0328c63b1?auto=format&fit=crop&q=80&w=600',
        'result' => '大型案件受注',
        'title' => '1人じゃ無理な案件も、AiNAの仲間とならできる。チームで挑んだ企業のPR動画制作プロジェクト。',
        'name' => 'Project Team A',
        'role' => 'リーダー: D. Kato',
        'date' => '2025.10.30',
        'body' => []
    ],
    8 => [
        'id' => 8,
        'category' => '翻訳・執筆',
        'type' => 'グローバル',
        'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=600',
        'result' => '海外案件 獲得',
        'title' => '英語は苦手だったけど、AI翻訳ツールを使いこなして翻訳案件を受注。世界と繋がる新しい働き方。',
        'name' => 'L. Wang',
        'role' => 'フリーライター',
        'date' => '2025.10.25',
        'body' => []
    ],
    9 => [
        'id' => 9,
        'category' => 'マーケティング',
        'type' => '自動化・効率化',
        'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=600',
        'result' => '稼働 1日30分',
        'title' => 'X（Twitter）運用をChatGPTで半自動化。本業が忙しい時期でも、安定して成果を出し続けるコツ。',
        'name' => 'Y. Yamada',
        'role' => '営業 / 副業マーケター',
        'date' => '2025.10.20',
        'body' => []
    ],
    10 => [
        'id' => 10,
        'category' => 'デザイン',
        'type' => '副業スタート',
        'image' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=600',
        'result' => '月5万の安定化',
        'title' => '週末だけのバナー制作で、趣味の旅行代を稼ぐ。楽しく続けるための「案件選び」と「時間管理」。',
        'name' => 'R. Ito',
        'role' => 'OL / 週末デザイナー',
        'date' => '2025.10.15',
        'body' => []
    ],
    11 => [
        'id' => 11,
        'category' => '動画編集',
        'type' => 'スピード成長',
        'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=600',
        'result' => '3ヶ月でプロ認定',
        'title' => '「動画編集なんて自分にできるの？」と不安だった私が、3ヶ月でアカデミー認定クリエイターになれた理由。',
        'name' => 'T. Nakata',
        'role' => '元飲食店店長',
        'date' => '2025.10.10',
        'body' => []
    ],
    12 => [
        'id' => 12,
        'category' => 'その他',
        'type' => 'ライフスタイル',
        'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=600',
        'result' => '場所を選ばない',
        'title' => '旅しながら働く。パソコン1台とAIスキルがあれば、どこでも「職場」になることを実感しています。',
        'name' => 'K. Ota',
        'role' => 'ノマドワーカー',
        'date' => '2025.10.05',
        'body' => []
    ]
];
?>
