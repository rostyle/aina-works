#!/usr/bin/env python3
"""AiNA Works ローカル回帰テスト（認可・漏えい・主要動線）。

前提: ローカルで以下が動いていること（手順は tests/README.md）。
  - アプリ:   php -S 127.0.0.1:8080 -t . tests/router.php
  - 会員API:  php -S 127.0.0.1:8099 tests/mock_member_api.php
  - DB:       sql/database.sql を流し tests/seed.php を実行済み
使い方: python3 tests/smoke_test.py [BASE_URL]
"""
import http.cookiejar, json, re, sys, urllib.error, urllib.parse, urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8080").rstrip("/")
MEMBER_PW = "Member1!"
LOCAL_PW = "Passw0rd!"
results = []


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect)

    def req(self, method, path, data=None, headers=None, raw=None):
        url = BASE + path
        body = None
        h = dict(headers or {})
        if raw is not None:
            body = raw.encode() if isinstance(raw, str) else raw
        elif data is not None:
            body = urllib.parse.urlencode(data).encode()
            h.setdefault("Content-Type", "application/x-www-form-urlencoded")
        r = urllib.request.Request(url, data=body, method=method, headers=h)
        try:
            resp = self.op.open(r, timeout=30)
            return resp.status, resp.read().decode("utf-8", "replace"), dict(resp.headers)
        except urllib.error.HTTPError as e:
            return e.code, e.read().decode("utf-8", "replace"), dict(e.headers)

    def get(self, path, follow=False, **k):
        """follow=True なら同じホスト内の 301/302（.php の除去・末尾スラッシュ等）を辿る"""
        code, body, hdr = self.req("GET", path, **k)
        hops = 0
        while follow and code in (301, 302, 303) and hops < 5:
            loc = hdr.get("Location", "")
            if loc.startswith(BASE):
                loc = loc[len(BASE):]
            if not loc.startswith("/"):
                break
            code, body, hdr = self.req("GET", loc, **k)
            hops += 1
        return code, body, hdr

    def post(self, path, data=None, **k):
        return self.req("POST", path, data=data, **k)

    def csrf(self, path="/login"):
        _, html, _ = self.get(path, follow=True)
        m = re.search(r'name="csrf_token" value="([0-9a-f]+)"', html) or re.search(r'csrf-token" content="([0-9a-f]+)"', html)
        return m.group(1) if m else ""

    def login(self, email, pw):
        tok = self.csrf("/login")
        code, body, hdr = self.post("/login", {"csrf_token": tok, "email": email, "password": pw})
        return code, body, hdr


def check(name, cond, detail=""):
    results.append((name, bool(cond), detail))
    print(("PASS " if cond else "FAIL ") + name + ("" if cond else f"  -- {detail}"))


def no_php_error(body):
    return not re.search(r"(Fatal error|Warning</b>|Notice</b>|Deprecated</b>|Uncaught|Stack trace|SQLSTATE)", body)


def main():
    anon = Client()
    # --- 公開ページが壊れていない ---
    for p in ["/", "/jobs", "/work/", "/creators", "/success-stories", "/login", "/terms", "/privacy"]:
        c, b, _ = anon.get(p)
        check(f"public page {p} 200", c == 200, c)
        check(f"public page {p} no php error", no_php_error(b), re.findall(r"(?:Warning|Notice|Fatal error)[^<]{0,160}", b)[:2])

    # --- S-02: 未ログインで案件APIから依頼者メールが取れない ---
    c, b, _ = anon.get("/api/get_job.php?id=1")
    check("S-02 get_job anonymous denied", c in (401, 403) and "client@example.com" not in b, (c, b[:120]))

    # --- S-06: debugスクリプトが無い ---
    for p in ["/debug_env.php", "/debug_schema.php", "/debug_production_stories_v2.php"]:
        c, b, _ = anon.get(p, follow=True)
        check(f"S-06 {p} gone", c == 404, c)

    # --- U-01: 反射XSS ---
    c, b, _ = anon.get("/jobs?status=%3Cscript%3Ealert(1)%3C/script%3E&urgency=%3Cimg%20src=x%3E")
    check("U-01 jobs.php reflected XSS escaped", "<script>alert(1)</script>" not in b and "<img src=x>" not in b)

    # --- S-10: ログイン後のオープンリダイレクト ---
    cl = Client()
    tok = cl.csrf("/login?redirect=https://evil.example/")
    c, b, h = cl.req("POST", "/login?redirect=https://evil.example/", data={"csrf_token": tok, "email": "client@example.com", "password": MEMBER_PW})
    check("S-10 open redirect blocked", c in (302, 303) and "evil.example" not in h.get("Location", ""), h.get("Location"))

    # --- S-10: 先頭に / を付けた外部URLでもすり抜けない（レビュー指摘）---
    for bad in ["/https://evil.example", "/http://evil.example/x", "/%2F%2Fevil.example"]:
        cl0 = Client()
        tok = cl0.csrf("/login?redirect=" + urllib.parse.quote(bad, safe=""))
        c, b, h = cl0.req("POST", "/login?redirect=" + urllib.parse.quote(bad, safe=""), data={"csrf_token": tok, "email": "client@example.com", "password": MEMBER_PW})
        loc = h.get("Location", "")
        # サイト内パス（/ で始まり // ではない、スキームなし）なら外部へは飛ばない
        safe = (loc.startswith("/") and not loc.startswith("//") and "://" not in loc) or loc.startswith(BASE + "/")
        check(f"S-10 redirect bypass {bad} blocked", safe, loc)

    # --- マイページに紐づく管理者は、マイページのプランに関係なくローカルパスワードで管理画面に入れる ---
    la = Client()
    la.login("linkedadmin@example.com", LOCAL_PW)
    c, b, _ = la.get("/admin/index.php", follow=True)
    check("linked admin can log in with local password", c == 200 and "アクセス権限がありません" not in b and "ダッシュボード" in b, c)

    # --- S-01: 一般会員が管理画面を使えない ---
    member = Client()
    member.login("creator@example.com", MEMBER_PW)
    c, b, _ = member.get("/dashboard")
    check("member login works (dashboard 200)", c == 200 and "ログアウト" in b, c)
    c, b, _ = member.get("/admin/users.php", follow=True)
    check("S-01 member sees no admin user list", "creator@example.com" not in b and "client@example.com" not in b, c)
    m = re.search(r'name="csrf_token" value="([0-9a-f]+)"', b)
    tok = m.group(1) if m else member.csrf("/profile")
    c, b, _ = member.post("/admin/users.php", {"csrf_token": tok, "action": "grant_admin", "user_id": "2"})
    c2, b2, _ = member.get("/admin/index.php", follow=True)
    check("S-01 member cannot grant self admin", "アクセス権限がありません" in b2 and "ダッシュボード" not in b2.split("アクセス権限がありません")[-1][:4000], c2)

    # --- S-03: 退会者がローカルパスワードで入れない ---
    q = Client()
    c, b, h = q.login("quit@example.com", LOCAL_PW)
    c2, b2, _ = q.get("/dashboard")
    check("S-03 quit member with local password blocked", c2 != 200 or "ログアウト" not in b2, (c, h.get("Location")))

    # --- ローカル専用アカウント（管理者）は入れる ---
    ad = Client()
    ad.login("admin@example.com", LOCAL_PW)
    c, b, _ = ad.get("/admin/index.php", follow=True)
    check("local admin can open admin", c == 200 and "アクセス権限がありません" not in b, c)

    # --- 役割未設定者に案内が出る ---
    nr = Client()
    nr.login("norole@example.com", MEMBER_PW)
    c, b, _ = nr.get("/job-detail?id=1")
    check("F-02 no-role member sees how to apply", c == 200 and ("クリエイター登録" in b or "役割" in b), c)

    # --- S-12: like/favorite に CSRF ---
    c, b, _ = member.post("/api/favorite.php", raw=json.dumps({"target_type": "work", "target_id": 1, "action": "add"}), headers={"Content-Type": "text/plain"})
    check("S-12 favorite without csrf rejected", c in (400, 403), (c, b[:100]))

    # --- 役割選択（F-03）: 役割未設定者は選択画面を経由して応募できるようになる ---
    nr2 = Client()
    c, b, h = nr2.login("norole@example.com", MEMBER_PW)
    check("F-03 no-role login goes to role selection", "select-role" in h.get("Location", ""), h.get("Location"))
    tok = nr2.csrf("/select-role")
    c, b, h = nr2.post("/select-role", {"csrf_token": tok, "is_creator": "1", "birthdate": "1995-05-05", "redirect": "job-detail?id=1"})
    check("F-03 role saved and redirected back", c in (302, 303) and "job-detail" in h.get("Location", ""), (c, h.get("Location")))
    c, b, _ = nr2.get("/job-detail?id=1")
    check("F-03 after role selection the apply button shows", "open-application-modal" in b)
    check("F-01 flash message is rendered", "設定しました" in b)

    # --- 取引の流れ（F-04 / S-08）: 応募→受諾→契約中→納品報告→検収→完了 ---
    cr = Client(); cr.login("creator@example.com", MEMBER_PW)
    cl = Client(); cl.login("client@example.com", MEMBER_PW)
    _, html, _ = cr.get("/job-detail?id=1")
    m = re.search(r'name="csrf_token" value="([0-9a-f]+)"', html)
    c, b, h = cr.post("/job-detail?id=1", {"submit_application": "1", "csrf_token": m.group(1) if m else "", "cover_letter": "バナー制作が得意です。3日で納品できます。", "proposed_price": "20000", "proposed_duration": "1"})
    check("apply with csrf succeeds", "applied=1" in h.get("Location", ""), (c, h.get("Location")))
    c, b, h = cr.post("/job-detail?id=1", {"submit_application": "1", "cover_letter": "x", "proposed_price": "20000", "proposed_duration": "1"})
    _, b2, _ = cr.get("/job-detail?id=1")
    check("apply without csrf is rejected", "有効期限" in b2, h.get("Location"))
    app_id = None
    _, html, _ = cl.get("/job-applications?id=1")
    m = re.search(r'data-application-id="(\d+)"', html) or re.search(r'application_id[^0-9]{1,40}(\d+)', html)
    import subprocess
    app_id = subprocess.run(["mysql", "-N", "-e", "SELECT id FROM aina_works.job_applications WHERE job_id=1 AND creator_id=2"], capture_output=True, text=True).stdout.strip()
    tok = cl.csrf("/job-detail?id=1")
    c, b, _ = cl.post("/api/update-application-status.php", {"csrf_token": tok, "application_id": app_id, "action": "accept"})
    check("client accepts application", c == 200 and '"success":true' in b, (c, b[:160]))
    st = subprocess.run(["mysql", "-N", "-e", "SELECT status FROM aina_works.jobs WHERE id=1"], capture_output=True, text=True).stdout.strip()
    check("F-04 job becomes contracted when hiring limit reached", st == "contracted", st)
    c, b, _ = cl.post("/api/update-job-settings.php", {"csrf_token": tok, "job_id": "1", "status": "delivered"})
    check("S-08 client cannot set delivered", c == 400, (c, b[:160]))
    c, b, _ = cl.get("/api/get-bank-account.php?job_id=1&creator_id=2")
    check("S-08 bank info hidden before delivery", c == 403, (c, b[:120]))
    _, b, _ = cr.get("/job-detail?id=1")
    check("F-04 creator sees deliver button", "納品を報告する" in b)
    ctok = cr.csrf("/job-detail?id=1")
    c, b, _ = cr.post("/api/update-job-settings.php", {"csrf_token": ctok, "job_id": "1", "status": "completed"})
    check("creator cannot complete the job", c in (400, 403), (c, b[:120]))
    c, b, _ = cr.post("/api/update-job-settings.php", {"csrf_token": ctok, "job_id": "1", "status": "delivered", "note": "納品物をチャットに送りました"})
    check("creator reports delivery", c == 200 and '"success":true' in b, (c, b[:160]))
    _, b, _ = cl.get("/job-detail?id=1")
    check("F-04 client sees inspect button", "検収して完了にする" in b)
    _, b, _ = cl.get("/dashboard")
    check("F-04 dashboard shows what to do", "納品の報告が届いています" in b)
    c, b, _ = cl.post("/api/update-job-settings.php", {"csrf_token": tok, "job_id": "1", "status": "completed"})
    check("client completes after delivery", c == 200 and '"success":true' in b, (c, b[:160]))

    # --- レビュー（S-13）: 取引相手だけ・1件まで ---
    stranger = Client(); stranger.login("norole@example.com", MEMBER_PW)
    stok = stranger.csrf("/creator-profile?id=2")
    stranger.post("/api/submit-user-review.php", {"csrf_token": stok, "reviewee_id": "2", "rating": "1", "comment": "ひどい"})
    cnt = subprocess.run(["mysql", "-N", "-e", "SELECT COUNT(*) FROM aina_works.reviews WHERE reviewer_id=4"], capture_output=True, text=True).stdout.strip()
    check("S-13 non-partner cannot post user review", cnt == "0", cnt)
    for _ in range(2):
        cl.post("/api/submit-user-review.php", {"csrf_token": tok, "reviewee_id": "2", "rating": "5", "comment": "丁寧でした"})
    cnt = subprocess.run(["mysql", "-N", "-e", "SELECT COUNT(*) FROM aina_works.reviews WHERE reviewer_id=3 AND reviewee_id=2"], capture_output=True, text=True).stdout.strip()
    check("S-13 partner review is one per pair", cnt == "1", cnt)

    # --- チャット添付（S-07）: 参加者だけが開ける ---
    room = subprocess.run(["mysql", "-N", "-e", "SELECT id FROM aina_works.chat_rooms WHERE (user1_id=2 AND user2_id=3) OR (user1_id=3 AND user2_id=2) LIMIT 1"], capture_output=True, text=True).stdout.strip()
    png = bytes.fromhex("89504e470d0a1a0a0000000d4948445200000001000000010806000000" + "1f15c4890000000d49444154789c6360000002000154a24f5d0000000049454e44ae426082")
    boundary = "----x"
    body = (f"--{boundary}\r\nContent-Disposition: form-data; name=\"csrf_token\"\r\n\r\n{ctok}\r\n"
            f"--{boundary}\r\nContent-Disposition: form-data; name=\"room_id\"\r\n\r\n{room}\r\n"
            f"--{boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"design.png\"\r\nContent-Type: image/png\r\n\r\n").encode() + png + f"\r\n--{boundary}--\r\n".encode()
    c, b, _ = cr.req("POST", "/api/upload-chat-file-v2.php", raw=body, headers={"Content-Type": f"multipart/form-data; boundary={boundary}"})
    check("upload chat image", c == 200 and '"success":true' in b, (c, b[:200]))
    try:
        data = json.loads(b)["data"]
    except Exception:
        data = {}
    stored = data.get("file_path", "")
    c, _, _ = anon.get("/" + stored) if stored else (0, "", {})
    check("S-07 direct storage URL is blocked", c == 403, (c, stored))
    c, _, h = cl.get(data.get("file_url", "/api/chat-file.php?id=0"))
    check("S-07 participant can open attachment", c == 200 and h.get("Content-Type", "").startswith("image/png"), (c, h.get("Content-Type")))
    c, _, _ = stranger.get(data.get("file_url", "/api/chat-file.php?id=0"))
    check("S-07 non-participant cannot open attachment", c == 403, c)
    fake = (f"--{boundary}\r\nContent-Disposition: form-data; name=\"csrf_token\"\r\n\r\n{ctok}\r\n"
            f"--{boundary}\r\nContent-Disposition: form-data; name=\"room_id\"\r\n\r\n{room}\r\n"
            f"--{boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"evil.png\"\r\nContent-Type: image/png\r\n\r\n<?php echo 1; ?>\r\n--{boundary}--\r\n").encode()
    c, b, _ = cr.req("POST", "/api/upload-chat-file-v2.php", raw=fake, headers={"Content-Type": f"multipart/form-data; boundary={boundary}"})
    check("S-07 fake image (PHP inside) rejected", c == 400, (c, b[:120]))

    # --- 作品ページからの依頼（F-05）: チャットに届く ---
    ltok = cl.csrf("/work-detail?id=1")
    c, b, _ = cl.post("/api/request-job.php", {"csrf_token": ltok, "creator_id": "2", "work_id": "1", "project_title": "名刺デザイン", "project_description": "名刺のデザインをお願いしたいです", "budget_min": "5000", "budget_max": "10000", "deadline": "2099-01-01"})
    check("F-05 work request accepted", c == 200 and '"success":true' in b, (c, b[:160]))
    cnt = subprocess.run(["mysql", "-N", "-e", "SELECT COUNT(*) FROM aina_works.chat_messages WHERE message LIKE '%お仕事のご依頼%名刺デザイン%'"], capture_output=True, text=True).stdout.strip()
    check("F-05 request delivered to chat", cnt == "1", cnt)

    # --- パスワード再設定（S-04）: 会員にはローカルパスワードを作らない ---
    fp = Client(); ftok = fp.csrf("/forgot-password")
    fp.post("/forgot-password", {"csrf_token": ftok, "email": "creator@example.com"})
    cnt = subprocess.run(["mysql", "-N", "-e", "SELECT COUNT(*) FROM aina_works.password_resets WHERE email='creator@example.com'"], capture_output=True, text=True).stdout.strip()
    check("S-04 no reset token for mypage members", cnt == "0", cnt)

    # --- LINE webhook（S-05）: シークレット未設定なら受け付けない ---
    c, b, _ = anon.post("/api/line-webhook.php", raw=json.dumps({"events": []}), headers={"Content-Type": "application/json"})
    check("S-05 webhook fails closed without secret", c == 503, c)

    # --- ログイン試行の制限（S-09）---
    bf = Client()
    for i in range(9):
        c, b, h = bf.login("client@example.com", "wrong-password")
    check("S-09 login throttled after repeated failures", "一時的に受け付けを止めています" in b, b[-200:])

    # --- 全ページ巡回（ステータスとPHPエラー表示の有無）---
    pages_public = ["/work-detail?id=1", "/creator-profile?id=2", "/creator-profile?id=3", "/job-detail?id=1", "/job-detail?id=2",
                    "/success-story-detail?id=1", "/success-stories?category=%E3%83%87%E3%82%B6%E3%82%A4%E3%83%B3%E3%83%BB%E7%94%BB%E5%83%8F",
                    "/lp-outsourcing", "/forgot-password", "/jobs?status=all&sort=deadline", "/work/?category_id=1"]
    for pth in pages_public:
        c, b, _ = anon.get(pth)
        check(f"page {pth} ok", c == 200 and no_php_error(b), (c, re.findall(r"(?:Warning|Notice|Fatal error)[^<]{0,160}", b)[:2]))
    c, b, h = anon.get("/sitemap.xml")
    check("sitemap.xml served", c == 200 and "<urlset" in b and "job-detail?id=2" in b and "job-detail?id=1<" not in b, (c, b[:120]))
    c, b, _ = anon.get("/robots.txt")
    check("robots.txt served", c == 200 and "Sitemap:" in b, c)
    c, b, _ = anon.get("/no-such-page-xyz")
    check("404 page", c == 404 and "ページが見つかりません" in b and no_php_error(b), c)
    c, b, _ = anon.get("/job-detail?id=1")
    check("U-06 title and description per page", "<title>LPのバナー作成 | AiNA Works</title>" in b and 'name="description"' in b and 'og:title' in b, re.findall(r"<title>[^<]*</title>", b)[:1])
    c, b, _ = anon.get("/login")
    check("U-06 login page is noindex", 'content="noindex' in b)
    for who, client in [("creator", cr), ("client", cl), ("norole", nr2), ("admin", ad)]:
        for pth in ["/dashboard", "/profile", "/favorites", "/chat", "/job-applications", "/post-job", "/edit-work", "/select-role", "/live-course"]:
            c, b, h = client.get(pth, follow=(pth != "/edit-work"))
            ok_status = c == 200 or (c in (302, 303) and pth in ("/edit-work",))
            check(f"{who} {pth} ok", ok_status and no_php_error(b), (c, h.get("Location"), re.findall(r"(?:Warning|Notice|Fatal error)[^<]{0,160}", b)[:2]))
    for pth in ["/admin/index.php", "/admin/users.php", "/admin/works.php", "/admin/jobs.php", "/admin/applications.php", "/admin/reviews.php",
                "/admin/categories.php", "/admin/success_stories.php", "/admin/success_story_edit.php", "/admin/works_edit.php", "/admin/chats.php",
                "/admin/reviews.php?q=test"]:
        c, b, _ = ad.get(pth, follow=True)
        check(f"admin {pth} ok", c == 200 and no_php_error(b) and "アクセス権限がありません" not in b, (c, re.findall(r"(?:Warning|Notice|Fatal error|エラー)[^<]{0,160}", b)[:2]))
    c, b, _ = ad.get("/api/get_job.php?id=1")
    check("admin get_job returns client email", c == 200 and "client@example.com" in b, (c, b[:100]))

    # --- 状態が空の案件でも画面が落ちず、先へ進める（レビュー指摘）---
    blank_id = subprocess.run(["mysql", "-N", "-e", "SELECT id FROM aina_works.jobs WHERE title='状態が空の案件'"], capture_output=True, text=True).stdout.strip()
    c, b, _ = cl.get(f"/job-detail?id={blank_id}")
    check("blank-status job page renders for owner", c == 200 and no_php_error(b) and "job-status-action" in b, (c, re.findall(r"(?:Warning|Fatal error)[^<]{0,160}", b)[:2]))

    # --- プロフィールを保存しても管理者権限が消えない（既存バグ）---
    ptok = ad.csrf("/profile")
    ad.post("/profile", {"csrf_token": ptok, "full_name": "管理者", "birthdate": "1990-01-01", "is_client": "1", "bio": "", "response_time": "24"})
    cnt = subprocess.run(["mysql", "-N", "-e", "SELECT COUNT(*) FROM aina_works.user_roles WHERE user_id=1 AND role='admin' AND is_enabled=1"], capture_output=True, text=True).stdout.strip()
    check("profile save keeps admin role", cnt == "1", cnt)

    # --- 管理画面で無効にした会員は、ログインしても有効に戻らない（既存バグ）---
    atok = ad.csrf("/admin/users.php")
    ad.post("/admin/users.php", {"csrf_token": atok, "action": "toggle_active", "user_id": "4"})
    off = Client()
    c, b, h = off.login("norole@example.com", MEMBER_PW)
    act = subprocess.run(["mysql", "-N", "-e", "SELECT is_active FROM aina_works.users WHERE id=4"], capture_output=True, text=True).stdout.strip()
    check("deactivated member stays inactive after login attempt", act == "0" and "ご利用いただけません" in b, (act, h.get("Location")))

    ok = sum(1 for r in results if r[1])
    print(f"\n{ok}/{len(results)} passed")
    return 0 if ok == len(results) else 1


if __name__ == "__main__":
    sys.exit(main())
