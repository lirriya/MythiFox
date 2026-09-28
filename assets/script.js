window.addEventListener("DOMContentLoaded", function () {
  // ========== Fonction toast (erreurs like) ==========
  function showToast(message) {
    let toast = document.querySelector(".toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "toast";
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.classList.remove("show");
    }, 3000);
  }

  // ========== Modal login / signup ==========
  const wrapper = document.querySelector(".wrapper");
  const loginBtn = document.getElementById("login-popup-btn");

  if (loginBtn && wrapper) {
    loginBtn.addEventListener("click", function () {
      wrapper.dataset.mode = "login";
      wrapper.classList.add("active-popup");
    });
  }

  if (wrapper) {
    const closeEls = wrapper.querySelectorAll("[data-close-modal]");
    closeEls.forEach(function (el) {
      el.addEventListener("click", function () {
        wrapper.classList.remove("active-popup");
      });
    });

    const switchLinks = wrapper.querySelectorAll("[data-switch]");
    switchLinks.forEach(function (link) {
      link.addEventListener("click", function () {
        wrapper.dataset.mode = link.dataset.switch;
      });
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        wrapper.classList.remove("active-popup");
      }
    });

    if (wrapper.querySelector(".form-msg")) {
      wrapper.classList.add("active-popup");
    }
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

  // ========== Navigation mobile (hamburger) ==========
  const navToggle = document.getElementById("nav-toggle");
  const nav = document.querySelector(".navigation");

  if (navToggle && nav) {
    navToggle.addEventListener("click", function () {
      const isOpen = nav.classList.toggle("open");
      navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    nav.querySelectorAll(".nav-link").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("open");
        navToggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  window.mythifoxToast = showToast;

  // ========== Édition d'un post ==========
  function setPostEditorOpen(button, open) {
    const postId = button.getAttribute("data-post-id");
    const editor = document.querySelector(`[data-post-editor="${postId}"]`);
    if (!editor) return;

    editor.hidden = !open;
    button.setAttribute("aria-expanded", open ? "true" : "false");
    button.textContent = open ? "Close" : "Edit";

    if (open) {
      const textarea = editor.querySelector("textarea");
      if (textarea) textarea.focus();
    }
  }

  document.querySelectorAll("[data-post-edit-toggle]").forEach(function (button) {
    button.addEventListener("click", function () {
      const isOpen = button.getAttribute("aria-expanded") === "true";
      setPostEditorOpen(button, !isOpen);
    });
  });

  document.querySelectorAll("[data-post-edit-cancel]").forEach(function (button) {
    button.addEventListener("click", function () {
      const postId = button.getAttribute("data-post-id");
      const toggle = document.querySelector(`[data-post-edit-toggle][data-post-id="${postId}"]`);
      if (toggle) setPostEditorOpen(toggle, false);
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
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";
      const xhr = new XMLHttpRequest();

      xhr.open("POST", "include/like_post.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.setRequestHeader("X-CSRF-Token", csrfToken);

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
              const likeMessages = {
                login_required: "Sign in to like posts.",
                invalid_token: "Your session expired. Reload the page.",
                invalid_post: "That post is not available.",
                post_not_found: "That post is not available.",
                method_not_allowed: "That request was not allowed.",
                server_error: "Something went wrong. Try again."
              };
              showToast(likeMessages[infos.message] || "We could not register that like.");
            }
          } else {
            showToast("Network error (status " + xhr.status + ").");
          }
        }
      };
      xhr.send(
        "post_id=" + encodeURIComponent(postId) + "&csrf=" + encodeURIComponent(csrfToken)
      );
    });
  });
});
