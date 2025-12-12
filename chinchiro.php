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
        
        /* ターンのエリア分離とハイライト */
        .player-area, .computer-area {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 2px solid transparent;
        }
        .current-turn {
            background-color: #e0f7fa; 
            border-color: #00bcd4;
            box-shadow: 0 0 10px rgba(0, 188, 212, 0.5);
            transition: all 0.5s;
        }
        /* -------------------- */
        
        .dice-display { font-size: 40px; margin: 30px 0; min-height: 50px; }
        .dice-display span { display: inline-block; margin: 0 10px; width: 50px; height: 50px; line-height: 50px; border: 2px solid #555; border-radius: 5px; background-color: #fff; box-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
        
        /* サイコロ回転アニメーション */
        @keyframes spin {
            0% { transform: rotate(0deg) scale(1.0); opacity: 1; }
            25% { transform: rotate(180deg) scale(1.2); opacity: 0.8; }
            50% { transform: rotate(360deg) scale(0.9); opacity: 0.6; }
            75% { transform: rotate(540deg) scale(1.1); opacity: 0.8; }
            100% { transform: rotate(720deg) scale(1.0); opacity: 1; }
        }
        .dice-spinning span {
            animation: spin 0.3s linear infinite; 
            border: 3px dashed #f00;
        }
        /* -------------------- */

        button { padding: 12px 25px; font-size: 18px; cursor: pointer; background-color: #d9534f; color: white; border: none; border-radius: 8px; margin: 10px; transition: background-color 0.3s; }
        button:hover { background-color: #c9302c; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }

        #game-message { 
            font-size: 1.6em; 
            font-weight: bold; 
            margin: 20px 0; 
            min-height: 30px; 
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #fff;
        }
        .score-board { display: flex; justify-content: space-around; margin-top: 20px; border-top: 1px dashed #ddd; padding-top: 15px; }
        .score-board div { text-align: center; font-size: 1.1em; }
        .score-board strong { display: block; font-size: 2em; color: #333; }
        .result-detail { margin-top: 15px; padding: 10px; border: 1px dashed #e0e0e0; background-color: #f9f9f9; }

    </style>
</head>
<body>
    <div class="container">
        <h1>🎲 チンチロリン 🎲</h1>

        <div id="game-message"></div>

        <div id="player-area" class="player-area">
            <h3>あなた (<span id="player-status"></span>)</h3>
            <div class="dice-display" id="player-dice-display">--- --- ---</div>
        </div>
        
        <div id="computer-area" class="computer-area">
            <h3>コンピュータ (<span id="computer-status"></span>)</h3>
            <div class="dice-display" id="computer-dice-display">--- --- ---</div>
        </div>
        
        <div class="result-detail" id="result-detail"></div>

        <button id="start-button">ゲーム開始</button>
        <button id="roll-button" disabled>サイコロを振る (1/3)</button>
        <button id="compare-button" disabled>勝敗を比較</button>

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
        const pAreaEl = document.getElementById('player-area');
        const cAreaEl = document.getElementById('computer-area');
        const pStatusEl = document.getElementById('player-status');
        const cStatusEl = document.getElementById('computer-status');

        // --- チンチロリン判定ロジック ---
        function evaluateDice(dice) {
            dice.sort((a, b) => a - b);
            const [d1, d2, d3] = dice;

            if (d1 === d2 && d2 === d3) { 
                if (d1 === 1) return { hand: "ピンゾロ (役満)", point: 6, isBigger: true }; 
                if (d1 === 6) return { hand: "嵐 (役満)", point: 5, isBigger: true }; 
                return { hand: `ゾロ目 (${d1})`, point: d1, isBigger: true };
            }
            if (d1 === 4 && d2 === 5 && d3 === 6) { 
                return { hand: "シゴロ (最強役)", point: 4, isBigger: true }; 
            }
            if (d1 === 1 && d2 === 2 && d3 === 3) { 
                return { hand: "ヒフミ (役なし/最弱)", point: 0, isBigger: false }; 
            }
            if (d1 !== d2 && d2 !== d3 && d1 !== d3) { 
                return { hand: "目なし", point: 0, isBigger: false }; 
            }
            if (d1 === d2) return { hand: `X・X・${d3} (x${d3}点)`, point: d3, isBigger: false };
            if (d1 === d3) return { hand: `X・${d2}・X (x${d2}点)`, point: d2, isBigger: false };
            if (d2 === d3) return { hand: `${d1}・X・X (x${d1}点)`, point: d1, isBigger: false };
            
            return { hand: "目なし", point: 0, isBigger: false };
        }

        // --- ターンの状態管理とハイライト ---
        function updateTurnHighlight(turn) {
            if (turn === 'player') {
                pAreaEl.classList.add('current-turn');
                cAreaEl.classList.remove('current-turn');
                pStatusEl.textContent = "現在操作中";
                cStatusEl.textContent = "待機中";
            } else if (turn === 'computer') {
                pAreaEl.classList.remove('current-turn');
                cAreaEl.classList.add('current-turn');
                pStatusEl.textContent = "待機中";
                cStatusEl.textContent = "現在操作中";
            } else {
                pAreaEl.classList.remove('current-turn');
                cAreaEl.classList.remove('current-turn');
                pStatusEl.textContent = "";
                cStatusEl.textContent = "";
            }
        }

        // --- アニメーション関連 ---
        function startSpinning(element) {
            element.classList.add('dice-spinning');
            element.innerHTML = '<span>?</span><span>?</span><span>?</span>';
        }

        function stopSpinning(element, dice) {
            element.classList.remove('dice-spinning');
            renderDice(element, dice);
        }
        
        // --- ゲームフロー関数 ---

        function generateRoll() {
            return [
                Math.floor(Math.random() * 6) + 1,
                Math.floor(Math.random() * 6) + 1,
                Math.floor(Math.random() * 6) + 1
            ];
        }

        function renderDice(element, dice) {
            element.innerHTML = dice.map(d => `<span>${d}</span>`).join('');
        }

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

            updateTurnHighlight('player');
            msgEl.textContent = "あなたのターンです。サイコロを振って勝負を始めましょう！";
        }

        function rollDice() {
            rollBtn.disabled = true;

            if (isPlayerTurn) {
                startSpinning(pDiceEl);
            } else {
                startSpinning(cDiceEl);
            }

            setTimeout(() => {
                let currentDice = generateRoll();
                let currentEl = isPlayerTurn ? pDiceEl : cDiceEl;
                
                stopSpinning(currentEl, currentDice);

                if (isPlayerTurn) {
                    playerDice = currentDice;
                    rollsLeft--;
                    handlePlayerResult(evaluateDice(playerDice));
                } else {
                    computerDice = currentDice;
                    handleComputerResult(evaluateDice(computerDice));
                }
            }, 800);
        }

        function handlePlayerResult(pResult) {
            msgEl.textContent = `出た目: ${pResult.hand}. 残り ${rollsLeft} 回振れます。`;
            
            if (pResult.point > 0 || rollsLeft === 0) {
                endPlayerTurn();
            } else {
                rollBtn.textContent = `サイコロを振る (${MAX_ROLLS - rollsLeft + 1}/${MAX_ROLLS})`;
                rollBtn.disabled = false;
            }
        }

        function handleComputerResult(cResult) {
            if (cResult.point === 0 && rollsLeft > 0) {
                rollsLeft--;
                setTimeout(rollDice, 1000);
                msgEl.textContent = `コンピュータは振り直します... (残り ${rollsLeft} 回)`;
            } else {
                msgEl.textContent = `コンピュータの出目: ${cResult.hand}. 比較ボタンを押してください。`;
                compareBtn.disabled = false;
            }
        }

        function endPlayerTurn() {
            isPlayerTurn = false;
            rollsLeft = MAX_ROLLS; 
            rollBtn.disabled = true;
            
            updateTurnHighlight('computer');

            msgEl.textContent = "プレイヤーの出目が確定しました。次はコンピュータの番です。";
            
            setTimeout(() => {
                rollBtn.textContent = `サイコロを振る (1/${MAX_ROLLS})`;
                rollDice(); 
            }, 1500); 
        }

        function compareResults() {
            const p = evaluateDice(playerDice);
            const c = evaluateDice(computerDice);

            let message = "";
            let scoreChange = 0;

            if (p.point === 0 && c.point === 0) {
                message = "両者とも目なしのため引き分けです。";
            } else if (p.point > 0 && c.point === 0) {
                scoreChange = p.point;
                playerScore += scoreChange;
                message = `あなたの勝利！ ${p.hand} で ${scoreChange} 点獲得。`;
            } else if (p.point === 0 && c.point > 0) {
                scoreChange = c.point;
                computerScore += scoreChange;
                message = `コンピュータの勝利！ ${c.hand} で ${scoreChange} 点獲得。`;
            } else if (p.point > 0 && c.point > 0) {
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
            
            pScoreEl.textContent = playerScore;
            cScoreEl.textContent = computerScore;
            msgEl.textContent = "勝敗が決まりました！「ゲーム開始」で次のラウンドへ。";
            
            resultDetailEl.innerHTML = `
                <p><strong>あなたの役:</strong> ${p.hand} (点: ${p.point})</p>
                <p><strong>コンピュータの役:</strong> ${c.hand} (点: ${c.point})</p>
                <p>${message}</p>
            `;

            compareBtn.disabled = true;
            startBtn.disabled = false;
            updateTurnHighlight('none');
        }

        // --- 初期設定 ---
        window.onload = function() {
            startBtn.onclick = startGame;
            rollBtn.onclick = rollDice;
            compareBtn.onclick = compareResults;
            
            // 初期状態の表示
            pDiceEl.innerHTML = "--- --- ---";
            cDiceEl.innerHTML = "--- --- ---";
            updateTurnHighlight('none');
            msgEl.textContent = "「ゲーム開始」を押してください。";
        };
        
    </script>
</body>
</html>