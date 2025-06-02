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
        if (xhr.readyState === 4) {
          if (xhr.status === 200) {
            // Format attendu : success=true&likes=42 OU success=false&message=already_liked
            const responseText = xhr.responseText;
            const params = {};
            responseText.split("&").forEach(function (part) {
              const pair = part.split("=");
              params[decodeURIComponent(pair[0])] = decodeURIComponent(
                pair[1] || ""
              );
            });

            if (params.success === "true") {
              self.classList.add("liked");
              if (countSpan) countSpan.textContent = params.likes;
              setTimeout(function () {
                self.classList.remove("liked");
              }, 200);
            } else if (params.message === "already_liked") {
              showPopup(self.parentElement, "You already liked that post !");
            } else {
              alert(params.message || "Erreur lors du like.");
            }
          } else {
            alert("Erreur réseau");
          }
        }
      };

      xhr.send("post_id=" + encodeURIComponent(postId));
    });
  });
});
