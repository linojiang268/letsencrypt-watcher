<?php
/**
 * Created by PhpStorm.
 * User: ouarea
 * Date: 2017/8/7
 * Time: 12:33
 */

const LIVE_DIR = '/etc/letsencrypt/live/domain';
const ROOT_DIR = '/root/letsencrypt-watcher';

const CERT_FILE      = LIVE_DIR . '/cert.pem';
const CHAIN_FILE     = LIVE_DIR . '/chain.pem';
const FULLCHAIN_FILE = LIVE_DIR . '/fullchain.pem';
const PRIVKEY_FILE   = LIVE_DIR . '/privkey.pem';

const LATEST_FILE  = ROOT_DIR . '/latest';
const CHANGED_FILE = ROOT_DIR . '/changed';

function storeMd5()
{
    file_put_contents(LATEST_FILE, json_encode([
        'cert'      => md5_file(CERT_FILE),
        'chain'     => md5_file(CHAIN_FILE),
        'fullchain' => md5_file(FULLCHAIN_FILE),
        'privkey'   => md5_file(PRIVKEY_FILE),
        'latest'    => date('Y-m-d H:i:s'),
    ]));
}

if (!file_exists(CERT_FILE)
    || !file_exists(CHAIN_FILE)
    || !file_exists(FULLCHAIN_FILE)
    || !file_exists(PRIVKEY_FILE)) {
    echo 'Required files not exists.';
    return;
}

if (!file_exists('latest')) {
    storeMd5();
    return;
}

$content = file_get_contents(LATEST_FILE);
if (empty($content) || !($latest = json_decode($content, true))) {
    storeMd5();
    return;
}

if (md5_file(CERT_FILE)      != $latest['cert']      ||
    md5_file(CHAIN_FILE)     != $latest['chain']     ||
    md5_file(FULLCHAIN_FILE) != $latest['fullchain'] ||
    md5_file(PRIVKEY_FILE)   != $latest['privkey']) {
    // changed
    file_put_contents(CHANGED_FILE, sprintf("%s. \n%s changed.\n", $content, date('Y-m-d H:i:s')), FILE_APPEND);
    exec("/usr/local/nginx/sbin/nginx -s reload");
}

storeMd5();