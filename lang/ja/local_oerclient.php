<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Japanese language strings for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['browseexchange'] = 'OER Exchangeを閲覧';
$string['checklist_dates'] = '活動の期限日およびコース開始日 — これらは元のコースから引き継がれているため、更新が必要な場合があります。';
$string['checklist_grading'] = '評定項目と評価尺度 — 成績表の設定と一致しているか確認してください。';
$string['checklist_names'] = '活動内容に記載されている氏名、所属機関、連絡先などの情報。';
$string['checklist_visibility'] = 'インポートされたコースは既定で非表示になっています — 内容を確認したうえで、学生に表示してください。';
$string['connectintro'] = '自分自身としてリソースを共有・レビューするために、Exchange上の個人アカウントをリンクしてください。';
$string['connectsuccess'] = 'アカウントがExchangeにリンクされました。';
$string['downloadcountlabel'] = 'ダウンロード数';
$string['error_invalidlicense'] = '一覧にあるライセンスから選択してください。';
$string['error_invalidlinkstate'] = 'このアカウントリンクのリクエストを確認できませんでした。もう一度アカウントのリンクをお試しください。';
$string['error_noexchangeurl'] = 'まず設定でExchange URLを設定してください。';
$string['error_notargetcourses'] = 'インポート先として利用できるコースへの権限がありません。';
$string['error_notlinked'] = 'まずあなたのExchangeアカウントをリンクしてください。';
$string['error_notregistered'] = 'このサイトはまだOER Exchangeに登録されていない（または未承認の）状態です。サイト管理 > プラグイン > OER Client > 全般設定 を確認してください。';
$string['error_resourcegone'] = 'このリソースはExchange上で利用できなくなりました。作成者によって非表示または削除された可能性があります。';
$string['error_restoreprecheckfailed'] = 'このバックアップの復元事前チェックに失敗しました。';
$string['error_sharecapabilitylost'] = 'このコースまたは活動を共有する権限がなくなりました。';
$string['error_sourcegone'] = 'この共有の元となったコースは、このサイトにすでに存在しません。そのため、ここから共有中のコピーを更新することはできません。Exchange上で公開されているリソースには影響ありません。';
$string['error_sourcegoneactivity'] = 'この共有の元となった活動は、このサイトにすでに存在しません。そのため、ここから共有中のコピーを更新することはできません。Exchange上で公開されているリソースには影響ありません。';
$string['error_statusunavailable'] = 'Exchangeから現在の状態を取得できませんでした: {$a}';
$string['error_targetcourserequired'] = '単一の活動をインポートするには、対象コースの指定が必要です。';
$string['error_userdatalockedon'] = 'このサイトのバックアップ既定値では、すべてのバックアップにユーザデータを含めることが強制されているため、安全に共有できるものがありません。サイト管理 > コース > バックアップ > 一般バックアップ既定値 で「登録利用者を含める」のロックを解除するよう管理者に依頼してください。';
$string['exchangeerror'] = 'Exchangeエラー: {$a}';
$string['exchangehidden'] = '非表示';
$string['exchangehiddenhint'] = 'このリソースはExchange上で非表示にされているため、カタログには表示されていません。Exchangeのリソースページから再び表示できます。';
$string['exchangevisibility'] = 'Exchange上の状態';
$string['exchangevisible'] = 'カタログに表示中';
$string['filterbytype'] = '種別';
$string['firstpublished'] = '最初の公開日';
$string['generalsettings'] = '全般設定';
$string['gotocourse'] = 'コースへ移動';
$string['importbutton'] = 'インポート';
$string['importcountlabel'] = 'インポート数';
$string['importheading'] = 'このリソースをインポート';
$string['importresulttitle'] = 'インポート完了';
$string['importsuccess'] = 'リソースは正常にインポートされました。';
$string['importtargetcourse'] = 'インポート先コース';
$string['lastupdated'] = '最終更新日';
$string['licenselabel'] = 'ライセンス: {$a}';
$string['linkaccount'] = '自分のExchangeアカウントをリンクする';
$string['linkedas'] = 'Exchangeアカウント #{$a} にリンクされています。';
$string['localizationchecklist'] = '学生と一緒に使用する前に、以下を確認してください。';
$string['nocatalogresources'] = '検索条件に一致するリソースがありません。';
$string['oerclient:import'] = 'OER Exchangeからリソースをインポートする';
$string['oerclient:share'] = 'コースまたは活動をOER Exchangeへ共有する';
$string['plugininstalled'] = 'このサイトにインストール済み';
$string['pluginmissing'] = '未インストール — スキップされます';
$string['pluginname'] = 'OERクライアント';
$string['privacy:metadata:local_oerclient_imports'] = 'Exchangeからインポートしたリソースの記録。';
$string['privacy:metadata:local_oerclient_imports:timecreated'] = 'インポートが行われた日時。';
$string['privacy:metadata:local_oerclient_imports:userid'] = 'インポートを行ったユーザー。';
$string['privacy:metadata:local_oerclient_link'] = 'OER Exchangeアカウントへの個人リンク。';
$string['privacy:metadata:local_oerclient_link:exchangeuserid'] = 'Exchange上のあなたのユーザーID。';
$string['privacy:metadata:local_oerclient_link:timecreated'] = 'リンクが作成された日時。';
$string['privacy:metadata:local_oerclient_link:token'] = 'Exchange上であなたとして操作するために使用されるウェブサービストークン。';
$string['privacy:metadata:local_oerclient_shares'] = 'Exchangeへ共有したコース/活動の記録。';
$string['privacy:metadata:local_oerclient_shares:timecreated'] = '共有がキューに登録された日時。';
$string['privacy:metadata:local_oerclient_shares:title'] = '共有に付けられたタイトル。';
$string['privacy:metadata:local_oerclient_shares:userid'] = '共有を行ったユーザー。';
$string['privacy:metadata:oerexchange'] = 'アカウントのリンク、リソースの共有、カタログの閲覧/インポートを行うため、このプラグインの設定で指定されたOER Exchangeサイトとデータがやり取りされます。';
$string['privacy:metadata:oerexchange:sharedcontent'] = 'あなたが共有を選択した、個人情報を含まない形にサニタイズされたコース/活動のバックアップ。';
$string['privacy:metadata:oerexchange:token'] = 'Exchangeが共有やレビューをあなたのアカウントに関連付けられるようにするための、あなた個人のウェブサービストークン。';

$string['registeractive'] = '登録済み・有効です（サイトID {$a}）。';
$string['registerbutton'] = 'このサイトを登録する';
$string['registerintro'] = 'このサイトを設定済みのExchangeに登録します。サイトトークンを受け取るには、Exchange管理者がリクエストを承認する必要があります。';
$string['registerpending'] = '登録は承認待ちです（サイトID {$a}）。メールでサイトトークンが届いたら設定に貼り付けてください。';
$string['registersuccess'] = '登録リクエストを送信しました。承認されるとメールでサイトトークンが届きますので確認してください。';
$string['registertitle'] = 'Exchangeへの登録';
$string['requiredplugins'] = '必要なプラグイン';
$string['searchbutton'] = '検索';
$string['sectionnumber'] = 'セクション {$a}';
$string['settings_exchangeurl'] = 'Exchange URL';
$string['settings_exchangeurl_desc'] = 'OER ExchangeサイトのベースURL（例: https://vagrant.wisecat.net）';
$string['settings_siteid'] = 'サイトID';
$string['settings_siteid_desc'] = '登録時にExchangeによって割り当てられます。登録ページによって自動的に設定されます。';
$string['settings_sitetoken'] = 'サイトトークン';
$string['settings_sitetoken_desc'] = 'Exchange管理者がこのサイトを承認した後にメールで送られるウェブサービストークン。';
$string['settingsheading'] = 'OER Exchange接続';
$string['settingsheading_desc'] = 'このサイトが通信するOER Exchangeを設定します。まず登録を行い、メールで届いたサイトキーを下の「サイトトークン」に貼り付けてください。';
$string['sharelanguagelabel'] = '言語';
$string['sharelicenselabel'] = 'ライセンス';
$string['sharequeued'] = 'キューに登録されました — 完了まで少し時間がかかる場合があります。状況はこちらに表示されます。';
$string['sharestatus_backingup'] = 'サニタイズされたバックアップを作成中';
$string['sharestatus_failed'] = '失敗';
$string['sharestatus_pending'] = 'キュー待ち';
$string['sharestatus_published'] = '公開済み';
$string['sharestatus_uploading'] = 'Exchangeへアップロード中';
$string['sharestatuslabel'] = 'ステータス: {$a}';
$string['sharestatustitle'] = '共有ステータス';
$string['sharesubmit'] = '共有する';
$string['sharesummarylabel'] = '概要';
$string['sharetagslabel'] = 'タグ（カンマ区切り）';
$string['sharetitlelabel'] = 'タイトル';
$string['sharetoexchange'] = 'OER Exchangeへ共有';
$string['sitecontact'] = '連絡先メールアドレス';
$string['structurepreview'] = '構造のプレビュー';
$string['typeactivity'] = '活動';
$string['typecourse'] = 'コース';
$string['updateexchangecopy'] = '共有中のコピーを更新する';
$string['updateexchangecopyhint'] = '現在の状態のコースを再アップロードし、Exchange上のコピーを置き換えます。カタログのエントリ、そのリンク、レビューはそのまま維持されます。';
$string['updatequeued'] = 'キューに登録しました。まもなく共有中のコピーが更新されます。';
$string['viewonexchange'] = 'Exchangeで表示';
