<?php
// ===================================================================
// create.php  ―  保存処理（書き込み）
// 役割：index.php から送られた入力を data/shops.csv に1行 追記する。
// ===================================================================

// --- 駅 → ブロックの対応表（連想配列）---------------------------------
// エリア(ブロック)はフォームで入力させず、選んだ駅からここで自動算出する。
// こうすると「駅は全域カバー・ブロックは穴あき」という不一致が起きない。
// 表にない駅はすべて「その他」になる。（PHPの典型パターン：キーで値を引く）
$blockOf = [
  '西新' => '西新・藤崎', '藤崎' => '西新・藤崎',
  '姪浜' => '姪浜・室見', '室見' => '姪浜・室見',
];
$station = $_POST['station'] ?? '';
$block   = $blockOf[$station] ?? 'その他';   // 表になければ「その他」

// --- id と 登録日時（自動）-------------------------------------------
// id：各店に一意の番号。将来の「編集・削除」「お気に入り」「送客ログ」が
//     全部これを参照する。後付けは全行に振り直しが必要なので今のうちに。
// created_at：現地で見た「検証日」とは別に、記録した日時を自動で打つ。
$id        = uniqid();              // 例：6655f3a1c9e0b
$createdAt = date('Y-m-d H:i:s');

// チェックボックス（子連れ歓迎）：チェック時だけ '1'、無ければ '0'
$welcome = isset($_POST['welcome']) ? '1' : '0';

// 複数チェック（配列で届く）は implode('|', ...) で1つの文字列にまとめる
$dishItems  = isset($_POST['dish_items'])  ? implode('|', $_POST['dish_items'])  : '';
$hours      = isset($_POST['hours'])       ? implode('|', $_POST['hours'])       : '';
$facilities = isset($_POST['facilities'])  ? implode('|', $_POST['facilities'])  : '';

// 写真欄：改行区切り → 「|」区切りに変換してCSVの1マスに収める
$photos = trim($_POST['photos'] ?? '');
$photos = preg_replace('/\r\n|\r|\n/', '|', $photos);

// CSVに書く1行ぶん（並び順は read.php の見出しと そろえる）
$row = [
  $id,
  $_POST['published']     ?? '公開',
  $_POST['name']          ?? '',
  $_POST['kana']          ?? '',
  $_POST['line']          ?? '',
  $station,
  $block,                              // ★駅から自動算出したブロック
  $_POST['zip']           ?? '',       // ★郵便番号（今は手入力）
  $_POST['address']       ?? '',       // ★住所
  $_POST['walk']          ?? '',
  $_POST['genre']         ?? '',
  $_POST['price']         ?? '',
  $hours,                              // ★営業時間帯（| 区切り）
  $welcome,
  $_POST['dish']          ?? '0',
  $dishItems,
  $_POST['dish_memo']     ?? '',
  $_POST['nurse']         ?? '0',
  $_POST['nurse_memo']    ?? '',
  $_POST['stroller']      ?? '0',
  $_POST['stroller_memo'] ?? '',
  $_POST['diaper']        ?? '0',
  $_POST['diaper_memo']   ?? '',
  $facilities,                         // ★設備タグ（| 区切り）
  $photos,
  $_POST['comment']       ?? '',
  $_POST['date']          ?? '',
  $createdAt,                          // ★登録日時（自動）
];

// 保存先
$dir  = __DIR__ . '/data';
$file = $dir . '/shops.csv';
if (!is_dir($dir)) {
  mkdir($dir, 0777, true);
}
$isNew = !file_exists($file);

// 'a' = 追記モード
$fp = fopen($file, 'a');

// 初回だけ見出し（ヘッダー）を書く ※列の並びは $row と一致させる
if ($isNew) {
  fputcsv($fp, [
    'id','公開状態','店名','読み','沿線','最寄駅','ブロック','郵便番号','住所','徒歩分',
    'ジャンル','価格帯','時間帯','子連れ歓迎',
    '食器','食器アイテム','食器メモ','授乳','授乳メモ','ベビーカー','ベビーカーメモ',
    'おむつ','おむつメモ','設備','写真','運営コメント','検証日','登録日時'
  ]);
}

fputcsv($fp, $row);   // カンマ・改行は自動で「"」で囲ってくれる
fclose($fp);

header('Location: read.php');
exit;
