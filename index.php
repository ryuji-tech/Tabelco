<?php
// ===================================================================
// index.php  ―  店舗検証フォーム（入力画面）
// 役割：運営が店を現地確認した内容を入力する画面。
//       送信すると create.php にデータが送られ、CSVに保存される。
// ===================================================================

// このページでも使う選択肢は、まとめて配列で持っておく。
// （create.php と read.php でも同じ選択肢を使うので、本来は1ファイルに
//   まとめて読み込むのが理想。今は学習段階なので各ファイルに置いている。）

// ジャンル：大手サイトを参考に、家族向けに絞った中庸なリスト（居酒屋も含む）
$genres = ['和食・定食','寿司','うどん・そば','ラーメン','洋食','イタリアン・パスタ',
           'フレンチ','中華','焼肉・ステーキ','カレー','カフェ・喫茶','ファミレス',
           'ベーカリー','スイーツ・甘味','居酒屋','その他'];

// 設備タグ：LENS（4視点）の外側の属性。チェックで複数選べる。
$facilities = ['個室','座敷・小上がり','全席禁煙','テラス席','駐車場あり',
               'キッズメニュー','ノンアル充実'];

// 時間帯：一軒が複数の時間帯をやるのでチェック（複数可）
$hours = ['モーニング','ランチ','ディナー'];

// 沿線 → その沿線の駅一覧（福岡市地下鉄・公式の駅ナンバリング順）。
// 沿線を選ぶと、その沿線の駅だけを最寄駅プルダウンに出す（下のJSで連動）。
// ※中洲川端は空港線・箱崎線の共用、博多は空港線・七隈線の共用なので両方に載る。
$stationsByLine = [
  '空港線' => ['姪浜','室見','藤崎','西新','唐人町','大濠公園','赤坂','天神',
               '中洲川端','祇園','博多','東比恵','福岡空港'],
  '箱崎線' => ['中洲川端','呉服町','千代県庁口','馬出九大病院前','箱崎宮前',
               '箱崎九大前','貝塚'],
  '七隈線' => ['橋本','次郎丸','賀茂','野芥','梅林','福大前','七隈','金山','茶山',
               '別府','六本松','桜坂','薬院大通','薬院','渡辺通','天神南',
               '櫛田神社前','博多'],
];
$lines = array_keys($stationsByLine);
$firstLine = $lines[0];                 // 初期表示の沿線（空港線）
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>店舗検証フォーム ｜ Tabelco</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="wrap">

  <header class="head">
    <div class="brand">Tabelco</div>
    <div class="sub">店舗検証フォーム（運営用）</div>
  </header>

  <form action="create.php" method="post" class="card">

    <p class="section">公開状態</p>
    <div class="lens">
      <!--
        公開状態：まだ見せたくない記録は「下書き」にして隠せる。
        現地でメモだけ先に取って、後から仕上げて公開、という使い方ができる。
      -->
      <div class="radios">
        <label><input type="radio" name="published" value="公開" checked> 公開</label>
        <label><input type="radio" name="published" value="下書き"> 下書き</label>
      </div>
    </div>

    <p class="section">基本情報</p>

    <label>店名 <span class="req">*</span></label>
    <input type="text" name="name" required placeholder="まんぷく食堂 西新">

    <div class="grid2">
      <div>
        <label>読みがな</label>
        <input type="text" name="kana" placeholder="まんぷくしょくどう">
      </div>
      <div>
        <label>検証日</label>
        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
      </div>
    </div>

    <div class="grid3">
      <div>
        <label>沿線</label>
        <!-- 沿線を選ぶと、下の「最寄駅」がその沿線の駅だけに切り替わる（連動はJS） -->
        <select name="line" id="line">
          <?php foreach ($lines as $ln): ?>
            <option><?php echo $ln; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>最寄駅</label>
        <!--
          沿線ごとに駅が変わるので、ここは沿線選択に連動して中身を作り直す。
          初期表示は最初の沿線（空港線）の駅。JSが無効でも空港線の駅は選べる。
          ※エリア(ブロック)は入力させず、駅から create.php で自動算出する。
        -->
        <select name="station" id="station">
          <?php foreach ($stationsByLine[$firstLine] as $st): ?>
            <option><?php echo $st; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>徒歩(分)</label>
        <input type="number" name="walk" min="0" placeholder="5">
      </div>
    </div>

    <div class="grid3">
      <div>
        <label>郵便番号</label>
        <!-- 郵便番号：今は手入力。将来ここから住所を自動入力できるようにする
             予定（郵便番号API＋JS）。先に「器」だけ用意しておく。 -->
        <input type="text" name="zip" id="zip" placeholder="814-0002" inputmode="numeric">
      </div>
      <div style="grid-column: span 2;">
        <label>住所</label>
        <!-- 住所：店舗ページの地図表示に将来必ず要る。後から貯めたデータに
             足すのは手間なので、今のうちに1欄だけ持っておく。 -->
        <input type="text" name="address" id="address" placeholder="福岡市早良区西新4-8-xx">
      </div>
    </div>

    <div class="grid2">
      <div>
        <label>ジャンル</label>
        <select name="genre">
          <?php foreach ($genres as $g): ?>
            <option><?php echo $g; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>価格帯</label>
        <select name="price">
          <option>〜1000円</option><option>〜1500円</option>
          <option>〜2000円</option><option>2000円〜</option>
        </select>
      </div>
    </div>

    <label>営業時間帯（複数可）</label>
    <!-- name="hours[]" の [] で、チェックした複数の値が配列で届く。
         将来、時間帯での絞り込み検索に使える。 -->
    <div class="items">
      <?php foreach ($hours as $h): ?>
        <label><input type="checkbox" name="hours[]" value="<?php echo $h; ?>"> <?php echo $h; ?></label>
      <?php endforeach; ?>
    </div>

    <label class="check">
      <input type="checkbox" name="welcome" value="1">
      子連れ歓迎を、店が公式に表明している
    </label>

    <p class="section">4つの確認視点（情報なし=0 / 要相談=1 / 対応あり=2）</p>

    <!-- ▼ 食器まわりだけは特別：品目をチェックで選べるようにする -->
    <div class="lens">
      <div class="lens-title">食器まわり</div>
      <div class="radios">
        <label><input type="radio" name="dish" value="0" checked> 情報なし</label>
        <label><input type="radio" name="dish" value="1"> 要相談</label>
        <label><input type="radio" name="dish" value="2"> 対応あり</label>
      </div>
      <!--
        name="dish_items[]" のように [] を付けると、
        チェックした複数の値が「配列」としてまとめて送られる。
        create.php 側で受け取って「|」で繋いで保存する。
      -->
      <div class="items">
        <?php
        $dishItems = ['子ども用箸・スプーン・フォーク','取り皿（仕切り皿）','こぼれにくいコップ',
                      '麺きりカッター・はさみ','お食事エプロン','子ども椅子'];
        foreach ($dishItems as $item):
        ?>
          <label><input type="checkbox" name="dish_items[]" value="<?php echo $item; ?>"> <?php echo $item; ?></label>
        <?php endforeach; ?>
      </div>
      <input type="text" name="dish_memo" placeholder="例：コップは要相談・温かい飲み物は陶器で提供">
    </div>

    <!-- ▼ 残り3つは共通の形。視点ごとに違う記載例(placeholder)を出す -->
    <?php
    // [内部キー => [見出し, メモの記載例]]
    $lenses = [
      'nurse'    => ['授乳スペース', '例：専用室あり／個室を授乳に使える（要ひとこと声かけ）'],
      'stroller' => ['ベビーカー',   '例：そのまま入店可／入口に置き場あり・店内は畳んで'],
      'diaper'   => ['おむつ替え',   '例：多目的トイレに設備あり／店内なし・徒歩2分の◯◯2Fに'],
    ];
    foreach ($lenses as $key => $info):
    ?>
      <div class="lens">
        <div class="lens-title"><?php echo $info[0]; ?></div>
        <div class="radios">
          <label><input type="radio" name="<?php echo $key; ?>" value="0" checked> 情報なし</label>
          <label><input type="radio" name="<?php echo $key; ?>" value="1"> 要相談</label>
          <label><input type="radio" name="<?php echo $key; ?>" value="2"> 対応あり</label>
        </div>
        <input type="text" name="<?php echo $key; ?>_memo" placeholder="<?php echo $info[1]; ?>">
      </div>
    <?php endforeach; ?>

    <p class="section">設備（複数可）</p>
    <!-- 設備タグ：LENS外の属性。店舗ページのチップにも将来のフィルタにもなる。 -->
    <div class="items">
      <?php foreach ($facilities as $f): ?>
        <label><input type="checkbox" name="facilities[]" value="<?php echo $f; ?>"> <?php echo $f; ?></label>
      <?php endforeach; ?>
    </div>

    <p class="section">写真・コメント</p>

    <label>写真パス／URL（1行に1つ）</label>
    <textarea name="photos" rows="3" placeholder="img/manpuku_hero.jpg&#10;img/manpuku_dish.jpg"></textarea>

    <label>運営コメント</label>
    <textarea name="comment" rows="3" placeholder="2歳児と訪問。店主が席まで子供椅子を運んでくれた。"></textarea>

    <button type="submit" class="btn">この内容で保存する</button>
  </form>

  <p class="link"><a href="read.php">→ 保存した店舗の一覧を見る</a></p>

</div>

<script>
// 沿線 → 駅の連動。PHPの $stationsByLine をそのままJSに渡す。
// json_encode で「PHPの配列」を「JSのオブジェクト」に変換している。
const stationsByLine = <?php echo json_encode($stationsByLine, JSON_UNESCAPED_UNICODE); ?>;
const lineEl    = document.getElementById('line');
const stationEl = document.getElementById('station');

// 選ばれた沿線の駅で、最寄駅プルダウンを作り直す
function refreshStations() {
  const list = stationsByLine[lineEl.value] || [];
  stationEl.innerHTML = '';                      // いったん空にする
  for (const name of list) {
    const opt = document.createElement('option');
    opt.textContent = name;
    stationEl.appendChild(opt);
  }
}
lineEl.addEventListener('change', refreshStations);

// （将来）郵便番号から住所を自動入力する処理は、ここに足す予定。
// 例：zip欄を見て郵便番号APIに問い合わせ→address欄を埋める。
</script>
</body>
</html>
