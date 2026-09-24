window.addEventListener("DOMContentLoaded", function () {
  // ========== Gestion des erreurs de login ==========
  if (typeof loginError !== "undefined") {
    if (loginError === "username") {
      alert("Username not found. Please check your spelling or sign up first.");
    } else if (loginError === "password") {
      alert("Incorrect password. Try again or reset your password.");
    } else if (loginError === "usertaken") {
      alert("This username is already taken. Please choose another.");
    }
  }

  // ========== Éléments de la popup ==========
  const wrapper = document.querySelector(".wrapper");
  const loginBtn = document.querySelector(".btnLogin-popup");
  const closeBtn = document.querySelector(".close");

  if (loginBtn) {
    loginBtn.addEventListener("click", function () {
      wrapper.classList.add("active-popup");
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      wrapper.classList.remove("active-popup");
    });
  }

  // ========== Dropdown menu de catégories ==========
  const arrowBtn = document.getElementById("arrow");
  const dropdown = document.querySelector(".navigation .dropdown");

  if (arrowBtn && dropdown) {
    arrowBtn.addEventListener("click", function () {
      dropdown.classList.toggle("show");
      const linkGroup = arrowBtn.closest(".big-link");
      if (linkGroup) linkGroup.classList.toggle("open");
    });
  }

  // ========== Formulaire d’édition de profil ==========
  const editToggle = document.getElementById("toggleEditBtn");
  const editForm = document.getElementById("epf-form");

  if (editToggle && editForm) {
    editToggle.addEventListener("click", function () {
      if (editForm.classList.contains("epf-hidden")) {
        editForm.classList.remove("epf-hidden");
        editToggle.textContent = "Close";
      } else {
        editForm.classList.add("epf-hidden");
        editToggle.textContent = "Edit Profile";
      }
    });
  }

  // ========== Activation des zones éditables pour les posts ==========
  const allEditButtons = document.querySelectorAll(".edit-btn");

  allEditButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      const postId = btn.dataset.postId;
      const contentBox = document.getElementById("content-" + postId);
      const text = contentBox.innerText.trim();

      contentBox.innerHTML = `<textarea name="content" rows="4" style="width:100%;">${text}</textarea>`;

      const saveButton = document.querySelector(
        `.save-btn[data-post-id="${postId}"]`
      );
      if (saveButton) saveButton.style.display = "inline";

      const updateForm = document.getElementById("form-" + postId);
      if (updateForm) updateForm.style.display = "block";
    });
  });

  // ========== Confirmation avant suppression ==========
  const allDeleteForms = document.querySelectorAll(".delete-form");

  allDeleteForms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      const confirmIt = confirm("Are you sure you want to delete this post?");
      if (!confirmIt) {
        e.preventDefault();
      }
    });
  });

  // ========== Like dynamique avec animation ==========
  const likeButtons = document.querySelectorAll(".like-btn");

  function showPopup(postElement, message) {
    const popup = postElement.querySelector(".popup");
    if (!popup) return;

    popup.textContent = message;
    popup.classList.add("show");

    clearTimeout(popup.timeoutId);
    popup.timeoutId = setTimeout(function () {
      popup.classList.remove("show");
    }, 3000);
  }

  likeButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const postId = this.getAttribute("data-post-id");
      const countSpan = this.querySelector(".like-count");
      const xhr = new XMLHttpRequest();
      const self = this;

      xhr.open("POST", "include/like_post.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      xhr.onreadystatechange = function () {
        // Vérifie si la requête est terminée (étape 4)
        if (xhr.readyState === 4) {
          // Vérifie si la réponse est OK (code 200)
          if (xhr.status === 200) {
            // Exemple de réponse : "success=true&likes=42"
            var texteReponse = xhr.responseText;
            // Crée un objet pour stocker les valeurs
            var infos = {};
            // Découpe la réponse en morceaux : ["success=true", "likes=42"]
            var morceaux = texteReponse.split("&");
            // Pour chaque morceau, on sépare la clé et la valeur
            for (var i = 0; i < morceaux.length; i++) {
              var paire = morceaux[i].split("=");
              var cle = decodeURIComponent(paire[0]);
              var valeur = decodeURIComponent(paire[1] || "");
              infos[cle] = valeur;
            }
            // Maintenant, on peut utiliser les données
            if (infos.success === "true") {
              // Like réussi
              button.classList.add("liked");
              if (countSpan) countSpan.textContent = infos.likes;
            } else if (infos.message === "already_liked") {
              // L'utilisateur a déjà liké
              showPopup(button.parentElement, "You already liked that post !");
            } else {
              alert(infos.message || "Erreur lors du like.");
            }
          } else {
            alert("Erreur réseau (statut " + xhr.status + ")");
          }
        }
      };
      xhr.send("post_id=" + encodeURIComponent(postId));
    });
  });
});
