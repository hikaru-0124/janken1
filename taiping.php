<?php
// PHPで提供するタイピング課題のリスト
$phrases = [
    "PHPはサーバーサイドで動作するスクリプト言語です。",
    "ウェブ開発の現場で広く使われています。",
    "このゲームはJavaScriptと連携して動いています。",
    "プログラミング学習は継続が力になります。",
    "集中してミスタイプを減らしましょう。",
    "入力速度を上げるには練習が必要です。",
    "エラーメッセージはバグ発見のヒントです。",
    "次の課題文の読み込みを待っています。",
    "お疲れ様でした、もう一度挑戦しますか？"
];

// JavaScriptで使えるようにJSON形式でエンコード
$json_phrases = json_encode($phrases, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>PHP & JavaScript タイピングゲーム</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; background-color: #f0f2f5; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 800px; margin: 30px auto; }
        h1 { color: #333; margin-bottom: 30px; }
        #target-text { font-size: 24px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; min-height: 80px; text-align: left; line-height: 1.5; margin-bottom: 20px; background-color: #e9ecef; }
        #input-area { width: 98%; padding: 15px; font-size: 20px; border: 2px solid #5cb85c; border-radius: 8px; box-sizing: border-box; }
        .correct { color: green; font-weight: bold; }
        .incorrect { color: red; background-color: #ffdddd; font-weight: bold; }
        .current { background-color: #ffff99; border-bottom: 3px solid #f0ad4e; }
        .stats { margin-top: 20px; display: flex; justify-content: space-around; font-size: 1.1em; }
        .stat-item { padding: 10px; border: 1px solid #eee; border-radius: 5px; background-color: #f9f9f9; flex: 1; margin: 0 5px; }
        button { padding: 10px 20px; font-size: 18px; cursor: pointer; background-color: #007bff; color: white; border: none; border-radius: 5px; margin-top: 20px; }
        button:hover { background-color: #0056b3; }
        #timer { font-size: 1.5em; font-weight: bold; color: #d9534f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 タイピングゲーム </h1>
        <div id="target-text"></div>
        <input type="text" id="input-area" placeholder="ここに文字を入力してください" autofocus disabled>
        
        <div class="stats">
            <div class="stat-item">経過時間: <span id="timer">0.00</span> 秒</div>
            <div class="stat-item">ミスタイプ: <span id="miss-count">0</span> 回</div>
            <div class="stat-item">正解率: <span id="accuracy">100.0</span> %</div>
        </div>
        
        <button id="start-button">ゲームスタート</button>
    </div>

    <script>
        // PHPから受け取った課題文のリスト
        const PHRASES = <?php echo $json_phrases; ?>;
        
        // DOM要素
        const targetTextElement = document.getElementById('target-text');
        const inputArea = document.getElementById('input-area');
        const startButton = document.getElementById('start-button');
        const timerElement = document.getElementById('timer');
        const missCountElement = document.getElementById('miss-count');
        const accuracyElement = document.getElementById('accuracy');

        // ゲームの状態変数
        let currentPhraseIndex = 0;
        let currentText = '';
        let correctCount = 0;
        let missCount = 0;
        let startTime = 0;
        let timerInterval = null;
        let totalInputLength = 0;

        /**
         * ゲームを初期化し、最初の課題文を表示する
         */
        function initializeGame() {
            currentPhraseIndex = 0;
            missCount = 0;
            correctCount = 0;
            totalInputLength = 0;
            missCountElement.textContent = 0;
            timerElement.textContent = '0.00';
            accuracyElement.textContent = '100.0';
            inputArea.value = '';
            inputArea.disabled = true;
            inputArea.removeEventListener('input', handleInput);
            startButton.style.display = 'block';

            loadPhrase();
        }

        /**
         * 新しい課題文を読み込み、表示を更新する
         */
        function loadPhrase() {
            if (currentPhraseIndex < PHRASES.length) {
                currentText = PHRASES[currentPhraseIndex];
                updateTargetTextDisplay(0);
            } else {
                // すべての課題文が終了した場合
                endGame(true);
            }
        }

        /**
         * ターゲットとなるテキストの表示を更新する
         * @param {number} inputLength - 現在の入力文字数
         */
        function updateTargetTextDisplay(inputLength) {
            let displayText = '';
            for (let i = 0; i < currentText.length; i++) {
                const char = currentText[i];
                let className = '';

                if (i < inputLength) {
                    // 入力済み
                    if (char === inputArea.value[i]) {
                        className = 'correct';
                    } else {
                        className = 'incorrect';
                    }
                } else if (i === inputLength) {
                    // 次に入力すべき文字
                    className = 'current';
                }

                displayText += `<span class="${className}">${char}</span>`;
            }
            targetTextElement.innerHTML = displayText;
        }

        /**
         * ユーザーの入力があったときの処理
         */
        function handleInput() {
            const inputValue = inputArea.value;
            const inputLength = inputValue.length;
            totalInputLength++; // 総入力回数をカウント

            // ミスタイプの判定とカウント
            if (inputLength > 0) {
                const lastChar = inputValue[inputLength - 1];
                const targetChar = currentText[inputLength - 1];

                if (lastChar !== targetChar) {
                    missCount++;
                } else {
                    correctCount++;
                }
                
                missCountElement.textContent = missCount;
                
                // 正解率の計算と表示
                const accuracy = (correctCount / (correctCount + missCount)) * 100;
                accuracyElement.textContent = (isNaN(accuracy) ? 100.0 : accuracy).toFixed(1);
            }
            
            // 表示の更新
            updateTargetTextDisplay(inputLength);

            // 課題文の終了判定
            if (inputLength === currentText.length) {
                currentPhraseIndex++;
                inputArea.value = '';
                // 次の課題文へ
                setTimeout(loadPhrase, 500); // 0.5秒待って次へ
            }
        }

        /**
         * タイマーを開始する
         */
        function startTimer() {
            startTime = Date.now();
            timerInterval = setInterval(() => {
                const elapsed = (Date.now() - startTime) / 1000;
                timerElement.textContent = elapsed.toFixed(2);
            }, 10);
        }

        /**
         * ゲームを開始する
         */
        function startGame() {
            startButton.style.display = 'none';
            inputArea.disabled = false;
            inputArea.focus();
            inputArea.addEventListener('input', handleInput);
            
            // 状態のリセット
            missCount = 0;
            correctCount = 0;
            totalInputLength = 0;
            missCountElement.textContent = 0;
            accuracyElement.textContent = '100.0';

            startTimer();
            loadPhrase();
        }

        /**
         * ゲームを終了する
         * @param {boolean} completed - 全ての課題文を完了したか
         */
        function endGame(completed) {
            clearInterval(timerInterval);
            inputArea.disabled = true;
            inputArea.removeEventListener('input', handleInput);
            
            const finalTime = timerElement.textContent;
            const finalAccuracy = accuracyElement.textContent;

            if (completed) {
                targetTextElement.innerHTML = `<span class="correct">🎉 ゲームクリア！ 🎉</span><br><br>タイム: **${finalTime}秒**<br>ミスタイプ: **${missCount}回**<br>正解率: **${finalAccuracy}%**`;
            } else {
                targetTextElement.innerHTML = `ゲーム終了。**リスタート**ボタンを押してください。`;
            }
            
            startButton.textContent = "リスタート";
            startButton.style.display = 'block';
            startButton.onclick = initializeGame; // リスタートボタンとして機能変更
        }

        // 初期化処理
        initializeGame();
        
        // スタートボタンのイベントリスナー設定
        startButton.addEventListener('click', startGame);

    </script>
</body>
</html>
