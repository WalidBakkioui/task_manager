// Charger le fichier txt
fetch("liste.txt")
    .then(response => response.text())
    .then(data => {
        let wordList = data.split("\n");
        let randomIndex = Math.floor(Math.random() * wordList.length);
        const wordToGuess = wordList[randomIndex];

        let wordLength = wordToGuess.length;
        let hiddenWord = "";
        for (let i = 0; i < wordLength; i++) {
            hiddenWord += "_";
        }
        document.getElementById("word").innerHTML = hiddenWord;

        var guessInput = document.getElementById("guess");
        var submitButton = document.getElementById("submit");
        var result = document.getElementById("result");
        var link = document.getElementById("link");

        var errors = 0; // Variable pour suivre le nombre d'erreurs

        submitButton.onclick = function () {
            var guess = guessInput.value;
            if (guess.length > 1 || guess.length === 0) {
                result.innerHTML = "Entrer une seule lettre";
            } else if (wordToGuess.indexOf(guess) === -1) {
                result.innerHTML = "Mauvaise lettre";
                errors++; // Incrémenter le compteur d'erreurs
                if (errors === 3) {
                    result.innerHTML = "Vous avez atteint 3 erreurs. Le jeu est terminé.";
                    guessInput.style.display = "none";
                    submitButton.style.display = "none";
                    link.style.display = "block";
                }
            } else {
                for (var i = 0; i < wordLength; i++) {
                    if (wordToGuess[i] === guess) {
                        hiddenWord = hiddenWord.substr(0, i) + guess + hiddenWord.substr(i + 1);
                    }
                }
                document.getElementById("word").innerHTML = hiddenWord;

                if (hiddenWord === wordToGuess) {
                    result.innerHTML = "Bravo, tu as trouvé le mot !";
                    guessInput.style.display = "none";
                    submitButton.style.display = "none";
                    link.style.display = "block";
                } else {
                    result.innerHTML = "Bonne lettre !";
                }
            }
            guessInput.value = "";
        }
    })
