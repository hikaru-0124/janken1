<?php
// 1. セッション開始と定数定義
session_start();

// 定数の定義
const ROCK = 0;    // ✊ グー
const PAPER = 1;   // ✋ パー
const SCISSORS = 2; // ✌️ チョキ

// 2. セッション変数の初期化（スコア管理）
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = [
        'win' => 0,
        'lose' => 0,
        'draw' => 0,
        'history' => [] // ユーザーの過去の手の履歴
    ];
}

// 3. 変数の初期化
$user_choice = null;
$computer_choice = null;
$result = null;
$message_class = '';

// 4. 表示用の関数
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

// 5. コンピュータの手の決定（簡易戦略付き）
function get_computer_choice() {
    $history = $_SESSION['score']['history'];
    
    // 履歴が少ない、または戦略を適用しない確率（例: 5回に1回はランダム）
    if (count($history) < 5 || rand(1, 5) === 1) {
        return rand(0, 2); // ランダム
    }

    // 過去5回の履歴から最も出された手を分析
    $recent_history = array_slice($history, -5);
    $counts = array_count_values($recent_history);

    // 最も出された手を特定
    $most_frequent_hand = -1;
    $max_count = 0;
    foreach ($counts as $hand => $count) {
        if ($count > $max_count) {
            $max_count = $count;
            $most_frequent_hand = $hand;
        }
    }
    
    // 戦略：ユーザーが最も出しやすい手に勝てる手を出す
    if ($most_frequent_hand !== -1) {
        // (最も出された手 + 1) % 3 が、その手に勝てる手
        // ROCK(0) -> PAPER(1) / PAPER(1) -> SCISSORS(2) / SCISSORS(2) -> ROCK(0)
        return ($most_frequent_hand + 1) % 3;
    }
    
    return rand(0, 2); // フォールバック
}


// 6. フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // スコアリセット処理
    if (isset($_POST['reset_score'])) {
        $_SESSION['score'] = [
            'win' => 0,
            'lose' => 0,
            'draw' => 0,
            'history' => []
        ];
        // リダイレクトしてPOSTデータをクリア
        header('Location: janken.php');
        exit;
    }

    // じゃんけん実行処理
    if (isset($_POST['user_choice'])) {
        $user_choice = (int)$_POST['user_choice'];
        
        // バリデーションチェック
        if (!in_array($user_choice, [ROCK, PAPER, SCISSORS])) {
             $result = '不正な入力です。';
             $message_class = 'alert';
        } else {
            
            $computer_choice = get_computer_choice();

            // 勝敗判定ロジック: (U - C + 3) % 3
            $judge = ($user_choice - $computer_choice + 3) % 3;
            
            // ユーザーの履歴を記録
            $_SESSION['score']['history'][] = $user_choice;

            if ($judge === 0) {
                $result = '引き分けです。もう一度！';
                $_SESSION['score']['draw']++;
                $message_class = 'draw';
            } elseif ($judge === 1) {
                $result = '🎉 あなたの**勝利**です！お見事！';
                $_SESSION['score']['win']++;
                $message_class = 'win';
            } else { // $judge === 2
                $result = '😫 コンピュータの**勝利**です。';
                $_SESSION['score']['lose']++;
                $message_class = 'lose';
            }
        }
    }
}

// 7. 選択肢の配列 (フォーム表示用)
$hands = [
    ROCK => 'グー ✊',
    PAPER => 'パー ✋',
    SCISSORS => 'チョキ ✌️',
];

// 8. スコア計算
$total_games = $_SESSION['score']['win'] + $_SESSION['score']['lose'] + $_SESSION['score']['draw'];
$win_rate = ($total_games > 0) ? round(($_SESSION['score']['win'] / $total_games) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>じゃんけんゲーム V2</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; margin-top: 50px; background-color: #f4f4f9; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); max-width: 600px; margin: auto; }
        h1 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .hand-button { font-size: 24px; padding: 15px 30px; margin: 8px; cursor: pointer; border: none; border-radius: 8px; transition: all 0.2s ease; box-shadow: 0 4px #999; }
        .hand-button:active { box-shadow: 0 2px #666; transform: translateY(2px); }
        .rock { background-color: #ffcccc; }
        .paper { background-color: #ccffcc; }
        .scissors { background-color: #ccccff; }
        
        .result-box { margin-top: 25px; padding: 20px; border-radius: 10px; font-weight: bold; font-size: 1.3em; animation: fadeIn 0.5s; }
        .win { background-color: #d4edda; color: #155724; border: 2px solid #c3e6cb; transform: scale(1.05); }
        .lose { background-color: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        .draw { background-color: #fff3cd; color: #856404; border: 2px solid #ffeeba; }
        .alert { background-color: #fce3e6; color: #a51835; }
        
        .choices-display { margin: 20px 0; font-size: 1.2em; border-bottom: 1px dashed #ddd; padding-bottom: 15px; }
        .score-board { display: flex; justify-content: space-around; margin-top: 20px; padding: 15px; background-color: #f0f8ff; border-radius: 8px; }
        .score-item { text-align: center; font-size: 1.1em; }
        .score-item span { display: block; font-size: 1.8em; font-weight: bold; color: #007bff; }
        
        .reset-form { margin-top: 20px; }
        .reset-button { background-color: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>✊✋✌️ AIじゃんけんチャレンジ V2 ✌️✋✊</h1>

        <div class="score-board">
            <div class="score-item">勝利<span><?php echo $_SESSION['score']['win']; ?></span></div>
            <div class="score-item">敗北<span><?php echo $_SESSION['score']['lose']; ?></span></div>
            <div class="score-item">引き分け<span><?php echo $_SESSION['score']['draw']; ?></span></div>
            <div class="score-item">勝率<span><?php echo $win_rate; ?>%</span></div>
        </div>

        <?php if ($result !== null): ?>
            <div class="choices-display">
                <p>あなた: **<?php echo get_hand_name($user_choice); ?>**</p>
                <p>コンピュータ: **<?php echo get_hand_name($computer_choice); ?>**</p>
            </div>
            
            <div class="result-box <?php echo $message_class; ?>">
                <?php echo $result; ?>
            </div>
            <p style="margin-top: 20px;">さあ、次の勝負です！</p>

        <?php else: ?>
            <p>あなたの手を決めて、コンピュータの戦略に挑んでください。</p>
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
        
        <form method="POST" action="janken.php" class="reset-form">
            <button type="submit" name="reset_score" class="reset-button">スコアをリセット</button>
        </form>
        
    </div>
</body>
</html>