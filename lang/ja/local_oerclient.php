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

$string['pluginname'] = 'OERクライアント';

// Capabilities.
$string['oerclient:share'] = 'コースまたは活動をOER Exchangeへ共有する';
$string['oerclient:import'] = 'OER Exchangeからリソースをインポートする';

// Privacy.
$string['privacy:metadata:local_oerclient_link'] = 'OER Exchangeアカウントへの個人リンク。';
$string['privacy:metadata:local_oerclient_link:exchangeuserid'] = 'Exchange上のあなたのユーザーID。';
$string['privacy:metadata:local_oerclient_link:token'] = 'Exchange上であなたとして操作するために使用されるウェブサービストークン。';
$string['privacy:metadata:local_oerclient_link:timecreated'] = 'リンクが作成された日時。';
$string['privacy:metadata:local_oerclient_shares'] = 'Exchangeへ共有したコース/活動の記録。';
$string['privacy:metadata:local_oerclient_shares:userid'] = '共有を行ったユーザー。';
$string['privacy:metadata:local_oerclient_shares:title'] = '共有に付けられたタイトル。';
$string['privacy:metadata:local_oerclient_shares:timecreated'] = '共有がキューに登録された日時。';
$string['privacy:metadata:local_oerclient_imports'] = 'Exchangeからインポートしたリソースの記録。';
$string['privacy:metadata:local_oerclient_imports:userid'] = 'インポートを行ったユーザー。';
$string['privacy:metadata:local_oerclient_imports:timecreated'] = 'インポートが行われた日時。';
$string['privacy:metadata:oerexchange'] = 'アカウントのリンク、リソースの共有、カタログの閲覧/インポートを行うため、このプラグインの設定で指定されたOER Exchangeサイトとデータがやり取りされます。';
$string['privacy:metadata:oerexchange:token'] = 'Exchangeが共有やレビューをあなたのアカウントに関連付けられるようにするための、あなた個人のウェブサービストークン。';
$string['privacy:metadata:oerexchange:sharedcontent'] = 'あなたが共有を選択した、個人情報を含まない形にサニタイズされたコース/活動のバックアップ。';

// Settings.
$string['generalsettings'] = '全般設定';
$string['settingsheading'] = 'OER Exchange接続';
$string['settingsheading_desc'] = 'このサイトが通信するOER Exchangeを設定します。まず登録を行い、メールで届いたサイトキーを下の「サイトトークン」に貼り付けてください。';
$string['settings_exchangeurl'] = 'Exchange URL';
$string['settings_exchangeurl_desc'] = 'OER ExchangeサイトのベースURL（例: https://vagrant.wisecat.net）';
$string['settings_siteid'] = 'サイトID';
$string['settings_siteid_desc'] = '登録時にExchangeによって割り当てられます。登録ページによって自動的に設定されます。';
$string['settings_sitetoken'] = 'サイトトークン';
$string['settings_sitetoken_desc'] = 'Exchange管理者がこのサイトを承認した後にメールで送られるウェブサービストークン。';

// Registration.
$string['registertitle'] = 'Exchangeへの登録';
$string['registerintro'] = 'このサイトを設定済みのExchangeに登録します。サイトトークンを受け取るには、Exchange管理者がリクエストを承認する必要があります。';
$string['registerbutton'] = 'このサイトを登録する';
$string['registersuccess'] = '登録リクエストを送信しました。承認されるとメールでサイトトークンが届きますので確認してください。';
$string['registeractive'] = '登録済み・有効です（サイトID {$a}）。';
$string['registerpending'] = '登録は承認待ちです（サイトID {$a}）。メールでサイトトークンが届いたら設定に貼り付けてください。';
$string['sitecontact'] = '連絡先メールアドレス';
$string['error_noexchangeurl'] = 'まず設定でExchange URLを設定してください。';
$string['error_notregistered'] = 'このサイトはまだOER Exchangeに登録されていない（または未承認の）状態です。サイト管理 > プラグイン > Localプラグイン > OER Client を確認してください。';
$string['error_notlinked'] = 'まずあなたのExchangeアカウントをリンクしてください。';
$string['error_targetcourserequired'] = '単一の活動をインポートするには、対象コースの指定が必要です。';
$string['error_restoreprecheckfailed'] = 'このバックアップの復元事前チェックに失敗しました。';
$string['error_notargetcourses'] = 'インポート先として利用できるコースへの権限がありません。';
$string['exchangeerror'] = 'Exchangeエラー: {$a}';

// Account linking.
$string['connectintro'] = '自分自身としてリソースを共有・レビューするために、Exchange上の個人アカウントをリンクしてください。';
$string['connectsuccess'] = 'アカウントがExchangeにリンクされました。';
$string['linkaccount'] = '自分のExchangeアカウントをリンクする';
$string['linkedas'] = 'Exchangeアカウント #{$a} にリンクされています。';

// Browse.
$string['browseexchange'] = 'OER Exchangeを閲覧';
$string['searchbutton'] = '検索';
$string['nocatalogresources'] = '検索条件に一致するリソースがありません。';
$string['filterbytype'] = '種別';
$string['typecourse'] = 'コース';
$string['typeactivity'] = '活動';
$string['licenselabel'] = 'ライセンス: {$a}';
$string['requiredplugins'] = '必要なプラグイン';
$string['plugininstalled'] = 'このサイトにインストール済み';
$string['pluginmissing'] = '未インストール — スキップされます';
$string['structurepreview'] = '構造のプレビュー';
$string['sectionnumber'] = 'セクション {$a}';

// Share wizard.
$string['sharetoexchange'] = 'OER Exchangeへ共有';
$string['sharetitlelabel'] = 'タイトル';
$string['sharesummarylabel'] = '概要';
$string['sharelanguagelabel'] = '言語';
$string['sharetagslabel'] = 'タグ（カンマ区切り）';
$string['sharelicenselabel'] = 'ライセンス';
$string['sharesubmit'] = '共有する';
$string['sharequeued'] = 'キューに登録されました — 完了まで少し時間がかかる場合があります。状況はこちらに表示されます。';
$string['sharestatustitle'] = '共有ステータス';
$string['sharestatuslabel'] = 'ステータス: {$a}';
$string['sharestatus_pending'] = 'キュー待ち';
$string['sharestatus_backingup'] = 'サニタイズされたバックアップを作成中';
$string['sharestatus_uploading'] = 'Exchangeへアップロード中';
$string['sharestatus_published'] = '公開済み';
$string['sharestatus_failed'] = '失敗';
$string['viewonexchange'] = 'Exchangeで表示';

// Import.
$string['importheading'] = 'このリソースをインポート';
$string['importtargetcourse'] = 'インポート先コース';
$string['importbutton'] = 'インポート';
$string['importresulttitle'] = 'インポート完了';
$string['importsuccess'] = 'リソースは正常にインポートされました。';
$string['localizationchecklist'] = '学生と一緒に使用する前に、以下を確認してください。';
$string['gotocourse'] = 'コースへ移動';
$string['checklist_dates'] = '活動の期限日およびコース開始日 — これらは元のコースから引き継がれているため、更新が必要な場合があります。';
$string['checklist_names'] = '活動内容に記載されている氏名、所属機関、連絡先などの情報。';
$string['checklist_visibility'] = 'インポートされたコースは既定で非表示になっています — 内容を確認したうえで、学生に表示してください。';
$string['checklist_grading'] = '評定項目と評価尺度 — 成績表の設定と一致しているか確認してください。';
