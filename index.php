<?php
session_start();

// Fonction pour générer une couleur sombre aléatoire
function generateRandomDarkColor() {
    $r = mt_rand(0, 64);
    $g = mt_rand(0, 64);
    $b = mt_rand(0, 64);
    return "rgb($r,$g,$b)";
}

$backgroundColor = generateRandomDarkColor();

// Vérifier si l'utilisateur a un cookie d'identification
if (!isset($_COOKIE['user_id'])) {
    // Générer un identifiant unique pour l'utilisateur
    $userId = uniqid();
    // Créer un cookie à vie avec l'identifiant unique
    setcookie('user_id', $userId, time() + (86400 * 365 * 10), "/"); // 10 ans
} else {
    $userId = $_COOKIE['user_id'];
}

// Créer un dossier pour l'utilisateur s'il n'existe pas
$userDir = "user/$userId";
if (!file_exists($userDir)) {
    mkdir($userDir, 0777, true);
}

// Créer un fichier de contexte s'il n'existe pas
$contextFilePath = "$userDir/context.txt";
if (!file_exists($contextFilePath)) {
    file_put_contents($contextFilePath, "");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Matrix</title>
    <style>
        body {
            background-color: <?php echo $backgroundColor; ?>;
            color: green;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        #chat-container {
            width: 80%;
            max-width: 600px;
            border: 1px solid green;
            padding: 20px;
            border-radius: 10px;
            background-color: black;
        }

        #chat-box {
            height: 300px;
            overflow-y: scroll;
            border-bottom: 1px solid green;
            margin-bottom: 10px;
            padding-bottom: 10px;
        }

        #user-input {
            width: calc(100% - 70px);
            padding: 10px;
            border: 1px solid green;
            border-radius: 5px;
            background-color: black;
            color: green;
        }

        button {
            padding: 10px;
            border: 1px solid green;
            border-radius: 5px;
            background-color: black;
            color: green;
            cursor: pointer;
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            opacity: 0;
            transform: translateX(-100%);
            transition: opacity 0.5s, transform 0.5s;
        }

        .message.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .user-message {
            text-align: right;
            align-items: flex-end;
        }

        .ai-message {
            text-align: left;
            align-items: flex-start;
        }
    </style>
</head>
<body>
    <div id="chat-container">
        <div id="chat-box"></div>
        <input type="text" id="user-input" placeholder="Tapez votre message...">
        <button onclick="sendMessage()">Envoyer</button>
    </div>
    <script>
        function sendMessage() {
            const userInput = document.getElementById('user-input').value;
            const chatBox = document.getElementById('chat-box');

            if (userInput.trim() === '') return;

            // Afficher le message de l'utilisateur
            const userMessage = document.createElement('div');
            userMessage.className = 'message user-message';
            userMessage.innerHTML = `Utilisateur: ${userInput.replace(/\n/g, '<br>')}`;
            chatBox.appendChild(userMessage);
            setTimeout(() => userMessage.classList.add('visible'), 100);

            // Envoyer la requête à chat.php
            fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: userInput })
            })
            .then(response => response.json())
            .then(data => {
                // Afficher la réponse de l'IA
                const aiMessage = document.createElement('div');
                aiMessage.className = 'message ai-message';
                aiMessage.innerHTML = `IA: ${data.response.replace(/\n/g, '<br>')}`;
                chatBox.appendChild(aiMessage);
                setTimeout(() => aiMessage.classList.add('visible'), 100);

                // Stocker la réponse cachée dans le contexte
                fetch('context.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ context: data.hidden })
                });
            })
            .catch(error => console.error('Error:', error));

            // Vider l'input
            document.getElementById('user-input').value = '';
        }

        // Envoyer le message en appuyant sur Entrée
        document.getElementById('user-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>