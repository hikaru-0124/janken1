<?php
// PHPはファイルの構造と基本的なデータを提供するのみ
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チンチロリン</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; background-color: #f4f4f9; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        h1 { color: #333; margin-bottom: 20px; }
        
        .dice-display { font-size: 40px; margin: 30px 0; min-height: 50px; }
        .dice-display span { display: inline-block; margin: 0 10px; width: 50px; height: 50px; line-height: 50px; border: 2px solid #555; border-radius: 5px; background-color: #fff; box-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
        
        button { padding: 12px 25px; font-size: 18px; cursor: pointer; background-color: #d9534f; color: white; border: none; border-radius: 8px; margin: 10px; transition: background-color 0.3s; }
        button:hover { background-color: #c9302c; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }

        #game-message { font-size: 1.4em; font-weight: bold; margin: 20px 0; min-height: 30px; }
        .score-board { display: flex; justify-content: space-around; margin-top: 20px; border-top: 1px dashed #ddd; padding-top: 15px; }
        .score-board div { text-align: center; font-size: 1.1em; }
        .score-board strong { display: block; font-size: 2em; color: #333; }

        .result-detail { margin-top: 15px; padding: 10px; border: 1px dashed #e0e0e0; background-color: #f9f9f9; }

    </style>
</head>
<body>
    <div class="container">
        <h1>🎲 チンチロリン 🎲</h1>

        <div id="game-message">「ゲーム開始」を押してください。</div>

        <div class="dice-display" id="player-dice-display">あなた: --- --- ---</div>
        <div class="dice-display" id="computer-dice-display">コンピュータ: --- --- ---</div>
        
        <div class="result-detail" id="result-detail"></div>

        <button id="start-button" onclick="startGame()">ゲーム開始</button>
        <button id="roll-button" onclick="rollDice()" disabled>サイコロを振る (1/3)</button>
        <button id="compare-button" onclick="compareResults()" disabled>勝敗を比較</button>

        <div class="score-board">
            <div>あなたの得点<strong id="player-score">0</strong></div>
            <div>コンピュータ得点<strong id="computer-score">0</strong></div>
        </div>
    </div>

    <script>
        // --- 状態変数 ---
        const MAX_ROLLS = 3;
        let playerScore = 0;
        let computerScore = 0;
        let isPlayerTurn = false;
        let rollsLeft = MAX_ROLLS;
        let playerDice = [];
        let computerDice = [];

        // --- DOM要素 ---
        const msgEl = document.getElementById('game-message');
        const pDiceEl = document.getElementById('player-dice-display');
        const cDiceEl = document.getElementById('computer-dice-display');
        const pScoreEl = document.getElementById('player-score');
        const cScoreEl = document.getElementById('computer-score');
        const startBtn = document.getElementById('start-button');
        const rollBtn = document.getElementById('roll-button');
        const compareBtn = document.getElementById('compare-button');
        const resultDetailEl = document.getElementById('result-detail');

        // --- チンチロリン判定ロジック ---
        /**
         * サイコロの出目から役と点数を判定する
         * @param {number[]} dice - 3つのサイコロの出目配列 (例: [1, 2, 3])
         * @returns {{hand: string, point: number, isBigger: boolean}} - 役名、点数、役の強さ (ゾロ目やシゴロは true)
         */
        function evaluateDice(dice) {
            dice.sort((a, b) => a - b);
            const [d1, d2, d3] = dice;

            // ゾロ目 (トリプル)
            if (d1 === d2 && d2 === d3) {
                if (d1 === 1) return { hand: "ピンゾロ (役満)", point: 6, isBigger: true }; // 6倍点
                if (d1 === 6) return { hand: "嵐 (役満)", point: 5, isBigger: true }; // 5倍点
                return { hand: `ゾロ目 (${d1})`, point: d1, isBigger: true }; // d1倍点
            }

            // シゴロ (4, 5, 6)
            if (d1 === 4 && d2 === 5 && d3 === 6) {
                return { hand: "シゴロ (最強役)", point: 4, isBigger: true }; // 4倍点
            }

            // ヒフミ (1, 2, 3)
            if (d1 === 1 && d2 === 2 && d3 === 3) {
                return { hand: "ヒフミ (役なし/最弱)", point: 0, isBigger: false }; // 0点
            }

            // 目なし (三枚バラ)
            if (d1 !== d2 && d2 !== d3 && d1 !== d3) {
                return { hand: "目なし", point: 0, isBigger: false }; // 0点
            }

            // 役 (二つの目が同じ場合)
            if (d1 === d2) return { hand: `X・X・${d3} (x${d3}点)`, point: d3, isBigger: false };
            if (d1 === d3) return { hand: `X・${d2}・X (x${d2}点)`, point: d2, isBigger: false };
            if (d2 === d3) return { hand: `${d1}・X・X (x${d1}点)`, point: d1, isBigger: false };
            
            // 例外的な目なしの判定
            return { hand: "目なし", point: 0, isBigger: false };
        }

        // --- ゲームフロー関数 ---

        /**
         * サイコロの目を生成 (1〜6)
         * @returns {number[]} 3つのサイコロの目
         */
        function generateRoll() {
            return [
                Math.floor(Math.random() * 6) + 1,
                Math.floor(Math.random() * 6) + 1,
                Math.floor(Math.random() * 6) + 1
            ];
        }

        /**
         * サイコロの目をHTMLで表示する
         * @param {HTMLElement} element - 表示先のDOM要素
         * @param {number[]} dice - サイコロの目
         */
        function renderDice(element, dice) {
            element.innerHTML = dice.map(d => `<span>${d}</span>`).join('');
        }

        /**
         * ゲーム開始時の初期化
         */
        function startGame() {
            playerDice = [];
            computerDice = [];
            rollsLeft = MAX_ROLLS;
            isPlayerTurn = true;
            
            pDiceEl.innerHTML = "--- --- ---";
            cDiceEl.innerHTML = "--- --- ---";
            resultDetailEl.textContent = "";

            startBtn.disabled = true;
            rollBtn.disabled = false;
            compareBtn.disabled = true;

            msgEl.textContent = "あなたのターンです。「サイコロを振る」を押してください。";
        }

        /**
         * サイコロを振る処理
         */
        function rollDice() {
            if (isPlayerTurn) {
                playerDice = generateRoll();
                rollsLeft--;
                renderDice(pDiceEl, playerDice);
                
                const pResult = evaluateDice(playerDice);
                
                msgEl.textContent = `出た目: ${pResult.hand}. 残り ${rollsLeft} 回振れます。`;
                
                // 役が出た、または振り直し回数が尽きたらプレイヤーのターン終了
                if (pResult.point > 0 || rollsLeft === 0) {
                    endPlayerTurn();
                } else {
                    rollBtn.textContent = `サイコロを振る (${MAX_ROLLS - rollsLeft + 1}/${MAX_ROLLS})`;
                }
                
            } else {
                // コンピュータのロール処理 (自動)
                computerDice = generateRoll();
                const cResult = evaluateDice(computerDice);

                // コンピュータの戦略: 役なしなら最大2回まで振り直し
                if (cResult.point === 0 && rollsLeft > 0) {
                    rollsLeft--;
                    setTimeout(rollDice, 1000); // 1秒待って振り直し
                    msgEl.textContent = `コンピュータは振り直します... (残り ${rollsLeft} 回)`;
                } else {
                    renderDice(cDiceEl, computerDice);
                    msgEl.textContent = `コンピュータの出目: ${cResult.hand}. 比較ボタンを押してください。`;
                    rollBtn.disabled = true;
                    compareBtn.disabled = false;
                }
            }
        }

        /**
         * プレイヤーのターンを終了し、コンピュータのターンを開始する
         */
        function endPlayerTurn() {
            isPlayerTurn = false;
            rollsLeft = MAX_ROLLS; // コンピュータ用にリセット
            rollBtn.disabled = true;
            
            msgEl.textContent = "コンピュータのターンです。";
            setTimeout(() => {
                rollBtn.textContent = `サイコロを振る (1/${MAX_ROLLS})`;
                rollDice(); // コンピュータのロール開始
            }, 1500); // 1.5秒待ってコンピュータが振る
        }

        /**
         * 勝敗を比較し、点数を計算する
         */
        function compareResults() {
            const p = evaluateDice(playerDice);
            const c = evaluateDice(computerDice);

            let message = "";
            let scoreChange = 0;

            if (p.point === 0 && c.point === 0) {
                message = "両者とも目なしのため引き分けです。";
            } else if (p.point > 0 && c.point === 0) {
                // プレイヤーが役あり、コンピュータが目なし
                scoreChange = p.point;
                playerScore += scoreChange;
                message = `あなたの勝利！ ${p.hand} で ${scoreChange} 点獲得。`;
            } else if (p.point === 0 && c.point > 0) {
                // プレイヤーが目なし、コンピュータが役あり
                scoreChange = c.point;
                computerScore += scoreChange;
                message = `コンピュータの勝利！ ${c.hand} で ${scoreChange} 点獲得。`;
            } else if (p.point > 0 && c.point > 0) {
                // 両者とも役ありの場合の点数勝負
                if (p.point > c.point) {
                    scoreChange = p.point;
                    playerScore += scoreChange;
                    message = `あなたの勝利！ (${p.point} vs ${c.point}) で ${scoreChange} 点獲得。`;
                } else if (c.point > p.point) {
                    scoreChange = c.point;
                    computerScore += scoreChange;
                    message = `コンピュータの勝利！ (${p.point} vs ${c.point}) で ${scoreChange} 点獲得。`;
                } else {
                    message = `点数が同じ (${p.point}) で引き分けです。`;
                }
            }
            
            // スコアと結果の表示を更新
            pScoreEl.textContent = playerScore;
            cScoreEl.textContent = computerScore;
            msgEl.textContent = "勝敗が決まりました。「ゲーム開始」で次のラウンドへ。";
            
            resultDetailEl.innerHTML = `
                <p><strong>あなたの役:</strong> ${p.hand} (点: ${p.point})</p>
                <p><strong>コンピュータの役:</strong> ${c.hand} (点: ${c.point})</p>
                <p>${message}</p>
            `;

            // 次のラウンド準備
            compareBtn.disabled = true;
            startBtn.disabled = false;
        }

        // --- 初期設定 ---
        startBtn.onclick = startGame;
        rollBtn.onclick = rollDice;
        compareBtn.onclick = compareResults;
        
    </script>
</body>
</html>