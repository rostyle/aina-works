#!/usr/bin/env python3
"""Isolated HTTP regression for legacy config output; no database or secrets required."""
import http.client
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[1]


def main():
    with tempfile.TemporaryDirectory(prefix='aina-headers-') as temp:
        root = Path(temp)
        for folder in ('includes', 'api', 'config', 'sessions'):
            (root / folder).mkdir()
        for path in ('includes/functions.php', 'includes/error-handler.php', 'api/health.php', 'api/get_job.php'):
            shutil.copyfile(ROOT / path, root / path)
        # Deliberately no initial ob_start(), and output after loading functions,
        # matching the legacy production config without reading that private file.
        (root / 'config/config.php').write_text('''<?php
session_start();
define('DEBUG', false);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/error-handler.php';
class Database {
    public static function getInstance() {
        file_put_contents(__DIR__ . '/../db-touched', '1');
        throw new RuntimeException('test database unavailable');
    }
}
?>

''')
        (root / 'session.php').write_text('''<?php
require __DIR__ . '/config/config.php';
$before = session_id();
regenerateSessionSecurely();
jsonResponse(['rotated' => $before !== session_id()]);
''')
        (root / 'redirect.php').write_text("<?php require __DIR__ . '/config/config.php'; redirect('/destination');")
        (root / 'warning.php').write_text("<?php require __DIR__ . '/config/config.php'; trigger_error('fixture warning', E_USER_WARNING); echo 'page';")
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        with (root / 'server.log').open('w+') as log:
            proc = subprocess.Popen([os.environ.get('PHP_BINARY', 'php'), '-d', 'output_buffering=0',
                '-d', 'display_errors=1', '-d', 'log_errors=1', '-d', 'session.save_path=' + str(root / 'sessions'),
                '-S', f'127.0.0.1:{port}', '-t', str(root)], stdout=log, stderr=log)
            try:
                for _ in range(100):
                    if proc.poll() is not None:
                        raise RuntimeError('PHP server exited')
                    try:
                        with socket.create_connection(('127.0.0.1', port), timeout=.1):
                            break
                    except OSError:
                        time.sleep(.05)
                else:
                    raise RuntimeError('PHP server did not start')

                def get(path, headers=None):
                    conn = http.client.HTTPConnection('127.0.0.1', port, timeout=5)
                    conn.request('GET', path, headers=headers or {})
                    response = conn.getresponse()
                    result = response.status, response.getheaders(), response.read()
                    conn.close()
                    return result

                def clean_json(path, status):
                    actual, headers, body = get(path)
                    assert actual == status, (path, actual, body)
                    assert body.startswith(b'{'), (path, body)
                    assert dict(headers)['Content-Type'].startswith('application/json'), headers
                    return json.loads(body)

                data = clean_json('/api/get_job.php?id=1', 401)
                assert data['error_code'] == 'AUTH_REQUIRED'
                assert not (root / 'db-touched').exists(), 'anonymous request reached database'
                data = clean_json('/api/health.php', 503)
                assert data['ok'] is False and data['clock_ok'] is None
                assert (root / 'db-touched').exists()
                status, headers, body = get('/session.php', {'X-Forwarded-Proto': 'https'})
                assert status == 200 and json.loads(body)['rotated'] is True
                cookies = [v.lower() for k, v in headers if k.lower() == 'set-cookie']
                assert any('httponly' in c and 'samesite=lax' in c and 'secure' in c for c in cookies), cookies
                status, headers, body = get('/redirect.php')
                assert status == 302 and dict(headers)['Location'] == '/destination' and body == b''
                status, headers, body = get('/warning.php')
                assert status == 200 and body.strip() == b'page', body
                log.flush()
                assert 'fixture warning' in (root / 'server.log').read_text(), 'warning was not logged'
                print('PASS: anonymous 401 before DB, clean health 503, rotated secure cookie, 302 redirect, logged-only warning')
            finally:
                proc.terminate()
                proc.wait(timeout=5)


if __name__ == '__main__':
    main()
