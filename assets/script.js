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

  // Fonction pour afficher un popup temporaire
  function showPopup(postElement, message) {
    const popup = postElement.querySelector(".popup");
    if (!popup) return;

    popup.textContent = message;
    popup.classList.add("show");

    clearTimeout(popup.timeoutId);
    popup.timeoutId = setTimeout(() => {
      popup.classList.remove("show");
    }, 3000);
  }

  likeButtons.forEach((button) => {
    button.addEventListener("click", async function (e) {
      e.preventDefault();
      const postId = this.getAttribute("data-post-id");
      const countSpan = this.querySelector(".like-count");

      try {
        const response = await fetch("include/like_post.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `post_id=${encodeURIComponent(postId)}`,
        });

        const result = await response.json();

        if (result.success) {
          this.classList.add("liked");
          if (countSpan) countSpan.textContent = result.likes;
          setTimeout(() => this.classList.remove("liked"), 200);
        } else {
          // Affiche popup si l’utilisateur a déjà liké
          if (result.message === "already_liked") {
            const postElement = this.closest(".post");
            showPopup(this.parentElement, "Vous avez déjà aimé ce post !");
          } else {
            alert(result.message || "Erreur lors du like.");
          }
        }
      } catch (error) {
        console.error("Like error:", error);
        alert("Une erreur est survenue.");
      }
    });
  });
});
