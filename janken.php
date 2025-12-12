<?php

// 1. 定数の定義
const ROCK = 0;    // ✊ グー
const PAPER = 1;   // ✋ パー
const SCISSORS = 2; // ✌️ チョキ

// 2. 変数の初期化
$user_choice = null;
$computer_choice = null;
$result = null;

// 3. フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_choice'])) {
    // ユーザーの選択を取得
    $user_choice = (int)$_POST['user_choice'];

    // コンピュータの選択をランダムに決定 (0: グー, 1: パー, 2: チョキ)
    $computer_choice = rand(0, 2);

    // 勝敗判定
    // (ユーザーの選択 - コンピュータの選択 + 3) % 3 で判定
    // 0: 引き分け, 1: ユーザーの勝ち, 2: コンピュータの勝ち
    $judge = ($user_choice - $computer_choice + 3) % 3;

    if ($judge === 0) {
        $result = '引き分けです！';
    } elseif ($judge === 1) {
        $result = 'あなたの**勝ち**です！おめでとうございます！';
    } else { // $judge === 2
        $result = 'コンピュータの**勝ち**です。残念！';
    }
}

// 4. 表示用の関数と配列
// 選択肢を数値から文字と絵文字に変換する関数
function get_hand_name($hand) {
    switch ($hand) {
        case ROCK:
            return 'グー ✊';
        case PAPER:
            return 'パー ✋';
        case SCISSORS:
            return 'チョキ ✌️';
        default:
            return '不明';
    }
}

// 選択肢の配列 (フォーム表示用)
$hands = [
    ROCK => 'グー ✊',
    PAPER => 'パー ✋',
    SCISSORS => 'チョキ ✌️',
];

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>じゃんけんゲーム</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; margin-top: 50px; background-color: #f4f4f9; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 400px; margin: auto; }
        h1 { color: #333; }
        .hand-button { font-size: 20px; padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 5px; transition: background-color 0.3s; }
        .hand-button:hover { background-color: #e0e0e0; }
        .rock { background-color: #ff9999; }
        .paper { background-color: #99ff99; }
        .scissors { background-color: #9999ff; }
        .result { margin-top: 20px; padding: 15px; border-radius: 5px; font-weight: bold; font-size: 1.2em; }
        .win { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .lose { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .draw { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .choices { margin-top: 20px; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✊✋✌️ じゃんけんゲーム ✌️✋✊</h1>

        <?php if ($result !== null): ?>
            <div class="choices">
                <p>あなた: **<?php echo get_hand_name($user_choice); ?>**</p>
                <p>コンピュータ: **<?php echo get_hand_name($computer_choice); ?>**</p>
            </div>
            
            <?php 
                $result_class = '';
                if (strpos($result, '勝ち') !== false && strpos($result, 'あなたの') !== false) {
                    $result_class = 'win';
                } elseif (strpos($result, '勝ち') !== false && strpos($result, 'コンピュータの') !== false) {
                    $result_class = 'lose';
                } else {
                    $result_class = 'draw';
                }
            ?>
            <div class="result <?php echo $result_class; ?>">
                <?php echo $result; ?>
            </div>
            <hr>
            <p>もう一度勝負しますか？</p>
        <?php else: ?>
            <p>あなたの手を決めてください。</p>
        <?php endif; ?>

        <form method="POST" action="janken.php">
            <?php foreach ($hands as $value => $name): ?>
                <button type="submit" name="user_choice" value="<?php echo $value; ?>" class="hand-button 
                    <?php 
                        if ($value === ROCK) echo 'rock';
                        elseif ($value === PAPER) echo 'paper';
                        else echo 'scissors';
                    ?>">
                    <?php echo $name; ?>
                </button>
            <?php endforeach; ?>
        </form>
    </div>
</body>
</html>
