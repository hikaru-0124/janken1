<?php
// PHPはファイルの構造を提供するのみ
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>五目並べ - 修正版</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; background-color: #f4f4f9; padding-top: 20px; }
        .container { background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 650px; margin: auto; }
        h1 { color: #333; margin-bottom: 20px; }
        
        /* 盤面スタイル */
        #board-container { 
            display: inline-block; 
            background-color: #fce8a6; 
            border: 1px solid #333; 
            box-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        }
        .board-row { display: flex; }
        .cell {
            width: 30px;
            height: 30px;
            /* 線はセルの四隅に描画される交点として表現 */
            border: 1px solid #333;
            box-sizing: border-box;
            position: relative;
            cursor: pointer;
            background-color: #fce8a6; /* セル背景色 */
        }
        /* セル間の線を消して、交点のみにする（五目並べの盤面表現） */
        .cell:not(:last-child) { border-right: none; }
        .board-row:not(:last-child) .cell { border-bottom: none; }
        /* 角と端の線を調整 */
        .cell:first-child { border-left: 1px solid #333; }
        .cell:last-child { border-right: 1px solid #333; }
        .board-row:first-child .cell { border-top: 1px solid #333; }
        .board-row:last-child .cell { border-bottom: 1px solid #333; }
        
        /* 碁石スタイル */
        .stone {
            width: 90%;
            height: 90%;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 1px 3px rgba(0,0,0,0.5);
            pointer-events: none; /* 石の上をクリックしても下のセルが反応するように */
        }
        .stone.black { background-color: black; }
        .stone.white { background-color: white; border: 1px solid #333; }

        /* メッセージと情報 */
        #message { font-size: 1.5em; font-weight: bold; margin: 20px 0; min-height: 40px; }
        .turn-black { color: black; }
        .turn-white { color: gray; }
        .win-message { color: red; animation: pulse 1s infinite; }

        /* ボタン */
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; background-color: #28a745; color: white; border: none; border-radius: 5px; margin-top: 10px; }
        button:hover { background-color: #1e7e34; }
        
        /* アニメーション */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>五目並べ</h1>
        <div id="message"></div>
        <div id="board-container">
            </div>
        <button id="reset-button" onclick="initGame()">リセットして再開</button>
    </div>

    <script>
        const BOARD_SIZE = 15; // 15x15の盤面
        const EMPTY = 0;
        const BLACK = 1; // 先手 (⚫)
        const WHITE = 2; // 後手 (⚪)
        const BOARD_CONTAINER = document.getElementById('board-container');
        const MESSAGE_ELEMENT = document.getElementById('message');

        let board = [];
        let currentPlayer = BLACK;
        let isGameOver = false;

        /**
         * ゲームの状態を初期化し、盤面を描画する
         */
        window.onload = initGame; // ページロード完了後に初期化を確実に実行

        function initGame() {
            // 盤面データの初期化
            board = Array(BOARD_SIZE).fill(0).map(() => Array(BOARD_SIZE).fill(EMPTY));
            currentPlayer = BLACK;
            isGameOver = false;
            
            drawBoard();
            updateMessage();
        }

        /**
         * 盤面をHTML上に描画する
         */
        function drawBoard() {
            BOARD_CONTAINER.innerHTML = ''; // 既存の盤面をクリア

            for (let r = 0; r < BOARD_SIZE; r++) {
                const rowElement = document.createElement('div');
                rowElement.className = 'board-row';
                
                for (let c = 0; c < BOARD_SIZE; c++) {
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    cell.dataset.row = r;
                    cell.dataset.col = c;
                    cell.onclick = handleMove;

                    // 既に石がある場合は描画
                    if (board[r][c] !== EMPTY) {
                        const stone = document.createElement('div');
                        stone.className = board[r][c] === BLACK ? 'stone black' : 'stone white';
                        cell.appendChild(stone);
                    }
                    
                    rowElement.appendChild(cell);
                }
                BOARD_CONTAINER.appendChild(rowElement);
            }
        }

        /**
         * セルがクリックされたときの処理
         */
        function handleMove(event) {
            if (isGameOver) return;

            const row = parseInt(event.currentTarget.dataset.row);
            const col = parseInt(event.currentTarget.dataset.col);

            // 既に石が置かれている場合は無視
            if (board[row][col] !== EMPTY) {
                return;
            }

            // 石を置く
            board[row][col] = currentPlayer;
            
            // 盤面の再描画 (ここでは、高速化のため、置いた石のみを再描画することも可能だが、今回はシンプルに全体を描画)
            drawBoard();

            // 勝敗判定
            if (checkWin(row, col)) {
                isGameOver = true;
                updateMessage(true);
            } else {
                // ターン交代
                currentPlayer = (currentPlayer === BLACK) ? WHITE : BLACK;
                updateMessage();
            }
        }

        /**
         * 勝敗を判定する (直近置かれた石の座標 r, c を起点にチェック)
         */
        function checkWin(r, c) {
            const player = board[r][c];
            // チェックする4方向の定義: [r増加, c増加]
            const directions = [
                [0, 1],  // 水平
                [1, 0],  // 垂直
                [1, 1],  // 右下斜め
                [1, -1]  // 左下斜め
            ];

            for (const [dr, dc] of directions) {
                let count = 1; // 置いた石自身を含む
                
                // 1. 正方向 (dr, dc) へチェック
                for (let i = 1; i < 5; i++) {
                    const nr = r + dr * i;
                    const nc = c + dc * i;
                    if (nr >= 0 && nr < BOARD_SIZE && nc >= 0 && nc < BOARD_SIZE && board[nr][nc] === player) {
                        count++;
                    } else {
                        break;
                    }
                }

                // 2. 逆方向 (-dr, -dc) へチェック
                for (let i = 1; i < 5; i++) {
                    const nr = r - dr * i;
                    const nc = c - dc * i;
                    if (nr >= 0 && nr < BOARD_SIZE && nc >= 0 && nc < BOARD_SIZE && board[nr][nc] === player) {
                        count++;
                    } else {
                        break;
                    }
                }

                if (count >= 5) {
                    return true; // 5つ以上並んだ
                }
            }

            return false;
        }

        /**
         * メッセージ表示を更新する
         */
        function updateMessage(win = false) {
            if (win) {
                const winner = (currentPlayer === BLACK) ? '黒 ⚫' : '白 ⚪';
                MESSAGE_ELEMENT.innerHTML = `<span class="win-message">🏆 ${winner}の勝利です！おめでとう！</span>`;
            } else if (isGameOver) {
                 // ゲームオーバーだが勝利ではない場合（理論上はあり得ないが）
            } else {
                const nextPlayer = (currentPlayer === BLACK) ? '黒 ⚫' : '白 ⚪';
                const turnClass = (currentPlayer === BLACK) ? 'turn-black' : 'turn-white';
                MESSAGE_ELEMENT.innerHTML = `<span class="${turnClass}">${nextPlayer}のターンです</span>`;
            }
        }
    </script>
</body>
</html>