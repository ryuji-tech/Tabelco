<?php
// ===================================================================
// read.php  ―  一覧画面（読み込み・表示）
// 役割：data/shops.csv を読み、保存された店舗をカードで一覧表示する。
//       4視点の 0/1/2 は、ここで ○△× のバッジに変換して表示する。
// ===================================================================

$file = __DIR__ . '/data/shops.csv';
$rows = [];      // 店舗データを入れる箱
$header = [];    // 見出し（1行目）

// --- 1) CSVを読み込む ---------------------------------------------
if (file_exists($file)) {
  $fp = fopen($file, 'r');          // 'r' = 読み込みモード
  $header = fgetcsv($fp);           // 最初の1行＝見出しを取り出す
  while (($data = fgetcsv($fp)) !== false) {   // 残りを1行ずつ読む
    if ($header && count($data) === count($header)) {
      // array_combine：見出しとデータを合体して「名前で取り出せる」形にする
      // 例：$r['店名']、$r['食器'] のようにアクセスできるようになる
      $rows[] = array_combine($header, $data);
    }
  }
  fclose($fp);
}

// 新しく保存したものを上に出したいので、並びを逆にする
$rows = array_reverse($rows);

// --- 2) 0/1/2 を ○△× に変換する小さな関数 -------------------------
// 戻り値は [記号, CSSクラス名] の2つ。クラス名で色を変える。
function mark($v) {
  if ($v === '2') return ['○', 'ok'];    // 対応あり → 緑
  if ($v === '1') return ['△', 'warn'];  // 要相談  → 橙
  return ['×', 'none'];                  // 情報なし → グレー
}

// htmlspecialchars：入力に < や " が混じっても画面が壊れない/乗っ取られない安全策
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// --- 3) ブロック別の件数を数える（進捗ダッシュボード用）--------------
// ブロックは保存時に駅から自動算出済み。下書きは進捗に数えない（公開のみ）。
$counts = [];
foreach ($rows as $r) {
  if (($r['公開状態'] ?? '公開') === '下書き') continue;
  $b = $r['ブロック'] ?? 'その他';
  $counts[$b] = ($counts[$b] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>検証済み店舗一覧 ｜ Tabelco</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="wrap">

  <header class="head">
    <div class="brand">Tabelco</div>
    <div class="sub">検証済み店舗一覧 ・ 全 <?php echo count($rows); ?> 軒</div>
  </header>

  <!-- ブロック別の進捗（目標 各10軒）。下書きを除いた公開分で数える -->
  <div class="counts">
    <?php foreach ($counts as $block => $n): ?>
      <div class="count-card">
        <div class="count-label"><?php echo h($block); ?> <span><?php echo $n; ?> / 10</span></div>
        <div class="bar"><div class="bar-in" style="width: <?php echo min($n * 10, 100); ?>%;"></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (count($rows) === 0): ?>
    <p class="empty">まだ登録がありません。<a href="index.php">フォームから追加</a>してください。</p>
  <?php endif; ?>

  <!-- 店舗カードを1行ずつ表示。$rows の1つ＝CSVの1行 -->
  <?php foreach ($rows as $r): ?>
    <?php
      // 4視点を変換しておく
      $d = mark($r['食器'] ?? '0');
      $n = mark($r['授乳'] ?? '0');
      $s = mark($r['ベビーカー'] ?? '0');
      $p = mark($r['おむつ'] ?? '0');
      $isDraft = (($r['公開状態'] ?? '公開') === '下書き');
    ?>
    <div class="shop<?php echo $isDraft ? ' draft' : ''; ?>">
      <div class="shop-head">
        <span class="shop-name">
          <?php echo h($r['店名']); ?>
          <?php if ($isDraft): ?><span class="draft-tag">下書き</span><?php endif; ?>
        </span>
        <span class="shop-date"><?php echo h($r['検証日']); ?></span>
      </div>
      <div class="shop-meta">
        <?php echo h($r['ブロック'] ?? ''); ?>
        ・ <?php echo h($r['沿線']); ?> <?php echo h($r['最寄駅']); ?> 徒歩<?php echo h($r['徒歩分']); ?>分
        ・ <?php echo h($r['ジャンル']); ?> ・ <?php echo h($r['価格帯']); ?>
        <?php if (($r['子連れ歓迎'] ?? '0') === '1'): ?>
          ・ <span class="welcome">子連れ歓迎(公式)</span>
        <?php endif; ?>
      </div>

      <?php // 住所（あれば）。郵便番号があれば頭に付ける
        $addr = trim($r['住所'] ?? '');
        $zip  = trim($r['郵便番号'] ?? '');
        if ($addr !== '' || $zip !== ''): ?>
        <div class="shop-addr">
          <?php if ($zip !== ''): ?>〒<?php echo h($zip); ?> <?php endif; ?><?php echo h($addr); ?>
        </div>
      <?php endif; ?>

      <?php // 時間帯（| 区切り）を小さなチップで表示
        $hrs = trim($r['時間帯'] ?? '');
        if ($hrs !== ''): ?>
        <div class="items-view">
          <?php foreach (explode('|', $hrs) as $hh): ?>
            <span class="hour-tag"><?php echo h($hh); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php // 写真（| 区切り）をサムネイルで表示。読み込めない時は枠だけ残す
        $photoStr = trim($r['写真'] ?? '');
        if ($photoStr !== ''): ?>
        <div class="thumbs">
          <?php foreach (explode('|', $photoStr) as $src): $src = trim($src); if ($src === '') continue; ?>
            <span class="thumb">
              <img src="<?php echo h($src); ?>" alt=""
                   loading="lazy"
                   onerror="this.parentNode.classList.add('broken'); this.remove();">
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="badges">
        <span class="badge <?php echo $d[1]; ?>">食器 <?php echo $d[0]; ?></span>
        <span class="badge <?php echo $n[1]; ?>">授乳 <?php echo $n[0]; ?></span>
        <span class="badge <?php echo $s[1]; ?>">ベビーカー <?php echo $s[0]; ?></span>
        <span class="badge <?php echo $p[1]; ?>">おむつ <?php echo $p[0]; ?></span>
      </div>

      <?php // 食器アイテム（| 区切り）を、あれば小さなタグで表示
        $items = trim($r['食器アイテム'] ?? '');
        if ($items !== ''): ?>
        <div class="items-view">
          <?php foreach (explode('|', $items) as $it): ?>
            <span class="item-tag"><?php echo h($it); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php // 設備タグ（| 区切り）
        $fac = trim($r['設備'] ?? '');
        if ($fac !== ''): ?>
        <div class="items-view">
          <?php foreach (explode('|', $fac) as $ft): ?>
            <span class="fac-tag"><?php echo h($ft); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <p class="link"><a href="index.php">← フォームに戻る</a></p>

</div>
</body>
</html>
